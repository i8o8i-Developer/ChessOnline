# AppPy RunsFlaskSocketIoServerForI8O8IChess
import eventlet
import hashlib
import chess  # AddChessModuleImport
from flask import Flask, request, jsonify
from flask_cors import CORS
from flask_socketio import SocketIO, emit, join_room, leave_room
from EloUtils import EloUtils
from ChessEngine import ChessEngine
from Models import UserModel, GameModel
from Config import SocketIoCorsOrigins
import time
import random
import os
import logging
from logging.handlers import RotatingFileHandler
import uuid

App = Flask(__name__)
App.config['SECRET_KEY'] = 'I8O8IChessSecretKey'
CORS(App, origins=SocketIoCorsOrigins, supports_credentials=True)
SocketIo = SocketIO(App, cors_allowed_origins=SocketIoCorsOrigins, async_mode="eventlet")

# Logging Setup
logs_dir = os.path.join(os.path.dirname(__file__), '..', 'Logs')
os.makedirs(logs_dir, exist_ok=True)
log_path = os.path.join(logs_dir, 'Backend.log')
logger = logging.getLogger('ChessOnline')
if not logger.handlers:
    logger.setLevel(logging.INFO)
    handler = RotatingFileHandler(log_path, maxBytes=5 * 1024 * 1024, backupCount=5)
    formatter = logging.Formatter('%(asctime)s [%(levelname)s] %(name)s: %(message)s')
    handler.setFormatter(formatter)
    logger.addHandler(handler)

logger.info('Starting ChessOnline Backend')

# -------------------
# InMemoryState
# -------------------
InMemoryMatchmakingQueue = []       # ListOfPlayerDictsWithUserIdAndGameType
InMemoryActiveGames = {}            # GameId -> GameSnapshot(OptionalCache)
GameTimers = {}  # GameId -> {white: int, black: int, last_update: float}
UserIdToSid = {}                    # UserId -> SocketIoSid

def init_game_timer(game_id):
    """InitializeGameTimerWithProperSynchronization"""
    if game_id not in GameTimers:
        # GetGameTypeToSetAppropriateTime
        game = GameModel.GetGameById(game_id)
        time_control = game.get('TimeControl', 600) if game else 600

        GameTimers[game_id] = {
            'white': time_control,
            'black': time_control,
            'last_move_time': time.time(),
            'current_turn': 'white',  # AlwaysStartWithWhite
            'is_active': False  # DontStartTimerUntilBothPlayersPresent
        }

        logger.info(f"Timer Initialized For Game {game_id}: {time_control}s Each")
    else:
        # If Timer Already Exists, Report Current White Time As Reference
        time_control = GameTimers[game_id].get('white', 600)
        logger.debug(f"Timer Already Exists For Game {game_id}, white={time_control}s")

def update_game_timer(game_id, move_made_by_color):
    """UpdateTimerAfterAMoveIsMadeFixedVersion"""
    if game_id not in GameTimers:
        init_game_timer(game_id)
        return GameTimers[game_id]

    now = time.time()
    timer = GameTimers[game_id]
    
    # OnlyUpdateIfTimerWasActive
    if timer.get('is_active', False):
        elapsed = int(now - timer['last_move_time'])
        
        # SubtractTimeFromThePlayerWhoJustMoved
        timer[move_made_by_color] = max(0, timer[move_made_by_color] - elapsed)
        
    logger.debug(f"Timer Updated For {move_made_by_color}: -{elapsed}s, Remaining: {timer[move_made_by_color]}s")
    
    # SwitchTurnAndResetTimer
    timer['current_turn'] = 'black' if move_made_by_color == 'white' else 'white'
    timer['last_move_time'] = now
    timer['is_active'] = True  # ActivateTimerAfterFirstMove
    
    return timer

def start_game_timer(game_id):
    """StartGameTimerWhenBothPlayersArePresent"""
    if game_id in GameTimers:
        GameTimers[game_id]['is_active'] = True
        GameTimers[game_id]['last_move_time'] = time.time()
    logger.info(f"Timer Started For Game {game_id}")

def stop_game_timer(game_id):
    """StopGameTimer"""
    if game_id in GameTimers:
        GameTimers[game_id]['is_active'] = False
    logger.info(f"Timer Stopped For Game {game_id}")

# -------------------
# HelperFunctions
# -------------------
def HelperHashPassword(Password):
    return hashlib.sha256(Password.encode('utf-8')).hexdigest()

def calculate_win_probability(board):
    """CalculateWinProbabilityBasedOnMaterialAndPosition"""
    try:
        # PieceValues
        values = {
            chess.PAWN: 1,
            chess.KNIGHT: 3,
            chess.BISHOP: 3,
            chess.ROOK: 5,
            chess.QUEEN: 9
        }
        
        # CalculateMaterialBalance
        white_material = 0
        black_material = 0
        
        for square in chess.SQUARES:
            piece = board.piece_at(square)
            if piece:
                value = values.get(piece.piece_type, 0)
                if piece.color == chess.WHITE:
                    white_material += value
                else:
                    black_material += value
        
        # CalculateMobilityNumberOfLegalMoves
        white_mobility = len(list(board.legal_moves))
        board.turn = chess.BLACK
        black_mobility = len(list(board.legal_moves))
        board.turn = chess.WHITE  # RestoreOriginalTurn
        
        # CombineFactors
        material_factor = (white_material - black_material) / 30  # Normalize
        mobility_factor = (white_mobility - black_mobility) / 50  # Normalize
        
        # ConvertToProbabilityUsingSigmoidFunction
        score = material_factor + 0.1 * mobility_factor
        probability = 1 / (1 + 10**(-score))
        
        return max(0.01, min(0.99, probability))  # ClampBetween1PercentAnd99Percent
        
    except Exception as e:
        print(f"SimpleAnalysisError: {e}")
        return 0.5  # Return50PercentOnError

# -------------------
# RestApi
# -------------------
@App.route("/")
def Home():
    return "WelcomeToI8O8IChessBackendApi"

@App.route("/api/test")
def Test():
    return jsonify({"Message": "ApiWorking!"})

# AuthEndpoints
@App.route('/api/register', methods=['POST'])
def ApiRegister():
    Data = request.json or {}
    UserName = Data.get('UserName')
    Password = Data.get('Password')
    if not UserName or not Password:
        return jsonify({'Success': False, 'Message': 'MissingUserNameOrPassword'}), 400
    if UserModel.GetUserByUserName(UserName):
        return jsonify({'Success': False, 'Message': 'UserNameAlreadyExists'}), 409
    UserModel.CreateUser(UserName, HelperHashPassword(Password))
    logger.info(f"New User Registered : {UserName}")
    return jsonify({'Success': True, 'Message': 'UserCreated'})

