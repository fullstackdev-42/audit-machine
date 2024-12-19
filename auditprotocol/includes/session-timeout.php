<style>
.sessionTimeoutPopup {
  position: absolute;
  top: 50%;
  left: 50%;
  -ms-transform: translate(-50%, -25%);
  transform: translate(-50%, -25%);
}
.butn {
  -webkit-border-radius: 0;
  -moz-border-radius: 0;
  border-radius: 0;
  -webkit-box-shadow: 3px 3px 2px #3d3d3d;
  -moz-box-shadow: 3px 3px 2px #3d3d3d;
  box-shadow: 3px 3px 2px #3d3d3d;
  font-family: Arial;
  color: #ffffff;
  font-size: 20px;
  background: #2bb32b;
  padding: 13px 23px 13px 23px;
  text-decoration: none;
}
.butn:hover {
  background: #3bdb3b;
  text-decoration: none;
}
.countdown-timer {
  margin-top: 10px;
  color: black;
}
.seconds-left {
  font-size: 50px;
}
</style>

<div class="sessionTimeoutPopup ui-dialog ui-widget ui-widget-content ui-corner-all" style="display: none; position: fixed; z-index: 1000;outline: 0px;">
  <div class="ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix">
    <span class="ui-dialog-title" style="color: red;">Warning! You will be logged out for inactivity in:</span>
    <div class="countdown-timer">
      <span id="seconds-left" class="seconds-left">60</span>
      <br/>
      <span style="font-size: 35px;">seconds.</span>
    </div>
    <br>
    <button id="stayLoggedInButn" class="butn" style="margin-bottom: 20px;">Keep me logged in.</button>
  </div>
</div>
<div class="popupOverlay ui-widget-overlay" style="width: 100%; height: 100vh; z-index: 900; position: fixed; display: none;"></div>

<?php
include __DIR__ . "/../config.php";
$dbh = new PDO('mysql:host='.LA_DB_HOST.';dbname='.LA_DB_NAME.'',''.LA_DB_USER.'',''.LA_DB_PASSWORD.'');

function getSessionTimeoutSettings() { // pull session timeout settings from MySQL
  foreach($dbh->query('SELECT enable_session_timeout, session_timeout_period from ap_settings') as $result) {
    return $result;
  }
}

function getUserEmail() { // pull session timeout settings from MySQL
  foreach($dbh->query("SELECT email from ap_ask_client_users where client_id = $_SESSION['la_user_id']") as $result) {
    return $result;
  }
}

$session_timeout_settings = getSessionTimeoutSettings();
$user_email = getUserEmail();
?>

<script>
var currentScript          = document.location.href.split("auditprotocol/")[1];
var enable_session_timeout = "<?php echo $session_timeout_settings[0] ?>";
var blockedScripts         = new Set([
  "download.php",
  "embed_code_ajax.php",
  "index.php",
  "logout.php",
  "login_verify.php"
]);

if(enable_session_timeout == 1 && !blockedScripts.has(currentScript) && document.location.href.indexOf(".php") > -1) {
  var popup = document.querySelector(".sessionTimeoutPopup");
  var popupOverlay = document.querySelector(".popupOverlay");
  var popupTimer = 60;
  var popupSecondsLeft = document.getElementById("seconds-left");
  var session_timeout_period = "<?php echo $session_timeout_settings[1] ?>";
      session_timeout_period = session_timeout_period * 60;

  // begin helper functions
  function createNewTimestamp () {
    localStorage.setItem("lastActivityTimestamp", new Date().getTime());
  }

  function showPopup() {
    popup.style.display = "block";
    popupOverlay.style.display = "block";
  }

  function hidePopup() {
    popup.style.display = "none";
    popupOverlay.style.display = "none";
  }

  function updatePopupTimer() {
    popupTimer--;
    popupSecondsLeft.innerText = popupTimer;
  }

  function resetPopupTimer() {
    popupTimer = 60;
    popupSecondsLeft.innerHTML = popupTimer;
  }

  function saveUsersWorkThenLogOut() {
    if(document.location.href.indexOf("view.php") > -1) {
      document.querySelector("#coming_from_session_timeout").value = "true";
      document.querySelector("#element_resume_checkbox").checked = true;
      document.querySelector("#element_resume_email").value = <?php echo $user_email[0] ?>;
      document.querySelector("#button_save_form").click();
      // logout is handled by view.php
    }
  }

  function logout() {
    if(document.querySelector("#element_resume_checkbox")) { // if save and resume later is enabled
      saveUsersWorkThenLogOut();
    } else { // just logout
      window.onbeforeunload = null; // prevents "Leave site? Changes you made might not be saved" popup
      document.location.href = "logout.php";
    }
  }

  function isSessionTimeoutPeriodExceeded() {
    if(Math.trunc((new Date().getTime() - parseInt(localStorage.getItem("lastActivityTimestamp"))) / 1000) > session_timeout_period){
      return true;
    } else {
      return false;
    }
  }
  // end helper functions

  // main session timeout logic
  function mainSessionTimout() {
    if(popup.style.display == "block") { // if popup is open
      updatePopupTimer();
    } else {
      resetPopupTimer();
    }

    if(isSessionTimeoutPeriodExceeded() == true) {
      showPopup();
    } else {
      hidePopup();
    }

    if(popupTimer < 1) {
      logout();
    }
  }

  // event listeners to capture user actions
  document.addEventListener("click", function(e) {
    if(e.target.id === "stayLoggedInButn") { // reset
      resetPopupTimer();
      hidePopup();
    }
    createNewTimestamp();
  });

  document.addEventListener("keypress", function() {
    createNewTimestamp();
  });

  if(currentScript.indexOf("?") > -1) { // drop any query
    currentScript = currentScript.split("?")[0];
  }

  setInterval(mainSessionTimout, 1000);
  createNewTimestamp(); // create initial timestamp on page load
}
</script>
