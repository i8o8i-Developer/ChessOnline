<?php
// LobbyPhpShowsQuickMatchAndRooms
require_once 'Config.php';
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>I8O8IChess Lobby</title>
  <script src="https://cdn.socket.io/4.7.5/socket.io.min.js"></script>
  <style>
    body { 
      font-family: 'Courier New', monospace;
      text-align: center; 
      background: #1a1a1a; 
      color: #fff;
      margin: 0;
      padding: 20px;
    }
    .Container { 
      width: 100%;
      max-width: 1000px;
      margin: 20px auto; 
      background: #2a2a2a; 
      padding: 20px;
      border-radius: 12px;
      box-sizing: border-box;
    }

    h1 {
      color: #4CAF50;
      text-shadow: 0 0 10px rgba(76, 175, 80, 0.3);
      margin-bottom: 30px;
    }
    .UserInfo {
      background: #333;
      padding: 18px 10px;
      border-radius: 8px;
      margin-bottom: 30px;
      font-size: 18px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      box-shadow: 0 2px 8px rgba(76,175,80,0.08);
      border: 1px solid #222;
    }
    .UserInfo .WelcomeRow {
      font-size: 1.3em;
      font-weight: bold;
      color: #4CAF50;
      margin-bottom: 10px;
      letter-spacing: 1px;
      text-shadow: 0 0 8px rgba(76,175,80,0.15);
    }
    .Ratings {
      display: flex;
      gap: 30px;
      margin-top: 8px;
      justify-content: center;
      align-items: stretch;
      flex-wrap: wrap;
    }
    .RatingBox {
      background: #222;
      padding: 14px 18px;
      border-radius: 8px;
      text-align: center;
      min-width: 120px;
      box-shadow: 0 2px 8px rgba(76,175,80,0.08);
      border: 1px solid #333;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }
    .RatingLabel {
      font-size: 15px;
      color: #4CAF50;
      margin-bottom: 6px;
      font-weight: 500;
      letter-spacing: 0.5px;
    }
    .RatingValue {
      font-size: 26px;
      color: #fff;
      font-weight: bold;
      margin-bottom: 2px;
      letter-spacing: 1px;
      word-break: break-word;
    }
    .PeakRating {
      font-size: 13px;
      color: #aaa;
      margin-left: 0;
      font-weight: 400;
      word-break: break-word;
    }
    button {
      padding: 15px 30px;
      font-size: 18px;
      background: #4CAF50;
      border: none;
      border-radius: 4px;
      color: white;
      cursor: pointer;
      font-family: 'Courier New', monospace;
      transition: all 0.3s;
      margin: 10px;
    }
    button:hover {
      background: #45a049;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(76, 175, 80, 0.3);
    }
    #MatchStatus {
      margin-top: 20px;
      padding: 15px;
      background: #333;
      border-radius: 4px;
      font-size: 18px;
      color: #4CAF50;
    }
    .Controls {
      margin-top: 30px;
      display: flex;
      justify-content: center;
      gap: 20px;
    }
    #BtnLogout {
      background: #f44336;
    }
    #BtnLogout:hover {
      background: #d32f2f;
      box-shadow: 0 5px 15px rgba(244, 67, 54, 0.3);
    }
    .loading {
      display: inline-block;
      width: 20px;
      height: 20px;
      border: 3px solid #4CAF50;
      border-radius: 50%;
      border-top-color: transparent;
      animation: spin 1s linear infinite;
      margin-left: 10px;
    }
    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    /* NEW: Game Type Selector Styles */
    .GameTypeSelector {
      margin: 15px 0;
      text-align: left;
    }

    .GameTypeLabel {
      display: block;
      padding: 8px 12px;
      margin: 5px 0;
      background: #444;
      border-radius: 4px;
      cursor: pointer;
      transition: background 0.3s;
      font-size: 16px;
    }

    .GameTypeLabel:hover {
      background: #555;
    }

    .GameTypeLabel input {
      margin-right: 8px;
    }

    .GameTypeLabel input:checked + span {
      color: #4CAF50;
      font-weight: bold;
    }

    /* Leaderboard Styles */
    .Leaderboard {
        background: #333;
        padding: 15px;
        border-radius: 4px;
        margin: 20px 0;
    }
    .LeaderboardTable {
        width: 100%;
        border-collapse: collapse;
        color: #fff;
    }
    .LeaderboardTable th, .LeaderboardTable td {
        padding: 8px;
        text-align: left;
        border-bottom: 1px solid #444;
    }
    .LeaderboardTable th {
        color: #4CAF50;
    }
    /* Rating History Styles */
    .RatingHistory {
        background: #333;
        padding: 15px;
        border-radius: 4px;
        margin: 20px 0;
    }
    .RatingType {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 0.8em;
        margin-right: 5px;
    }

    .RatingType.classical {
        background: #2d3b2d;
        color: #4CAF50;
    }

    .RatingType.rapid {
        background: #3b2d2d;
        color: #f44336;
    }
    /* Achievements Styles */
    .Achievements {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 10px;
        margin: 20px 0;
    }
    
    .Achievement {
        background: #333;
        padding: 15px 10px;
        border-radius: 4px;
        text-align: center;
        opacity: 0.4;
        transition: all 0.3s;
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-width: 0;
    }
    
    .Achievement.Unlocked {
        opacity: 1;
        background: #2d3b2d;
    }
    
    .Achievement .Icon {
        font-size: 32px;
        margin-bottom: 6px;
    }
    .Achievement .Title {
      font-size: 1em;
      font-weight: bold;
      color: #4CAF50;
      margin-bottom: 4px;
      letter-spacing: 0.5px;
      text-shadow: 0 0 6px rgba(76,175,80,0.12);
    }
    .Achievement .Description {
      font-size: 0.95em;
      color: #ccc;
      margin-bottom: 0;
      line-height: 1.2;
      word-break: break-word;
    }
    @media (max-width: 1200px) {
      .Achievements {
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
      }
    }
    @media (max-width: 900px) {
      .Achievements {
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
      }
      .Achievement .Icon {
        font-size: 24px;
      }
      .Achievement .Title {
        font-size: 0.95em;
      }
      .Achievement .Description {
        font-size: 0.9em;
      }
    }
    @media (max-width: 600px) {
      .Achievements {
        grid-template-columns: 1fr;
        gap: 6px;
      }
      .Achievement {
        padding: 8px 4px;
        font-size: 13px;
      }
      .Achievement .Icon {
        font-size: 18px;
        margin-bottom: 2px;
      }
      .Achievement .Title {
        font-size: 0.9em;
        margin-bottom: 2px;
      }
      .Achievement .Description {
        font-size: 0.85em;
      }
    }
    @media (max-width: 400px) {
      .Achievement {
        padding: 3px;
        font-size: 11px;
      }
      .Achievement .Icon {
        font-size: 14px;
      }
      .Achievement .Title {
        font-size: 0.8em;
      }
      .Achievement .Description {
        font-size: 0.75em;
      }
    }
    @media (max-width: 900px) {
      .RatingBox {
        padding: 10px 8px;
        min-width: 90px;
      }
      .RatingLabel {
        font-size: 13px;
      }
      .RatingValue {
        font-size: 18px;
      }
      .PeakRating {
        font-size: 11px;
      }
    }
    @media (max-width: 600px) {
      .Ratings {
        flex-direction: row;
        gap: 8px;
        align-items: center;
      }
      .RatingBox {
        padding: 8px 4px;
        min-width: 70px;
      }
      .RatingLabel {
        font-size: 12px;
      }
      .RatingValue {
        font-size: 15px;
      }
      .PeakRating {
        font-size: 10px;
      }
    }
    /* Stats Grid Fix */
    .Stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 20px;
      margin: 20px 0;
      padding: 0;
      background: transparent;
    }
    .StatBox {
      background: #222;
      padding: 18px 10px;
      border-radius: 8px;
      text-align: center;
      transition: box-shadow 0.3s;
      box-shadow: 0 2px 8px rgba(76,175,80,0.08);
      border: 1px solid #333;
      min-width: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }
    .StatBox .Label {
      font-size: 15px;
      color: #e5cc88ff;
      margin-bottom: 8px;
      font-weight: 500;
      letter-spacing: 0.5px;
    }
    .StatBox .Value {
      font-size: 28px;
      font-weight: bold;
      color: #4CAF50;
      margin: 0;
      letter-spacing: 1px;
    }
    .StatBox:hover {
      box-shadow: 0 4px 16px rgba(76,175,80,0.15);
      background: #263826;
      border-color: #4CAF50;
    }
    @media (max-width: 1200px) {
      .Stats {
        gap: 14px;
      }
    }
    @media (max-width: 900px) {
      .Stats {
        gap: 10px;
      }
    }
    @media (max-width: 600px) {
      .Stats {
        grid-template-columns: 1fr;
        gap: 6px;
      }
      .StatBox {
        padding: 10px 4px;
        font-size: 13px;
      }
      .StatBox .Label {
        font-size: 13px;
        margin-bottom: 4px;
      }
      .StatBox .Value {
        font-size: 18px;
      }
    }
  </style>