@App.route('/api/login', methods=['POST'])
def ApiLogin():
    Data = request.json or {}
    UserName = Data.get('UserName')
    Password = Data.get('Password')
    User = UserModel.GetUserByUserName(UserName)
    if not User or HelperHashPassword(Password) != User['PasswordHash']:
        return jsonify({'Success': False, 'Message': 'InvalidCredentials'}), 401
    logger.info(f"User Login : {UserName} (id={User['UserId']})")
    return jsonify({
        'Success': True, 
        'UserId': User['UserId'], 
        'UserName': User['UserName'], 
        'ClassicalRating': User['ClassicalRating'],
        'RapidRating': User['RapidRating']
    })

# AddCancelEndpoint
@App.route('/api/cancel-match', methods=['POST'])
def ApiCancelMatch():
    Data = request.json or {}
    UserId = Data.get('UserId')
    # RemoveUserFromQueueNowHandlesDictFormat
    InMemoryMatchmakingQueue[:] = [player for player in InMemoryMatchmakingQueue 
                                   if player.get('UserId') != UserId]
    return jsonify({'Success': True})

# CompletelyFixedQuickMatchEndpointWithProperGameTypeSupport
@App.route('/api/quickmatch', methods=['POST'])
def ApiQuickMatch():
    Data = request.json or {}
    UserId = Data.get('UserId')
    GameType = Data.get('GameType', 'classical')  # NewAcceptGameType
    
    if not UserId:
        return jsonify({'Success': False, 'Message': 'MissingUserId'}), 400

    # ConvertUserIdToIntForConsistentComparison
    UserId = int(UserId)
    
    # RemoveAnyExistingEntriesForThisUser
    InMemoryMatchmakingQueue[:] = [player for player in InMemoryMatchmakingQueue 
                                   if player.get('UserId') != UserId]

    # FindOpponentWithSameGameTypePreference
    opponent = None
    for i, queued_player in enumerate(InMemoryMatchmakingQueue):
        if (queued_player.get('UserId') != UserId and 
            queued_player.get('GameType', 'classical') == GameType):
            opponent = InMemoryMatchmakingQueue.pop(i)
            break

    if opponent:
        logger.info(f"QuickMatch : Found Opponent For User {UserId} (Opponent {opponent.get('UserId')}) Game Type {GameType}")
        try:
            # CreateNewGameWithSpecifiedType
            if random.random() < 0.5:
                WhiteId, BlackId = UserId, opponent['UserId']
            else:
                WhiteId, BlackId = opponent['UserId'], UserId

            # SetTimeControlBasedOnGameType
            time_controls = {
                'classical': 600,  # TenMinutes
                'rapid': 300,      # FiveMinutes
                'blitz': 180       # ThreeMinutes
            }
            TimeControl = time_controls.get(GameType, 600)
            
            Fen = ChessEngine.CreateNewBoard()
            GameId = GameModel.CreateGameWithType(WhiteId, BlackId, Fen, GameType, TimeControl)

            join_token = uuid.uuid4().hex
            InMemoryActiveGames[GameId] = {
                'Fen': Fen,
                'MoveHistory': "",
                'WhiteUserId': WhiteId,
                'BlackUserId': BlackId,
                'JoinToken': join_token,
                'AllowedUserIds': [WhiteId, BlackId],
                'TokenUsedBy': set(),
                'TokenSentToSids': set(),
                'GameType': GameType,
                'TimeControl': TimeControl,
                'Rankings_Updated': False  # PreventDuplicateRankingUpdates
            }

            # Persist Token Fields Into DB For Consistency (Store JSON Arrays)
            try:
                import json
                # Convert sets to lists for JSON
                token_sids_list = list(InMemoryActiveGames[GameId].get('TokenSentToSids') or [])
                GameModel.UpdateGameTokenFields(GameId, join_token, [WhiteId, BlackId], [], token_sids_list)
            except Exception as e:
                logger.exception(f"Error persisting join token fields for game {GameId}: {e}")

            payload = {
                'Success': True,
                'MatchCreated': True,
                'GameId': GameId,
                'JoinToken': join_token,
                'WhiteUserId': WhiteId,
                'BlackUserId': BlackId,
                'Fen': Fen,
                'GameType': GameType
            }

            # NotifyBothPlayersViaSocket
            # NotifyBothPlayersViaSocket and record which sids received the token
            sent_sids = set()
            for uid in (WhiteId, BlackId):
                sid = UserIdToSid.get(str(uid))
                if sid:
                    SocketIo.emit('match_found', payload, room=sid)
                    try:
                        InMemoryActiveGames[GameId].setdefault('TokenSentToSids', set()).add(sid)
                        sent_sids.add(sid)
                    except Exception:
                        pass

            # Persist The Join Token and Allowed Users, Including Which Sids Received The Token
            try:
                GameModel.UpdateGameTokenFields(GameId, join_token, [WhiteId, BlackId], [], list(sent_sids))
            except Exception as e:
                logger.exception(f"Error Persisting Token Sids After Notify For Game {GameId}: {e}")

            return jsonify(payload)
            
        except Exception as e:
            print(f"MatchCreationError: {str(e)}")
            # ReturnPlayersToQueueOnError
            InMemoryMatchmakingQueue.extend([
                {'UserId': UserId, 'GameType': GameType}, 
                opponent
            ])
            return jsonify({'Success': False, 'Message': 'MatchCreationError'})
    else:
        # AddToQueueWithGameTypePreference
        InMemoryMatchmakingQueue.append({'UserId': UserId, 'GameType': GameType})
        logger.info(f"QuickMatch : Queued User {UserId} For Game Type {GameType}")
        return jsonify({'Success': True, 'MatchCreated': False})


# Localhost-only debug endpoint to inspect in-memory game state for debugging
@App.route('/debug/inmemory/game/<int:game_id>')
def DebugInMemoryGame(game_id):
    # Only allow localhost for safety
    if request.remote_addr not in ('127.0.0.1', '::1', 'localhost'):
        return jsonify({'Success': False, 'Message': 'Forbidden'}), 403
    game = InMemoryActiveGames.get(game_id)
    if not game:
        return jsonify({'Success': False, 'Message': 'NotFound'}), 404
    # Convert non-serializable objects
    safe_game = {}
    for k, v in game.items():
        if isinstance(v, set):
            safe_game[k] = list(v)
        else:
            safe_game[k] = v
    return jsonify({'Success': True, 'Game': safe_game})

# -------------------
# SocketIoEvents
# -------------------
@SocketIo.on('connect')
def OnConnect():
    emit('connected', {'ok': True})
    logger.info(f"Socket Connected : sid={request.sid}")

