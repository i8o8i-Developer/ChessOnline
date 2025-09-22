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

```bash
Git Clone Https://Github.Com/Yourusername/I8O8IChessOnline.Git
```

### 2. DatabaseSetup

- Import The Schema:
  ```bash
  Mysql -U Root -P < Backend/DbSchema.Sql
  ```
- Update `Backend/Config.Py` With Your Database Credentials If Needed.

### 3. BackendSetup

- Install Python Dependencies:
  ```bash
  Pip Install Flask FlaskSocketio Eventlet Pymysql Chess
  ```
- Configure `Backend/Config.Py` For Your Database And Server Settings.
- Start The Backend Server:
  ```bash
  Python Backend/App.Py
  ```

### 4. FrontendSetup

- Configure `Frontend/Config.Php` With Your Api Base Url (For Production, Set Your Server Ip/Domain).
- Serve The `Frontend` Folder Using Xampp Or Any PhpCompatible Web Server.

### 5. AccessTheApp

- Open Your Browser And Go To:
  ```
  Http://Localhost/ChessOnline/Frontend/Index.Php
  ```
- Register A New Account Or Log In To Start Playing!

---

## Configuration ⚙️

- **ApiBaseUrl** : Set In `Frontend/Config.Php` For Frontend Api Calls.
- **SocketIoCorsOrigins** : Set In `Backend/Config.Py` For Allowed Cors Origins.
- **FlaskHost/FlaskPort** : Set In `Backend/Config.Py` For Backend Server Host/Port.

---

## FolderStructure 📂

```
ChessOnline/
├── Backend/
│   ├── App.Py
│   ├── Config.Py
│   ├── Models.Py
│   ├── ChessEngine.Py
│   ├── EloUtils.Py
│   ├── DbSchema.Sql
├── Frontend/
│   ├── Index.Php
│   ├── Lobby.Php
│   ├── Game.Php
│   ├── Config.Php
├── Readme.Md
├── License
├── .Gitignore
```

---

## Screenshots 🖼️

> <img src="ScreenShots\Lobby.png" alt="LobbyScreenshot" style="max-width:100%;height:auto;" />
> *Lobby With Stats, Achievements, Leaderboard, And Quick Match Options.*

> <img src="ScreenShots\Game.png" alt="GameScreenshot" style="max-width:100%;height:auto;" />
> *Live Game Board, Timers, Move History, Win Probability, And Chat.*

---

## License 📄

Distributed Under The MitLicense.  
See [License](./License) For Details.

---

## Contact 📬

For Support, Feature Requests, Or Bug Reports, Please Open An Issue On Github Or Contact The Author Via Email.

---

## Contributing 🤝

PullRequests Are Welcome!  
Please Follow PascalCase For Section Headers And Keep Code Clean And WellDocumented.

---

## SpecialThanks 🙏

- ChessboardJs & ChessJs For Board And Move Logic
- FlaskSocketio For RealTime Backend
- All Contributors And Testers

---
