<?php
// GamePhp RealTimeChessWithChatCompleteFixedVersion
require_once 'Config.php';

$GameId = $_GET['gameId'] ?? 0;

// BasicValidation
if (!$GameId || $GameId <= 0) {
    header('Location: lobby.php');
    exit();
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>I8O8IChess Game #<?php echo $GameId; ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- jQuery and jQuery UI -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  
  <!-- Chessboard.Js Css -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/chessboard-js/1.0.0/chessboard-1.0.0.min.css" />

  <!-- Chessboard.Js Js -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/chessboard-js/1.0.0/chessboard-1.0.0.min.js"></script>

  <!-- Chess.js From Official Source -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/chess.js/0.10.2/chess.js"></script>

  <!-- Socket.IO -->
  <script src="https://cdn.socket.io/4.7.5/socket.io.min.js"></script>

  <style>
    * {
      box-sizing: border-box;
    }
    
    body { 
      font-family: 'Courier New', monospace;
      text-align: center; 
      background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
      color: #fff;
      margin: 0;
      padding: 10px;
      min-height: 100vh;
    }
    
    .Container { 
      max-width: 1200px; 
      margin: 0 auto; 
      background: rgba(42, 42, 42, 0.95);
      padding: 20px; 
      border: 2px solid #444;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.7);
      backdrop-filter: blur(10px);
    }

    h1 {
      color: #4CAF50;
      text-shadow: 0 0 20px rgba(76, 175, 80, 0.5);
      margin-bottom: 20px;
      font-size: 2.2em;
    }

    /* Players Info */
    .PlayersInfo {
      display: grid;
      grid-template-columns: 1fr auto 1fr;
      gap: 20px;
      align-items: center;
      margin: 10px 0 30px;
      padding: 15px;
      background: rgba(51, 51, 51, 0.8);
      border-radius: 8px;
      border: 1px solid #444;
    }
    
    .PlayerBox {
      padding: 15px;
      background: rgba(34, 34, 34, 0.8);
      border: 2px solid #444;
      border-radius: 8px;
      min-width: 180px;
      transition: all 0.3s ease;
    }
    
    .PlayerBox.Active {
      border-color: #4CAF50;
      box-shadow: 0 0 20px rgba(76, 175, 80, 0.3);
      background: rgba(76, 175, 80, 0.1);
    }
    
    .PlayerBox.Waiting {
      border-color: #666;
      background: rgba(102, 102, 102, 0.1);
      opacity: 0.8;
    }
    
    .PlayerName {
      font-weight: bold;
      margin-bottom: 8px;
      color: #fff;
    }
    
    .Timer {
      font-size: 28px;
      font-weight: bold;
      font-family: 'Digital-7', 'Courier New', monospace;
      color: #4CAF50;
      text-shadow: 0 0 10px rgba(76, 175, 80, 0.3);
      margin: 8px 0;
      letter-spacing: 2px;
    }
    
    .Timer.Warning { 
      color: #ffa000; 
      animation: pulse 1s infinite;
    }
    
    .Timer.Danger { 
      color: #f44336; 
      animation: urgent 0.5s infinite;
    }
    
    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.7; }
    }
    
    @keyframes urgent {
      0%, 100% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.8; transform: scale(1.05); }
    }
    
    /* Chess Board Container */
    #Board { 
      width: 100%;
      max-width: 600px;
      margin: 20px auto;
      border: 4px solid #444;
      border-radius: 8px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.6);
      overflow: hidden;
    }
    
    /* Board Squares Customization */
    .white-1e1d7 { 
      background-color: #e9edcc !important; 
      transition: background-color 0.2s;
    }
    .black-3c85d { 
      background-color: #779952 !important; 
      transition: background-color 0.2s;
    }
    .square-55d63 { 
      border: none !important; 
    }

    /* Highlight Possible Moves */
    .highlight-move {
      box-shadow: inset 0 0 20px rgba(255, 255, 0, 0.5) !important;
    }

    /* Game Status */
    #Status { 
      margin: 20px 0;
      padding: 15px;
      background: rgba(51, 51, 51, 0.8);
      border: 2px solid #444;
      border-radius: 8px;
      font-size: 18px;
      font-family: 'Courier New', monospace;
      color: #4CAF50;
      font-weight: bold;
      text-shadow: 0 0 10px rgba(76, 175, 80, 0.3);
    }

    /* Game Info Layout */
    .GameInfo {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin: 20px 0;
    }

    /* Move History Styles */
    .MoveHistory {
      background: rgba(51, 51, 51, 0.8);
      padding: 20px;
      border-radius: 8px;
      border: 1px solid #444;
      height: 280px;
      overflow: hidden;
    }
    
    .MoveHistory h3 {
      color: #4CAF50;
      margin-bottom: 15px;
      font-size: 18px;
      text-shadow: 0 0 10px rgba(76, 175, 80, 0.3);
    }
    
    .MoveList {
      height: 220px;
      overflow-y: auto;
      padding-right: 10px;
    }
    
    .MoveList::-webkit-scrollbar {
      width: 8px;
    }
    
    .MoveList::-webkit-scrollbar-track {
      background: rgba(0,0,0,0.2);
      border-radius: 4px;
    }
    
    .MoveList::-webkit-scrollbar-thumb {
      background: #4CAF50;
      border-radius: 4px;
    }
    
    .MoveRow {
      display: grid;
      grid-template-columns: 40px 1fr 1fr;
      gap: 10px;
      align-items: center;
      margin-bottom: 5px;
    }
    
    .MoveNumber {
      color: #666;
      text-align: right;
      font-weight: bold;
      font-size: 14px;
    }
    
    .Move {
      padding: 4px 8px;
      border-radius: 4px;
      min-height: 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      transition: background-color 0.2s;
      cursor: pointer;
    }
    
    .WhiteMove { 
      color: #fff; 
      background: rgba(255,255,255,0.1);
    }
    
    .BlackMove { 
      color: #ddd; 
      background: rgba(255,255,255,0.05);
    }
    
    .Move:hover {
      background: rgba(76, 175, 80, 0.2) !important;
    }
    
    .MoveText {
      font-weight: bold;
    }
    
    .PlayerName {
      font-size: 11px;
      opacity: 0.7;
    }

    /* Win Probability Styles */
    .WinProbability {
      background: rgba(51, 51, 51, 0.8);
      padding: 20px;
      border-radius: 8px;
      border: 1px solid #444;
      text-align: center;
    }
    
    .WinProbability h3 {
      color: #4CAF50;
      margin-bottom: 15px;
      font-size: 18px;
      text-shadow: 0 0 10px rgba(76, 175, 80, 0.3);
    }
    
    .ProbBar {
      width: 100%;
      height: 24px;
      background: rgba(34, 34, 34, 0.8);
      border-radius: 12px;
      overflow: hidden;
      border: 2px solid #444;
      margin: 15px 0;
      position: relative;
    }

    .ProbFill {
      height: 100%;
      background: linear-gradient(90deg, #4CAF50, #45a049);
      width: 50%;
      transition: width 0.8s ease-in-out;
      border-radius: 8px;
    }
    
    #ProbText {
      color: #4CAF50;
      font-weight: bold;
      margin-top: 10px;
      font-size: 16px;
      text-shadow: 0 0 10px rgba(76, 175, 80, 0.3);
    }

    /* Game Controls */
    .GameControls {
      margin: 20px 0;
      padding: 15px;
      background: rgba(51, 51, 51, 0.8);
      border-radius: 8px;
      border: 1px solid #444;
      display: flex;
      justify-content: center !important;   /* Ensure horizontal centering */
      align-items: center !important;       /* Ensure vertical centering */
      gap: 15px;
      flex-wrap: wrap;
      width: 100%;
      box-sizing: border-box;
    }
    
    .MainGrid .LeftCol .GameControls {
      margin-left: auto;
      margin-right: auto;
      width: max-content;
      min-width: 340px;
      max-width: 100%;
    }
    
    .GameControls button {
      padding: 10px 20px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-family: 'Courier New', monospace;
      font-weight: bold;
      font-size: 14px;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }
    
    .GameControls button::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
      transition: left 0.5s;
    }
    
    .GameControls button:hover::before {
      left: 100%;
    }
    
    .BtnDraw { 
      background: linear-gradient(45deg, #ffa000, #ff8f00);
      color: white;
      box-shadow: 0 4px 15px rgba(255, 160, 0, 0.3);
    }
    
    .BtnDraw:hover { 
      background: linear-gradient(45deg, #ff8f00, #ffa000);
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(255, 160, 0, 0.4);
    }
    
    .BtnResign { 
      background: linear-gradient(45deg, #f44336, #d32f2f);
      color: white;
      box-shadow: 0 4px 15px rgba(244, 67, 54, 0.3);
    }
    
    .BtnResign:hover { 
      background: linear-gradient(45deg, #d32f2f, #f44336);
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(244, 67, 54, 0.4);
    }
    
    .BtnExit { 
      background: linear-gradient(45deg, #666, #555);
      color: white;
      box-shadow: 0 4px 15px rgba(102, 102, 102, 0.3);
    }
    
    .BtnExit:hover { 
      background: linear-gradient(45deg, #555, #666);
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(102, 102, 102, 0.4);
    }

    /* Chat Area */
    #ChatArea { 
      margin-top: 30px;
      text-align: left;
      background: rgba(51, 51, 51, 0.8);
      padding: 20px;
      border: 1px solid #444;
      border-radius: 8px;
    }
    
    #ChatArea h3 {
      color: #4CAF50;
      margin-bottom: 15px;
      text-shadow: 0 0 10px rgba(76, 175, 80, 0.3);
    }
    
    #ChatList { 
      height: 150px; 
      overflow-y: auto; 
      padding: 15px;
      background: rgba(34, 34, 34, 0.8);
      border: 1px solid #444;
      border-radius: 6px;
      margin-bottom: 15px;
      font-family: 'Courier New', monospace;
      font-size: 14px;
    }
    
    #ChatList::-webkit-scrollbar {
      width: 8px;
    }
    
    #ChatList::-webkit-scrollbar-track {
      background: rgba(0,0,0,0.2);
      border-radius: 4px;
    }
    
    #ChatList::-webkit-scrollbar-thumb {
      background: #4CAF50;
      border-radius: 4px;
    }
    
    .ChatInputContainer {
      display: flex;
      gap: 10px;
    }
    
    #ChatInput { 
      flex: 1;
      padding: 12px;
      background: rgba(34, 34, 34, 0.8);
      border: 2px solid #444;
      border-radius: 6px;
      color: #fff;
      font-family: 'Courier New', monospace;
      font-size: 14px;
      transition: border-color 0.3s;
    }
    
    #ChatInput:focus {
      border-color: #4CAF50;
      outline: none;
      box-shadow: 0 0 10px rgba(76, 175, 80, 0.3);
    }
    
    #BtnSendChat { 
      padding: 12px 20px;
      background: linear-gradient(45deg, #4CAF50, #45a049);
      border: none;
      border-radius: 6px;
      color: white;
      cursor: pointer;
      font-family: 'Courier New', monospace;
      font-weight: bold;
      transition: all 0.3s ease;
    }
    
    #BtnSendChat:hover { 
      background: linear-gradient(45deg, #45a049, #4CAF50);
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
    }

    /* Chat Message Styles */
    .chat-message {
      margin: 8px 0;
      padding: 8px 12px;
      border-radius: 6px;
      word-wrap: break-word;
      transition: background-color 0.2s;
    }
    
    .chat-message.own {
      background: rgba(76, 175, 80, 0.2);
      color: #4CAF50;
      border-left: 3px solid #4CAF50;
    }
    
    .chat-message.opponent {
      background: rgba(255, 255, 255, 0.1);
      color: #fff;
      border-left: 3px solid #666;
    }
    
    .chat-message .sender {
      font-weight: bold;
      margin-right: 8px;
    }

    /* Piece Styling */
    .piece-417db {
      cursor: grab;
      transition: all 0.2s ease;
    }
    
    .piece-417db:hover {
      transform: scale(1.1);
      filter: drop-shadow(0 0 10px rgba(255,255,255,0.5));
    }
    
    .piece-417db:active {
      cursor: grabbing;
    }

    /* Popup Styling */
    .popup-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.85);
      z-index: 1000;
      backdrop-filter: blur(5px);
    }

    .popup {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background: linear-gradient(135deg, #2a2a2a 0%, #3a3a3a 100%);
      padding: 30px;
      border: 2px solid #444;
      border-radius: 12px;
      box-shadow: 0 20px 40px rgba(0,0,0,0.8);
      z-index: 1001;
      min-width: 350px;
      max-width: 90vw;
      max-height: 90vh;
      overflow-y: auto;
    }

    .popup-title {
      font-size: 24px;
      color: #4CAF50;
      margin-bottom: 20px;
      text-shadow: 0 0 10px rgba(76, 175, 80, 0.3);
      text-align: center;
    }

    .popup-message {
      font-size: 16px;
      color: #fff;
      margin-bottom: 25px;
      white-space: pre-line;
      line-height: 1.6;
      text-align: center;
    }

    .popup-buttons {
      display: flex;
      gap: 15px;
      justify-content: center;
      flex-wrap: wrap;
    }

    .popup-button {
      padding: 12px 24px;
      background: linear-gradient(45deg, #4CAF50, #45a049);
      border: none;
      border-radius: 6px;
      color: white;
      cursor: pointer;
      font-family: 'Courier New', monospace;
      font-weight: bold;
      font-size: 14px;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .popup-button:hover {
      background: linear-gradient(45deg, #45a049, #4CAF50);
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
    }

    .popup-button.secondary {
      background: linear-gradient(45deg, #666, #555);
    }

    .popup-button.secondary:hover {
      background: linear-gradient(45deg, #555, #666);
      box-shadow: 0 6px 20px rgba(102, 102, 102, 0.4);
    }

    /* Loading States */
    .loading {
      display: inline-block;
      width: 20px;
      height: 20px;
      border: 3px solid rgba(255, 255, 255, 0.3);
      border-radius: 50%;
      border-top-color: #4CAF50;
      animation: spin 1s linear infinite;
      margin-left: 10px;
    }
    
    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    /* Connection Status */
    .connection-status {
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 10px 15px;
      border-radius: 6px;
      font-size: 12px;
      font-weight: bold;
      z-index: 999;
      transition: all 0.3s ease;
    }
    
    .connection-status.connected {
      background: rgba(76, 175, 80, 0.9);
      color: white;
    }
    
    .connection-status.disconnected {
      background: rgba(244, 67, 54, 0.9);
      color: white;
    }

    /* === NEW LAYOUT FOR >1200px === */
    @media (min-width: 1200px) {
      .Container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 15px 40px;
      }
      .MainGrid {
        display: grid;
        grid-template-columns: 1.1fr 0.9fr;
        gap: 32px;
        align-items: flex-start;
      }
      .LeftCol {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 24px;
      }
      .RightCol {
        display: flex;
        flex-direction: column;
        gap: 24px;
      }
      #Board {
        margin: 0;
        width: 480px;
        max-width: 100%;
      }
      .GameControls {
        width: 100%;
        justify-content: flex-start;
        margin: 0;
      }
      .PlayersInfo {
        margin-bottom: 0;
        margin-top: 0;
        width: 100%;
      }
      .GameInfo {
        display: flex;
        flex-direction: column;
        gap: 24px;
        margin: 0;
      }
      .MoveHistory, .WinProbability, #ChatArea {
        width: 100%;
        min-width: 340px;
        max-width: 420px;
        margin: 0 auto;
      }
      #ChatArea {
        margin-top: 0;
      }
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .Container {
        margin: 5px;
        padding: 15px;
      }
      
      .PlayersInfo {
        grid-template-columns: 1fr;
        gap: 15px;
        text-align: center;
      }
      
      #Status {
        order: 2;
      }
      
      .GameInfo {
        grid-template-columns: 1fr;
        gap: 15px;
      }
      
      .GameControls {
        flex-direction: column;
        align-items: stretch;
      }
      
      .GameControls button {
        width: 100%;
        margin: 5px 0;
      }
      
      #Board {
        width: 100%;
        max-width: 400px;
      }
      
      .Timer {
        font-size: 24px;
      }
      
      h1 {
        font-size: 1.8em;
      }
      
      .ChatInputContainer {
        flex-direction: column;
      }
      
      #BtnSendChat {
        width: 100%;
      }
    }

    @media (max-width: 480px) {
      .Container {
        margin: 2px;
        padding: 10px;
      }
      
      .Timer {
        font-size: 20px;
      }
      
      .popup {
        margin: 20px;
        padding: 20px;
        min-width: unset;
      }
      
      .popup-buttons {
        flex-direction: column;
      }
      
      .popup-button {
        width: 100%;
      }
    }
  </style>