@SocketIo.on('disconnect')
def OnDisconnect():
    sid = request.sid
    user_id = None
    # FindUserIdFromSid
    for uid, user_sid in UserIdToSid.items():
        if user_sid == sid:
            user_id = uid
            UserIdToSid.pop(uid)
            break
    
    if user_id:
        logger.info(f"Socket Disconnected : sid={sid}, user_id={user_id}")
        # RemovePlayerFromPresenceTrackingAndNotifyGames
        for game_id, game in InMemoryActiveGames.items():
            if str(game.get('WhiteUserId')) == str(user_id) or str(game.get('BlackUserId')) == str(user_id):
                if 'players_present' in game:
                    game['players_present'].discard(int(user_id))
                else:
                    game['players_present'] = set()

                # Record Time Of Disconnection For Grace Period Handling
                disconnected = InMemoryActiveGames[game_id].setdefault('Disconnected', {})
                disconnected[str(user_id)] = time.time()

                Room = f"GameRoom{game_id}"
                # Notify Room That A Player Disconnected But Do NOT End The Game Yet
                emit('player_disconnected', {
                    'UserId': user_id,
                    'BothPlayersPresent': len(game.get('players_present', set())) >= 2
                }, room=Room)

                # Start A Background Checker That Will Finalize The Leave After A Grace Period
                def finalize_disconnect(gid, uid, grace=30):
                    try:
                        eventlet.sleep(grace)
                        g = InMemoryActiveGames.get(gid)
                        if not g:
                            return
                        # If The User Rejoined, Do Nothing
                        if int(uid) in g.get('players_present', set()):
                            # Cleaned Up Reconnection
                            g.get('Disconnected', {}).pop(str(uid), None)
                            return

                        # Final Removal: Opponent Wins By Abandonment
                        Game = GameModel.GetGameById(gid)
                        if not Game or Game.get('Status') == 'finished':
                            return

                        # Determine Winner
                        uid_int = int(uid)
                        if Game['WhiteUserId'] == uid_int:
                            Winner = 'Black'
                        else:
                            Winner = 'White'

                        # Mark Game Finished And Update DB
                        GameModel.UpdateGameStatus(gid, 'finished')
                        GameModel.UpdateGameFen(gid, Game['Fen'], Game.get('MoveHistory', ''), Winner)

                        # Cleanup Timers
                        stop_game_timer(gid)
                        if gid in GameTimers:
                            del GameTimers[gid]

                        # Notify Room And Update Rankings (use SocketIo.emit from background)
                        SocketIo.emit('player_left', {
                            'UserId': uid,
                            'BothPlayersPresent': False
                        }, room=f"GameRoom{gid}")

                        SocketIo.emit('game_over', {
                            'GameId': gid,
                            'UserId': uid,
                            'Reason': 'disconnect_timeout',
                            'Winner': Winner
                        }, room=f"GameRoom{gid}")

                        # update_rankings may emit events; ensure it uses SocketIo.emit internally
                        update_rankings(gid, Winner)
                    except Exception as e:
                        logger.exception(f"Error Finalizing Disconnect For Game {gid} User {uid}: {e}")

                eventlet.spawn_n(finalize_disconnect, game_id, user_id)

@SocketIo.on('register_user')
def OnRegisterUser(Data):
    UserId = (Data or {}).get('UserId')
    if not UserId:
        emit('register_result', {'Success': False, 'Message': 'MissingUserId'})
        return
    UserIdToSid[str(UserId)] = request.sid
    emit('register_result', {'Success': True})
    logger.info(f"User Registered Socket : user_id={UserId}, sid={request.sid}")

@SocketIo.on('join_game')
def OnJoinGame(Data):
    logger.info(f"Join Game Request : {Data}")
    UserId = Data.get('UserId')
    GameId = Data.get('GameId')
    JoinToken = Data.get('JoinToken')

    # If GameId Not Provided, Try To Resolve Via JoinToken
    if not GameId and JoinToken:
        for k, v in InMemoryActiveGames.items():
            try:
                if isinstance(v, dict) and v.get('JoinToken') == JoinToken:
                    GameId = k
                    logger.debug(f"Resolved GameId {GameId} From JoinToken")
                    break
            except Exception:
                continue

    if not all([UserId, GameId]):
        emit('game_state', {'Success': False, 'Message': 'MissingParameters'})
        return

    Room = f"GameRoom{GameId}"
    join_room(Room)

    Game = GameModel.GetGameById(GameId)
    if not Game:
        emit('game_state', {'Success': False, 'Message': 'GameNotFound'})
        return

    # If a join token exists for this game in-memory, enforce that only allowed users can use it
    inmem = InMemoryActiveGames.get(GameId, {})
    # If we have a JoinToken but the in-memory entry is missing or doesn't have the token
    # try to rehydrate the in-memory state from the DB (handles refresh/race cases)
    if JoinToken and (not inmem or not inmem.get('JoinToken')):
        try:
            db_game = GameModel.GetGameByJoinToken(JoinToken)
            if db_game:
                # Rehydrate minimal in-memory structure from DB
                InMemoryActiveGames[GameId] = InMemoryActiveGames.get(GameId, {})
                InMemoryActiveGames[GameId].update({
                    'Fen': db_game.get('Fen'),
                    'MoveHistory': db_game.get('MoveHistory', ''),
                    'WhiteUserId': db_game.get('WhiteUserId'),
                    'BlackUserId': db_game.get('BlackUserId'),
                    'JoinToken': db_game.get('JoinToken'),
                    'AllowedUserIds': db_game.get('AllowedUserIds') or [db_game.get('WhiteUserId'), db_game.get('BlackUserId')],
                    'TokenUsedBy': db_game.get('TokenUsedBy') or set(),
                    'TokenSentToSids': db_game.get('TokenSentToSids') or set(),
                    'GameType': db_game.get('GameType', 'classical'),
                    'TimeControl': db_game.get('TimeControl', 600),
                    'Rankings_Updated': False
                })
                inmem = InMemoryActiveGames.get(GameId, {})
                logger.info(f"Rehydrated In-Memory Game {GameId} From DB Using JoinToken")
        except Exception as e:
            logger.exception(f"Error Rehydrating In-Memory Game for Token {JoinToken}: {e}")
    # Verify The Socket Is Registered As The Claiming UserId
    registered_sid = UserIdToSid.get(str(UserId))
    if not registered_sid or registered_sid != request.sid:
        logger.info(f"Join Attempt With Unregistered Or Mismatched Sid : User={UserId}, Sid={request.sid}, Registered_Sid={registered_sid}")
        emit('game_state', {'Success': False, 'Message': 'NotAuthenticated'})
        return
    if inmem:
        join_token = inmem.get('JoinToken')
        allowed = inmem.get('AllowedUserIds') or []
        token_used_by = inmem.get('TokenUsedBy') or set()
        if JoinToken:
            # Token Provided By Client - Validate It Matches Server's Token
            if not join_token or JoinToken != join_token:
                emit('game_state', {'Success': False, 'Message': 'InvalidJoinToken'})
                return
            # Ensure User Is In Allowed List
            if int(UserId) not in [int(x) for x in allowed]:
                emit('game_state', {'Success': False, 'Message': 'NotAllowed'})
                return
            # Record Token Usage
            token_used_by = set(token_used_by) if not isinstance(token_used_by, set) else token_used_by
            token_used_by.add(int(UserId))
            inmem['TokenUsedBy'] = token_used_by
            logger.debug(f"User {UserId} Used Join Token For Game {GameId}. TokenUsedBy={token_used_by}")
            # If Both Players Used Token, Invalidate
            if set([int(x) for x in allowed]).issubset(token_used_by):
                inmem.pop('JoinToken', None)
                logger.info(f"JoinToken For Game {GameId} Invalidated After Both Users Joined")
        else:
            # No Token Provided By Client: Allow Join Only If The Socket Is Registered As The User And The User Is One Of The Allowed Players
            if join_token:
                if int(UserId) not in [int(x) for x in allowed] or registered_sid != request.sid:
                    emit('game_state', {'Success': False, 'Message': 'NotAllowed'})
                    return

    # TrackJoinedPlayersInMemoryForEachGame
    if 'players_present' not in InMemoryActiveGames.get(GameId, {}):
        InMemoryActiveGames[GameId] = InMemoryActiveGames.get(GameId, {})
        InMemoryActiveGames[GameId]['players_present'] = set()

    InMemoryActiveGames[GameId]['players_present'].add(UserId)
    both_present = len(InMemoryActiveGames[GameId]['players_present']) >= 2

    # FirstAssignColorImmediately
    if Game['WhiteUserId'] == UserId:
        emit('assign_color', {'Color': 'white', 'Success': True})
    elif Game['BlackUserId'] == UserId:
        emit('assign_color', {'Color': 'black', 'Success': True})
    else:
        emit('game_state', {'Success': False, 'Message': 'NotAPlayer'})
        return

    # InitializeTimerButDontStartItYet
    init_game_timer(GameId)
    
    # StartTimerIfBothPlayersAreNowPresent
    if both_present:
        start_game_timer(GameId)
    
    # SendInitialGameStateWithTimersAndPresenceInfo
    emit('game_state', {
        'Success': True,
        'Fen': Game['Fen'],
        'MoveHistory': Game.get('MoveHistory', ""),
        'WhiteUserId': Game['WhiteUserId'],
        'BlackUserId': Game['BlackUserId'],
        'Timers': GameTimers[GameId],
        'GameType': Game.get('GameType', 'classical'),
        'BothPlayersPresent': both_present
    })

    # NotifyOthersInRoomAboutTheJoin
    emit('player_joined', {
        'UserId': UserId, 
        'IsWhite': Game['WhiteUserId'] == UserId,
        'BothPlayersPresent': both_present
    }, room=Room, include_self=False)
    
    # IfBothPlayersPresentSendGameStartNotification
    if both_present:
        emit('game_start', {
            'WhiteUserId': Game['WhiteUserId'],
            'BlackUserId': Game['BlackUserId']
        }, room=Room)

    logger.info(f"Player {UserId} Joined Game {GameId}, Both Present : {both_present}")

