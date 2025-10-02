# ♟️ I8O8IChessOnline

Modern Professional RealTime Chess Platform With A PHP Frontend, Python (Flask + Socket.IO) Backend, And MySQL Database.

## ProjectOverview

I8O8IChessOnline Is A PurposeBuilt RealTime Multiplayer Chess Platform Intended For Local Development, Testing, And Small Scale Deployment. The Project Combines A Lightweight PHP Frontend For The User Interface With A Python Backend That Handles Matchmaking, GameState, And RealTime Synchronization Via Socket.IO.

This Repository Contains Both Frontend And Backend Code, Database Schema, And Utility Modules For EloRating And ChessLogic.

## KeyFeatures

- RealTime Multiplayer With LowLatency Move Sync.
- Matchmaking Queue And QuickMatch Support.
- Multiple TimeControls (Classical, Rapid, Blitz).
- EloRating Calculation And RatingHistory Tracking.
- InGame Chat And Basic Achievement System.
- LiveGameAnalysis Hooks For Integrations With Engines.
- Local Debug Endpoints For Inspecting InMemory GameState.

## TechnologyStack

- Frontend: PHP, HTML5, CSS3, Javascript, Chessboard.js, Chess.js, Socket.IO Client.
- Backend: Python, Flask, Flask-SocketIO, Eventlet (Optional), PyMySQL, Python-Chess.
- Database: MySQL (DbSchema Provided In Backend/DbSchema.sql).
- DevTools: XAMPP For Local Frontend Hosting, Virtualenv For Python Backend.

## ArchitectureOverview

The Backend Maintains Two Primary Concerns:

1. Persistent Storage For Users, Games, And Ratings (MySQL).
2. InMemory Structures For ActiveGames, Timers, And Matchmaking Queues.

Socket.IO Is Used For RealTime Notifications. The Backend Emits MatchFound Events And GameUpdate Events. The Frontend Joins Rooms Using A JoinToken Provided By The Backend.

## SetupAndInstallation

Follow These Steps For A Local Development Environment On Windows. Commands Are Shown For PowerShell Where Applicable.

### Prerequisites

- Windows With Administrative Privileges (For XAMPP).
- XAMPP Or Equivalent PHP Server (For Frontend).
- Python 3.8+ Installed And On PATH.
- MySQL Server Or XAMPP MySQL Enabled.

### CloneRepository

```powershell
git clone https://github.com/YourUser/I8O8IChessOnline.git
cd ChessOnline
```

### DatabaseSetup

1. Create The Database And Import The Schema.

```powershell
# From Project Root
mysql -u root -p < .\Backend\DbSchema.sql
```

2. Verify Tables Exist Using PhpMyAdmin Or Mysql Client.

### BackendSetup

1. Create And Activate A Virtual Environment.

```powershell
python -m venv .venv
.\.venv\Scripts\Activate.ps1
pip install -r .\Backend\Requirements.txt
```

2. Review And Update `Backend/Config.py` To Match Your Environment.

- DatabaseHost
- DatabasePort
- DatabaseUser
- DatabasePassword
- DatabaseName
- SocketIoCorsOrigins
- FlaskHost
- FlaskPort

3. Run The Backend Server.

```powershell
# From Repository Root
python .\Backend\App.py
```

The Backend By Default Binds To The Host And Port Defined In `Backend/Config.py`.

### FrontendSetup

1. Copy Or Symlink The `Frontend` Folder Into Your Webserver DocumentRoot. For XAMPP Use The Following Path:

`C:\xampp\htdocs\ChessOnline`

2. Edit `Frontend/Config.php` And Set `ApiBaseUrl` To Your Backend Address (For Example `http://localhost:5000`).

3. Open The Frontend In A Browser, Example:

`http://localhost/ChessOnline/Frontend/index.php`

## ApiReference

The Backend Exposes A RESTful API Under The `/api` Prefix. The Most Common Endpoints Are Documented Below Along With Example PowerShell Requests.

### RegisterUser

- Endpoint: POST /api/register
- Payload: { "UserName": "Alice", "Password": "S3cret" }

```powershell
Invoke-RestMethod -Method Post -Uri "http://localhost:5000/api/register" -Body (@{UserName='Alice';Password='S3cret'} | ConvertTo-Json) -ContentType 'application/json'
```