</head>
<body>
  <div class="Container">
    <h1>I8O8IChess Lobby</h1>
    
    <div class="UserInfo">
      <div class="WelcomeRow">
        Welcome, <span id="LblUserName" class="UserName"></span>!
      </div>
      <div class="Ratings">
        <div class="RatingBox">
            <div class="RatingLabel">Classical</div>
            <div class="RatingValue">
                <span id="LblClassicalRating">1000</span>
            </div>
            <span class="PeakRating" id="LblClassicalPeak"></span>
        </div>
        <div class="RatingBox">
            <div class="RatingLabel">Rapid</div>
            <div class="RatingValue">
                <span id="LblRapidRating">1000</span>
            </div>
            <span class="PeakRating" id="LblRapidPeak"></span>
        </div>
      </div>
    </div>

    <div class="MainSection">
      <div class="LeftPanel">
        <div class="SectionHeader">Your Statistics</div>
        <div class="Stats" id="PlayerStats">
          <div class="StatBox">
              <div class="Label">Games Played</div>
              <div class="Value" id="GamesPlayed">-</div>
          </div>
          <div class="StatBox">
              <div class="Label">Win Rate</div>
              <div class="Value" id="WinRate">-</div>
          </div>
          <div class="StatBox">
              <div class="Label">Peak Rating</div>
              <div class="Value" id="PeakRating">-</div>
          </div>
          <div class="StatBox">
              <div class="Label">Current Streak</div>
              <div class="Value" id="CurrentStreak">-</div>
          </div>
          <div class="StatBox">
              <div class="Label">Checkmate Wins</div>
              <div class="Value" id="CheckmateWins">-</div>
          </div>
          <div class="StatBox">
              <div class="Label">Fast Wins (&lt;10min)</div>
              <div class="Value" id="FastWins">-</div>
          </div>
          <div class="StatBox">
              <div class="Label">Avg Game Length</div>
              <div class="Value" id="AvgGameLength">-</div>
          </div>
          <div class="StatBox">
              <div class="Label">Longest Game</div>
              <div class="Value" id="LongestGame">-</div>
          </div>
        </div>

        <div class="SectionHeader">Achievements</div>
        <div class="Achievements" id="PlayerAchievements">
            <div class="Achievement" id="FirstWin">
                <div class="Icon">🏆</div>
                <div class="Title">First Victory</div>
                <div class="Description">Win Your First Game</div>
            </div>
            <div class="Achievement" id="SpeedDemon">
                <div class="Icon">⚡</div>
                <div class="Title">Speed Demon</div>
                <div class="Description">Win A Game In Under 2 Minutes</div>
            </div>
            <div class="Achievement" id="MasterRating">
                <div class="Icon">👑</div>
                <div class="Title">Chess Master</div>
                <div class="Description">Reach 2000 Rating</div>
            </div>
            <div class="Achievement" id="CheckmateKing">
                <div class="Icon">♔</div>
                <div class="Title">Checkmate King</div>
                <div class="Description">Win 5 Games By Checkmate</div>
            </div>
            <div class="Achievement" id="Veteran">
                <div class="Icon">🎮</div>
                <div class="Title">Veteran</div>
                <div class="Description">Play 100 Games</div>
            </div>
        </div>
      </div>

      <div class="RightPanel">
        <div>
          <div class="SectionHeader">Quick Match</div>
          <!-- NewGameTypeSelector -->
          <div class="GameTypeSelector">
            <label class="GameTypeLabel">
                <input type="radio" name="gameType" value="classical" checked>
                <span>Classical (10 Min)</span>
            </label>
            <label class="GameTypeLabel">
                <input type="radio" name="gameType" value="rapid">
                <span>Rapid (5 Min)</span>
            </label>
            <label class="GameTypeLabel">
                <input type="radio" name="gameType" value="blitz">
                <span>Blitz (3 Min)</span>
            </label>
          </div>
          <div class="Controls">
            <button id="BtnQuickMatch">Quick Match</button>
            <button id="BtnLogout" onclick="logout()">Logout</button>
          </div>
          <div id="MatchStatus"></div>
        </div>

        <div>
          <div class="SectionHeader">Leaderboard</div>
          <div class="Leaderboard">
            <table class="LeaderboardTable">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Player</th>
                        <th>Classical</th>
                        <th>Rapid</th>
                    </tr>
                </thead>
                <tbody id="LeaderboardBody">
                    <!-- Filled Dynamically -->
                </tbody>
            </table>
          </div>
        </div>

        <div>
          <div class="SectionHeader">Rating History</div>
          <div class="RatingHistory">
            <div id="RatingHistoryList"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

