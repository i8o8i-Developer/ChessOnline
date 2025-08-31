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

App = Flask(__name__)
App.config['SECRET_KEY'] = 'I8O8IChessSecretKey'
CORS(App, origins=SocketIoCorsOrigins, supports_credentials=True)
SocketIo = SocketIO(App, cors_allowed_origins=SocketIoCorsOrigins, async_mode="eventlet")

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
        
        print(f"TimerInitializedForGame{game_id}: {time_control}sEach")

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
        
        print(f"TimerUpdatedFor{move_made_by_color}: -{elapsed}s, Remaining: {timer[move_made_by_color]}s")
    
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
        print(f"TimerStartedForGame{game_id}")

def stop_game_timer(game_id):
    """StopGameTimer"""
    if game_id in GameTimers:
        GameTimers[game_id]['is_active'] = False
        print(f"TimerStoppedForGame{game_id}")

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
    return jsonify({'Success': True, 'Message': 'UserCreated'})

@App.route('/api/login', methods=['POST'])
def ApiLogin():
    Data = request.json or {}
    UserName = Data.get('UserName')
    Password = Data.get('Password')
    User = UserModel.GetUserByUserName(UserName)
    if not User or HelperHashPassword(Password) != User['PasswordHash']:
        return jsonify({'Success': False, 'Message': 'InvalidCredentials'}), 401
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

            InMemoryActiveGames[GameId] = {
                'Fen': Fen,
                'MoveHistory': "",
                'WhiteUserId': WhiteId,
                'BlackUserId': BlackId,
                'GameType': GameType,
                'TimeControl': TimeControl,
                'rankings_updated': False  # PreventDuplicateRankingUpdates
            }

            payload = {
                'Success': True,
                'MatchCreated': True,
                'GameId': GameId,
                'WhiteUserId': WhiteId,
                'BlackUserId': BlackId,
                'Fen': Fen,
                'GameType': GameType
            }

            # NotifyBothPlayersViaSocket
            for uid in (WhiteId, BlackId):
                sid = UserIdToSid.get(str(uid))
                if sid:
                    SocketIo.emit('match_found', payload, room=sid)

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
        return jsonify({'Success': True, 'MatchCreated': False})

# -------------------
# SocketIoEvents
# -------------------
@SocketIo.on('connect')
def OnConnect():
    emit('connected', {'ok': True})

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
        # RemovePlayerFromPresenceTrackingAndNotifyGames
        for game_id, game in InMemoryActiveGames.items():
            if str(game['WhiteUserId']) == str(user_id) or str(game['BlackUserId']) == str(user_id):
                if 'players_present' in game:
                    game['players_present'].discard(int(user_id))
                Room = f"GameRoom{game_id}"
                emit('player_left', {
                    'UserId': user_id,
                    'BothPlayersPresent': len(game.get('players_present', set())) >= 2
                }, room=Room)

@SocketIo.on('register_user')
def OnRegisterUser(Data):
    UserId = (Data or {}).get('UserId')
    if not UserId:
        emit('register_result', {'Success': False, 'Message': 'MissingUserId'})
        return
    UserIdToSid[str(UserId)] = request.sid
    emit('register_result', {'Success': True})

@SocketIo.on('join_game')
def OnJoinGame(Data):
    print(f"Join game request: {Data}")
    UserId = Data.get('UserId')
    GameId = Data.get('GameId')
    
    if not all([UserId, GameId]):
        emit('game_state', {'Success': False, 'Message': 'MissingParameters'})
        return

    Room = f"GameRoom{GameId}"
    join_room(Room)

    Game = GameModel.GetGameById(GameId)
    if not Game:
        emit('game_state', {'Success': False, 'Message': 'GameNotFound'})
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

    print(f"Player {UserId} joined game {GameId}, both present: {both_present}")

# FixedOnMakeMoveToPreventDuplicateRankingCallsAndHandleCheckmateProperly
@SocketIo.on('make_move')
def OnMakeMove(Data):
    try:
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
            print(f"Checkmate! Winner: {winner}")  # Debug log
            
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
        print(f"Move error: {str(e)}")
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
        if InMemoryActiveGames[game_id].get('rankings_updated', False):
            return
        InMemoryActiveGames[game_id]['rankings_updated'] = True
        
    Room = f"GameRoom{game_id}"
    
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
        emit('ratings_updated', {
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
        emit('ratings_updated', {
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

# AddEndpointForLeaderboarding
@App.route("/api/top-players")
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
    GameId = data.get('GameId')
    UserId = data.get('UserId')
    Reason = data.get('Reason')
    Winner = data.get('Winner')
    Room = f"GameRoom{GameId}"

    # Get And Update Game State
    Game = GameModel.GetGameById(GameId)
    if Game and Game['Status'] != 'finished':
        # Don't Update Rankings If Already Done
        if GameId in InMemoryActiveGames and InMemoryActiveGames[GameId].get('rankings_updated', False):
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