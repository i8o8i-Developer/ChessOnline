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
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body { 
      font-family: 'Courier New', monospace;
      background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
      color: #fff;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    
    .Container { 
      width: 100%;
      max-width: 450px;
      background: rgba(42, 42, 42, 0.95);
      padding: 40px;
      border: 2px solid #444;
      border-radius: 12px;
      box-shadow: 0 15px 35px rgba(0,0,0,0.7);
      backdrop-filter: blur(10px);
    }
    
    .Logo {
      text-align: center;
      margin-bottom: 40px;
    }
    
    h1 {
      color: #4CAF50;
      text-shadow: 0 0 20px rgba(76, 175, 80, 0.5);
      margin-bottom: 10px;
      font-size: 2.5em;
      font-weight: bold;
    }
    
    .Tagline {
      color: #aaa;
      font-size: 1.1em;
      margin-bottom: 20px;
    }
    
    .Form {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }
    
    .InputGroup {
      position: relative;
    }
    
    .InputGroup label {
      display: block;
      margin-bottom: 5px;
      color: #4CAF50;
      font-weight: bold;
      font-size: 0.9em;
    }
    
    input {
      width: 100%;
      padding: 15px;
      background: rgba(34, 34, 34, 0.8);
      border: 2px solid #444;
      border-radius: 8px;
      color: #fff;
      font-family: 'Courier New', monospace;
      font-size: 16px;
      transition: all 0.3s ease;
    }
    
    input:focus {
      border-color: #4CAF50;
      outline: none;
      box-shadow: 0 0 20px rgba(76, 175, 80, 0.3);
      background: rgba(34, 34, 34, 0.95);
    }
    
    input::placeholder {
      color: #666;
    }
    
    .BtnGroup {
      display: flex;
      flex-direction: column;
      gap: 15px;
      margin-top: 10px;
    }
    
    button {
      padding: 15px;
      border: none;
      border-radius: 8px;
      color: white;
      cursor: pointer;
      font-family: 'Courier New', monospace;
      font-size: 16px;
      font-weight: bold;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }
    
    button:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }
    
    button::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
      transition: left 0.5s;
    }
    
    button:hover::before {
      left: 100%;
    }
    
    #BtnLogin {
      background: linear-gradient(45deg, #4CAF50, #45a049);
      box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
    }
    
    #BtnLogin:hover {
      background: linear-gradient(45deg, #45a049, #4CAF50);
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
    }
    
    #BtnRegister {
      background: linear-gradient(45deg, #666, #555);
      box-shadow: 0 4px 15px rgba(102, 102, 102, 0.3);
    }
    
    #BtnRegister:hover {
      background: linear-gradient(45deg, #555, #666);
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(102, 102, 102, 0.4);
    }
    
    .Loading {
      display: none;
      width: 20px;
      height: 20px;
      border: 3px solid rgba(255,255,255,0.3);
      border-radius: 50%;
      border-top-color: #fff;
      animation: spin 1s linear infinite;
      margin: 0 auto;
    }
    
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
    
    .popup-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.8);
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
    }
    
    .popup-title {
      font-size: 24px;
      color: #4CAF50;
      margin-bottom: 15px;
      text-shadow: 0 0 10px rgba(76, 175, 80, 0.3);
      text-align: center;
    }
    
    .popup-message {
      font-size: 18px;
      color: #fff;
      margin-bottom: 25px;
      line-height: 1.5;
      text-align: center;
    }
    
    .popup-button {
      display: block;
      width: 100%;
      padding: 12px 20px;
      background: linear-gradient(45deg, #4CAF50, #45a049);
      border: none;
      border-radius: 8px;
      color: white;
      cursor: pointer;
      font-family: 'Courier New', monospace;
      font-size: 16px;
      font-weight: bold;
      margin: 0 auto;
      transition: all 0.3s ease;
    }
    
    .popup-button:hover {
      background: linear-gradient(45deg, #45a049, #4CAF50);
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
    }
    
    .ErrorMessage {
      color: #f44336;
      background: rgba(244, 67, 54, 0.1);
      border: 1px solid #f44336;
      padding: 10px;
      border-radius: 6px;
      margin-top: 10px;
      font-size: 14px;
      display: none;
    }
    
    .SuccessMessage {
      color: #4CAF50;
      background: rgba(76, 175, 80, 0.1);
      border: 1px solid #4CAF50;
      padding: 10px;
      border-radius: 6px;
      margin-top: 10px;
      font-size: 14px;
      display: none;
    }
    
    .Features {
      margin-top: 30px;
      padding-top: 20px;
      border-top: 1px solid #444;
    }
    
    .Features h3 {
      color: #4CAF50;
      margin-bottom: 15px;
      text-align: center;
    }
    
    .FeatureList {
      list-style: none;
      padding: 0;
    }
    
    .FeatureList li {
      padding: 5px 0;
      color: #aaa;
      font-size: 0.9em;
    }
    
    .FeatureList li::before {
      content: "✓ ";
      color: #4CAF50;
      font-weight: bold;
    }
    
    @media (max-width: 480px) {
      .Container {
        padding: 30px 20px;
      }
      
      h1 {
        font-size: 2em;
      }
      
      input, button {
        font-size: 14px;
        padding: 12px;
      }
    }
  </style>
</head>
<body>
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
        <button type="submit" id="BtnLogin">
          <span id="LoginText">Sign In</span>
          <div id="LoginLoading" class="Loading"></div>
        </button>
        <button type="button" id="BtnRegister">
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
  </div>

  <div class="popup-overlay" id="popupOverlay">
    <div class="popup">
      <div class="popup-title" id="popupTitle"></div>
      <div class="popup-message" id="popupMessage"></div>
      <button class="popup-button" id="popupButton" onclick="hidePopup()">OK</button>
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
        // Verify session is still valid
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
    document.getElementById('popupOverlay').style.display = 'block';
}

function hidePopup() {
    document.getElementById('popupOverlay').style.display = 'none';
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
            // Store user data
            localStorage.setItem("I8O8IChessUserId", result.UserId);
            localStorage.setItem("I8O8IChessUserName", result.UserName);
            localStorage.setItem("I8O8IChessClassicalRating", result.ClassicalRating || 1000);
            localStorage.setItem("I8O8IChessRapidRating", result.RapidRating || 1000);
            
            showSuccess('Login Successful! Redirecting...');
            
            // Redirect after short delay
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