# FixedOnMakeMoveToPreventDuplicateRankingCallsAndHandleCheckmateProperly
@SocketIo.on('make_move')
def OnMakeMove(Data):
    try:
        logger.debug(f"Make Move Received: {Data}")
        UserId = Data.get('UserId')
        GameId = Data.get('GameId')
        UciMove = Data.get('UciMove')

        if not all([UserId, GameId, UciMove]):
            emit('move_result', {'Success': False, 'Message': 'MissingParameters'})
            return

        Game = GameModel.GetGameById(GameId)
        if not Game:
            emit('move_result', {'Success': False, 'Message': 'GameNotFound'})
            return

        # Define Room At The Start
        Room = f"GameRoom{GameId}"

        # Parse The Current Game Position
        Board = chess.Board(Game['Fen'])

        # Verify It's The Player's Turn
        isWhite = Game['WhiteUserId'] == UserId
        isBlack = Game['BlackUserId'] == UserId
        if (Board.turn and not isWhite) or (not Board.turn and not isBlack):
            emit('move_result', {'Success': False, 'Message': 'NotYourTurn'})
            return

        # FIXED: Update Timer BEFORE Applying The Move
        current_color = 'white' if Board.turn else 'black'
        timer_state = update_game_timer(GameId, current_color)

        # Apply The Move
        Res = ChessEngine.IsMoveLegalAndApply(Game['Fen'], UciMove)

        # Update Game State
        PrevHistory = Game.get('MoveHistory') or ""
        MoveHistory = (PrevHistory + "|" + UciMove) if PrevHistory else UciMove

        # FIXED: Check Game Result AFTER The Move Is Applied
        NewBoard = chess.Board(Res['Fen'])
        
        # SaveMoveToDatabaseFirst
        GameModel.UpdateGameFen(GameId, Res['Fen'], MoveHistory)

        # FIXED: Handle Checkmate Properly With Correct Winner Determination
        if NewBoard.is_checkmate():
            # The Player Who Just Made The Move (Caused Checkmate) Is The Winner
            winner = "White" if current_color == 'white' else "Black"
            print(f"Checkmate! Winner : {winner}")  # Debug log
            
            GameModel.UpdateGameStatus(GameId, 'checkmate')
            GameModel.UpdateGameFen(GameId, Res['Fen'], MoveHistory, winner, 'checkmate')
            
            stop_game_timer(GameId)

            # Calculate Game Stats
            from datetime import datetime
            created = datetime.strptime(str(Game['CreatedAt']), '%Y-%m-%d %H:%M:%S')
            updated = datetime.now()
            game_duration = (updated - created).total_seconds() / 60
            moves_count = len(MoveHistory.split('|')) if MoveHistory else 0

            # Emit Move With Checkmate Flag
            emit('move_made', {
                'Success': True,
                'Fen': Res['Fen'],
                'UciMove': UciMove,
                'San': Res.get('San', ''),
                'ResultState': winner,
                'IsCheckmate': True,
                'Winner': winner,  # Added Explicit Winner Field
                'Timers': timer_state
            }, room=Room)

            # Then Emit Game Over With Correct Winner
            emit('game_over', {
                'GameId': GameId,
                'Reason': 'checkmate',
                'Winner': winner,
                'Duration': game_duration,
                'Moves': moves_count,
                'IsCheckmate': True,
                'LastMoveBy': current_color  # Add Who Made The Winning Move
            }, room=Room)

            # Update Rankings With Correct Winner
            update_rankings(GameId, winner)
            return

        # Check For Other Game Endings
        result_state = "Ongoing"
        if NewBoard.is_stalemate():
            result_state = "Draw"
            GameModel.UpdateGameFen(GameId, Res['Fen'], MoveHistory, "Draw", 'stalemate')
            stop_game_timer(GameId)
        elif NewBoard.is_insufficient_material():
            result_state = "Draw" 
            GameModel.UpdateGameFen(GameId, Res['Fen'], MoveHistory, "Draw", 'insufficient_material')
            stop_game_timer(GameId)

        # Continue With Regular Move Broadcast
        emit('move_made', {
            'Success': True,
            'Fen': Res['Fen'],
            'UciMove': UciMove,
            'San': Res.get('San', ''),
            'ResultState': result_state,
            'IsCheckmate': False,
            'Timers': timer_state
        }, room=Room)

        # Handle Draws
        if result_state == "Draw":
            emit('game_over', {
                'GameId': GameId,
                'Reason': 'stalemate' if NewBoard.is_stalemate() else 'insufficient_material',
                'Winner': 'Draw'
            }, room=Room)
            update_rankings(GameId, 'Draw')

    except Exception as e:
        logger.exception(f"Error In Make_Move Handler: {e}")
        emit('move_result', {'Success': False, 'Message': str(e)})
        return

