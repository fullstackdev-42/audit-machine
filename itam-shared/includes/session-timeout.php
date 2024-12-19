<?php
// PHP for:
// resetting the php server session expiration
// getting the session-timeout settings from the database 
// getting the user's email address

if($_REQUEST['refresh_server_session'] == true) {
  session_start();
  echo "session-timeout: user action detected - php server session was refreshed (to keep alive) - session id = ";
  echo session_id();
  die();
}

include_once $_SERVER['DOCUMENT_ROOT']."/auditprotocol/config.php";

/* 
get the settings for session timeout from the database
enable_session_timeout = 1 || 0
session_timeout_period = number (e.g. 30) for how many minutes you want to time out after
*/
function getSessionTimeoutSettings() {
  $dbh = new PDO('mysql:host='.LA_DB_HOST.';dbname='.LA_DB_NAME.'',''.LA_DB_USER.'',''.LA_DB_PASSWORD.'');
  foreach($dbh->query('SELECT enable_session_timeout, session_timeout_period from ap_settings') as $result) {
    return $result;
  }
}

/*
Get the user's email so that we can use it for auto-saving before logging them out
(on view.php or view_entry.php).
This is done by populating the save and resume later field with the user's email
and simulating a button click on the "save and resume later" button. Once completed, proceed with the logout 
*/
function getUserEmail() {
  $dbh = new PDO('mysql:host='.LA_DB_HOST.';dbname='.LA_DB_NAME.'',''.LA_DB_USER.'',''.LA_DB_PASSWORD.'');

  // the variable for the user's email is different depending on if we are on auditprotocol or portal
  if (strrpos($_SERVER['PHP_SELF'], "auditprotocol")) {
    $user_id = $_SESSION['la_user_id'];
    if(!empty($user_id)) {
      foreach($dbh->query("SELECT user_email from ap_users where `user_id` = $user_id") as $result) {
        return $result[0];
      }
    }
  }
  
  if (strrpos($_SERVER['PHP_SELF'], "portal")) {
    $user_id = $_SESSION['la_client_client_id'];
    if(!empty($user_id)) {
      foreach($dbh->query("SELECT email from ap_ask_client_users where client_id = $user_id") as $result) {
        return $result[0];
      }
    }
  }
}

// store in variables for easy access
$session_timeout_settings = getSessionTimeoutSettings();
$user_email               = getUserEmail();
?>

<!-- BEGIN HTML FOR THE SESSION-TIMEOUT WARNING POPUP -->
<div id="sessionTimeoutPopupBackground"></div>
<div id="sessionTimeoutPopup">
    <span id="sessionTimeoutTitle">Warning! You will be logged out for inactivity in:</span>
    <div id="countdown-timer">
      <span id="seconds-left">60</span>
      <br />
      <span id="seconds">seconds.</span>
    </div>
    <br />
    <div id="sessionTimeoutButtonContainer">
      <button id="stayLoggedInButton">Keep me logged in.</button>
    </div>
</div>
<!-- END HTML FOR THE SESSION-TIMEOUT WARNING POPUP -->

<!-- BEGIN CSS FOR THE SESSION-TIMEOUT WARNING POPUP -->
<style>
#sessionTimeoutPopupBackground {
  display: none;
  height: 100vh;
  width: 100vw;
  z-index: 10001;
  background-color: #333;
  opacity: 0.5;
  position: fixed;
}
#sessionTimeoutPopup {
  display: none;
  font-weight: bold;
  text-align: center;
  z-index: 11000;
  padding-top: 25px;
  background-color: white;
  border: 10px solid #1878AB;
  border-radius: 5px;
  position: fixed;
  height: 300px;
  width: 400px;
  top: 40%;
  left: 50%;
  -ms-transform: translate(-50%, -25%);
  transform: translate(-50%, -25%);
}
#sessionTimeoutTitle {
  color: red;
  font-size: 25px;
  text-align: center;
}
#countdown-timer {
  margin-top: 10px;
  color: black;
}
#seconds-left {
  font-size: 50px;
  text-align: center;
}
#seconds {
  text-align: center;
  font-size: 35px;
}
#stayLoggedInButtonContainer {
  width: 100%;
}
#stayLoggedInButton {
  margin: auto;
  margin-bottom: 20px;
  padding: 11px;
  border-radius: 5px;
  background-color: #25C18A;
  border: none;
  color: white;
  cursor: pointer;
}
#stayLoggedInButton:hover {
  background-color: #3be4a9;
}
.dropui, .dropui-tab {
  z-index: 0;
}
</style>
<!-- END CSS FOR THE SESSION-TIMEOUT WARNING POPUP -->