</head>
<body>
<div class="connection-status" id="connectionStatus" style="display: none;">
  Connected
</div>

<div class="Container">
  <h1>I8O8IChess Game #<?php echo $GameId; ?></h1>
  <div class="MainGrid">
    <div class="LeftCol">
      <div class="PlayersInfo">
        <div id="OpponentBox" class="PlayerBox">
          <div class="PlayerName" id="OpponentName">Waiting for opponent...</div>
          <div class="Timer" id="OpponentTimer">10:00</div>
        </div>
        <div id="Status">Connecting to game...</div>
        <div id="PlayerBox" class="PlayerBox Active">
          <div class="PlayerName" id="PlayerName">You</div>
          <div class="Timer" id="PlayerTimer">10:00</div>
        </div>
      </div>
      <!-- Ensure the board container is present and not hidden -->
      <div id="Board"></div>
      <div class="GameControls">
        <button class="BtnDraw" onclick="offerDraw()">Offer Draw</button>
        <button class="BtnResign" onclick="resignGame()">Resign</button>
        <button class="BtnExit" onclick="exitGame()">Exit Game</button>
      </div>
    </div>
    <div class="RightCol">
      <div class="GameInfo">
        <div class="MoveHistory">
          <h3>Move History</h3>
          <div class="MoveList" id="MoveList">
            <!-- Moves Will Be Added Here Dynamically -->
          </div>
        </div>
        <div class="WinProbability">
          <h3>Win Probability</h3>
          <div class="ProbBar">
            <div class="ProbFill" id="ProbFill"></div>
          </div>
          <div id="ProbText">50% win probability</div>
        </div>
      </div>
      <div id="ChatArea">
        <h3>Game Chat</h3>
        <div id="ChatList"></div>
        <div class="ChatInputContainer">
          <input id="ChatInput" placeholder="Type a message..." maxlength="200" />
          <button id="BtnSendChat">Send</button>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="popup-overlay" id="popupOverlay">
  <div class="popup">
    <div class="popup-title" id="popupTitle"></div>
    <div class="popup-message" id="popupMessage"></div>
    <div class="popup-buttons">
      <button class="popup-button" id="popupPrimary"></button>
      <button class="popup-button secondary" id="popupSecondary"></button>
    </div>
  </div>