# CompletelyFixedUpdateRankingsFunction
def update_rankings(game_id, winner):
    """UpdatePlayerRankingsAndRecordHistoryCompletelyFixed"""
    game = GameModel.GetGameById(game_id)
    if not game:
        return

    # Prevent Duplicate Ranking Updates
    if game_id in InMemoryActiveGames:
        if InMemoryActiveGames[game_id].get('Rankings_Updated', False):
            return
        InMemoryActiveGames[game_id]['Rankings_Updated'] = True
        
    Room = f"GameRoom{game_id}"
    logger.info(f"Updating Rankings For Game {game_id}, Winner={winner}")
    
    white_user = UserModel.GetUserById(game['WhiteUserId'])
    black_user = UserModel.GetUserById(game['BlackUserId'])
    
    if winner == 'Draw':
        score = 0.5
    else:
        score = 1.0 if winner == 'White' else 0.0

    # Get Game Type From Database (Not Duration)
    game_type = game.get('GameType', 'classical')

    # Get Appropriate Ratings Based On Game Type
    if game_type == 'rapid' or game_type == 'blitz':
        white_rating = white_user['RapidRating']
        black_rating = black_user['RapidRating']
        rating_type = 'rapid'
        k_factor = 40 if (white_rating < 2100 and black_rating < 2100) else 28
    else:  # Classical
        white_rating = white_user['ClassicalRating']
        black_rating = black_user['ClassicalRating']
        rating_type = 'classical'
        k_factor = 32 if (white_rating < 2100 and black_rating < 2100) else 24

    # Calculate New Ratings For The Specific Type Only
    new_white_rating, new_black_rating = EloUtils.UpdateElo(
        white_rating, black_rating, score, k_factor
    )

    # Calculate Game Stats
    from datetime import datetime
    created = datetime.strptime(str(game['CreatedAt']), '%Y-%m-%d %H:%M:%S')
    updated = datetime.now()
    game_duration = (updated - created).total_seconds() / 60
    moves_count = len(game['MoveHistory'].split('|')) if game['MoveHistory'] else 0

    # Update Game Stats For Both Players
    white_game_data = {
        'result': 'win' if winner == 'White' else ('draw' if winner == 'Draw' else 'loss'),
        'duration': game_duration,
        'moves': moves_count,
        'won': winner == 'White',
        'checkmate': game.get('Status') == 'checkmate'
    }
    
    black_game_data = {
        'result': 'win' if winner == 'Black' else ('draw' if winner == 'Draw' else 'loss'),
        'duration': game_duration,
        'moves': moves_count,
        'won': winner == 'Black',
        'checkmate': game.get('Status') == 'checkmate'
    }

    # Update Game Stats And Achievements
    UserModel.UpdateGameStats(game['WhiteUserId'], white_game_data)
    UserModel.UpdateGameStats(game['BlackUserId'], black_game_data)
    UserModel.UpdateAchievements(game['WhiteUserId'], white_game_data)
    UserModel.UpdateAchievements(game['BlackUserId'], black_game_data)

    # Record Rating Changes ONLY For The Type That Was Played
    result_white = 'draw' if winner == 'Draw' else ('win' if winner == 'White' else 'loss')
    result_black = 'draw' if winner == 'Draw' else ('win' if winner == 'Black' else 'loss')
    
    UserModel.RecordRatingChange(
        game['WhiteUserId'], game_id,
        white_rating, new_white_rating,
        result_white, game['BlackUserId'], black_rating, rating_type
    )
    UserModel.RecordRatingChange(
        game['BlackUserId'], game_id,
        black_rating, new_black_rating,
        result_black, game['WhiteUserId'], white_rating, rating_type
    )

    # Update ONLY the Rating Type That Was Played
    UserModel.UpdateRatingsForType(game['WhiteUserId'], new_white_rating, rating_type)
    UserModel.UpdateRatingsForType(game['BlackUserId'], new_black_rating, rating_type)

    # Send Appropriate Rating Update Notification
    if rating_type == 'rapid':
        SocketIo.emit('ratings_updated', {
            'WhiteUserId': game['WhiteUserId'],
            'BlackUserId': game['BlackUserId'],
            'WhiteClassicalRating': white_user['ClassicalRating'],
            'WhiteRapidRating': new_white_rating,
            'BlackClassicalRating': black_user['ClassicalRating'],
            'BlackRapidRating': new_black_rating,
            'WhiteClassicalChange': 0,
            'WhiteRapidChange': new_white_rating - white_rating,
            'BlackClassicalChange': 0,
            'BlackRapidChange': new_black_rating - black_rating,
            'GameType': game_type
        }, room=Room)
    else:
        SocketIo.emit('ratings_updated', {
            'WhiteUserId': game['WhiteUserId'],
            'BlackUserId': game['BlackUserId'],
            'WhiteClassicalRating': new_white_rating,
            'WhiteRapidRating': white_user['RapidRating'],
            'BlackClassicalRating': new_black_rating,
            'BlackRapidRating': black_user['RapidRating'],
            'WhiteClassicalChange': new_white_rating - white_rating,
            'WhiteRapidChange': 0,
            'BlackClassicalChange': new_black_rating - black_rating,
            'BlackRapidChange': 0,
            'GameType': game_type
        }, room=Room)

# AddEndpointForGettingUserStatsAndRating
@App.route("/api/user/<int:user_id>")
def GetUserInfo(user_id):
    user = UserModel.GetPlayerStats(user_id)
    if not user:
        return jsonify({'Success': False, 'Message': 'UserNotFound'}), 404
    return jsonify({
        'Success': True,
        'UserName': user['UserName'],
        'ClassicalRating': user['ClassicalRating'],
        'RapidRating': user['RapidRating'],
        'ClassicalPeak': user['ClassicalPeak'],
        'RapidPeak': user['RapidPeak'],
        'GamesPlayed': user['GamesPlayed'],
        'GamesWon': user['GamesWon'],
        'GamesLost': user['GamesLost'],
        'GamesDraw': user['GamesDraw'],
        'WinRate': user['WinRate'],
        'QuickWins': user['QuickWins'],
        'FastWins': user['FastWins'],
        'CurrentStreak': user['CurrentStreak'],
        'BestStreak': user['BestStreak'],
        'AvgGameLength': user['AvgGameLength'],
        'LongestGame': user['LongestGame'],
        'TotalMoves': user['TotalMoves'],
        'CheckmateWins': user['CheckmateWins']
    })