### LoginUser

- Endpoint: POST /api/login
- Payload: { "UserName": "Alice", "Password": "S3cret" }

### QuickMatch

- Endpoint: POST /api/quickmatch
- Payload: { "UserId": 123, "GameType": "blitz" }

```powershell
Invoke-RestMethod -Method Post -Uri "http://localhost:5000/api/quickmatch" -Body (@{UserId=123;GameType='blitz'} | ConvertTo-Json) -ContentType 'application/json'
```

### CancelMatch

- Endpoint: POST /api/cancel-match
- Payload: { "UserId": 123 }

### DebugInMemoryGame

- Endpoint: GET /debug/inmemory/game/<GameId>
- Note: Endpoint Is Intended For Localhost Debugging Only.

## DetailedApiSchemas

The Following JSON Schemas Use PascalCase Keys To Match The Project Naming Style.

### UserRegisterRequest

```json
{
  "UserName": "Alice",
  "Password": "S3cret"
}
```

### UserRegisterResponse

```json
{
  "Success": true,
  "UserId": 42,
  "Message": "UserRegistered"
}
```

### QuickMatchRequest

```json
{
  "UserId": 42,
  "GameType": "Blitz",
  "PreferredTimeControl": "3+0"
}
```

### MatchFoundNotification

```json
{
  "GameId": "game_abc123",
  "JoinToken": "token_xyz",
  "Fen": "startfen",
  "WhiteUserId": 42,
  "BlackUserId": 99
}
```

### MoveEvent

```json
{
  "GameId": "game_abc123",
  "UciMove": "e2e4",
  "MoveSan": "e4",
  "NewFen": "fen_after_move",
  "MoverUserId": 42,
  "MoveNumber": 1,
  "RemainingTimeMs": 295000
}
```

## SocketIODetails

SocketIO Is Used For MatchNotifications And GameEvents. The Backend Emits Events And Accepts Client Connections That Join Game Rooms.

### ImportantEvents

- connected — Emitted By The Server After A Successful Socket Connection.
- matchFound — Emitted When The Matchmaker Pairs Players. Payload Includes GameId, JoinToken, Fen, WhiteUserId, BlackUserId.
- gameUpdate — Emitted When A Move Is Made. Payload Includes UciMove, NewFen, MoveSan, MoveNumber, RemainingTime.
- playerDisconnected — Emitted When A Player Drops Out Of A Game.

## SocketIOMessages

The Following Examples Use PascalCase Keys For All Message Fields.

### ExampleMatchFoundEvent

```json
{
  "Event": "MatchFound",
  "Payload": {
    "GameId": "game_abc123",
    "JoinToken": "token_xyz",
    "Fen": "startfen",
    "WhiteUserId": 42,
    "BlackUserId": 99
  }
}
```

### ExampleGameUpdateEvent

```json
{
  "Event": "GameUpdate",
  "Payload": {
    "GameId": "game_abc123",
    "UciMove": "e2e4",
    "MoveSan": "e4",
    "NewFen": "fen_after_move",
    "MoverUserId": 42,
    "MoveNumber": 1,
    "RemainingTimeMs": 295000
  }
}
```

## MatchmakingFlow

1. Player Requests QuickMatch Via POST /api/quickmatch.
2. Server Places Player In A Queue Indexed By GameType And Rating Bracket.
3. Server Attempts To Pair Players Periodically.
4. When A Pair Is Found, Server Creates An InMemory Game And Emits matchFound To Both Players.
5. Players Use The JoinToken To Join The SocketIO Room And Begin The Game.
6. Moves Are Exchanged Via Socket Events And Validated On The Server Using python-chess.
7. GameResult Is Calculated And EloRating Is Updated Persistently.

## GameStateModel

The InMemory GameState Contains The Following Conceptual Fields:

- GameId
- WhiteUserId
- BlackUserId
- CurrentFen
- MoveHistory
- Clocks (WhiteRemainingMs, BlackRemainingMs)
- GameStateStatus (Waiting, Active, Finished)
- JoinTokens

Persistent Representation Is Stored In The Database Via Models.py When Games End.

## DataModels

Example Data Models Use PascalCase For Field Names.

### UserModel