</div>

<script>
// FixedGamePhpJavaScriptTimerAndCheckmateIssuesResolved
// CheckAuthentication
if (!localStorage.getItem('I8O8IChessUserId')) {
    alert('Please Log In First');
    window.location.href = 'index.php';
}

// GameConfiguration
const GameId = parseInt(window.location.href.match(/gameId=(\d+)/)?.[1] || 0);
const UserId = parseInt(localStorage.getItem('I8O8IChessUserId'));
const UserName = localStorage.getItem('I8O8IChessUserName') || 'Player';

// ValidateRequiredData
if (!GameId || GameId <= 0 || !UserId) {
    alert('Invalid Game Data');
    window.location.href = 'lobby.php';
}

// DOM Elements
const StatusEl = document.getElementById('Status');
const ChatList = document.getElementById('ChatList');
const ChatInput = document.getElementById('ChatInput');
const BtnSendChat = document.getElementById('BtnSendChat');
const connectionStatus = document.getElementById('connectionStatus');

// Game State
let chess;
let board = null;
let playerColor = null;
let gameInitialized = false;
let reconnectAttempts = 0;
const maxReconnectAttempts = 5;

// Initialize Chess Engine
try {
    chess = new Chess();
    console.log('Chess Engine Initialized Successfully');
} catch (error) {
    console.error('Chess Initialization Error:', error);
    StatusEl.innerText = 'Error Initializing Chess Game. Please Refresh The Page.';
}