# AddNewAchievementsEndpoint
@App.route("/api/user/<int:user_id>/achievements")
def GetUserAchievements(user_id):
    achievements = UserModel.GetAchievements(user_id)
    if not achievements:
        return jsonify({
            'Success': False,
            'Message': 'UserNotFound'
        }), 404
        
    return jsonify({
        'Success': True,
        'Achievements': {
            'FirstWin': bool(achievements['FirstWin']),
            'SpeedDemon': bool(achievements['SpeedDemon']),
            'MasterRating': bool(achievements['MasterRating']),
            'CheckmateKing': bool(achievements['CheckmateKing']),
            'Veteran': bool(achievements['Veteran'])
        }
    })

# FixedEndpointForRatingsHistory
@App.route("/api/user/<int:user_id>/ratings")
def GetUserRatings(user_id):
    history = UserModel.GetRatingHistory(user_id)
    return jsonify({
        'Success': True,
        'History': history
    })


@App.route('/api/game/validate', methods=['POST'])
def ApiValidateGame():
    Data = request.json or {}
    GameId = Data.get('GameId')
    JoinToken = Data.get('JoinToken')
    UserId = Data.get('UserId')
    # Accept Either A GameId Or A JoinToken (Token Issued At Match Creation)
    if not (GameId or JoinToken) or not UserId:
        return jsonify({'Success': False, 'Message': 'MissingParameters'}), 400
    logger.info(f"ApiValidateGame Called : GameId={GameId}, UserId={UserId}")
    Game = None
    try:
        if GameId:
            Game = GameModel.GetGameById(GameId)
    except Exception as e:
        logger.exception(f"Error When Calling GetGameById({GameId}): {e}")

    # If DB lookup Failed Or Returned None, Try Robust In-Memory Fallbacks (Handles Creation Race / Transient Cases)
    if not Game:
        found = None
        # Try Numeric Key
        try:
            key_int = int(GameId)
            found = InMemoryActiveGames.get(key_int)
            if found:
                logger.debug(f"Found In InMemoryActiveGames By int Key: {key_int}")
        except Exception:
            key_int = None

        # Try String Key If Numeric Didn't Match
        if not found:
            found = InMemoryActiveGames.get(str(GameId))
            if found:
                logger.debug(f"Found In InMemoryActiveGames By str Key: {GameId}")

        # Try Resolving By Provided JoinToken (Trusted One-Time Token Created At Match Creation)
        if not found and JoinToken:
            for k, v in InMemoryActiveGames.items():
                try:
                    if isinstance(v, dict) and v.get('JoinToken') == JoinToken:
                        found = v
                        logger.debug(f"Found in InMemoryActiveGames by JoinToken : {JoinToken} -> game {k}")
                        # Set GameId So Later Code Can Use It (Coerce To Int If Possible)
                        try:
                            GameId = int(k)
                        except Exception:
                            GameId = k
                        break
                except Exception:
                    continue

        # As Last Resort, Try To Discover By Scanning Entries For Matching GameId Property (Some Code May Store Nested Dicts)
        if not found:
            for k, v in InMemoryActiveGames.items():
                try:
                    if isinstance(v, dict) and ('WhiteUserId' in v or 'BlackUserId' in v):
                        # If This Entry Actually Corresponds To The Requested GameId
                        # Some Entries May Include A GameId Field
                        if v.get('GameId') == GameId or v.get('GameId') == key_int:
                            found = v
                            logger.debug(f"Found In InMemoryActiveGames By Scanning : key={k}")
                            break
                except Exception:
                    continue

        if found:
            Game = found

    if not Game:
        logger.info(f"Game Validation Failed : Game {GameId} Not Found (User {UserId}) - InMemory Keys: {list(InMemoryActiveGames.keys())}")
        return jsonify({'Success': False, 'Message': 'GameNotFound'}), 404

    # Determine Allowed Participants From Available Game Data
    Wid = None
    Bid = None
    status = ''
    Allowed = False
    try:
        if isinstance(Game, dict):
            white = Game.get('WhiteUserId')
            black = Game.get('BlackUserId')
            status = Game.get('Status', '')
        else:
            # Assume Object-Like From ORM / Model
            white = getattr(Game, 'WhiteUserId', None) or getattr(Game, 'WhiteId', None)
            black = getattr(Game, 'BlackUserId', None) or getattr(Game, 'BlackId', None)
            status = getattr(Game, 'Status', '')

        try:
            Wid = int(white) if white is not None else None
        except Exception:
            Wid = None
        try:
            Bid = int(black) if black is not None else None
        except Exception:
            Bid = None

        Allowed = (int(UserId) == Wid) or (int(UserId) == Bid)
    except Exception as e:
        logger.exception(f"Error While Checking Allowed Users For Validation : {e}")
        Allowed = False

    # If Join Token Was Provided, Ensure It Matches The Server Token And That The User Is Allowed.
    try:
        if JoinToken and isinstance(Game, dict):
            allowed_ids = Game.get('AllowedUserIds') or []
            token_used_by = Game.get('TokenUsedBy') or set()
            # Coerce IDs To Ints For Comparison
            allowed_ids_int = [int(x) for x in allowed_ids]
            token_sids = Game.get('TokenSentToSids') or set()

            # Verify The Provided Token Matches The Server's Token
            server_token = Game.get('JoinToken')
            if not server_token or JoinToken != server_token:
                logger.info(f"ApiValidateGame : Invalid Join Token Provided For Game {GameId}. Provided={JoinToken} Expected={server_token}")
                return jsonify({'Success': False, 'Message': 'InvalidJoinToken'}), 403

            # Allow HTTP-Based Validation For Allowed Users (Practical For Link/Bookmark Flows).
            if int(UserId) in allowed_ids_int:
                token_used_by = set(token_used_by) if not isinstance(token_used_by, set) else token_used_by
                token_used_by.add(int(UserId))
                Game['TokenUsedBy'] = token_used_by
                logger.debug(f"JoinToken Used By User {UserId} For Game {GameId}. TokenUsedBy={token_used_by} TokenSentToSids={list(token_sids)}")
                # If Both Players Have Used Token, Invalidate It
                if set(allowed_ids_int).issubset(token_used_by):
                    Game.pop('JoinToken', None)
                    logger.info(f"JoinToken For Game {GameId} Invalidated After Both Users Used It")

                # Persist Token Usage To DB
                try:
                    GameModel.UpdateGameTokenFields(GameId, server_token, allowed_ids_int, list(token_used_by), list(token_sids))
                except Exception as e:
                    logger.exception(f"Error Persisting Token Usage For Game {GameId}: {e}")
            else:
                logger.info(f"ApiValidateGame : User {UserId} Attempted To Use JoinToken For Game {GameId} But Is Not Allowed. Allowed={allowed_ids_int}")
                return jsonify({'Success': False, 'Message': 'NotAllowed'}), 403
    except Exception as e:
        logger.exception(f"Error Handling JoinToken Usage For Game {GameId}: {e}")

    logger.info(f"ApiValidateGame Result For Game {GameId}: Allowed={Allowed} (User {UserId}) White={Wid} Black={Bid}")
    return jsonify({'Success': True, 'Allowed': Allowed, 'WhiteUserId': Wid, 'BlackUserId': Bid, 'Status': status})


