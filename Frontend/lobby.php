<?php
// LobbyPhpShowsQuickMatchAndRooms
require_once 'Config.php';

// Check for maintenance mode
if (file_exists('maintenance')) {
    include 'maintenance.php';
    exit();
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>I8O8IChess Lobby</title>
    <script src="https://cdn.socket.io/4.7.5/socket.io.min.js"></script>
    <!-- External Styles (Use static/lobby.css As The Single Source For Lobby Styles) -->
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="static/game.css">
    <link rel="stylesheet" href="static/lobby.css">
</head>
<body class="retro">
    <div class="Container">
        <header class="LobbyHeader">
            <h1>I8O8IChess Lobby</h1>
            <div class="HeaderActions">
                <a class="btn btn-primary" href="release.php">Release Notes</a>
                <a class="btn btn-secondary" href="contact.php">Contact Dev</a>
            </div>
        </header>
    
        <!-- UserInfo Is Shown Inside The left Panel (Compact) -->
    <div class="MainSection">
            <div class="LeftPanel">
                <div class="UserInfo compact">
                    <div class="WelcomeRow">Welcome, <span id="LblUserName" class="UserName"></span>!</div>
                    <div class="Ratings">
                        <div class="RatingBox">
                            <div class="RatingLabel">Classical</div>
                            <div class="RatingValue"><span id="LblClassicalRating">1000</span></div>
                            <span class="PeakRating" id="LblClassicalPeak"></span>
                        </div>
                        <div class="RatingBox">
                            <div class="RatingLabel">Rapid</div>
                            <div class="RatingValue"><span id="LblRapidRating">1000</span></div>
                            <span class="PeakRating" id="LblRapidPeak"></span>
                        </div>
                        <div class="RatingBox">
                            <div class="RatingLabel">Blitz</div>
                            <div class="RatingValue"><span id="LblBlitzRating">100</span></div>
                            <span class="PeakRating" id="LblBlitzPeak"></span>
                        </div>
                    </div>
                </div>
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
              <div class="Label">Quick Wins (&lt;3min)</div>
              <div class="Value" id="QuickWins">-</div>
          </div>
          <div class="StatBox">
              <div class="Label">Total Moves</div>
              <div class="Value" id="TotalMoves">-</div>
          </div>
          <div class="StatBox">
              <div class="Label">Avg Game Length</div>
              <div class="Value" id="AvgGameLength">-</div>
          </div>
          <div class="StatBox">
              <div class="Label">Longest Game</div>
              <div class="Value" id="LongestGame">-</div>
          </div>
          <div class="StatBox">
              <div class="Label">Best Win Streak</div>
              <div class="Value" id="BestWinStreak">-</div>
          </div>
          <div class="StatBox">
              <div class="Label">Draws</div>
              <div class="Value" id="Draws">-</div>
          </div>
          <div class="StatBox">
              <div class="Label">Losses</div>
              <div class="Value" id="Losses">-</div>
          </div>
          <div class="StatBox">
              <div class="Label">Avg Time/Move</div>
              <div class="Value" id="AvgTimePerMove">-</div>
          </div>
          <div class="StatBox">
              <div class="Label">Games Abandoned</div>
              <div class="Value" id="GamesAbandoned">-</div>
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
            <div class="Achievement" id="QuickMaster">
                <div class="Icon">⚡️</div>
                <div class="Title">Quick Master</div>
                <div class="Description">Win 5 Games Under 3 Minutes</div>
            </div>
            <div class="Achievement" id="Marathonist">
                <div class="Icon">🏁</div>
                <div class="Title">Marathonist</div>
                <div class="Description">Play 50 Games</div>
            </div>
            <div class="Achievement" id="Mover">
                <div class="Icon">♞</div>
                <div class="Title">Mover</div>
                <div class="Description">Make 1000 Moves</div>
            </div>
            <div class="Achievement" id="Streak5">
                <div class="Icon">🔥</div>
                <div class="Title">Hot Streak</div>
                <div class="Description">Win 5 Games In A Row</div>
            </div>
            <div class="Achievement" id="ConsistentPlayer">
                <div class="Icon">📅</div>
                <div class="Title">Consistent Player</div>
                <div class="Description">Play 10 Games In One Week</div>
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
                        <th>Blitz</th>
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

<!-- Mobile responsive fixes for lobby <=475px -->
<style>
@media (max-width:475px){
  html, body { height:auto; min-height:100%; padding:8px; margin:0; overflow:auto }
  body.retro{ display:flex; align-items:center; justify-content:center }
  .Container{ width:100%; max-width:460px; padding:12px; margin:0 auto; box-sizing:border-box; border-radius:12px; max-height: calc(100vh - 32px); overflow:auto }
  .MainSection, .LeftPanel, .RightPanel{ width:100%; display:block }
  .UserInfo.compact{ text-align:center }
  .SectionHeader{ text-align:center }
  .Stats, .Achievements{ display:block }
  .HeaderActions{ justify-content:center }
  input, .btn, a{ width:100% !important }
  .popup-overlay{ align-items:flex-start; padding-top:18px }
  .popup{ max-width:94%; margin:0 auto; max-height: calc(100vh - 64px); overflow:auto }
}
</style>

<script>
let ApiBaseUrl = "<?php echo $AppConfig['ApiBaseUrl']; ?>";
const ApiBase = ApiBaseUrl + "/api";

function escapeHtml(text) {
    if (!text && text !== 0) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

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
            console.log('Cleanup Error :', error);
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
  // If Server Provided A JoinToken Include It In The URL To Allow Immediate Validation/Join
  const tokenPart = data.JoinToken ? ('&token=' + encodeURIComponent(data.JoinToken)) : '';
  window.location = 'game.php?gameId=' + data.GameId + tokenPart;
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
        status.innerHTML = `Searching For ${selectedType} Type Game Opponent ... <div class="loading"></div>`;

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
        console.error('QuickMatch Error :', error);
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
    try {
        const resp = await fetch(ApiBase + '/top-players?limit=5');
        const text = await resp.text();
        let json;
        try {
            json = JSON.parse(text);
        } catch (e) {
            console.error('Failed Parsing Leaderboard JSON', text);
            return;
        }
        if (json?.Success) {
            const tbody = document.getElementById('LeaderboardBody');
            tbody.innerHTML = json.Players.map((p, i) => {
                const classical = p.ClassicalRating || p.Rating || '-';
                const rapid = p.RapidRating || '-';
                const blitz = p.BlitzRating || p.Blitz || '-';
                return `
                <tr>
                    <td>${i + 1}</td>
                    <td>${escapeHtml(p.UserName)}</td>
                    <td>${classical}</td>
                    <td>${rapid}</td>
                    <td>${blitz}</td>
                </tr>
            `}).join('');
        }
    } catch (err) {
        console.error('Error Fetching Leaderboard', err);
    }
}

// FIXED UpdatePlayerStats Function For lobby.php (Extended With Blitz And Extra Stats)
async function updatePlayerStats() {
    try {
        const resp = await fetch(`${ApiBase}/user/${UserId}`);
        const json = await resp.json();
        if (json?.Success) {
            const updateElement = (id, value, suffix = '') => {
                const element = document.getElementById(id);
                if (element) element.innerText = (value === null || value === undefined) ? '-' : (value + suffix);
            };

            // Ratings
            updateElement('LblClassicalRating', json.ClassicalRating || json.Classical || 100);
            updateElement('LblRapidRating', json.RapidRating || json.Rapid || 100);
            updateElement('LblBlitzRating', json.BlitzRating || json.Blitz || 100);

            updateElement('LblClassicalPeak', (json.ClassicalPeak || json.ClassicalRating || json.Classical) ? `(Peak: ${json.ClassicalPeak || json.ClassicalRating || json.Classical})` : '');
            updateElement('LblRapidPeak', (json.RapidPeak || json.RapidRating || json.Rapid) ? `(Peak: ${json.RapidPeak || json.RapidRating || json.Rapid})` : '');
            const blitzPeakValue = json.BlitzPeak || json.BlitzRating || json.Blitz || 100;
            updateElement('LblBlitzPeak', `(Peak: ${blitzPeakValue})`);

            // General stats
            updateElement('GamesPlayed', json.GamesPlayed || 0);
            updateElement('WinRate', json.WinRate || 0, '%');
            updateElement('CheckmateWins', json.CheckmateWins || 0);
            updateElement('FastWins', json.FastWins || 0);
            updateElement('QuickWins', json.QuickWins || json.FastWinsUnder3 || 0);
            updateElement('TotalMoves', json.TotalMoves || 0);
            updateElement('AvgGameLength', Math.round(json.AvgGameLength || 0), 'm');
            updateElement('LongestGame', json.LongestGame || 0, 'm');
            updateElement('BestWinStreak', json.BestWinStreak || json.BestStreak || 0);

            // New Stats Added : Draws, Losses, AvgTimePerMove (seconds), GamesAbandoned
            updateElement('Draws', json.Draws || json.TotalDraws || 0);
            updateElement('Losses', json.Losses || json.TotalLosses || 0);
            // Avg Time Per Move May Be Provided In Seconds; Display In Seconds With One Decimal
            const avgMove = (json.AvgTimePerMove !== undefined && json.AvgTimePerMove !== null) ? json.AvgTimePerMove : (json.AvgMoveTime || 0);
            updateElement('AvgTimePerMove', (Math.round((avgMove || 0) * 10) / 10));
            updateElement('GamesAbandoned', json.GamesAbandoned || json.AbandonedGames || 0);

            // Peak Rating In Stats Section (Choose The Max Across Types)
            const classicalPeak = json.ClassicalPeak || json.ClassicalRating || json.Classical || 0;
            const rapidPeak = json.RapidPeak || json.RapidRating || json.Rapid || 0;
            const blitzPeak = json.BlitzPeak || json.BlitzRating || json.Blitz || 0;
            const currentPeak = Math.max(classicalPeak, rapidPeak, blitzPeak);
            updateElement('PeakRating', currentPeak);

            // Update Streak Separately Since It Needs Await
            const streak = await calculateStreak();
            updateElement('CurrentStreak', streak);

            // Store Updated Ratings In localStorage safely
            try { localStorage.setItem('I8O8IChessClassicalRating', (json.ClassicalRating || json.Classical || 100).toString()); } catch(e){}
            try { localStorage.setItem('I8O8IChessRapidRating', (json.RapidRating || json.Rapid || 100).toString()); } catch(e){}
            try { localStorage.setItem('I8O8IChessBlitzRating', (json.BlitzRating || json.Blitz || 100).toString()); } catch(e){}
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