<script>
// this script overwrites setInterval, setTimeout, with ones that wont be slowed down in background tabs
(function () {
  var $momentum;

  function createWorker() {
    var containerFunction = function () {
      var idMap = {};
        
        self.onmessage = function (e) {
          if (e.data.type === 'setInterval') {
            idMap[e.data.id] = setInterval(function () {
              self.postMessage({
                type: 'fire',
                id: e.data.id
              });
            }, e.data.delay);
          } else if (e.data.type === 'clearInterval') {
                    clearInterval(idMap[e.data.id]);
                    delete idMap[e.data.id];
                  } else if (e.data.type === 'setTimeout') {
                    idMap[e.data.id] = setTimeout(function () {
                      self.postMessage({
                        type: 'fire',
                        id: e.data.id
                      });
                        // remove reference to this timeout after is finished
                        delete idMap[e.data.id];
                      }, e.data.delay);
                    } else if (e.data.type === 'clearCallback') {
                      clearTimeout(idMap[e.data.id]);
                      delete idMap[e.data.id];
                    }
                  };
                };
                
                return new Worker(URL.createObjectURL(new Blob([
            '(',
            containerFunction.toString(),
            ')();'
          ], {type: 'application/javascript'})));
        }
        
        $momentum = {
          worker: createWorker(),
          idToCallback: {},
            currentId: 0
          };
          
          function generateId() {
            return $momentum.currentId++;
          }

          function patchedSetInterval(callback, delay) {
            var intervalId = generateId();
            
            $momentum.idToCallback[intervalId] = callback;
            $momentum.worker.postMessage({
              type: 'setInterval',
              delay: delay,
              id: intervalId
            });
            return intervalId;
          }
          
          function patchedClearInterval(intervalId) {
            $momentum.worker.postMessage({
              type: 'clearInterval',
              id: intervalId
        });
        
        delete $momentum.idToCallback[intervalId];
    }
    
    function patchedSetTimeout(callback, delay) {
      var intervalId = generateId();
      
      $momentum.idToCallback[intervalId] = function () {
        callback();
        delete $momentum.idToCallback[intervalId];
      };
      
      $momentum.worker.postMessage({
        type: 'setTimeout',
        delay: delay,
        id: intervalId
      });
      return intervalId;
    }
    
    function patchedClearTimeout(intervalId) {
      $momentum.worker.postMessage({
        type: 'clearInterval',
        id: intervalId
      });
      
      delete $momentum.idToCallback[intervalId];
    }
    
    $momentum.worker.onmessage = function (e) {
      if (e.data.type === 'fire') {
        $momentum.idToCallback[e.data.id]();
      }
    };
    
    window.$momentum     = $momentum;
    window.setInterval   = patchedSetInterval;
    window.clearInterval = patchedClearInterval;
    window.setTimeout    = patchedSetTimeout;
    window.clearTimeout  = patchedClearTimeout;
})();
</script>

<script>
var enable_session_timeout = "<?php echo $session_timeout_settings[0] ?>";
var currentURL             = document.location.href;
var currentScript          = "";
var blockedScripts = [
  "index.php",
  "download.php",
  "embed_code_ajax.php",
  "logout.php",
  "client_logout.php",
  "login_verify.php",
  "login_tsv_setup.php",
  "login-using-code.php"
];

