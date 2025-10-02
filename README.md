# ♟️ I8O8IChessOnline - v00v

## ProjectOverview 🚀

Welcome To **I8O8IChessOnline** – A Modern, Professional Chess Platform For RealTime Multiplayer Games.  
Built With Php, Python (Flask/Socket.Io), And Mysql, It Delivers A Seamless, FeatureRich Chess Experience With A Beautiful Ui And Robust Backend.

---

## WhyChooseUs ❓

- 🌐 **GlobalPlay** : Connect And Play With Anyone, Anywhere.
- 🏅 **CompetitiveSpirit** : Climb The Leaderboard And Earn Achievements.
- 🎨 **ModernDesign** : Enjoy A Visually Stunning, Responsive Interface.
- 🔒 **SecureAccounts** : Your Data And Games Are Protected.

---

## KeyFeatures ✨

- **♟️ RealTimeMultiplayerChess** : Play Live Games With Instant Move Sync.
- **⏱️ MultipleTimeControls** : Classical (10 Min), Rapid (5 Min), Blitz (3 Min).
- **🏆 EloRatingSystem** : Dynamic Elo Ratings For Classical And Rapid/Blitz.
- **📊 LiveGameAnalysis** : See Win Probability And Position Analysis After Every Move.
- **🎉 AchievementSystem** : Unlock Achievements For Milestones (FirstWin, SpeedDemon, MasterRating, CheckmateKing, Veteran).
- **📈 RatingHistoryTracking** : View Your Rating Changes And Game History.
- **🥇 Leaderboard** : Compete For The Top Spot.
- **💬 GameChat** : Chat With Your Opponent During Games.
- **📱 ResponsiveDesign** : Optimized For Desktop And Mobile.

---

## TechnologiesUsed 🛠️

- **Frontend** : Php, Html5, Css3, Javascript (Chessboard.Js, Chess.Js, Socket.Io)
- **Backend** : Python (Flask, FlaskSocketio, Pymysql)
- **Database** : Mysql (`Backend/DbSchema.Sql`)
- **Other** : Xampp Or Any Php Server For Frontend Hosting

---

## SetupInstructions 📝

### 1. CloneRepository

```POWERSHELL
git clone https://github.com/YourUser/I8O8IChessOnline.git
cd ChessOnline
```
2. Import Database Schema (Using Xampp/phpMyAdmin Or Mysql Cli)
```powershell
# Using Mysql CommandLine (Adjust User/Password As Needed)
mysql -u root -p < .\Backend\DbSchema.sql
```
3. Install Python Dependencies
```powershell
python -m venv .venv
.\.venv\Scripts\Activate.ps1
pip install -r .\Backend\Requirements.txt
```
4. Configure App Settings
- Edit `Backend/Config.py` To Match Your Mysql Credentials And SocketIo Cors Origins
- Edit `Frontend/Config.php` To Set `ApiBaseUrl` To Your Backend Base Url (For Local Development: `http://localhost:5000`)
5. Run Backend Server
```powershell
# From Repository Root
python .\Backend\App.py
# The Backend Listens By Default On Host/Port From Backend/Config.py (Default: 0.0.0.0:5000)
```
6. Serve Frontend

- Copy Or Place The `Frontend` Folder Under Your Php Server DocumentRoot (For Xampp Typically: `C:\xampp\htdocs\ChessOnline`) And Open `http://localhost/ChessOnline/Frontend/index.php` In Your Browser.

## BackendConfiguration

- `Backend/Config.py` Contains: `DatabaseHost`, `DatabasePort`, `DatabaseUser`, `DatabasePassword`, `DatabaseName`, `SocketIoCorsOrigins`, `FlaskHost`, `FlaskPort`.
- Update Values Before Running The Backend.

## FrontendConfiguration

- `Frontend/Config.php` Contains `ApiBaseUrl` And Other FrontendSpecific Settings. Ensure `ApiBaseUrl` Points To The Running Backend (Example: `http://localhost:5000`).

## ApiEndpoints

