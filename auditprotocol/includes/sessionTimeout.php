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
function getSessionTimeoutSettings() { // pull session timeout settings from MySQL
  $dbh = new PDO('mysql:host='.LA_DB_HOST.';dbname='.LA_DB_NAME.'',''.LA_DB_USER.'',''.LA_DB_PASSWORD.'');
  foreach($dbh->query('SELECT enable_session_timeout, session_timeout_period from ap_settings') as $result) {
    return $result;
  }
}
$session_timeout_settings = getSessionTimeoutSettings();
?>

<script>
var blockedScripts = {
  "download.php": "download.php",
  "embed_code_ajax.php": "embed_code_ajax.php",
  "index.php": "index.php",
  "logout.php": "logout.php",
  "login_verify.php": "login_verify.php"
};
var currentScript = document.location.href.split("auditprotocol/")[1];
var enable_session_timeout = "<?php echo $session_timeout_settings[0] ?>";

if(enable_session_timeout == 1 && !blockedScripts[currentScript] && document.location.href.indexOf(".php") > -1) {

  var popup = document.querySelector(".sessionTimeoutPopup");
  var popupOverlay = document.querySelector(".popupOverlay");
  var popupTimer = 60;
  var popupSecondsLeft = document.getElementById("seconds-left");
  var session_timeout_period = "<?php echo $session_timeout_settings[1] ?>";
  session_timeout_period = session_timeout_period * 60;

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

  function logout () {
    document.location.href = "logout.php";
  }

  function isSessionTimeoutPeriodExceeded() {
    if(Math.trunc((new Date().getTime() - parseInt(localStorage.getItem("lastActivityTimestamp"))) / 1000) > session_timeout_period){
      return true;
    } else {
      return false;
    }
  }

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
      createNewTimestamp();
      resetPopupTimer();
      hidePopup();
    }
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