const ApiBaseUrl = "<?php echo $AppConfig['ApiBaseUrl']; ?>";

// Socket.IO Connection
const socket = io(ApiBaseUrl, {
    timeout: 10000,
    forceNew: true,
    reconnection: true,
    reconnectionDelay: 2000,
    reconnectionAttempts: maxReconnectAttempts
});

// FIXED Timer Management - Improved Synchronization
const timers = {
    white: 600,
    black: 600,
    activeInterval: null,
    currentTurn: 'white',
    lastServerSync: Date.now(),
    isActive: false,
    gameStarted: false
};

function updateConnectionStatus(connected) {
    if (connected) {
        connectionStatus.textContent = 'Connected';
        connectionStatus.className = 'connection-status connected';
        connectionStatus.style.display = 'block';
        setTimeout(() => {
            connectionStatus.style.display = 'none';
        }, 3000);
    } else {
        connectionStatus.textContent = 'Disconnected';
        connectionStatus.className = 'connection-status disconnected';
        connectionStatus.style.display = 'block';
    }
}

function initBoard() {
    if (!chess || !playerColor) {
        console.log('Cannot Initialize Board - Missing Chess Engine or Player Color');
        return;
    }

    console.log('Initializing Board with Color:', playerColor);

    if (board) {
        board.destroy();
    }
    
    const config = {
        draggable: true,
        position: chess.fen(),
        orientation: playerColor,
        pieceTheme: 'https://chessboardjs.com/img/chesspieces/wikipedia/{piece}.png',
        showNotation: false,
        onDrop: handleDrop,
        onSnapEnd: () => {
            if (board) board.position(chess.fen())
        },
        moveSpeed: 200,
        snapSpeed: 100
    };
    
    try {
        board = Chessboard('Board', config);
        board.position(chess.fen(), false);

        // Force Proper Rendering
        setTimeout(() => {
            if (board) {
                board.resize();
                window.dispatchEvent(new Event('resize'));
            }
        }, 150);
        
        gameInitialized = true;
        console.log('Board Initialized Successfully');
    } catch (error) {
        console.error('Board Initialization Error:', error);
        StatusEl.innerText = 'Error Initializing Game Board. Please Refresh The Page.';
    }
}

function handleDrop(source, target) {
    // Validation Checks
    if (!playerColor || !gameInitialized || !chess) {
        console.log('Drop Rejected - Game Not Ready');
        return 'snapback';
    }

    // Check If It's Player's Turn
    const currentTurn = chess.turn() === 'w' ? 'white' : 'black';
    if (currentTurn !== playerColor) {
        console.log('Drop Rejected - Not Your Turn');
        return 'snapback';
    }

    // Check Piece Ownership
    const piece = chess.get(source);
    if (!piece) {
        console.log('Drop Rejected - No Piece At Source');
        return 'snapback';
    }
    
    if ((playerColor === 'white' && piece.color !== 'w') || 
        (playerColor === 'black' && piece.color !== 'b')) {
        console.log('Drop Rejected - Not Your Piece');
        return 'snapback';
    }

    // Attempt The Move
    const move = chess.move({ 
        from: source, 
        to: target, 
        promotion: 'q'
    });
    
    if (move === null) {
        console.log('Drop Rejected - Illegal Move');
        return 'snapback';
    }

    console.log('Move Made:', move);

    // Send Move to Server
    socket.emit('make_move', { 
        UserId, 
        GameId, 
        UciMove: source + target + (move.promotion || '')
    });

    // Update Board Immediately for Responsive Feel
    board.position(chess.fen(), false);
    return; // Allow The Move
}