The Backend Exposes RestApi Endpoints Under `/api`:
- `POST /api/register` — Register A User (Payload: `{"UserName","Password"}`)
- `POST /api/login` — Login And Retrieve User Info (Payload: `{"UserName","Password"}`)
- `POST /api/quickmatch` — Join QuickMatch Queue (Payload: `{"UserId","GameType"}`) Returns `GameId` And `JoinToken` When Matched
- `POST /api/cancel-match` — Cancel Matchmaking (Payload: `{"UserId"}`)
- `GET /debug/inmemory/game/<game_id>` — (LocalhostOnly) Inspect InMemory Game State For Debugging

Note: See `Backend/App.py` For Full Implementation Details And Additional HelperRoutes.

## SocketEvents

The Backend Uses Socket.IO For RealTime Notifications. Important Events:
- `connect` — Server Emits `connected` On Successful Connection
- `match_found` — Sent To Matched Players With `GameId`, `JoinToken`, `Fen`, `WhiteUserId`, `BlackUserId`
- `player_disconnected` — Notifies Room When A Player Disconnects

See `Backend/App.py` For Additional RoomAndGameEvents And How To Join Rooms Using The `JoinToken` Mechanism.

## FolderStructure

```
ChessOnline/
  Backend/         # Python Backend (Flask + SocketIO + Models)
    App.py
    ChessEngine.py
    Config.py
    Models.py
    EloUtils.py
    DbSchema.sql
    Requirements.txt
  Frontend/        # Php Frontend (Index, Lobby, Game, Static Assets)
    Config.php
    index.php
    lobby.php
    game.php
    static/
  Logs/
  ScreenShots/
  README.md
  LICENSE
```
## Screenshots

> <img src="ScreenShots/LOBBY.png" alt="LobbyScreenshot" style="max-width:100%;height:auto;" />
> *Lobby With Stats, Achievements, Leaderboard, And Quick Match Options.*

> <img src="ScreenShots/INDEX.png" alt="IndexScreenshot" style="max-width:100%;height:auto;" />
> *Index / Login Page With SignIn And CreateAccount Options.*

> <img src="ScreenShots/GAME.png" alt="GameScreenshot" style="max-width:100%;height:auto;" />
> *Live Game Board, Timers, Move History, Win Probability, And Chat.*

## DevelopmentNotes

- The Backend Maintains InMemory Structures For Matchmaking And ActiveGames. Persistent GameState Is Stored In The Database Via `Models.py`.
- ChessLogic Uses `python-chess` (See `Backend/ChessEngine.py`) For MoveValidation, SAN/UCi Conversion, And GameResult Detection.
- Timers Use A Cooperative EventLoop (Eventlet). The Timer Logic Is In `Backend/App.py` (Functions: `init_game_timer`, `update_game_timer`, `start_game_timer`).

## TestingAndDebugging

- Use The Provided Debug Endpoint `GET /debug/inmemory/game/<game_id>` From Localhost To Inspect InMemory State.
- Check Logs In `Logs/Backend.log` For Server Activity And Errors.

## Troubleshooting

- If Frontend Cannot Reach Backend: Confirm `Frontend/Config.php` `ApiBaseUrl` Matches Backend Address And That CORS Origins In `Backend/Config.py` Allow The Frontend Origin.
- If Mysql Connection Fails: Confirm Credentials In `Backend/Config.py` And That Mysql Is Running. Use `phpMyAdmin` (Xampp) To Inspect The Database And Tables.
- If SocketIO Fails To Connect In Browser: Ensure Backend Is Running And Accessible On The Host/Port; Check Browser Console For Errors; Confirm `socket.io` Client Version Is Compatible.

## Contributing

Contributions Are Welcome. Please Follow These Guidelines:
- Use PascalCase For SectionHeaders And Keep Code And Documentation Clear.
- Open Issues For Bugs Or FeatureRequests Before Large Work.
- Create PullRequests Against `main` With A Clear Description And Tests/SmokeChecks When Applicable.

## License

This Project Is Distributed Under The MIT License. See `LICENSE` For Details.

## Contact

For Support Or Questions Open An Issue On Github Or Use The Contact Information In The Repo Metadata.

---

RequirementsCoverage: The README Has Been Rewritten In PascalCase Headings And Covers ProjectOverview, Setup, Backend And Frontend Configuration, ApiEndpoints, SocketEvents, FolderStructure, Troubleshooting, Contribution, And License.
