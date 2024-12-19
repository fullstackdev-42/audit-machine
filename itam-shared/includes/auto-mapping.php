<?php 

?>

<style>
#auto-mapping-popup-background {
    background-color: black;
    height: 100vh;
    width: 100vw;
    position: fixed;
    z-index: 9998;
    opacity: 0.7;
}
#auto-mapping-popup {
    opacity: 1;
    z-index: 9999;
    position: fixed;
    color: black !important;
    top: 50%;
    left: 50%;
    -moz-transform: translateX(-50%) translateY(-50%);
    -webkit-transform: translateX(-50%) translateY(-50%);
    transform: translateX(-50%) translateY(-50%);
    height: 60%;
    width: 50%;
    margin: auto;
    background-color: white;
    border: 2px solid black;
}
#auto-mapping-popup-title {
    color: black;
    text-align: center;
    font-size: 2em;
    font-weight: bold;
    margin-top: 10px;
}
#auto-mapping-popup-close-button {
    top: 0px;
    right: 0px;
    position: absolute;
    margin-top: 10px;
    margin-right: 10px;
    font-weight: bold;
    font-size: 20px;
    cursor: pointer;
}
#auto-mapping-progress-bar-title {
    text-align: center;
    margin-top: 10px;
}
#auto-mapping-progress-bar {
    text-align: center;
    height: 20px;
    width: 75%;
    background-color: green;
    margin: auto;
    border: 1px solid black;
    margin-top: 5px;
    margin-bottom: 20px;
}
#auto-mapping-popup-sub-title {
    text-align: center;
    margin-bottom: 20px;
}
#eligible-fields-label {
    /* text-align: center; */
    width: 75%;
    margin: auto;
}
#disable-auto-mappign-instructions {
    position: fixed;
    text-align: center;
    bottom: 0px;
    color: black;
    width: 100%;
    margin-bottom: 5px;
}
#fields-eligible-to-be-auto-mapped {
    height: 50%;
    width: 75%;
    margin: auto;
    border: 1px solid black;
    overflow-y: scroll;
}
.eligible-field {
    width: 100%;
    height: 20px;
    border-bottom: 1px solid black;
}
#select-all-eligible-fields-contianer {
    width: 75%;
    margin: auto;
}
#progress {
    display: none;
}
#begin-auto-mapping-button-container {
    width: 100%;
    height: 50px;
}
#begin-auto-mapping-button {
    margin: auto;
    margin-top: 20px;
    height: 30px;
    width: 250px;
    border: 1px solid black;
    background-color: green;
    border-radius: 5px;
    text-align: center;
    cursor: pointer;
    padding-top: 10px;
    color: black;
    font-weight: bold;
}
#begin-auto-mapping-button:hover {
    background-color: #00af00;
}
</style>

