var API_Endpoint;
var path = "itam-shared/api/fetch-entity-search-suggestions.php?searchTerm=";
if(document.location.href.indexOf("portal/") > -1) {
  API_Endpoint = document.location.href.split("portal/")[0] + path;
}
if(document.location.href.indexOf("auditprotocol/") > -1) {
  API_Endpoint = document.location.href.split("auditprotocol/")[0] + path;
}

// clear currently displayed search suggestions 
function clearResults () { 
  document.getElementById('resultsContainer').innerHTML = ""; 
} 
  
// display returned search suggestions
function showResults(data) {
  var numberOfResults = data.split('class="result"').length - 1;
  if (numberOfResults && numberOfResults == 1) {
    var wrap = document.createElement("ul");
    wrap.classList.add("resultList");
    wrap.innerHTML = data;
    document.getElementById('resultsContainer').appendChild(wrap);

    // if input field matches the only result, auto click the only result
    var inputFieldValue = document.querySelector("#company_name").value.trim().toLowerCase();
    var resultValue     = document.querySelector(".result").innerText.trim().toLowerCase();
    if(inputFieldValue == resultValue) {
      document.querySelector(".result").click();
      document.querySelector("#company_name").blur();
    }
  } else {
    var wrap = document.createElement("ul");
    wrap.classList.add("resultList");
    wrap.innerHTML = data;
    document.getElementById('resultsContainer').appendChild(wrap);
  }
}
  
// make request on keyup 
document.getElementById('company_name').onkeyup = function() { 
  var input = this.value.replace(/^\s|\s $/, "");
  if (input.length > 1) {
    searchForData(input); 
  }
} 
  
// logic for requesting results matching search input from database 
function searchForData(value) { 
  var ajax = null; // for abort previous requests 
  if (ajax && typeof ajax.abort === 'function') { // abort previous requests 
    ajax.abort(); 
  } 
  ajax = new XMLHttpRequest(); 
  ajax.onreadystatechange = function() { 
    if (this.readyState === 4 && this.status === 200) { 
      try { 
        var data = this.responseText;
      } catch (e) { 
        console.log(e); 
        return; 
      }
      clearResults();
      showResults(data); 
    } 
  } 
  ajax.open('GET', API_Endpoint + value); 
  ajax.send(); 
} 
  
// on entity clicked, set values 
document.addEventListener("click", function(e){ 
  if(e.target.classList.contains("result")) { 
    document.getElementById("company_name").value         = e.target.innerText; 
    document.getElementById("client_id").value            = e.target.getAttribute('data-client-id'); 
    document.getElementById("resultsContainer").innerHTML = ""; 
  } 
});