@SocketIo.on('validate_game')
def OnValidateGame(Data):
    """Socket-Based Validation : Ensures Request Comes From Registered Socket And Validates GameId/JoinToken For The Registered User."""
    try:
        Data = Data or {}
        GameId = Data.get('GameId')
        JoinToken = Data.get('JoinToken')
        UserId = Data.get('UserId')

        # Require UserId And Either GameId Or JoinToken
        if not (GameId or JoinToken) or not UserId:
            emit('validate_result', {'Success': False, 'Message': 'MissingParameters'}, room=request.sid)
            return

        # Ensure The Socket Is Registered For This UserId
        reg_sid = UserIdToSid.get(str(UserId))
        if not reg_sid:
            # If No Registration Exists Yet, Auto-Register This Socket For The User.
            UserIdToSid[str(UserId)] = request.sid
            logger.info(f"Auto-Registered Socket For User {UserId} During Validate: sid={request.sid}")
        elif reg_sid != request.sid:
            # If Mapping Exists But Points To Another Sid (Stale), Update It To Current
            logger.info(f"Socket Validation : Updating Mapping For User {UserId} From sid={reg_sid} To sid={request.sid}")
            UserIdToSid[str(UserId)] = request.sid

        # Reuse ApiValidateGame logic But Operate On In-Memory Structures Directly To Avoid DB Race
        Game = None
        try:
            if GameId:
                Game = GameModel.GetGameById(GameId)
        except Exception as e:
            logger.exception(f"Error When Calling GetGameById({GameId}) In Socket Validate : {e}")

        # In-Memory Fallback
        if not Game:
            found = None
            try:
                key_int = int(GameId) if GameId else None
            except Exception:
                key_int = None

            if key_int is not None:
                found = InMemoryActiveGames.get(key_int)
            if not found and GameId:
                found = InMemoryActiveGames.get(str(GameId))

            if not found and JoinToken:
                for k, v in InMemoryActiveGames.items():
                    try:
                        if isinstance(v, dict) and v.get('JoinToken') == JoinToken:
                            found = v
                            GameId = k
                            break
                    except Exception:
                        continue

            if found:
                Game = found

        if not Game:
            emit('validate_result', {'Success': False, 'Message': 'GameNotFound'}, room=request.sid)
            return

        # Determine White/Black IDs
        if isinstance(Game, dict):
            white = Game.get('WhiteUserId')
            black = Game.get('BlackUserId')
            status = Game.get('Status', '')
        else:
            white = getattr(Game, 'WhiteUserId', None) or getattr(Game, 'WhiteId', None)
            black = getattr(Game, 'BlackUserId', None) or getattr(Game, 'BlackId', None)
            status = getattr(Game, 'Status', '')

        try:
            Wid = int(white) if white is not None else None
        except Exception:
            Wid = None
        try:
            Bid = int(black) if black is not None else None
        except Exception:
            Bid = None

        Allowed = (int(UserId) == Wid) or (int(UserId) == Bid)

        # If JoinToken Provided, Enforce Allowed User And Mark Usage
        if JoinToken and isinstance(Game, dict):
            allowed_ids = [int(x) for x in (Game.get('AllowedUserIds') or [])]
            token_used_by = Game.get('TokenUsedBy') or set()
            token_sids = Game.get('TokenSentToSids') or set()

            server_token = Game.get('JoinToken')
            # Verify Provided JoinToken Matches Server's Token
            if not server_token or JoinToken != server_token:
                logger.info(f"Socket Validate_Game : Invalid Join Token For Game {GameId}. Provided={JoinToken} Expected={server_token}")
                emit('validate_result', {'Success': False, 'Message': 'InvalidJoinToken'}, room=request.sid)
                return

            if int(UserId) not in allowed_ids:
                logger.info(f"Socket Validate_Game : User {UserId} Not In Allowed List For Game {GameId}. AllowedIds={allowed_ids}")
                emit('validate_result', {'Success': False, 'Message': 'NotAllowed'}, room=request.sid)
                return

            # Record The Token Usage And Also Record This Sid As One That Used/Received The Token
            token_used_by = set(token_used_by) if not isinstance(token_used_by, set) else token_used_by
            token_used_by.add(int(UserId))
            Game['TokenUsedBy'] = token_used_by
            token_sids = set(token_sids) if not isinstance(token_sids, set) else token_sids
            token_sids.add(request.sid)
            Game['TokenSentToSids'] = token_sids
            if set([int(x) for x in allowed_ids]).issubset(token_used_by):
                Game.pop('JoinToken', None)

            # Persist Updated Token Fields
            try:
                GameModel.UpdateGameTokenFields(GameId, server_token, [int(x) for x in allowed_ids], list(token_used_by), list(token_sids))
            except Exception as e:
                logger.exception(f"Error Persisting Token Usage For Game {GameId} In Socket Validate: {e}")

        emit('validate_result', {'Success': True, 'Allowed': Allowed, 'WhiteUserId': Wid, 'BlackUserId': Bid, 'Status': status}, room=request.sid)
    except Exception as e:
        logger.exception(f"Error In Socket Validate_Game : {e}")
        emit('validate_result', {'Success': False, 'Message': 'ServerError'}, room=request.sid)
@App.route('/api/top-players')
def GetLeaderboard():
    limit = request.args.get('limit', default=10, type=int)
    players = UserModel.GetTopPlayers(limit)
    return jsonify({
        'Success': True,
        'Players': players
    })

