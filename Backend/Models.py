# ModelsPyContainsDatabaseHelpersCompletelyFixedVersion
import pymysql
from Config import DatabaseHost, DatabasePort, DatabaseUser, DatabasePassword, DatabaseName

class DbHelper:
    # GetConnectionReturnsPooledConnectionLike
    @staticmethod
    def GetConnection():
        Conn = pymysql.connect(
            host=DatabaseHost,
            port=DatabasePort,
            user=DatabaseUser,
            password=DatabasePassword,
            db=DatabaseName,
            cursorclass=pymysql.cursors.DictCursor,
            autocommit=True
        )
        return Conn

class UserModel:
    # CreateUserCreatesNewUserWithPasswordHash
    @staticmethod
    def CreateUser(UserName, PasswordHash):
        """CreatesNewUserWithPasswordHash"""
        Conn = DbHelper.GetConnection()
        try:
            Sql = """
                INSERT INTO Users (
                    UserName, 
                    PasswordHash, 
                    ClassicalRating, 
                    RapidRating,
                    ClassicalPeak,
                    RapidPeak
                ) VALUES (%s, %s, 100, 100, 100, 100)
            """
            Conn.cursor().execute(Sql, (UserName, PasswordHash))
        finally:
            Conn.close()

    # GetUserByUserNameReturnsUserOrNone
    @staticmethod
    def GetUserByUserName(UserName):
        Conn = DbHelper.GetConnection()
        try:
            Sql = "SELECT * FROM Users WHERE UserName=%s"
            Cur = Conn.cursor()
            Cur.execute(Sql, (UserName,))
            Row = Cur.fetchone()
            return Row
        finally:
            Conn.close()

    # GetUserByIdReturnsUserOrNone
    @staticmethod
    def GetUserById(UserId):
        Conn = DbHelper.GetConnection()
        try:
            Sql = "SELECT * FROM Users WHERE UserId=%s"
            Cur = Conn.cursor()
            Cur.execute(Sql, (UserId,))
            Row = Cur.fetchone()
            return Row
        finally:
            Conn.close()

    @staticmethod
    def UpdateRatingsForType(UserId, NewRating, RatingType):
        """FixedUpdatesOnlyTheSpecifiedRatingTypeWithProperPeakTracking"""
        Conn = DbHelper.GetConnection()
        try:
            cursor = Conn.cursor()
            
            if RatingType == 'classical':
                # Get Current Peak
                cursor.execute("""
                    SELECT COALESCE(ClassicalPeak, 1000) as ClassicalPeak 
                    FROM Users WHERE UserId = %s
                """, (UserId,))
                result = cursor.fetchone()
                current_peak = result['ClassicalPeak'] if result else 1000
                new_peak = max(current_peak, NewRating)
                
                cursor.execute("""
                    UPDATE Users 
                    SET ClassicalRating = %s, ClassicalPeak = %s
                    WHERE UserId = %s
                """, (NewRating, new_peak, UserId))
                
            elif RatingType == 'rapid':
                # Get Current Peak
                cursor.execute("""
                    SELECT COALESCE(RapidPeak, 1000) as RapidPeak 
                    FROM Users WHERE UserId = %s
                """, (UserId,))
                result = cursor.fetchone()
                current_peak = result['RapidPeak'] if result else 1000
                new_peak = max(current_peak, NewRating)
                
                cursor.execute("""
                    UPDATE Users 
                    SET RapidRating = %s, RapidPeak = %s
                    WHERE UserId = %s
                """, (NewRating, new_peak, UserId))
        finally:
            Conn.close()

    @staticmethod
    def UpdateBothRatings(UserId, ClassicalElo, RapidElo):
        """FixedUpdatesBothClassicalAndRapidRatingsWithProperPeakTracking"""
        Conn = DbHelper.GetConnection()
        try:
            cursor = Conn.cursor()

            # First Get Current Peak Values
            cursor.execute("""
                SELECT COALESCE(ClassicalPeak, 1000) as ClassicalPeak, 
                       COALESCE(RapidPeak, 1000) as RapidPeak
                FROM Users WHERE UserId = %s
            """, (UserId,))
            current = cursor.fetchone()
            
            if current:
                new_classical_peak = max(current['ClassicalPeak'], ClassicalElo)
                new_rapid_peak = max(current['RapidPeak'], RapidElo)
            else:
                new_classical_peak = ClassicalElo
                new_rapid_peak = RapidElo

            # Update With Calculated Peaks
            sql = """
                UPDATE Users 
                SET ClassicalRating = %s,
                    ClassicalPeak = %s,
                    RapidRating = %s,
                    RapidPeak = %s
                WHERE UserId = %s
            """
            cursor.execute(sql, (
                ClassicalElo, new_classical_peak,
                RapidElo, new_rapid_peak,
                UserId
            ))
        finally:
            Conn.close()

    @staticmethod
    def UpdateGameStats(UserId, game_data):
        """FixedUpdatesUserStatsAfterGameCompletion"""
        Conn = DbHelper.GetConnection()
        try:
            cursor = Conn.cursor()

            # Get Current Win Streak for Proper Calculation
            cursor.execute("""
                SELECT COALESCE(WinStreak, 0) as WinStreak,
                       COALESCE(BestWinStreak, 0) as BestWinStreak
                FROM Users WHERE UserId = %s
            """, (UserId,))
            current = cursor.fetchone()
            
            if current:
                if game_data['won']:
                    new_win_streak = current['WinStreak'] + 1
                    new_best_streak = max(current['BestWinStreak'], new_win_streak)
                else:
                    new_win_streak = 0
                    new_best_streak = current['BestWinStreak']
            else:
                new_win_streak = 1 if game_data['won'] else 0
                new_best_streak = new_win_streak

            # FIXED: Properly Handle Checkmate Wins
            checkmate_increment = 1 if (game_data.get('checkmate', False) and game_data['won']) else 0
            
            sql = """
                UPDATE Users 
                SET GamesPlayed = COALESCE(GamesPlayed, 0) + 1,
                    GamesWon = COALESCE(GamesWon, 0) + %s,
                    GamesLost = COALESCE(GamesLost, 0) + %s,
                    GamesDraw = COALESCE(GamesDraw, 0) + %s,
                    TotalGameTime = COALESCE(TotalGameTime, 0) + %s,
                    TotalMoves = COALESCE(TotalMoves, 0) + %s,
                    LongestGame = GREATEST(COALESCE(LongestGame, 0), %s),
                    QuickWins = COALESCE(QuickWins, 0) + %s,
                    FastWins = COALESCE(FastWins, 0) + %s,
                    WinStreak = %s,
                    BestWinStreak = %s,
                    CheckmateWins = COALESCE(CheckmateWins, 0) + %s
                WHERE UserId = %s
            """
            cursor.execute(sql, (
                1 if game_data['won'] else 0,
                1 if game_data['result'] == 'loss' else 0,
                1 if game_data['result'] == 'draw' else 0,
                int(game_data['duration']),
                game_data['moves'],
                int(game_data['duration']),
                1 if game_data['won'] and game_data['duration'] < 5 else 0,
                1 if game_data['won'] and game_data['duration'] < 10 else 0,
                new_win_streak,
                new_best_streak,
                checkmate_increment,
                UserId
            ))
        finally:
            Conn.close()

    @staticmethod
    def GetPlayerStats(UserId):
        """GetsDetailedPlayerStatistics"""
        Conn = DbHelper.GetConnection()
        try:
            Sql = """
                SELECT 
                    u.UserId, 
                    u.UserName, 
                    COALESCE(u.ClassicalRating, 1000) as ClassicalRating,
                    COALESCE(u.RapidRating, 1000) as RapidRating,
                    COALESCE(u.ClassicalPeak, u.ClassicalRating, 1000) as ClassicalPeak,
                    COALESCE(u.RapidPeak, u.RapidRating, 1000) as RapidPeak,
                    COALESCE(u.GamesPlayed, 0) as GamesPlayed,
                    COALESCE(u.GamesWon, 0) as GamesWon,
                    COALESCE(u.GamesLost, 0) as GamesLost,
                    COALESCE(u.GamesDraw, 0) as GamesDraw,
                    COALESCE(u.QuickWins, 0) as QuickWins,
                    COALESCE(u.FastWins, 0) as FastWins,
                    COALESCE(u.CheckmateWins, 0) as CheckmateWins,
                    COALESCE(u.TotalGameTime, 0) as TotalGameTime,
                    COALESCE(u.LongestGame, 0) as LongestGame,
                    COALESCE(u.WinStreak, 0) as CurrentStreak,
                    COALESCE(u.BestWinStreak, 0) as BestStreak,
                    COALESCE(u.TotalMoves, 0) as TotalMoves,
                    CASE 
                        WHEN COALESCE(u.GamesPlayed, 0) = 0 THEN 0
                        ELSE ROUND(COALESCE(u.GamesWon, 0) * 100.0 / COALESCE(u.GamesPlayed, 1), 2)
                    END as WinRate,
                    CASE 
                        WHEN COALESCE(u.GamesPlayed, 0) = 0 THEN 0
                        ELSE ROUND(COALESCE(u.TotalGameTime, 0) / COALESCE(u.GamesPlayed, 1), 2)
                    END as AvgGameLength
                FROM Users u
                WHERE u.UserId = %s
            """
            Cur = Conn.cursor()
            Cur.execute(Sql, (UserId,))
            Row = Cur.fetchone()

            # Ensure All Fields Have Default Values If NULL
            if Row:
                for key in Row:
                    if Row[key] is None:
                        Row[key] = 0
                        
            return Row
        finally:
            Conn.close()

    @staticmethod
    def UpdateAchievements(UserId, game_data):
        """FixedUpdatesUserAchievementsBasedOnGamePerformance"""
        Conn = DbHelper.GetConnection()
        try:
            cursor = Conn.cursor()
            
            # Check For First Win
            if game_data.get('won', False):
                cursor.execute("UPDATE Users SET FirstWin = 1 WHERE UserId = %s AND FirstWin = 0", (UserId,))

            # Check For Speed Demon (Win Under 2 Minutes)
            if game_data.get('duration', 0) < 2 and game_data.get('won', False):
                cursor.execute("UPDATE Users SET SpeedDemon = 1 WHERE UserId = %s AND SpeedDemon = 0", (UserId,))

            # Check For Master rating (2000+ Rating)
            cursor.execute("""
                UPDATE Users 
                SET MasterRating = 1 
                WHERE UserId = %s 
                  AND (ClassicalRating >= 2000 OR RapidRating >= 2000)
                  AND MasterRating = 0
            """, (UserId,))

            # FIXED: Check For Checkmate King (5 Checkmate Wins)
            cursor.execute("""
                UPDATE Users 
                SET CheckmateKing = 1 
                WHERE UserId = %s 
                  AND COALESCE(CheckmateWins, 0) >= 5
                  AND CheckmateKing = 0
            """, (UserId,))

            # Check For Veteran status (100 games played)
            cursor.execute("""
                UPDATE Users 
                SET Veteran = 1 
                WHERE UserId = %s 
                  AND COALESCE(GamesPlayed, 0) >= 100
                  AND Veteran = 0
            """, (UserId,))
            
        finally:
            Conn.close()

    @staticmethod
    def GetTopPlayers(limit=10):
        Conn = DbHelper.GetConnection()
        try:
            Sql = """
                SELECT UserId, UserName, ClassicalRating, RapidRating 
                FROM Users 
                ORDER BY ClassicalRating DESC, RapidRating DESC 
                LIMIT %s
            """
            Cur = Conn.cursor()
            Cur.execute(Sql, (limit,))
            Rows = Cur.fetchall()
            return Rows
        finally:
            Conn.close()

    @staticmethod
    def RecordRatingChange(UserId, GameId, OldElo, NewElo, ChangeReason, OpponentId, OpponentRating, RatingType='classical'):
        """RecordsDetailedRatingChangesOnlyOnePerGameType"""
        Conn = DbHelper.GetConnection()
        try:
            Sql = """
                INSERT INTO RankingsHistory 
                (UserId, GameId, OldElo, NewElo, ChangeReason, OpponentId, OpponentRating, RatingType)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
            """
            Conn.cursor().execute(Sql, (
                UserId, GameId, OldElo, NewElo, ChangeReason,
                OpponentId, OpponentRating, RatingType
            ))
        finally:
            Conn.close()

    @staticmethod
    def GetRatingHistory(UserId):
        """GetsRatingChangeHistoryForAUser"""
        Conn = DbHelper.GetConnection()
        try:
            Sql = """
                SELECT 
                    GameId, 
                    OldElo, 
                    NewElo, 
                    ChangeReason, 
                    OpponentId, 
                    OpponentRating, 
                    RatingType,
                    ChangeAt
                FROM RankingsHistory 
                WHERE UserId = %s 
                ORDER BY ChangeAt DESC 
                LIMIT 20
            """
            Cur = Conn.cursor()
            Cur.execute(Sql, (UserId,))
            Rows = Cur.fetchall()
            return Rows
        finally:
            Conn.close()

    @staticmethod
    def GetAchievements(UserId):
        """GetsAllAchievementsForAUser"""
        Conn = DbHelper.GetConnection()
        try:
            cursor = Conn.cursor()
            cursor.execute("""
                SELECT 
                    COALESCE(FirstWin, 0) as FirstWin,
                    COALESCE(SpeedDemon, 0) as SpeedDemon,
                    COALESCE(MasterRating, 0) as MasterRating,
                    COALESCE(CheckmateKing, 0) as CheckmateKing,
                    COALESCE(Veteran, 0) as Veteran
                FROM Users 
                WHERE UserId = %s
            """, (UserId,))
            result = cursor.fetchone()
            if not result:
                return {
                    'FirstWin': 0,
                    'SpeedDemon': 0,
                    'MasterRating': 0,
                    'CheckmateKing': 0,
                    'Veteran': 0
                }
            return result
        finally:
            Conn.close()