- UserId
- UserName
- Email
- HashedPassword
- CreatedAt
- LastActiveAt
- EloClassical
- EloRapid
- EloBlitz

### GameModel

- GameId
- WhiteUserId
- BlackUserId
- StartFen
- EndFen
- Result
- Moves
- StartedAt
- EndedAt

## LoggingAndMonitoring

- Backend Logs Are Appended To `Logs/Backend.log` By Default.
- Use The Log File To Debug Matchmaking, Exception Traces, And Timer Behavior.
- Add Additional Monitoring Or Instrumentation As Needed (Prometheus, Sentry, Etc.).

## DockerDeployment

A Minimal DockerCompose Example Is Included In `docker-compose.yml`. The Backend Also Includes A `Dockerfile` Under `Backend/` To Build The Service.

## Examples

### PowerShellApiExamples

```powershell
# Register
Invoke-RestMethod -Method Post -Uri "http://localhost:5000/api/register" -Body (@{UserName='Alice';Password='S3cret'} | ConvertTo-Json) -ContentType 'application/json'

# Login
Invoke-RestMethod -Method Post -Uri "http://localhost:5000/api/login" -Body (@{UserName='Alice';Password='S3cret'} | ConvertTo-Json) -ContentType 'application/json'

# QuickMatch
Invoke-RestMethod -Method Post -Uri "http://localhost:5000/api/quickmatch" -Body (@{UserId=42;GameType='Blitz'} | ConvertTo-Json) -ContentType 'application/json'
```

### SocketIOClientExample (Javascript)

```javascript
const socket = io(ApiBaseUrl, { transports: ['websocket'] });
socket.on('connect', () => {
  console.log('Connected');
});
socket.on('matchFound', (data) => {
  console.log('MatchFound', data);
});
socket.on('gameUpdate', (data) => {
  console.log('GameUpdate', data);
});
```

## SecurityNotes

- Do Not Commit Production Credentials To Source Control.
- Use Environment Variables For Secrets In Deployment.
- Consider Using HTTPS/TLS For All Client–Server Communication In Production.
- Validate And Sanitize All Inputs From The Frontend, Including Chess Moves And Chat Messages.

## TroubleshootingAndFaq

- FrontendCannotConnect: Verify `Frontend/Config.php` `ApiBaseUrl` Matches The Backend Address And That Socket Io Origins Are Allowed In `Backend/Config.py`.
- MysqlConnectionError: Confirm Mysql Is Running And Credentials Are Correct. Test With The Mysql Client Or PhpMyAdmin.
- SocketIoVersionMismatch: Ensure The Client And Server Socket Io Versions Are Compatible; Check Browser Console For Errors.
- PortInUse: If Port 5000 Is In Use, Change `FlaskPort` In `Backend/Config.py` And Update Frontend `ApiBaseUrl`.

## Screenshots

### Lobby

![Lobby Screenshot](ScreenShots\LOBBY.png)

### Index

![Index Screenshot](ScreenShots\INDEX.png)

### Game

![Game Screenshot](ScreenShots\GAME.png)

### Contact

![Contact Screenshot](ScreenShots\CONTACT.png)

### ReleaseNotes

![ReleaseNote Screenshot](ScreenShots\RELEASENOTE.png)

## Contributing

- OpenAnIssue For Bugs Or FeatureRequests Before Starting Significant Work.
- Fork The Repository And Open A Pull Request Against `main` When Changes Are Ready.
- Include Tests For New Or Changed Business Logic Where Practical.
- Maintain Clear And Focused Commits.

If You Would Like, A `CONTRIBUTING.md` File Is Provided In The Repository With Additional Guidance.

## License

This Project Is Licensed Under The MIT License. See The `LICENSE` File For Full Text.

## Contact

Open An Issue On GitHub For Support, Or Use The Repository's Issue Tracker To Report Bugs And Request Features.

---

## NextSteps

If You'd Like, I Can:

- Add A Dockerfile And Verified DockerCompose Setup.
- Add A CONTRIBUTING.md And CODE_OF_CONDUCT File.
- Add A Pytest Skeleton For `EloUtils.py` And A Basic CI Workflow (GitHub Actions).

Please Tell Me Which NextStep You Prefer And I Will Implement It.