// FIXED Timer Functions With Proper Synchronization
function syncTimersWithServer(serverTimers) {
    if (!serverTimers) return;
    
    // Update timer values from server
    timers.white = serverTimers.white || 600;
    timers.black = serverTimers.black || 600;
    timers.currentTurn = serverTimers.current_turn || 'white';
    timers.isActive = serverTimers.is_active || false;
    timers.lastServerSync = Date.now();

    console.log('Timers Synced With Server:', timers);
    updateTimerDisplay();
}

function startClientTimer() {
    stopClientTimer();
    
    if (!timers.isActive || !timers.gameStarted) {
        console.log('Timer Not Started - Game Not Active');
        return;
    }

    console.log('Starting Client Timer For:', timers.currentTurn);

    timers.activeInterval = setInterval(() => {
        if (!timers.isActive) return;
        
        const now = Date.now();
        const elapsed = Math.floor((now - timers.lastServerSync) / 1000);

        // Only Count Down If It's Been At Least 1 Second Since Last Update
        if (elapsed >= 1) {
            // Decrease Time for Current Player Only
            const currentPlayerTime = timers[timers.currentTurn];
            if (currentPlayerTime > 0) {
                timers[timers.currentTurn] = Math.max(0, currentPlayerTime - elapsed);
                timers.lastServerSync = now;
            }
            
            updateTimerDisplay();

            // Check For Time Expiry
            if (timers[timers.currentTurn] <= 0) {
                onTimeExpired(timers.currentTurn);
            }
        }
    }, 100); // More Frequent Updates for Smoother Countdown
}

function stopClientTimer() {
    if (timers.activeInterval) {
        clearInterval(timers.activeInterval);
        timers.activeInterval = null;
    }
}

function updateTimerDisplay() {
    const formatTime = (seconds) => {
        const mins = Math.floor(Math.max(0, seconds) / 60);
        const secs = Math.max(0, seconds) % 60;
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    };

    const playerTimerEl = document.getElementById('PlayerTimer');
    const opponentTimerEl = document.getElementById('OpponentTimer');

    if (playerColor === 'white') {
        playerTimerEl.innerText = formatTime(timers.white);
        opponentTimerEl.innerText = formatTime(timers.black);
        
        // Apply warning classes to player timer
        const myTime = timers.white;
        playerTimerEl.classList.toggle('Warning', myTime <= 60 && myTime > 30);
        playerTimerEl.classList.toggle('Danger', myTime <= 30);
    } else {
        playerTimerEl.innerText = formatTime(timers.black);
        opponentTimerEl.innerText = formatTime(timers.white);
        
        // Apply warning classes to player timer
        const myTime = timers.black;
        playerTimerEl.classList.toggle('Warning', myTime <= 60 && myTime > 30);
        playerTimerEl.classList.toggle('Danger', myTime <= 30);
    }
}

function onTimeExpired(color) {
    stopClientTimer();
    timers.isActive = false;

    console.log('Time Expired For:', color);

    socket.emit('time_expired', {
        GameId,
        UserId,
        Color: color
    });
    
    const iLost = playerColor === color;
    StatusEl.innerText = iLost ? 'Game Over - You Ran Out Of Time' : 'Game Over - Opponent Ran Out Of Time';

    if (board) {
        board.draggable = false;
    }
}

function updateActivePlayer(isMyTurn) {
    document.getElementById('PlayerBox').classList.toggle('Active', isMyTurn);
    document.getElementById('OpponentBox').classList.toggle('Active', !isMyTurn);
}

// Socket.IO Event Handlers
socket.on('connect', () => {
    console.log('Socket Connected Successfully');
    updateConnectionStatus(true);
    reconnectAttempts = 0;
    
    // Register user and join game
    socket.emit('register_user', { UserId });
    
    setTimeout(() => {
        socket.emit('join_game', { UserId, GameId });
    }, 100);
});

socket.on('disconnect', (reason) => {
    console.log('Socket disconnected:', reason);
    updateConnectionStatus(false);
    stopClientTimer();
    timers.isActive = false;
    StatusEl.innerText = 'Connection Lost - Attempting To Reconnect...';
});

socket.on('reconnect', (attemptNumber) => {
    console.log('Socket Reconnected After', attemptNumber, 'Attempts');
    updateConnectionStatus(true);
    
    // Re-register and rejoin game
    socket.emit('register_user', { UserId });
    setTimeout(() => {
        socket.emit('join_game', { UserId, GameId });
    }, 100);
});

socket.on('reconnect_failed', () => {
    console.log('Socket Reconnection Failed');
    StatusEl.innerText = 'Connection Lost. Please Refresh The Page.';
    showPopup('Connection Lost', 
        'Unable To Reconnect To The Game Server. Please Refresh The Page To Continue.',
        'Refresh Page', 'Return To Lobby',
        () => window.location.reload(),
        () => window.location.href = 'lobby.php'
    );
});

socket.on('register_result', (res) => {
    if (res.Success) {
        console.log('User Registered Successfully');
    } else {
        console.error('User Registration Failed:', res);
        showPopup('Registration Error', 
            'Failed To Register With The Game Server. Please Refresh The Page.',
            'Refresh', null,
            () => window.location.reload(), null
        );
    }
});

socket.on('assign_color', (data) => {
    console.log('Color assigned:', data);
    if (data.Success) {
        playerColor = data.Color;
        document.getElementById('PlayerName').innerText = `${UserName} (${playerColor})`;

        // Initialize Board After Color Assignment
        setTimeout(() => {
            initBoard();
        }, 100);
    } else {
        console.error('Color Assignment Failed:', data);
        StatusEl.innerText = 'Error : Failed To Assign Player Color';
    }
});