class GameModel:
    # CreateGameCreatesANewGameRecordOriginalMethod
    @staticmethod
    def CreateGame(WhiteUserId, BlackUserId, Fen):
        Conn = DbHelper.GetConnection()
        try:
            with Conn.cursor() as cursor:
                sql = "INSERT INTO Games (WhiteUserId, BlackUserId, Fen, MoveHistory) VALUES (%s, %s, %s, %s)"
                cursor.execute(sql, (WhiteUserId, BlackUserId, Fen, "")) 
                GameId = cursor.lastrowid  
                return GameId
        finally:
            Conn.close()

    # CreateGameWithTypeCreatesGameWithSpecificTypeAndTimeControl
    @staticmethod
    def CreateGameWithType(WhiteUserId, BlackUserId, Fen, GameType='classical', TimeControl=600, Increment=0):
        """CreatesGameWithSpecificTypeAndTimeControl"""
        Conn = DbHelper.GetConnection()
        try:
            with Conn.cursor() as cursor:
                sql = """
                    INSERT INTO Games 
                    (WhiteUserId, BlackUserId, Fen, MoveHistory, GameType, TimeControl, Increment, JoinToken, AllowedUserIds, TokenUsedBy, TokenSentToSids) 
                    VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
                """
                # Initialize token fields as NULL by default; callers may provide a JoinToken and allowed ids
                cursor.execute(sql, (
                    WhiteUserId, BlackUserId, Fen, "", GameType, TimeControl, Increment, None, None, None, None
                ))
                GameId = cursor.lastrowid
                return GameId
        finally:
            Conn.close()

    # UpdateGameFenUpdatesFenAndMoveHistory
    @staticmethod
    def UpdateGameFen(GameId, Fen, MoveHistory, Result=None, Status=None):
        """UpdatesGameStateWithOptionalStatusForCheckmateTracking"""
        Conn = DbHelper.GetConnection()
        try:
            cursor = Conn.cursor()
            if Result and Status:
                sql = "UPDATE Games SET Fen=%s, MoveHistory=%s, Result=%s, Status=%s WHERE GameId=%s"
                cursor.execute(sql, (Fen, MoveHistory, Result, Status, GameId))
            elif Result:
                sql = "UPDATE Games SET Fen=%s, MoveHistory=%s, Result=%s WHERE GameId=%s"
                cursor.execute(sql, (Fen, MoveHistory, Result, GameId))
            else:
                sql = "UPDATE Games SET Fen=%s, MoveHistory=%s WHERE GameId=%s"
                cursor.execute(sql, (Fen, MoveHistory, GameId))
        finally:
            Conn.close()

    # GetGameByIdReturnsGame
    @staticmethod
    def GetGameById(GameId):
        Conn = DbHelper.GetConnection()
        try:
            Sql = "SELECT * FROM Games WHERE GameId=%s"
            Cur = Conn.cursor()
            Cur.execute(Sql, (GameId,))
            Row = Cur.fetchone()
            # Parse JSON Token Fields Into Python Structures For Safer Use
            if Row:
                try:
                    if 'AllowedUserIds' in Row and Row['AllowedUserIds']:
                        import json
                        Row['AllowedUserIds'] = [int(x) for x in json.loads(Row['AllowedUserIds'])]
                except Exception:
                    Row['AllowedUserIds'] = Row.get('AllowedUserIds') or []
                try:
                    if 'TokenUsedBy' in Row and Row['TokenUsedBy']:
                        import json
                        Row['TokenUsedBy'] = set(int(x) for x in json.loads(Row['TokenUsedBy']))
                except Exception:
                    Row['TokenUsedBy'] = set(Row.get('TokenUsedBy') or [])
                try:
                    if 'TokenSentToSids' in Row and Row['TokenSentToSids']:
                        import json
                        Row['TokenSentToSids'] = set(json.loads(Row['TokenSentToSids']))
                except Exception:
                    Row['TokenSentToSids'] = set(Row.get('TokenSentToSids') or [])
            return Row
        finally:
            Conn.close()

    @staticmethod
    def GetGameByJoinToken(JoinToken):
        """Return The Game Row (Parsed) Matching The Provided JoinToken Or None."""
        if not JoinToken:
            return None
        Conn = DbHelper.GetConnection()
        try:
            Sql = "SELECT * FROM Games WHERE JoinToken = %s LIMIT 1"
            Cur = Conn.cursor()
            Cur.execute(Sql, (JoinToken,))
            Row = Cur.fetchone()
            if not Row:
                return None
            # reuse GetGameById parsing logic by calling GetGameById with the id
            return GameModel.GetGameById(Row['GameId'])
        finally:
            Conn.close()
        
    @staticmethod
    def UpdateGameStatus(GameId, Status):
        """UpdatesTheGameStatus(EgOngoingFinished)"""
        Conn = DbHelper.GetConnection()
        try:
            Sql = "UPDATE Games SET Status=%s WHERE GameId=%s"
            Conn.cursor().execute(Sql, (Status, GameId))
        finally:
            Conn.close()

    @staticmethod
    def UpdateGameTokenFields(GameId, JoinToken=None, AllowedUserIds=None, TokenUsedBy=None, TokenSentToSids=None):
        """Store join-token related fields as JSON in the database."""
        import json
        Conn = DbHelper.GetConnection()
        try:
            cursor = Conn.cursor()
            sql = """
                UPDATE Games SET JoinToken=%s, AllowedUserIds=%s, TokenUsedBy=%s, TokenSentToSids=%s WHERE GameId=%s
            """
            allowed_json = json.dumps(AllowedUserIds) if AllowedUserIds is not None else None
            used_json = json.dumps(list(TokenUsedBy)) if TokenUsedBy is not None else None
            sids_json = json.dumps(TokenSentToSids) if TokenSentToSids is not None else None
            cursor.execute(sql, (JoinToken, allowed_json, used_json, sids_json, GameId))
        finally:
            Conn.close()

    @staticmethod
    def StoreAnalysis(GameId, Fen, Probability):
        """StoresPositionAnalysisInDatabase"""
        Conn = DbHelper.GetConnection()
        try:
            Sql = """
                INSERT INTO GameAnalysis 
                (GameId, Fen, WinProbability) 
                VALUES (%s, %s, %s)
            """
            Conn.cursor().execute(Sql, (GameId, Fen, Probability))
        finally:
            Conn.close()

    @staticmethod
    def AddChatMessage(GameId, FromUserId, MessageText):
        """StoresChatMessageInDatabase"""
        Conn = DbHelper.GetConnection()
        try:
            Sql = """
                INSERT INTO ChatMessages 
                (GameId, FromUserId, MessageText) 
                VALUES (%s, %s, %s)
            """
            Conn.cursor().execute(Sql, (GameId, FromUserId, MessageText))
        finally:
            Conn.close()