# FixedOnGameOverToPreventDoubleRankingUpdates
@SocketIo.on('game_over')
def OnGameOver(data):
    logger.info(f"Game Over Received : {data}")
    GameId = data.get('GameId')
    UserId = data.get('UserId')
    Reason = data.get('Reason')
    Winner = data.get('Winner')
    Room = f"GameRoom{GameId}"

    # Get And Update Game State
    Game = GameModel.GetGameById(GameId)
    if Game and Game['Status'] != 'finished':
        # Don't Update Rankings If Already Done
        if GameId in InMemoryActiveGames and InMemoryActiveGames[GameId].get('Rankings_Updated', False):
            return

        # Calculate Game Stats
        from datetime import datetime
        created = datetime.strptime(str(Game['CreatedAt']), '%Y-%m-%d %H:%M:%S')
        updated = datetime.now()
        game_duration = (updated - created).total_seconds() / 60
        moves_count = len(Game['MoveHistory'].split('|')) if Game['MoveHistory'] else 0

        # Mark Game As Finished And Set Status
        is_checkmate = Reason == 'checkmate'
        new_status = 'checkmate' if is_checkmate else 'finished'
        GameModel.UpdateGameStatus(GameId, new_status)
        GameModel.UpdateGameFen(GameId, Game['Fen'], Game['MoveHistory'], Winner, new_status)

        # Clean Up Game Timers
        stop_game_timer(GameId)
        if GameId in GameTimers:
            del GameTimers[GameId]

        # Update Rankings And Send Notification To Room
        update_rankings(GameId, Winner)
        
        emit('game_over', {
            'GameId': GameId,
            'UserId': UserId,
            'Reason': Reason,
            'Winner': Winner,
            'Duration': game_duration,
            'Moves': moves_count,
            'IsCheckmate': is_checkmate
        }, room=Room)

@SocketIo.on('draw_response')
def OnDrawResponse(Data):
    GameId = Data.get('GameId')
    UserId = Data.get('UserId')
    Accept = Data.get('Accept')
    
    if not all([GameId, UserId]):
        return
        
    Room = f"GameRoom{GameId}"

    # If draw was accepted, end the game
    if Accept:
        logger.info(f"Draw Accepted For Game {GameId} By User {UserId}")
        Game = GameModel.GetGameById(GameId)
        if Game and Game['Status'] != 'finished':  # Prevent Duplicate Handling
            # Mark Game As Finished
            GameModel.UpdateGameStatus(GameId, 'finished')
            GameModel.UpdateGameFen(GameId, Game['Fen'], Game['MoveHistory'], 'Draw')
            
            stop_game_timer(GameId)
            if GameId in GameTimers:
                del GameTimers[GameId]
            
            emit('game_over', {
                'GameId': GameId,
                'UserId': UserId,
                'Reason': 'draw_agreed',
                'Winner': 'Draw',
                'isDraw': True
            }, room=Room)

            # Update Rankings Only Once
            update_rankings(GameId, 'Draw')
    else:
        logger.info(f"Draw Declined For Game {GameId} By User {UserId}")
        emit('draw_response', {
            'UserId': UserId,
            'Accept': Accept
        }, room=Room)

@SocketIo.on('resign_game')
def OnResignGame(Data):
    GameId = Data.get('GameId')
    UserId = Data.get('UserId')
    
    if not all([GameId, UserId]):
        return
        
    Game = GameModel.GetGameById(GameId)
    if not Game or Game['Status'] == 'finished':  # Prevent Duplicate Handling
        return
        
    Room = f"GameRoom{GameId}"
    logger.info(f"Resign Received For Game {GameId} By User {UserId}")

    # Determine Winner Based On Who Resigned
    Winner = 'Black' if UserId == Game['WhiteUserId'] else 'White'

    # Mark Game As Finished
    GameModel.UpdateGameStatus(GameId, 'finished')
    GameModel.UpdateGameFen(GameId, Game['Fen'], Game['MoveHistory'], Winner)

    # Clean Up Game Timers
    stop_game_timer(GameId)
    if GameId in GameTimers:
        del GameTimers[GameId]

    # Emit Game Over With Resignation
    emit('game_over', {
        'GameId': GameId,
        'UserId': UserId,
        'Reason': 'resignation',
        'Winner': Winner
    }, room=Room)

    # Update Rankings Only Once
    update_rankings(GameId, Winner)

# UpdateTimeExpiredHandler
@SocketIo.on('time_expired')
def OnTimerExpired(Data):
    GameId = Data.get('GameId')
    Color = Data.get('Color')
    
    Game = GameModel.GetGameById(GameId)
    if not Game:
        return
    Room = f"GameRoom{GameId}"
    logger.info(f"Timer Expired For Game {GameId}, Color={Color}")
    Winner = "Black" if Color == "white" else "White"
    GameModel.UpdateGameFen(GameId, Game['Fen'], Game['MoveHistory'], Winner)
    
    stop_game_timer(GameId)
    if GameId in GameTimers:
        del GameTimers[GameId]
        
    emit('game_over', {
        'Reason': 'timeout',
        'Color': Color,
        'Winner': Winner
    }, room=Room)
    update_rankings(GameId, Winner)

@SocketIo.on('analyze_position')
def OnAnalyzePosition(Data):
    """AnalyzeCurrentPositionForWinProbability"""
    GameId = Data.get('GameId')
    Game = GameModel.GetGameById(GameId)
    
    if Game:
        logger.debug(f"Analyze Position Requested For Game {GameId}")
        board = chess.Board(Game['Fen'])
        probability = calculate_win_probability(board)

        # Store Analysis In Database
        GameModel.StoreAnalysis(GameId, Game['Fen'], probability)
        
        Room = f"GameRoom{GameId}"
        emit('position_analysis', {
            'probability': probability,
            'fen': Game['Fen']
        }, room=Room)

# AddTheseNewEventHandlersAfterOtherSocketHandlers
@SocketIo.on('send_chat')
def OnChatMessage(data):
    """HandleChatMessagesBetweenPlayers"""
    GameId = data.get('GameId')
    UserId = data.get('UserId')
    MessageText = data.get('MessageText')
    
    if not all([GameId, UserId, MessageText]):
        return
        
    Room = f"GameRoom{GameId}"
    Game = GameModel.GetGameById(GameId)
    
    if Game and (Game['WhiteUserId'] == UserId or Game['BlackUserId'] == UserId):
        logger.info(f"Chat Message In Game {GameId} From User {UserId}: {MessageText[:200]}")
        # Store Chat In Database
        GameModel.AddChatMessage(GameId, UserId, MessageText)

        # Broadcast To Room (ensure FromUserId is int)
        emit('chat_message', {
            'GameId': GameId,
            'FromUserId': int(UserId),
            'MessageText': MessageText
        }, room=Room)

@SocketIo.on('draw_offer')
def OnDrawOffer(data):
    """HandleDrawOffersBetweenPlayers"""
    GameId = data.get('GameId')
    UserId = data.get('UserId')
    
    if not all([GameId, UserId]):
        return
        
    Room = f"GameRoom{GameId}"
    Game = GameModel.GetGameById(GameId)
    
    if Game and (Game['WhiteUserId'] == UserId or Game['BlackUserId'] == UserId):
        logger.info(f"Draw Offer For Game {GameId} By User {UserId}")
        # Broadcast Draw Offer To Opponent
        emit('draw_offer', {
            'GameId': GameId,
            'UserId': UserId
        }, room=Room, include_self=False)  # Don't Send Back To Offerer

# -------------------
# RunServer
# -------------------
if __name__ == '__main__':
    # OptionallyGetHostPortFromConfigPyIfYouAddThemThere
    SocketIo.run(App, host='0.0.0.0', port=5000)