socket.on('game_state', (data) => {
    console.log('Game State Received:', data);
    
    if (!data.Success) {
        StatusEl.innerText = `Error: ${data.Message || 'Unknown Game Error'}`;
        
        if (data.Message === 'GameNotFound') {
            showPopup('Game Not Found', 
                'This Game No Longer Exists. You Will Be Redirected To The Lobby.',
                'Return To Lobby', null,
                () => window.location.href = 'lobby.php', null
            );
        } else if (data.Message === 'NotAPlayer') {
            showPopup('Access Denied', 
                'You Are Not A Participant In This Game.',
                'Return To Lobby', null,
                () => window.location.href = 'lobby.php', null
            );
        }
        return;
    }
    
    // Update Opponent Information
    const opponentId = data.WhiteUserId === UserId ? data.BlackUserId : data.WhiteUserId;
    const opponentColor = data.WhiteUserId === UserId ? 'black' : 'white';
    document.getElementById('OpponentName').innerText = `Player #${opponentId} (${opponentColor})`;

    // Update Game Position
    try {
        chess.load(data.Fen);
        if (board) {
            board.position(chess.fen());
        }
    } catch (error) {
        console.error('Error Loading FEN:', error);
        StatusEl.innerText = 'Error Loading Game Position';
        return;
    }

    // Sync Timers With Server
    if (data.Timers) {
        syncTimersWithServer(data.Timers);
    }

    // Handle Presence and Game Start
    if (data.BothPlayersPresent) {
        timers.gameStarted = true;
        document.getElementById('OpponentBox').classList.remove('Waiting');
        document.getElementById('PlayerBox').classList.remove('Waiting');

        // Determine Turn and Update UI
        if (playerColor) {
            const currentTurn = chess.turn() === 'w' ? 'white' : 'black';
            const isMyTurn = currentTurn === playerColor;
            
            updateActivePlayer(isMyTurn);
            StatusEl.innerText = isMyTurn ? "Your Turn!" : "Opponent's Turn...";

            // Start Timer if Game Active
            if (!chess.game_over() && data.Timers?.is_active) {
                timers.isActive = true;
                startClientTimer();
            }
        }
    } else {
        // Add Waiting Class To Both Boxes
        document.getElementById('OpponentBox').classList.add('Waiting');
        document.getElementById('PlayerBox').classList.add('Waiting');
        StatusEl.innerText = "Waiting For Opponent...";
    }
});

socket.on('game_start', (data) => {
    console.log('Game Started:', data);
    document.getElementById('OpponentBox').classList.remove('Waiting');
    document.getElementById('PlayerBox').classList.remove('Waiting');
    
    const isMyTurn = playerColor === 'white';
    updateActivePlayer(isMyTurn);
    StatusEl.innerText = isMyTurn ? "Game Started! Your Turn!" : "Game Started! Opponent's Turn...";
});

socket.on('player_joined', (data) => {
    console.log('Player Joined:', data);

    const opponentId = data.UserId;
    const opponentColor = data.IsWhite ? 'white' : 'black';
    document.getElementById('OpponentName').innerText = `Player #${opponentId} (${opponentColor})`;

    // FIXED: Start Game When Both Players Present
    if (data.BothPlayersPresent) {
        timers.gameStarted = true;
        
        if (playerColor) {
            const currentTurn = chess.turn() === 'w' ? 'white' : 'black';
            const isMyTurn = currentTurn === playerColor;

            StatusEl.innerText = isMyTurn ? "Game Started! Your Turn!" : "Game Started! Opponent's Turn...";
            updateActivePlayer(isMyTurn);
            
            // Start Timer
            timers.isActive = true;
            startClientTimer();
        }
    }
});

socket.on('player_left', (data) => {
    console.log('Player Left:', data);

    StatusEl.innerText = "You Won ! Opponent Has Left The Game.";
    stopClientTimer();
    timers.isActive = false;
    
    if (board) {
        board.draggable = false;
    }
    
    showPopup('Opponent Left', 
        'Your Opponent Has Disconnected From The Game.',
        'Return To Lobby', null,
        () => window.location.href = 'lobby.php', null
    );
});

socket.on('move_made', (data) => {
    if (!data.Success) {
        console.error('Move Failed:', data);
        if (board) {
            board.position(chess.fen());
        }
        return;
    }
    
    try {
        // Update Chess Position
        chess.load(data.Fen);
        if (board) {
            board.position(chess.fen(), true);
        }

        // Add Move To History If Provided
        if (data.San) {
            const prevTurn = chess.turn() === 'w' ? 'black' : 'white';
            const isMyMove = prevTurn === playerColor;
            addMoveToHistory(data.San, prevTurn, isMyMove);
        }

        // FIXED: Sync Timers With Server Data
        if (data.Timers) {
            syncTimersWithServer(data.Timers);
            if (timers.gameStarted && timers.isActive) {
                startClientTimer();
            }
        }

        // Update Turn and Active Player
        const currentTurn = chess.turn() === 'w' ? 'white' : 'black';
        const isMyTurn = currentTurn === playerColor;
        
        updateActivePlayer(isMyTurn);

        // FIXED: Don't Update Status If Checkmate Detected
        if (data.IsCheckmate) {
            // Let The game_over Handler Manage Status
            stopClientTimer();
            timers.isActive = false;
            if (board) {
                board.draggable = false;
            }
        } else {
            StatusEl.innerText = isMyTurn ? "Your Turn!" : "Opponent's Turn...";
        }

        // Request Position Analysis
        socket.emit('analyze_position', { GameId });

        // Check For Other Game Endings (But Not Checkmate - Handled By Server)
        if (chess.game_over() && !data.IsCheckmate) {
            stopClientTimer();
            timers.isActive = false;
            handleGameOver();
        }
        
    } catch (error) {
        console.error('Error Processing Move:', error);
        StatusEl.innerText = 'Error Processing Move';
    }
});