// we need to capture the name of the current script so we can compare against blocked scripts
if(currentURL.indexOf("portal/") > -1) {
  currentScript = document.location.href.split("portal/")[1];
}
if(currentURL.indexOf("auditprotocol/") > -1) {
  currentScript = document.location.href.split("auditprotocol/")[1];
}

if(currentScript.indexOf("?") > -1) { // drop any params
  currentScript = currentScript.split("?")[0];
}
if(currentScript.indexOf(".php") == -1) { // index doesn't always display index.php, sometimes just shows auditprotocol/
  currentScript = "index.php";
}


if(enable_session_timeout == 1 && !blockedScripts.includes(currentScript)) {
  console.log("session-timeout is enabled on this page");
  console.log("session-timeout limit is set to <?php echo $session_timeout_settings[1] ?> minute(s).");

  var originalTitle        = document.title;
  var popup                = document.querySelector("#sessionTimeoutPopup");
  var popupOverlay         = document.querySelector("#sessionTimeoutPopupBackground");
  var popupSecondsLeft     = document.querySelector("#seconds-left");
  var popupTimer           = 60;
  var sessionTimeoutPeriod = "<?php echo $session_timeout_settings[1] ?>";
      sessionTimeoutPeriod = sessionTimeoutPeriod * 60;

  function showPopup() {
    // temporary fix until view.php sphagetti css is addressed
    if(currentURL.indexOf("view.php") > -1 || currentURL.indexOf("edit_entry.php") > -1) {
      document.querySelector("#sessionTimeoutPopupBackground").style.background = "white";
    }

    // make visible
    popupOverlay.style.display = "block";
    popup.style.display        = "block";
  }

  function hidePopup() {
    // make hidden
    popupOverlay.style.display = "none";
    popup.style.display        = "none";
    
    resetPopupTimer();
  }

  function updatePopupTimer() {
    popupTimer = popupTimer - 1;
    if(popupTimer < 1) {
      popupTimer = 0; // prevent popupTimer from going into the negatives
    }
    popupSecondsLeft.innerText = popupTimer;
  }

  function resetPopupTimer() {
    popupTimer                 = 60;
    popupSecondsLeft.innerHTML = popupTimer;
  }

  function createNewTimestamp () {
    localStorage.setItem("lastActivityTimestamp", new Date().getTime());

    // make an ajax call to the server to reset the PHP session expiration as well
    var API_Endpoint;
    var path = "itam-shared/includes/session-timeout.php?refresh_server_session=true";
    if(document.location.href.indexOf("portal/") > -1) {
      API_Endpoint = document.location.href.split("portal/")[0] + path;
    }
    if(document.location.href.indexOf("auditprotocol/") > -1) {
      API_Endpoint = document.location.href.split("auditprotocol/")[0] + path;
    }

    var ajax = null; // for abort previous requests 
    if (ajax && typeof ajax.abort === 'function') { // abort previous requests 
      ajax.abort(); 
    }
    ajax = new XMLHttpRequest(); 
    ajax.onreadystatechange = function() { 
      if (this.readyState === 4 && this.status === 200) { 
        console.log(this.responseText); 
      } 
    } 
    ajax.open('GET', API_Endpoint); 
    ajax.send();
  }

  function saveUsersWorkThenLogOut() {
    popupTimer           = 999999; // this just a way to stop the auto save from triggering
    sessionTimeoutPeriod = 999999; // again while an auto-save is still loading from a previous call
    if(document.querySelector("#element_resume_checkbox")){
      document.querySelector("#element_resume_checkbox").click();
      document.querySelector("#element_resume_email").value = "<?php echo $user_email ?>";
      document.querySelector("#coming_from_session_timeout").value = "true";
      document.querySelector("#button_save_form").click();
    } else if(document.querySelector("#submit_form")) {
      document.querySelector("#submit_form").click();
    } else if(document.querySelector("#submit_primary")) {
      document.querySelector("#submit_primary").click();
    }
    
    localStorage.setItem("auto-save-then-logout", true);
    // logout is handled by view.php or edit_entry.php
  }

  function logoutNormal() {
    window.onbeforeunload  = null; // prevents "Leave site? Changes you made might not be saved" popup
    var current_uri = window.location.href;
    if(current_uri.includes("portal")){
      document.location.href = "/portal/client_logout.php";
    }
    if(current_uri.includes("auditprotocol")){
      document.location.href = "/auditprotocol/logout.php";
    }
    window.onbeforeunload  = null; // prevents "Leave site? Changes you made might not be saved." popup
  }

  function updateTitleBarTimer(secondsLeftUntilTimeout) {
    if(secondsLeftUntilTimeout > 0) {
      function formatMinutesSeconds(s){return(s-(s%=60))/60+(9<s?':':':0')+s}
      document.title = formatMinutesSeconds(secondsLeftUntilTimeout) + " - " + originalTitle;
    } else {
      document.title = originalTitle;
    }
  }

  function isSessionTimeoutPeriodExceeded() {
    var secondsSinceLastActivity = Math.trunc((new Date().getTime() - parseInt(localStorage.getItem("lastActivityTimestamp"))) / 1000);

    var secondsLeftUntilTimeout = sessionTimeoutPeriod - secondsSinceLastActivity;
    updateTitleBarTimer(secondsLeftUntilTimeout);

    if(secondsSinceLastActivity > (sessionTimeoutPeriod - 61)){ // -61 so that popup shows when one minute is left, rather than when 0 is left
      return true;
    } else {
      return false;
    }
  }

  /************************************/
  /* BEGIN MAIN SESSION-TIMEOUT LOGIC */
  /************************************/
  function mainSessionTimout() {
    if(popup.style.display === "block") { // if popup is being shown, update timer inside popup
      updatePopupTimer();
    }
    
    if(isSessionTimeoutPeriodExceeded() === true) {
      showPopup();
    } else {
      hidePopup();
    }

    if(popupTimer < 1) {
      if(currentScript == "view.php" || currentScript == "edit_entry.php") {
        saveUsersWorkThenLogOut();
      } else { // just logout without saving
        logoutNormal();
      }
    }
  }
  /************************************/
  /*  END MAIN SESSION-TIMEOUT LOGIC  */
  /************************************/

  /***********************************************/
  /*  BEGIN EVENT LISTENERS FOR USER ACTIVITIES  */
  /***********************************************/
  document.addEventListener("click", function(e) {
    createNewTimestamp(); // on any click

    if(e.target.id == "stayLoggedInButton") { // only if click is on the "stay logged in" button
      resetPopupTimer();
      hidePopup();
    }
  });

  document.addEventListener("keypress", function() {
    createNewTimestamp(); // on any keypress
  });
  /***********************************************/
  /*   END EVENT LISTENERS FOR USER ACTIVITIES   */
  /***********************************************/

  createNewTimestamp(); // create initial timestamp on page load
  setInterval(mainSessionTimout, 1000); // update every second

  console.log("The user's email = <?php echo $user_email ?> (for auto-save feature)");

  // begin - temporary fix for view.php's CSS until view.php's CSS is cleaned up
  // since right now it's spaghetti code and it's overwriting the CSS properties
  if(currentURL.indexOf("view.php") > -1) {
    document.querySelector("#sessionTimeoutPopupBackground").style.display = "none";
    document.querySelector("#sessionTimeoutPopup").style.height            = "250px";
    document.querySelector("#sessionTimeoutPopup").style.width             = "440px";
  }
  // end - fix for view.php css

} else {
  console.log("session-timeout is disabled on this page.");
}
</script>