<script>
let ApiBaseUrl = "<?php echo $AppConfig['ApiBaseUrl']; ?>";
const ApiBase = ApiBaseUrl + "/api";

// StateManagement
const gameState = {
    searchController: null,
    intervalId: null,
    isSearching: false
};

// CleanupFunction
async function cleanupSearch() {
    if (gameState.searchController) {
        gameState.searchController.abort();
        gameState.searchController = null;
    }
    if (gameState.intervalId) {
        clearInterval(gameState.intervalId);
        gameState.intervalId = null;
    }
    if (gameState.isSearching) {
        try {
            await fetch(ApiBase + '/cancel-match', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({UserId: parseInt(UserId)}),
                keepalive: true
            });
        } catch (error) {
            console.log('Cleanup error:', error);
        }
        gameState.isSearching = false;
    }
}

const UserId = localStorage.getItem('I8O8IChessUserId');
const UserName = localStorage.getItem('I8O8IChessUserName');
document.getElementById('LblUserName').innerText = UserName || 'Guest';

const socket = io(ApiBaseUrl);

socket.on("connect", ()=> {
  if(UserId) socket.emit("register_user", { UserId: parseInt(UserId) });
});

socket.on("match_found", (data)=> {
  window.location = 'game.php?gameId=' + data.GameId;
});

