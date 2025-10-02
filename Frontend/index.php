<?php
// IndexPhpLoginAndRegistrationFixedVersion
session_start();
require_once 'Config.php';

// RedirectIfAlreadyLoggedIn
if (isset($_SESSION['user_id']) || 
    (isset($_COOKIE['I8O8IChessUserId']) && isset($_COOKIE['I8O8IChessUserName']))) {
    header('Location: lobby.php');
    exit();
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>I8O8IChess - Professional Chess Platform</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    :root{
      --bg-0: #0f0f10;
      --bg-1: #1c1c1c;
      --panel: #222426;
      --muted: #2f2f2f;
      --accent: #4CAF50;
      --text-on-dark: #dfeee0;
      --glass: rgba(255,255,255,0.02);
      --card-radius: 14px;
    }

    *{ box-sizing: border-box; margin:0; padding:0 }

    html,body{ height:100%; }
    body{
      font-family: 'Courier New', monospace;
      background: radial-gradient(1200px 400px at 10% 10%, rgba(76,175,80,0.03), transparent), linear-gradient(180deg,var(--bg-0),var(--bg-1));
      color:var(--text-on-dark);
      min-height:100vh;
      display:flex;
      align-items:center;
      justify-content:center;
      padding:28px;
      -webkit-font-smoothing:antialiased;
    }

    .Container{
      width:100%;
      max-width:520px;
      background: linear-gradient(180deg, rgba(28,28,28,0.96), rgba(20,20,20,0.96));
      padding:32px 36px;
      border-radius: var(--card-radius);
      border:1px solid rgba(255,255,255,0.03);
      box-shadow: 0 18px 50px rgba(0,0,0,0.7), inset 0 1px 0 rgba(255,255,255,0.02);
    }

    .Logo{ text-align:center; margin-bottom:28px }
    .Logo h1{
      font-family: 'Press Start 2P', monospace;
      color:var(--accent);
      font-size:44px;
      line-height:1;
      letter-spacing:2px;
      text-shadow: 0 6px 18px rgba(76,175,80,0.12), 0 1px 0 rgba(0,0,0,0.6);
      margin-bottom:8px;
    }
    .Tagline{ color:#9aa79a; font-size:14px; letter-spacing:0.6px }

    .Form{ display:flex; flex-direction:column; gap:16px }
    .InputGroup label{ display:block; margin-bottom:8px; color:var(--accent); font-weight:700; font-size:13px }

    input{
      width:100%;
      padding:14px 16px;
      border-radius:10px;
      border:1px solid rgba(255,255,255,0.06);
      background: linear-gradient(180deg,#eef7ff, #e2ecf7);
      color:#052127;
      font-size:15px;
      box-shadow: inset 0 4px 10px rgba(12,20,30,0.06);
      transition: box-shadow 150ms ease, transform 120ms ease;
    }
    input:focus{ outline:none; box-shadow: 0 6px 30px rgba(76,175,80,0.14); transform: translateY(-1px) }
    input::placeholder{ color:#668899 }

    .BtnGroup{ display:flex; gap:12px; margin-top:6px }
    .BtnGroup .btn{ flex:1 1 auto }

    /* Button Utilities (Used By This Page) */
    .btn{ display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:14px 18px; border-radius:10px; font-weight:800; font-size:15px; text-decoration:none; border:none; cursor:pointer; transition: transform 140ms ease, box-shadow 140ms ease }
    .btn:focus{ outline:3px solid rgba(76,175,80,0.12); outline-offset:2px }
    .btn[disabled], button[disabled]{ opacity:0.6; pointer-events:none }

    .btn-primary{ background: linear-gradient(180deg,var(--accent), #3fa34a); color:#06210a; box-shadow: 0 8px 26px rgba(76,175,80,0.14) }
    .btn-primary:hover{ transform: translateY(-3px); box-shadow: 0 18px 36px rgba(76,175,80,0.18) }

    .btn-secondary{ background: linear-gradient(180deg,#666, #515151); color:#fff; box-shadow: 0 8px 18px rgba(0,0,0,0.35) }
    .btn-secondary:hover{ transform: translateY(-3px); box-shadow: 0 16px 30px rgba(0,0,0,0.36) }

    .Loading{ display:none; width:18px; height:18px; border:3px solid rgba(255,255,255,0.4); border-radius:50%; border-top-color:transparent; animation:spin 0.9s linear infinite }
    @keyframes spin{ to{ transform:rotate(360deg) } }

    .popup-overlay{ display:none; position:fixed; inset:0; background: rgba(0,0,0,0.6); z-index:1000; align-items:center; justify-content:center; }
    .popup{ border-radius:12px; background: linear-gradient(180deg,#242424,#2e2e2e); padding:20px; border:1px solid rgba(255,255,255,0.03); box-shadow: 0 20px 50px rgba(0,0,0,0.7); max-width:420px }
    .popup-title{ color:var(--accent); font-weight:800; margin-bottom:8px }

    .ErrorMessage{ color:#f44336; background: rgba(244,67,54,0.06); border:1px solid rgba(244,67,54,0.12); padding:10px; border-radius:8px; display:none }
    .SuccessMessage{ color:var(--accent); background: rgba(76,175,80,0.06); border:1px solid rgba(76,175,80,0.12); padding:10px; border-radius:8px; display:none }

    .Features{ margin-top:22px; padding-top:20px; border-top:1px solid rgba(255,255,255,0.03) }
    .Features h3{ color:var(--accent); text-align:center; margin-bottom:12px }
    .FeatureList{ list-style:none; padding:0; display:grid; gap:8px }
    .FeatureList li{ color:#a9b9ac; font-size:14px }
    .FeatureList li::before{ content:'✓ '; color:var(--accent); margin-right:6px }

    .ExtraLinks{ display:flex; gap:12px; justify-content:center; margin-top:20px }
    .ExtraLinks a{ text-decoration:none }

    @media (max-width:520px){
      .Container{ padding:22px; max-width:420px }
      .Logo h1{ font-size:34px }
      .BtnGroup{ flex-direction:column }
      .ExtraLinks{ flex-direction:column }
    }
  </style>
  <!-- Retro Theme Font and Overrides (shared) -->
  <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="static/game.css">
</head>
<body class="retro">
  <div class="Container">
    <div class="Logo">
      <h1>I8O8I</h1>
      <div class="Tagline">Professional Chess Platform</div>
    </div>
    
    <form class="Form" id="loginForm">
      <div class="InputGroup">
        <label for="UserName">Username</label>
        <input id="UserName" name="username" placeholder="Enter your username" autocomplete="username" required />
      </div>
      
      <div class="InputGroup">
        <label for="Password">Password</label>
        <input id="Password" name="password" type="password" placeholder="Enter your password" autocomplete="current-password" required />
      </div>
      
      <div id="ErrorMessage" class="ErrorMessage"></div>
      <div id="SuccessMessage" class="SuccessMessage"></div>
      
            <div class="BtnGroup">
                <button type="submit" id="BtnLogin" class="btn btn-primary">
                    <span id="LoginText">Sign In</span>
                    <div id="LoginLoading" class="Loading"></div>
                </button>
                <button type="button" id="BtnRegister" class="btn btn-secondary">
                    <span id="RegisterText">Create Account</span>
                    <div id="RegisterLoading" class="Loading"></div>
                </button>
            </div>
    </form>
    
    <div class="Features">
      <h3>Features</h3>
      <ul class="FeatureList">
        <li>Real-Time Multiplayer Chess</li>
        <li>Multiple Time Controls (Classical, Rapid, Blitz)</li>
        <li>ELO Rating System</li>
        <li>Live Game Analysis</li>
        <li>Achievement System</li>
        <li>Rating History Tracking</li>
      </ul>
    </div>
    
        <!-- Extra Links: Contact & Release -->
        <div class="ExtraLinks">
            <a class="btn btn-primary" href="release.php">What's New / Release</a>
            <a class="btn btn-secondary" href="contact.php">Contact Developer</a>
        </div>
  </div>

  <div class="popup-overlay" id="popupOverlay">
    <div class="popup">
      <div class="popup-title" id="popupTitle"></div>
      <div class="popup-message" id="popupMessage"></div>
    <button class="popup-button btn btn-primary" id="popupButton" onclick="hidePopup()">OK</button>
    </div>
  </div>

<script>
// Configuration
const ApiBaseUrl = "<?php echo $AppConfig['ApiBaseUrl']; ?>";
const ApiBase = ApiBaseUrl + "/api";

// DOM Elements
const form = document.getElementById('loginForm');
const userNameInput = document.getElementById('UserName');
const passwordInput = document.getElementById('Password');
const loginBtn = document.getElementById('BtnLogin');
const registerBtn = document.getElementById('BtnRegister');
const errorMsg = document.getElementById('ErrorMessage');
const successMsg = document.getElementById('SuccessMessage');

// StateManagement
let isProcessing = false;

// CheckIfUserIsAlreadyLoggedIn
function checkExistingSession() {
    const userId = localStorage.getItem('I8O8IChessUserId');
    const userName = localStorage.getItem('I8O8IChessUserName');
    
    if (userId && userName) {
        // Verify Session Is Still Valid
        fetch(`${ApiBase}/user/${userId}`)
            .then(response => response.json())
            .then(data => {
                if (data.Success) {
                    window.location.href = 'lobby.php';
                } else {
                    // Clear Invalid Session
                    clearSession();
                }
            })
            .catch(() => {
                // On Error, Don't Redirect But Don't Clear Session Either
                // In Case It's Just A Network Issue
            });
    }
}

function clearSession() {
    localStorage.removeItem('I8O8IChessUserId');
    localStorage.removeItem('I8O8IChessUserName');
    localStorage.removeItem('I8O8IChessClassicalRating');
    localStorage.removeItem('I8O8IChessRapidRating');
}

function getFormValues() {
    return {
        UserName: userNameInput.value.trim(),
        Password: passwordInput.value.trim()
    };
}

function showError(message) {
    hideMessages();
    errorMsg.textContent = message;
    errorMsg.style.display = 'block';
    errorMsg.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function showSuccess(message) {
    hideMessages();
    successMsg.textContent = message;
    successMsg.style.display = 'block';
    successMsg.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function hideMessages() {
    errorMsg.style.display = 'none';
    successMsg.style.display = 'none';
}

function showPopup(title, message) {
    document.getElementById('popupTitle').textContent = title;
    document.getElementById('popupMessage').textContent = message;
    const overlay = document.getElementById('popupOverlay');
    overlay.style.display = 'flex';
    overlay.setAttribute('aria-hidden', 'false');
    // move focus to popup button for keyboard users
    setTimeout(() => { const btn = document.getElementById('popupButton'); if (btn) btn.focus(); }, 60);
}

function hidePopup() {
    const overlay = document.getElementById('popupOverlay');
    overlay.style.display = 'none';
    overlay.setAttribute('aria-hidden', 'true');
}

function setLoadingState(button, loading) {
    const textEl = button.querySelector('span');
    const loadingEl = button.querySelector('.Loading');
    
    if (loading) {
        textEl.style.display = 'none';
        loadingEl.style.display = 'block';
        button.disabled = true;
    } else {
        textEl.style.display = 'inline';
        loadingEl.style.display = 'none';
        button.disabled = false;
    }
}

function validateInputs(UserName, Password) {
    if (!UserName) {
        showError('Please Enter Your Username.');
        userNameInput.focus();
        return false;
    }
    if (UserName.length < 3) {
        showError('Username Must Be At Least 3 Characters Long.');
        userNameInput.focus();
        return false;
    }
    if (!Password) {
        showError('Please Enter Your Password.');
        passwordInput.focus();
        return false;
    }
    if (Password.length < 6) {
        showError('Password Must Be At Least 6 Characters Long.');
        passwordInput.focus();
        return false;
    }
    return true;
}

async function apiPost(endpoint, data) {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 Second Timeout
    
    try {
        const response = await fetch(ApiBase + endpoint, {
            method: "POST",
            headers: { 
                "Content-Type": "application/json",
                "Accept": "application/json"
            },
            body: JSON.stringify(data),
            signal: controller.signal
        });
        
        clearTimeout(timeoutId);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        return result;
    } catch (error) {
        clearTimeout(timeoutId);
        
        if (error.name === 'AbortError') {
            throw new Error('Request Timeout. Please Check Your Connection And Try Again.');
        } else if (error.message.includes('Failed To Fetch')) {
            throw new Error('Unable To Connect To Server. Please Check Your Connection.');
        } else {
            throw error;
        }
    }
}

// RegistrationHandler
async function handleRegister() {
    if (isProcessing) return;
    
    const { UserName, Password } = getFormValues();
    if (!validateInputs(UserName, Password)) return;
    
    isProcessing = true;
    setLoadingState(registerBtn, true);
    hideMessages();
    
    try {
        const result = await apiPost("/register", { UserName, Password });
        
        if (result && result.Success) {
            showSuccess('Registration Successful! You Can Now Sign In With Your Credentials.');
            passwordInput.value = ''; // Clear Password For Security
            userNameInput.focus();
        } else {
            const message = result?.Message || 'Registration Failed. Please Try Again.';
            showError(getErrorMessage(message));
        }
    } catch (error) {
        console.error('Registration Error:', error);
        showError(error.message || 'Registration Failed. Please Try Again.');
    } finally {
        isProcessing = false;
        setLoadingState(registerBtn, false);
    }
}

// LoginHandler
async function handleLogin() {
    if (isProcessing) return;
    
    const { UserName, Password } = getFormValues();
    if (!validateInputs(UserName, Password)) return;
    
    isProcessing = true;
    setLoadingState(loginBtn, true);
    hideMessages();
    
    try {
        const result = await apiPost("/login", { UserName, Password });
        
        if (result && result.Success) {
            // Store User Data
            localStorage.setItem("I8O8IChessUserId", result.UserId);
            localStorage.setItem("I8O8IChessUserName", result.UserName);
            localStorage.setItem("I8O8IChessClassicalRating", result.ClassicalRating || 1000);
            localStorage.setItem("I8O8IChessRapidRating", result.RapidRating || 1000);
            
            showSuccess('Login Successful! Redirecting...');
            
            // Redirect After Short Delay
            setTimeout(() => {
                window.location.href = "lobby.php";
            }, 1000);
        } else {
            const message = result?.Message || 'Login Failed. Please Check Your Credentials.';
            showError(getErrorMessage(message));
            passwordInput.value = ''; // Clear Password On Failed Login
            passwordInput.focus();
        }
    } catch (error) {
        console.error('Login Error:', error);
        showError(error.message || 'Login Failed. Please Try Again.');
        passwordInput.value = ''; // Clear Password On Error
        passwordInput.focus();
    } finally {
        isProcessing = false;
        setLoadingState(loginBtn, false);
    }
}

function getErrorMessage(serverMessage) {
    const errorMap = {
        'MissingUserNameOrPassword': 'Please Provide Both Username And Password.',
        'UserNameAlreadyExists': 'Username Already Exists. Please Choose A Different Username.',
        'InvalidCredentials': 'Invalid Username Or Password. Please Try Again.',
        'UserNotFound': 'User Not Found. Please Check Your Username.',
        'NetworkError': 'Network Error. Please Check Your Connection.'
    };

    return errorMap[serverMessage] || serverMessage || 'An Unexpected Error Occurred.';
}

// EventListeners
form.addEventListener('submit', (e) => {
    e.preventDefault();
    handleLogin();
});

registerBtn.addEventListener('click', (e) => {
    e.preventDefault();
    handleRegister();
});

// Handle Enter Key For Better UX
userNameInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        passwordInput.focus();
    }
});

passwordInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        handleLogin();
    }
});

// Auto-Hide Messages When User Starts Typing
userNameInput.addEventListener('input', hideMessages);
passwordInput.addEventListener('input', hideMessages);

// Close Popup On Escape Key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        hidePopup();
    }
});

// Close Popup When Clicking Outside
document.getElementById('popupOverlay').addEventListener('click', (e) => {
    if (e.target.id === 'popupOverlay') {
        hidePopup();
    }
});

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    checkExistingSession();
    userNameInput.focus();
});

// HandleConnectionStatus
window.addEventListener('online', () => {
    hideMessages();
    showSuccess('Connection Restored.');
    setTimeout(hideMessages, 3000);
});

window.addEventListener('offline', () => {
    showError('No Internet Connection. Please Check Your Network.');
});
</script>
</body>
</html>