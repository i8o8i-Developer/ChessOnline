-- FixedDbSchemaSqlForI8O8IChessCompleteVersionWithGameTypes
CREATE DATABASE IF NOT EXISTS I8O8IChessDb
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE I8O8IChessDb;

CREATE TABLE IF NOT EXISTS Users (
  UserId INT AUTO_INCREMENT PRIMARY KEY,
  UserName VARCHAR(64) UNIQUE NOT NULL,
  PasswordHash VARCHAR(255) NOT NULL,
  ClassicalRating INT NOT NULL DEFAULT 1000 CHECK (ClassicalRating >= 0),
  ClassicalPeak INT NOT NULL DEFAULT 1000 CHECK (ClassicalPeak >= 0),
  RapidRating INT NOT NULL DEFAULT 1000,
  RapidPeak INT NOT NULL DEFAULT 1000,
  GamesPlayed INT NOT NULL DEFAULT 0,
  GamesWon INT NOT NULL DEFAULT 0,
  GamesLost INT NOT NULL DEFAULT 0,
  GamesDraw INT NOT NULL DEFAULT 0,
  QuickWins INT NOT NULL DEFAULT 0,
  FastWins INT NOT NULL DEFAULT 0,
  TotalGameTime INT NOT NULL DEFAULT 0,
  LongestGame INT NOT NULL DEFAULT 0,
  WinStreak INT NOT NULL DEFAULT 0,
  BestWinStreak INT NOT NULL DEFAULT 0,
  TotalMoves INT NOT NULL DEFAULT 0,
  CheckmateWins INT NOT NULL DEFAULT 0,
  FirstWin TINYINT NOT NULL DEFAULT 0,
  SpeedDemon TINYINT NOT NULL DEFAULT 0,
  MasterRating TINYINT NOT NULL DEFAULT 0,
  CheckmateKing TINYINT NOT NULL DEFAULT 0,
  Veteran TINYINT NOT NULL DEFAULT 0,
  CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_username (UserName),
  INDEX idx_rating (ClassicalRating)
);

CREATE TABLE IF NOT EXISTS Games (
  GameId INT AUTO_INCREMENT PRIMARY KEY,
  WhiteUserId INT,
  BlackUserId INT,
  Fen VARCHAR(1024) NOT NULL,
  MoveHistory TEXT,
  -- Persistent Token Fields For Join-Token Workflows
  JoinToken VARCHAR(64) DEFAULT NULL,
  AllowedUserIds JSON DEFAULT NULL,
  TokenUsedBy JSON DEFAULT NULL,
  TokenSentToSids JSON DEFAULT NULL,
  Result ENUM('White', 'Black', 'Draw', 'Ongoing') DEFAULT 'Ongoing',
  Status ENUM('ongoing', 'finished', 'abandoned', 'timeout', 'checkmate', 'draw_offered') DEFAULT 'ongoing',
  GameType ENUM('classical', 'rapid', 'blitz') DEFAULT 'classical',
  TimeControl INT DEFAULT 600,
  Increment INT DEFAULT 0,
  CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (WhiteUserId) REFERENCES Users(UserId) ON DELETE SET NULL,
  FOREIGN KEY (BlackUserId) REFERENCES Users(UserId) ON DELETE SET NULL,
  INDEX idx_white_user (WhiteUserId),
  INDEX idx_black_user (BlackUserId),
  INDEX idx_created (CreatedAt),
  INDEX idx_status (Status),
  INDEX idx_game_type (GameType)
);

CREATE TABLE IF NOT EXISTS ChatMessages (
  ChatId INT AUTO_INCREMENT PRIMARY KEY,
  GameId INT NOT NULL,
  FromUserId INT,
  MessageText VARCHAR(1000) NOT NULL,
  SentAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (GameId) REFERENCES Games(GameId) ON DELETE CASCADE,
  FOREIGN KEY (FromUserId) REFERENCES Users(UserId) ON DELETE SET NULL,
  INDEX idx_game_time (GameId, SentAt)
);

CREATE TABLE IF NOT EXISTS RankingsHistory (
  RankingId INT AUTO_INCREMENT PRIMARY KEY,
  UserId INT NOT NULL,
  GameId INT NOT NULL,
  OldElo INT NOT NULL CHECK (OldElo >= 0),
  NewElo INT NOT NULL CHECK (NewElo >= 0),
  ChangeReason ENUM('win', 'loss', 'draw') NOT NULL,
  OpponentId INT,
  OpponentRating INT,
  ChangeAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  RatingType ENUM('classical', 'rapid') NOT NULL DEFAULT 'classical',
  CONSTRAINT fk_rankings_user FOREIGN KEY (UserId) REFERENCES Users(UserId) ON DELETE CASCADE,
  CONSTRAINT fk_rankings_game FOREIGN KEY (GameId) REFERENCES Games(GameId) ON DELETE CASCADE,
  CONSTRAINT fk_rankings_opponent FOREIGN KEY (OpponentId) REFERENCES Users(UserId) ON DELETE SET NULL,
  INDEX idx_user_change (UserId, ChangeAt),
  INDEX idx_rating_type (RatingType)
);

CREATE TABLE IF NOT EXISTS GameAnalysis (
    AnalysisId INT AUTO_INCREMENT PRIMARY KEY,
    GameId INT NOT NULL,
    Fen VARCHAR(100) NOT NULL,
    EvalScore FLOAT,
    WinProbability FLOAT,
    TimeStamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (GameId) REFERENCES Games(GameId)
);

-- UpdateExistingGamesToHaveProperGameTypeBasedOnDuration
UPDATE Games 
SET GameType = CASE 
    WHEN TIMESTAMPDIFF(MINUTE, CreatedAt, COALESCE(UpdatedAt, NOW())) <= 5 THEN 'rapid'
    WHEN TIMESTAMPDIFF(MINUTE, CreatedAt, COALESCE(UpdatedAt, NOW())) <= 15 THEN 'rapid'
    ELSE 'classical'
END
WHERE GameType IS NULL OR GameType = '';

-- CriticalFixesUpdateExistingDataAndEnsureConsistency
UPDATE Users 
SET ClassicalPeak = CASE 
        WHEN ClassicalPeak IS NULL OR ClassicalPeak < ClassicalRating 
        THEN ClassicalRating 
        ELSE ClassicalPeak 
    END,
    RapidPeak = CASE 
        WHEN RapidPeak IS NULL OR RapidPeak < RapidRating 
        THEN RapidRating 
        ELSE RapidPeak 
    END,
    CheckmateWins = COALESCE(CheckmateWins, 0),
    BestWinStreak = COALESCE(BestWinStreak, WinStreak, 0),
    FirstWin = COALESCE(FirstWin, 0),
    SpeedDemon = COALESCE(SpeedDemon, 0),
    MasterRating = COALESCE(MasterRating, 0),
    CheckmateKing = COALESCE(CheckmateKing, 0),
    Veteran = COALESCE(Veteran, 0)
WHERE 1=1;