function logout() {
  localStorage.removeItem('I8O8IChessUserId');
  localStorage.removeItem('I8O8IChessUserName');
  window.location = 'index.php';
}

// UPDATED: Quick Match With Game Type Selection
document.getElementById('BtnQuickMatch').onclick = async function() {
    this.disabled = true;
    const status = document.getElementById('MatchStatus');
    
    // Get Selected Game Type
    const selectedType = document.querySelector('input[name="gameType"]:checked').value;

    // Cleanup Any Existing Search
    await cleanupSearch();
    
    // Initialize New Search
    gameState.searchController = new AbortController();
    gameState.isSearching = true;
    
    try {
        status.innerHTML = `Searching For ${selectedType} Type Game Opponent... <div class="loading"></div>`;

        // Function To Check For Match
        const checkMatch = async () => {
            try {
                const resp = await fetch(ApiBase + '/quickmatch', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        UserId: parseInt(UserId),
                        GameType: selectedType  // Include Game Type
                    }),
                    signal: gameState.searchController.signal
                });
                
                const json = await resp.json();
                
                if (json.MatchCreated) {
                    cleanupSearch();
                    window.location = 'game.php?gameId=' + json.GameId;
                }
                return json;
            } catch (error) {
                if (error.name === 'AbortError') {
                    // Ignore Abort Errors
                    return { MatchCreated: false };
                }
                throw error;
            }
        };
        
        // Initial Check
        const initialCheck = await checkMatch();
        if (!initialCheck.MatchCreated) {
            // Start Polling If No Immediate Match
            let attempts = 0;
            gameState.intervalId = setInterval(async () => {
                attempts++;
                if (attempts > 30) { // 1 Minute Timeout (30 * 2 seconds)
                    cleanupSearch();
                    this.disabled = false;
                    status.innerHTML = 'No Opponent Found. Try Again.';
                    return;
                }
                await checkMatch();
            }, 2000);
        }
    } catch (error) {
        console.error('QuickMatch Error:', error);
        cleanupSearch();
        status.innerHTML = 'Error Finding Match. Please Try Again.';
        this.disabled = false;
    }
};