socket.on('position_analysis', (data) => {
    if (data.probability !== undefined) {
        const probability = data.probability * 100;
        const displayProb = playerColor === 'white' ? probability : 100 - probability;
        
        const probFill = document.getElementById('ProbFill');
        const probText = document.getElementById('ProbText');
        
        if (probFill && probText) {
            probFill.style.width = `${displayProb}%`;
            probText.innerText = `${Math.round(displayProb)}% ${playerColor || 'white'} Win Probability`;
        }
    }
});

// FIXED: Game Over Handling With Proper Checkmate Detection
socket.on('game_over', (data) => {
    console.log('Game Over:', data);

    stopClientTimer();
    timers.isActive = false;

    if (board) {
        board.draggable = false;
    }

    // FIXED: Properly Determine Winner For Checkmate
    let message;

    if (data.Reason === 'checkmate') {
        // Correct Logic: Winner Is The Player Whose Color Matches data.Winner
        const iWon = (data.Winner === 'White' && playerColor === 'white') ||
                     (data.Winner === 'Black' && playerColor === 'black');
        message = iWon ?
            'You Won !! By Checkmating Your Opponent!' :
            'You Lose !! Your Opponent Has Checkmated You!';
    } else {
        message = getGameResultMessage(data);
    }

    StatusEl.innerText = message;

    // Show Popup And Wait For Ratings
    showPopup('Game Over',
        message + '\n\nCalculating Rating Changes...',
        'OK', null,
        () => hidePopup(), null
    );
});

socket.on('ratings_updated', (data) => {
    console.log('Ratings Updated :', data);
    
    const myUserId = parseInt(UserId);
    const isWhite = myUserId === data.WhiteUserId;
    const ratingMsg = formatRatingChanges(data, isWhite);
    const resultMessage = StatusEl.innerText;
    
    showPopup('Game Complete', 
        `${resultMessage}\n\n${ratingMsg}`,
        'Return To Lobby', null,
        () => window.location.href = 'lobby.php', null
    );
    
    // Update Stored Ratings
    localStorage.setItem('I8O8IChessClassicalRating', 
        isWhite ? data.WhiteClassicalRating : data.BlackClassicalRating);
    localStorage.setItem('I8O8IChessRapidRating', 
        isWhite ? data.WhiteRapidRating : data.BlackRapidRating);
});

socket.on('draw_offer', (data) => {
    showPopup('Draw Offer', 
        `Player #${data.UserId} Has Offered a Draw. Do You Accept?`,
        'Accept', 'Decline',
        () => {
            socket.emit('draw_response', { GameId, UserId, Accept: true });
            hidePopup();
        },
        () => {
            socket.emit('draw_response', { GameId, UserId, Accept: false });
            hidePopup();
        }
    );
});

socket.on('draw_response', (data) => {
    if (data.Accept) {
        StatusEl.innerText = 'Draw Accepted! Game Ending...';
    } else {
        showPopup('Draw Declined', 
            'Your Draw Offer Was Declined. The Game Continues.',
            'OK', null,
            () => hidePopup(), null
        );
    }
});

socket.on('chat_message', (data) => {
    console.log('Chat Message Received:', data);

    const isCurrentUser = data.FromUserId === UserId;
    const messageEl = document.createElement('div');
    messageEl.className = `chat-message ${isCurrentUser ? 'own' : 'opponent'}`;
    
    const senderName = isCurrentUser ? 'You' : 'Opponent';
    messageEl.innerHTML = `<span class="sender">${senderName}:</span> ${escapeHtml(data.MessageText)}`;
    
    ChatList.appendChild(messageEl);
    ChatList.scrollTop = ChatList.scrollHeight;
});

// Helper Functions
function addMoveToHistory(san, moveColor, isMyMove) {
    const moveList = document.getElementById('MoveList');
    if (!moveList) return;

    // Count How Many Rows Exist (Each Row Is A Full Move: White+Black)
    let moveNumber = moveList.children.length + 1;

    if (moveColor === 'white') {
        // Create New Row For White Move, Increment Move Number
        const newRow = document.createElement('div');
        newRow.className = 'MoveRow';
        newRow.innerHTML = `
            <div class="MoveNumber">${moveNumber}.</div>
            <div class="Move WhiteMove">
                <span class="MoveText">${escapeHtml(san)}</span>
                <span class="PlayerName">(${isMyMove ? 'You' : 'Opp'})</span>
            </div>
            <div class="Move BlackMove"></div>
        `;
        moveList.appendChild(newRow);
    } else {
        // Add Black Move To Existing Row (Do Not Increment Move Number)
        const lastRow = moveList.lastElementChild;
        if (lastRow) {
            const blackMove = lastRow.querySelector('.BlackMove');
            if (blackMove) {
                blackMove.innerHTML = `
                    <span class="MoveText">${escapeHtml(san)}</span>
                    <span class="PlayerName">(${isMyMove ? 'You' : 'Opp'})</span>
                `;
            }
        }
    }

    moveList.scrollTop = moveList.scrollHeight;
}

function handleGameOver() {
    if (chess.in_stalemate()) {
        StatusEl.innerText = "Game Drawn By Stalemate";
        socket.emit('game_over', {
            GameId,
            UserId,
            Reason: 'stalemate',
            Winner: 'Draw'
        });
    } else if (chess.insufficient_material()) {
        StatusEl.innerText = "Game Drawn By Insufficient Material";
        socket.emit('game_over', {
            GameId,
            UserId,
            Reason: 'insufficient_material',
            Winner: 'Draw'
        });
    }
    // Note: Checkmate Is Handled By The Server In move_made Event
}

function getGameResultMessage(data) {
    switch (data.Reason) {
        case 'checkmate':
            // FIXED: Properly Determine Winner For Checkmate
            const iWon = (data.Winner === 'White' && playerColor === 'white') || 
                        (data.Winner === 'Black' && playerColor === 'black');
            return iWon ? 
                'You Won !! By Checkmating Your Opponent!' :
                'You Lose !! Your Opponent Has Checkmated You!';
        case 'timeout':
            return data.Winner !== playerColor ? 
                'You Lost On Time!' : 
                'You Won! Opponent\'s Time Expired.';
        case 'resignation':
            return data.UserId === UserId ? 
                'You Lose !! You Resigned The Game.' : 
                'You Won! Opponent Resigned.';
        case 'draw':
        case 'stalemate':
        case 'insufficient_material':
        case 'draw_agreed':
            return 'Game Drawn';
        default:
            return 'Game Over';
    }
}