<div id="auto-mapping-popup-background"></div>
<div id="auto-mapping-popup">
    <div id="auto-mapping-popup-close-button" onclick="closeAutoMappingPopup()">X</div>
    <div id="auto-mapping-popup-title">Auto-Mapping Has Been Triggered.</div>
    <div id="auto-mapping-popup-sub-title">Please select the fields you want to auto-map.</div>
    <div id="progress">
        <div id="auto-mapping-progress-bar-title">Loading:</div>
        <div id="auto-mapping-progress-bar">Progress Bar Goes Here</div>
    </div>
    <!-- <div id="eligible-fields-label">The fields available for auto-mapping are:</div> -->
    <div id="select-all-eligible-fields-contianer">
        <input type="checkbox" class="select-all-eligible-field" onclick="selectAllEligibleFields()">
        <span>Select All</span>
    </div>
    <ul id="fields-eligible-to-be-auto-mapped">
        <li class="eligible-field">
            <input type="checkbox" class="field-checkbox">
            <span class="field-policy-machine-code">Policy Machine Code: 14231</span>
            <span> - </span>
            <span class="field-policy-machine-code">Field: First Name</span>
            <span> - </span>
            <span>Input: Michael</span>
        </li>
        <li class="eligible-field">
            <input type="checkbox" class="field-checkbox">
            <span class="field-policy-machine-code">Policy Machine Code: 14231</span>
            <span> - </span>
            <span class="field-policy-machine-code">Field: First Name</span>
            <span> - </span>
            <span>Input: Michael</span>
        </li>
        <li class="eligible-field">
            <input type="checkbox" class="field-checkbox">
            <span class="field-policy-machine-code">Policy Machine Code: 14231</span>
            <span> - </span>
            <span class="field-policy-machine-code">Field: First Name</span>
            <span> - </span>
            <span>Input: Michael</span>
        </li>
        <li class="eligible-field">
            <input type="checkbox" class="field-checkbox">
            <span class="field-policy-machine-code">Policy Machine Code: 14231</span>
            <span> - </span>
            <span class="field-policy-machine-code">Field: First Name</span>
            <span> - </span>
            <span>Input: Michael</span>
        </li>
        <li class="eligible-field">
            <input type="checkbox" class="field-checkbox">
            <span class="field-policy-machine-code">Policy Machine Code: 14231</span>
            <span> - </span>
            <span class="field-policy-machine-code">Field: First Name</span>
            <span> - </span>
            <span>Input: Michael</span>
        </li>
        <li class="eligible-field">
            <input type="checkbox" class="field-checkbox">
            <span class="field-policy-machine-code">Policy Machine Code: 14231</span>
            <span> - </span>
            <span class="field-policy-machine-code">Field: First Name</span>
            <span> - </span>
            <span>Input: Michael</span>
        </li>
        <li class="eligible-field">
            <input type="checkbox" class="field-checkbox">
            <span class="field-policy-machine-code">Policy Machine Code: 14231</span>
            <span> - </span>
            <span class="field-policy-machine-code">Field: First Name</span>
            <span> - </span>
            <span>Input: Michael</span>
        </li>
        <li class="eligible-field">
            <input type="checkbox" class="field-checkbox">
            <span class="field-policy-machine-code">Policy Machine Code: 14231</span>
            <span> - </span>
            <span class="field-policy-machine-code">Field: First Name</span>
            <span> - </span>
            <span>Input: Michael</span>
        </li>
        <li class="eligible-field">
            <input type="checkbox" class="field-checkbox">
            <span class="field-policy-machine-code">Policy Machine Code: 14231</span>
            <span> - </span>
            <span class="field-policy-machine-code">Field: First Name</span>
            <span> - </span>
            <span>Input: Michael</span>
        </li>
        <li class="eligible-field">
            <input type="checkbox" class="field-checkbox">
            <span class="field-policy-machine-code">Policy Machine Code: 14231</span>
            <span> - </span>
            <span class="field-policy-machine-code">Field: First Name</span>
            <span> - </span>
            <span>Input: Michael</span>
        </li>
        <li class="eligible-field">
            <input type="checkbox" class="field-checkbox">
            <span class="field-policy-machine-code">Policy Machine Code: 14231</span>
            <span> - </span>
            <span class="field-policy-machine-code">Field: First Name</span>
            <span> - </span>
            <span>Input: Michael</span>
        </li>
        <li class="eligible-field">
            <input type="checkbox" class="field-checkbox">
            <span class="field-policy-machine-code">Policy Machine Code: 14231</span>
            <span> - </span>
            <span class="field-policy-machine-code">Field: First Name</span>
            <span> - </span>
            <span>Input: Michael</span>
        </li>
        <li class="eligible-field">
            <input type="checkbox" class="field-checkbox">
            <span class="field-policy-machine-code">Policy Machine Code: 14231</span>
            <span> - </span>
            <span class="field-policy-machine-code">Field: First Name</span>
            <span> - </span>
            <span>Input: Michael</span>
        </li>
        <li class="eligible-field">
            <input type="checkbox" class="field-checkbox">
            <span class="field-policy-machine-code">Policy Machine Code: 14231</span>
            <span> - </span>
            <span class="field-policy-machine-code">Field: First Name</span>
            <span> - </span>
            <span>Input: Michael</span>
        </li>
        <li class="eligible-field">
            <input type="checkbox" class="field-checkbox">
            <span class="field-policy-machine-code">Policy Machine Code: 14231</span>
            <span> - </span>
            <span class="field-policy-machine-code">Field: First Name</span>
            <span> - </span>
            <span>Input: Michael</span>
        </li>
        <li class="eligible-field">
            <input type="checkbox" class="field-checkbox">
            <span class="field-policy-machine-code">Policy Machine Code: 14231</span>
            <span> - </span>
            <span class="field-policy-machine-code">Field: First Name</span>
            <span> - </span>
            <span>Input: Michael</span>
        </li>
        <li class="eligible-field">
            <input type="checkbox" class="field-checkbox">
            <span class="field-policy-machine-code">Policy Machine Code: 14231</span>
            <span> - </span>
            <span class="field-policy-machine-code">Field: First Name</span>
            <span> - </span>
            <span>Input: Michael</span>
        </li>
        <li class="eligible-field">
            <input type="checkbox" class="field-checkbox">
            <span class="field-policy-machine-code">Policy Machine Code: 14231</span>
            <span> - </span>
            <span class="field-policy-machine-code">Field: First Name</span>
            <span> - </span>
            <span>Input: Michael</span>
        </li>
        <li class="eligible-field">
            <input type="checkbox" class="field-checkbox">
            <span class="field-policy-machine-code">Policy Machine Code: 14231</span>
            <span> - </span>
            <span class="field-policy-machine-code">Field: First Name</span>
            <span> - </span>
            <span>Input: Michael</span>
        </li>
        <li class="eligible-field">
            <input type="checkbox" class="field-checkbox">
            <span class="field-policy-machine-code">Policy Machine Code: 14231</span>
            <span> - </span>
            <span class="field-policy-machine-code">Field: First Name</span>
            <span> - </span>
            <span>Input: Michael</span>
        </li>
        <li class="eligible-field">
            <input type="checkbox" class="field-checkbox">
            <span class="field-policy-machine-code">Policy Machine Code: 14231</span>
            <span> - </span>
            <span class="field-policy-machine-code">Field: First Name</span>
            <span> - </span>
            <span>Input: Michael</span>
        </li>
    </ul>
    <div id="begin-auto-mapping-button-container">
        <div id="begin-auto-mapping-button" onclick="beginAutoMapping()">Begin Auto-Mapping</div>
    </div>
    <div id="disable-auto-mappign-instructions">You can disable auto-mapping from the settings page.</div>
</div>

<script>
function closeAutoMappingPopup() {
    console.log("test");
    document.querySelector("#auto-mapping-popup-background").style.display = "none";
    document.querySelector("#auto-mapping-popup").style.display = "none";
}

function selectAllEligibleFields() {
    console.log("click");
    var allCheckboxes = document.querySelectorAll(".field-checkbox");
    for(var i=0; i<allCheckboxes.length; i++) {
        allCheckboxes[i].click();
    }
}

function beginAutoMapping() {
    alert("begin auto mapping button was clicked");
}
</script>