// Cleanup On Page Unload And Before Socket Disconnects
async function handlePageCleanup() {
    await cleanupSearch();
}

// Use Beforeunload To Ensure Cleanup Happens Before Page Navigation
window.addEventListener('beforeunload', handlePageCleanup);
socket.on('disconnect', handlePageCleanup);

// Initialize User Info
const userName = localStorage.getItem('I8O8IChessUserName');
document.getElementById('LblUserName').innerText = userName || 'Guest';

// Add This Function To Update Leaderboard
async function updateLeaderboard() {
    // Request Only Top 5 Players From Backend
    const resp = await fetch(ApiBase + '/top-players?limit=5');
    const json = await resp.json();
    if (json?.Success) {
        const tbody = document.getElementById('LeaderboardBody');
        tbody.innerHTML = json.Players.map((p, i) => `
            <tr>
                <td>${i + 1}</td>
                <td>${p.UserName}</td>
                <td>${p.ClassicalRating}</td>
                <td>${p.RapidRating}</td>
            </tr>
        `).join('');
    }
}

// FIXED UpdatePlayerStats Function For lobby.php
async function updatePlayerStats() {
    try {
        const resp = await fetch(`${ApiBase}/user/${UserId}`);
        const json = await resp.json();
        if (json?.Success) {
            // Update Player Stats With Null Checks And Proper Peak Rating Handling
            const updateElement = (id, value, suffix = '') => {
                const element = document.getElementById(id);
                if (element) element.innerText = value + suffix;
            };
            
            updateElement('GamesPlayed', json.GamesPlayed || 0);
            updateElement('WinRate', json.WinRate || 0, '%');
            updateElement('CheckmateWins', json.CheckmateWins || 0);
            updateElement('FastWins', json.FastWins || 0);
            updateElement('AvgGameLength', Math.round(json.AvgGameLength || 0), 'm');
            updateElement('LongestGame', json.LongestGame || 0, 'm');
            updateElement('LblClassicalRating', json.ClassicalRating || 100);
            updateElement('LblRapidRating', json.RapidRating || 100);

            // FIXED: Properly Display Peak Ratings
            const classicalPeak = json.ClassicalPeak || json.ClassicalRating || 100;
            const rapidPeak = json.RapidPeak || json.RapidRating || 100;
            updateElement('LblClassicalPeak', `(Peak: ${classicalPeak})`);
            updateElement('LblRapidPeak', `(Peak: ${rapidPeak})`);

            // Update Peak Rating In Stats Section
            const currentPeak = Math.max(classicalPeak, rapidPeak);
            updateElement('PeakRating', currentPeak);

            // Update Streak Separately Since It Needs Await
            const streak = await calculateStreak();
            updateElement('CurrentStreak', streak);

            // Store Updated Ratings In localStorage
            localStorage.setItem('I8O8IChessClassicalRating', json.ClassicalRating || 100);
            localStorage.setItem('I8O8IChessRapidRating', json.RapidRating || 100);
        }
    } catch (error) {
        console.error('Error Updating Stats :', error);
    }
}