function formatRatingChanges(data, isWhite) {
    const myClassical = isWhite ? data.WhiteClassicalRating : data.BlackClassicalRating;
    const myRapid = isWhite ? data.WhiteRapidRating : data.BlackRapidRating;
    const myClassicalChange = isWhite ? data.WhiteClassicalChange : data.BlackClassicalChange;
    const myRapidChange = isWhite ? data.WhiteRapidChange : data.BlackRapidChange;
    
    const oppClassical = isWhite ? data.BlackClassicalRating : data.WhiteClassicalRating;
    const oppRapid = isWhite ? data.BlackRapidRating : data.WhiteRapidRating;
    const oppClassicalChange = isWhite ? data.BlackClassicalChange : data.WhiteClassicalChange;
    const oppRapidChange = isWhite ? data.BlackRapidChange : data.WhiteRapidChange;
    
    const formatChange = change => (change > 0 ? `+${change}` : change.toString());
    
    return `Your Ratings:\n` +
           `Classical: ${myClassical} (${formatChange(myClassicalChange)})\n` +
           `Rapid: ${myRapid} (${formatChange(myRapidChange)})\n\n` +
           `Opponent Ratings:\n` +
           `Classical: ${oppClassical} (${formatChange(oppClassicalChange)})\n` +
           `Rapid: ${oppRapid} (${formatChange(oppRapidChange)})`;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Chat Functions
function sendChatMessage() {
    const msg = ChatInput.value.trim();
    if (!msg || msg.length === 0) return;

    if (msg.toLowerCase() === 'clear') {
        // Clear Chat History
        ChatList.innerHTML = '';
        return;
    }

    // FIX: Use the correct event name 'send_chat' to match backend
    socket.emit('send_chat', {
        GameId: GameId,
        UserId: UserId,
        MessageText: msg
    });

    // Do not add the message locally, wait for server echo for consistency
    ChatInput.value = '';
}

// Chat Event Listeners
BtnSendChat.addEventListener('click', () => {
    sendChatMessage();
});

ChatInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        sendChatMessage();
    }
});

// === RESTORE GLOBAL BUTTON FUNCTIONS ===
function offerDraw() {
    if (!chess || chess.game_over()) {
        showPopup('Game Over', 
            'The Game Has Already Ended.',
            'OK', null,
            () => hidePopup(), null
        );
        return;
    }
    showPopup('Offer Draw', 
        'Are You Sure You Want To Offer A Draw?',
        'Offer', 'Cancel',
        () => {
            socket.emit('draw_offer', { 
                GameId: GameId,
                UserId: UserId 
            });
            showPopup('Draw Offered', 
                'Your Draw Offer Has Been Sent. Waiting For Opponent\'s Response...',
                'OK', null,
                () => hidePopup(), null
            );
        },
        () => hidePopup()
    );
}

function resignGame() {
    if (!chess || chess.game_over()) {
        showPopup('Game Over', 
            'The Game Has Already Ended.',
            'OK', null,
            () => hidePopup(), null
        );
        return;
    }
    showPopup('Resign Game', 
        'Are You Sure You Want To Resign? This Will Count As A Loss.',
        'Resign', 'Cancel',
        () => {
            socket.emit('resign_game', { 
                GameId: GameId,
                UserId: UserId 
            });
            StatusEl.innerText = 'You Lose !! Because You Resigned The Game.';
            if (board) {
                board.draggable = false;
            }
            hidePopup();
        },
        () => hidePopup()
    );
}

function exitGame() {
    if (!chess.game_over()) {
        showPopup('Exit Game', 
            'The Game Is Still In Progress. Exiting Now Will Count As Resignation. Are You Sure?',
            'Exit & Resign', 'Stay In Game',
            () => {
                socket.emit('resign_game', { 
                    GameId: GameId,
                    UserId: UserId 
                });
                window.location.href = 'lobby.php';
            },
            () => hidePopup()
        );
    } else {
        window.location.href = 'lobby.php';
    }
}

// === RESTORE GLOBAL POPUP FUNCTIONS ===
function showPopup(title, message, primaryBtn, secondaryBtn, primaryAction, secondaryAction) {
    document.getElementById('popupTitle').innerText = title;
    document.getElementById('popupMessage').innerText = message;
    
    const primaryBtnEl = document.getElementById('popupPrimary');
    const secondaryBtnEl = document.getElementById('popupSecondary');
    
    primaryBtnEl.innerText = primaryBtn;
    primaryBtnEl.onclick = () => {
        if (primaryAction) primaryAction();
        else hidePopup();
    };
    
    if (secondaryBtn && secondaryAction) {
        secondaryBtnEl.style.display = 'inline-block';
        secondaryBtnEl.innerText = secondaryBtn;
        secondaryBtnEl.onclick = () => {
            if (secondaryAction) secondaryAction();
            else hidePopup();
        };
    } else {
        secondaryBtnEl.style.display = 'none';
    }
    
    document.getElementById('popupOverlay').style.display = 'block';
}

function hidePopup() {
    document.getElementById('popupOverlay').style.display = 'none';
}

// Initial Setup
document.addEventListener('DOMContentLoaded', () => {
    // Set Initial Player Name
    document.getElementById('PlayerName').innerText = `${UserName} (${playerColor})`;
    
    // Initialize Board if Color Already Assigned
    if (playerColor) {
        setTimeout(() => {
            initBoard();
        }, 100);
    }
});

// Global Error Handler
window.addEventListener('error', (event) => {
    console.error('Global Error Caught:', event);
    StatusEl.innerText = 'An unexpected error occurred. Please refresh the page.';
});

// Prevent Default Context Menu
document.addEventListener('contextmenu', (event) => {
    event.preventDefault();
});
</script>
</body>
</html>