async function calculateStreak() {
    try {
        const resp = await fetch(`${ApiBase}/user/${UserId}/ratings`);
        const json = await resp.json();
        if (!json?.Success) return 0;
        
        let streak = 0;
        const history = json.History;

        // Count Consecutive Wins Until First Non-win
        for (let i = 0; i < history.length; i++) {
            if (history[i].ChangeReason === 'win') streak++;
            else break;
        }
        return streak;
    } catch (error) {
        console.error('Error Calculating Streak :', error);
        return 0;
    }
}

async function updateAchievements() {
    try {
        const resp = await fetch(`${ApiBase}/user/${UserId}/achievements`);
        const json = await resp.json();
        if (json?.Success) {
            const achievements = json.Achievements;
            for (let [achievement, unlocked] of Object.entries(achievements)) {
                const element = document.getElementById(achievement);
                if (element) {
                    element.classList.toggle('Unlocked', unlocked);
                }
            }
        }
    } catch (error) {
        console.error('Error Updating Achievements :', error);
    }
}

// CACHED: Get Username By ID
async function getUserNameById(userId) {
    // Simple Cache To Avoid Repeated Requests
    if (!window._userNameCache) window._userNameCache = {};
    if (window._userNameCache[userId]) return window._userNameCache[userId];

    try {
        const resp = await fetch(`${ApiBase}/user/${userId}`);
        const json = await resp.json();
        if (json?.Success && json.UserName) {
            window._userNameCache[userId] = json.UserName;
            return json.UserName;
        }
    } catch (e) {}
    return `Player #${userId}`;
}

// Update The DOMContentLoaded Event Handler
document.addEventListener('DOMContentLoaded', async () => {
    // Set Username First
    document.getElementById('LblUserName').innerText = userName || 'Guest';
    
    try {
        await updatePlayerStats();
        await updateAchievements();
        updateLeaderboard();
        updateRatingHistory();
    } catch (error) {
        console.error('Error During Initialization :', error);
    }
    
    // Start Periodic Updates
    setInterval(() => {
        updatePlayerStats();
        updateAchievements();
        updateLeaderboard();
        updateRatingHistory();
    }, 30000);
});

async function updateRatingHistory() {
    try {
        const resp = await fetch(`${ApiBase}/user/${UserId}/ratings`);
        const json = await resp.json();
        if (json?.Success) {
            const list = document.getElementById('RatingHistoryList');
            // Build Entries with Opponent Usernames
            const entries = await Promise.all(json.History.map(async h => {
                const change = h.NewElo - h.OldElo;
                const changeClass = change > 0 ? 'RatingUp' : change < 0 ? 'RatingDown' : 'RatingDraw';
                const changeText = change > 0 ? `+${change}` : change;
                const ratingType = h.RatingType || 'classical';
                const oppName = await getUserNameById(h.OpponentId);
                return `
                    <div class="RatingEntry">
                        <span class="RatingType ${ratingType}">${ratingType}</span>
                        <span class="RatingChange ${changeClass}">${changeText}</span>
                        <span class="RatingValue">(${h.NewElo})</span>
                        vs ${oppName} (${h.OpponentRating})
                        <span class="RatingDate">${new Date(h.ChangeAt).toLocaleDateString()}</span>
                    </div>
                `;
            }));
            list.innerHTML = entries.join('');
        }
    } catch (error) {
        console.error('Error Updating Rating History :', error);
    }
}
</script>
</body>
</html>