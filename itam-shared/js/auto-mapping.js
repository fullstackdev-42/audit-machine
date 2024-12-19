var lockedViaThisPage;
var form_id;
window.addEventListener('DOMContentLoaded', () => {

	var hrefObj        = new URL(document.location.href);
    var pathName        = hrefObj.pathname;
    if( pathName.indexOf('edit_entry.php') != -1 ){
        form_id = hrefObj.searchParams.get("form_id");
    } else {
      	form_id        = hrefObj.searchParams.get("id");
    }

	// var entity_id      = document.querySelector("#form_" + form_id).getAttribute("entity_id");
	var entity_id      = $("#form_" + form_id).data("selected_entity_id");
	var ajax           = new XMLHttpRequest();
	var API_Endpoint   = document.location.href.indexOf("/portal") > -1 ? document.location.href.split("/portal")[0] + "/itam-shared/api/auto-mapping-api.php" : document.location.href.split("/auditprotocol")[0] + "/itam-shared/api/auto-mapping-api.php";
	var disabledFields = [];
	var thisPageElementMachineCodes = getElementMachineCodesOnThisPage();

	(function checkIfAutoMappingIsEnabled () {
		var getSettingsTimer = performance.now();
		// check if auto-mapping is enabled or disabled in main_settings.php
		ajax.open("POST", API_Endpoint, true);
		ajax.setRequestHeader('Content-Type', 'application/json');
		ajax.onreadystatechange = function() {
			if (this.readyState === 4 && this.status === 200) {
				var response = parseResponse(this.responseText);
				if(response.indexOf("auto-mapping is enabled") > -1) {
					console.log("get auto-mapping settings (enabled/disabled) took " + (performance.now() - getSettingsTimer) + " milliseconds.");
					console.log(parseResponse(this.responseText));
					window.autoMapping = "enabled";
					if(document.location.href.indexOf("/portal") > -1) {
						prefillFieldValues();
					}
					lockOrUnlockFields();
					addCustomSubmitHandler();
				} else {
					console.log(parseResponse(this.responseText));
					window.autoMapping = "disabled";
				}
			}
		}
		ajax.send(JSON.stringify({
			form_id: form_id,
			entity_id: entity_id,
			task: "get auto-mapping settings"
		}));
	})();

	function prefillFieldValues () {
		var prefillTimer = performance.now();
		// get the values for each field with a machine code so we can pre-populate the field
		ajax.open("POST", API_Endpoint, true);
		ajax.setRequestHeader('Content-Type', 'application/json');
		ajax.onreadystatechange = function() {
			if (this.readyState === 4 && this.status === 200) {
				var response = parseResponse(this.responseText);
				console.log("prefillFieldValues =", response);
				if(typeof response === 'object') {
					for (var key in response){
						prefillValue(response[key]);
					}
					console.log("prefill values took " + (performance.now() - prefillTimer) + " milliseconds.");
				}
			}
		}
		ajax.send(JSON.stringify({
			form_id: form_id,
			entity_id: entity_id,
			elementMachineCodesOnThisPage: thisPageElementMachineCodes,
			task: "prefillFieldValues"
		}));
	}

	function addCustomSubmitHandler () {
		document.querySelector(".itauditm").addEventListener("submit", function(event) {
			if(window.autoMappingResume !== true) {
				event.preventDefault();
				console.log("js-submit handler was triggered");
				autoMappingMain();
			}
		});
		
		document.querySelector("#li_buttons").addEventListener("click", function(e) {
			if(e.target.id == "button_save_form") {
				console.log("js-submit handler was triggered");
				window.autoMappingResume = true;
				autoMappingMain();
			}
		});
	}

	function getElementMachineCodesOnThisPage () {
		var getElementMachineCodesOnThisPageTimer = performance.now();

		var elementMachineCodesOnThisPage = [];
		var elements                      = document.querySelectorAll(".element");
		var inputs                        = document.querySelectorAll("input");

		for(var i=0; i<elements.length; i++) {
			var elementMachineCode = elements[i].getAttribute("element_machine_code");
			if(elementMachineCodesOnThisPage.includes(elementMachineCode) == false && elementMachineCode != null && elementMachineCode != "") {
				elementMachineCodesOnThisPage.push(elementMachineCode);
			}
		}

		for(var i=0; i<inputs.length; i++) {
			var elementMachineCode = inputs[i].getAttribute("element_machine_code");
			if(elementMachineCodesOnThisPage.includes(elementMachineCode) == false && elementMachineCode != null && elementMachineCode != "") {
				elementMachineCodesOnThisPage.push(elementMachineCode);
			}
		}

		console.log("get element machine codes on this page took " + (performance.now() - getElementMachineCodesOnThisPageTimer) + " milliseconds.");
		return elementMachineCodesOnThisPage;
	}

	function autoMappingMain () {
		var autoMappingMainTimer = performance.now();
		console.log("auto mapping main triggered");
		// capture inputs and send them to the api for processing
		var mostElements                    = document.querySelectorAll('*[id^="element_"]'); // get all elements where the id starts with "element_"
		var radioButtonElements             = document.querySelectorAll("input[type=radio]");
		var checkboxElements                = document.querySelectorAll("input[type=checkbox]");
		var paragraphElements               = document.querySelectorAll(".textarea-formatting");
		// var filenames                       = document.querySelectorAll(".filename");
		var matrixFields                    = document.querySelectorAll(".matrix input");
		var typeOfMatrixFields              = {};
		var thisFormsElementsAndTheirValues = {};

		for(var i=0; i<mostElements.length; i++) {
			thisFormsElementsAndTheirValues[mostElements[i].id] = mostElements[i].value; // doesn't seem to capture all values
		}
		
		for(var i=0; i<radioButtonElements.length; i++) {
			thisFormsElementsAndTheirValues[radioButtonElements[i].id] = radioButtonElements[i].checked; // doesn't seem to capture all values
		}
		
		for(var i=0; i<checkboxElements.length; i++) {
			thisFormsElementsAndTheirValues[checkboxElements[i].id] = checkboxElements[i].checked;
		}
		
		for(var i=0; i<paragraphElements.length; i++) {
			thisFormsElementsAndTheirValues[paragraphElements[i].id] = editors[i].getData();
		}
		
		/*for(var i=0; i<filenames.length; i++) {
			var filename                                = filenames[i].innerText;
			var element_id                              = filenames[i].closest(".file_queue").id.split("_queue")[0];
			var selector                                = "#" + element_id + "_token"; 
			var element_token                           = filenames[i].closest(".file_queue").parentElement.querySelector(selector).value;
			var currentValue                            = thisFormsElementsAndTheirValues[element_id];
			var newValue                                = currentValue + element_id + "_" + element_token + "-" + filename + " ";
			thisFormsElementsAndTheirValues[element_id] = newValue;
		}*/

		for(var i=0; i<matrixFields.length; i++) {
			var element_id                 = matrixFields[i].id;
			var element_type               = matrixFields[i].type;
			typeOfMatrixFields[element_id] = element_type;
		}
	
		ajax.open("POST", API_Endpoint, true);
		ajax.setRequestHeader('Content-Type', 'application/json');
		ajax.onreadystatechange = function() {
			if (this.readyState === 4 && this.status === 200) {
				console.log("response =", parseResponse(this.responseText));
				console.log("auto-map values took " + (performance.now() - autoMappingMainTimer) + " milliseconds.");
			} else {
				console.log(this.responseText);
			}
			document.querySelector("#form_" + form_id).submit();
		}
		var object = {
			form_id: form_id,
			entity_id: entity_id,
			codeValueMap: thisFormsElementsAndTheirValues,
			codeValueMap2: thisFormsElementsAndTheirValues,
			typeOfMatrixFields: typeOfMatrixFields,
			elementMachineCodesOnThisPage: thisPageElementMachineCodes,
			disabledFields: disabledFields,
			useremail: $("form").data('useremail'),
			task: "map values to other entries"
		};
		console.log("auto mapping object", object);
		ajax.send(JSON.stringify(object));
	}

	function parseResponse(response) {
		try {
			response = JSON.parse(response);
		} catch (e) {
			// do nothing
		}
		return response;
	}

	function prefillValue(object) {
		// console.log("prefillFieldValues =", object);
		var elementType        = object.element_type;
		var elementId          = object.element_id;
		var elementMachineCode = object.element_machine_code;
		var prefillValues      = object.prefillValues;

		if(elementType == "text" ||
		   elementType == "simple_phone" ||
		   elementType == "number" ||
		   elementType == "url" ||
		   elementType == "email" ||
		   elementType == "select") {
			for(var i=0; i<prefillValues.length; i++) {
				var prefillValue = prefillValues[i];
				if(prefillValue !== "") {
					var element = document.querySelector("[element_machine_code='" + elementMachineCode + "']");
					if(element) {
						element.value = prefillValue;
					}
				}
			}
		}

		if(elementType == "signature") {
			// extra work needs to be done to get the lines to appear in the signature pad C:\Programs\wamp64\www\auditprotocol\js\signaturepad\jquery.signaturepad.js
			for(var i=0; i<prefillValues.length; i++) {
				if(prefillValues[i] !== "") {
					var element = document.querySelector("[element_machine_code='" + elementMachineCode + "']");
					if(element) {
						element.value = prefillValues[i];
					}
				}
			}
		}

		if(elementType == "textarea") {
			for(var i=0; i<prefillValues.length; i++) {
				var prefillValue = prefillValues[i];
				if(prefillValue !== "") {
					var element = document.querySelector("[element_machine_code='" + elementMachineCode + "']");
					if(element) {
						element = element.parentElement
						if(element) {
							element.querySelector("iframe").contentDocument.querySelector(".cke_editable").innerHTML = prefillValue;
						}
					}
				}
			}
		}

		if(elementType == "phone") {
			for(var i=0; i<prefillValues.length; i++) {
				var prefillValue = prefillValues[i];
				if(prefillValue !== "") {
					var elements = document.querySelectorAll("[element_machine_code='" + elementMachineCode + "']");
					if(elements.length > 0) {
						elements[0].value = prefillValue[0] + prefillValue[1] + prefillValue[2];
						elements[1].value = prefillValue[3] + prefillValue[4] + prefillValue[5];
						elements[2].value = prefillValue[6] + prefillValue[7] + prefillValue[8] + prefillValue[9];
					}
				}
			}
		}
		
		if(elementType == "money") {
			for(var i=0; i<prefillValues.length; i++) {
				if(prefillValues[i] !== "") {
					var elements = document.querySelectorAll("[element_machine_code='" + elementMachineCode + "']");

					if(elements.length == 1) { // yen (1 field)
						elements[0].value = prefillValues[i];
					}
					
					if(elements.length == 2) { // all other currencies (2 fields)
						elements[0].value = prefillValues[i].split(".")[0];
						elements[1].value = prefillValues[i].split(".")[1];
					}
				}
			}
		}

		if(elementType == "address") {
			for(var i=0; i<prefillValues.length; i++) {
				if(prefillValues[i] !== "") {
					var streetAddress = prefillValues[0];					
					var lineTwo       = prefillValues[1];					
					var city          = prefillValues[2];					
					var state         = prefillValues[3];					
					var zipCode       = prefillValues[4];					
					var country       = prefillValues[5];					
					var elements      = document.querySelectorAll("[element_machine_code='" + elementMachineCode + "']");

					if(elements.length !== 0) {
						elements[0].value = streetAddress;
						elements[1].value = lineTwo;
						elements[2].value = city;
						elements[3].value = state;
						elements[4].value = zipCode;
						elements[5].value = country;
					}
				}
			}
		}

		if(elementType == "date") {
			for(var i=0; i<prefillValues.length; i++) {
				if(prefillValues[i] !== "") {
					var month    = prefillValues[i].split("-")[1];					
					var day      = prefillValues[i].split("-")[2];					
					var year     = prefillValues[i].split("-")[0];
					var elements = document.querySelectorAll("[element_machine_code='" + elementMachineCode + "']");

					if(elements.length !== 0) {
						elements[0].value = month;
						elements[1].value = day;
						elements[2].value = year;
					}
				}
			}
		}

		if(elementType == "europe_date") {
			for(var i=0; i<prefillValues.length; i++) {
				if(prefillValues[i] !== "") {
					var month    = prefillValues[i].split("-")[1];					
					var day      = prefillValues[i].split("-")[2];					
					var year     = prefillValues[i].split("-")[0];
					var elements = document.querySelectorAll("[element_machine_code='" + elementMachineCode + "']");

					if(elements.length !== 0) {
						elements[0].value = day;
						elements[1].value = month;
						elements[2].value = year;
					}
				}
			}
		}

		if(elementType == "radio") {
			for(var i=0; i<prefillValues.length; i++) {
				var elements      = document.querySelectorAll("[element_machine_code='" + elementMachineCode + "']");
				var containsOther = false;

				for(var j=0; j<elements.length; j++) {
					if(elements[j].id.indexOf("other") > -1) {
						containsOther = true;
					}
				}

				if(elements[i]) {
					if(containsOther == true) {
						elements[elements.length - 1].value   = prefillValues[0];
						elements[elements.length - 2].checked = true;
					} else {
						elements[i].checked = true;
					}
				}
			}
		}
		
		if(elementType == "matrix") {
			for(var i=0; i<prefillValues.length; i++) {
				if(prefillValues[i] !== "") {
					var selector = "#element_" + elementId + "_" + prefillValues[i];
					var element  = document.querySelector(selector);

					if(element) {
						element.checked = true;
					}
				}
			}
		}

		if(elementType == "checkbox") {
			for(var i=0; i<prefillValues.length; i++) {
				var elements      = document.querySelectorAll('*[id^="' + 'element_' + elementId + '"]');
				var containsOther = false;

				for(var j=0; j<elements.length; j++) {
					if(elements[j].id.indexOf("other") > -1) {
						containsOther = true;
					}
				}

				if(elements[i]) {
					if(prefillValues[i] == "1") {
						if(containsOther == true) {
							elements[i - 1].checked = true;
						} else {
							elements[i].checked = true;
						}
					}

					if(prefillValues[i] !== "1" && prefillValues[i] !== "") { // other
						elements[elements.length - 1].value   = prefillValues[i];
						elements[elements.length - 2].checked = true;
					}
				}
			}
		}

		if(elementType == "time") {
			for(var i=0; i<prefillValues.length; i++) {
				if(prefillValues[i] !== "") {
					var hoursOriginal  = prefillValues[i].split(":")[0];
					var hoursFormatted = prefillValues[i].split(":")[0];
					var minutes        = prefillValues[i].split(":")[1];
					var seconds        = prefillValues[i].split(":")[2];
					var elements       = document.querySelectorAll("[element_machine_code='" + elementMachineCode + "']");
					
					if(hoursOriginal == 0) { // am
						hoursFormatted = 12;
					}
					
					if(hoursOriginal > 12) { // pm
						hoursFormatted = hoursOriginal - 12;
					}

					elements[0].value = hoursFormatted;
					elements[1].value = minutes;

					if(elements[2]) {
						if(elements[2].classList.contains("select") == false) {
							elements[2].value = seconds;
						} else {
							hoursOriginal < 12 ? elements[2].value = "AM" : elements[2].value = "PM";
						}
					}

					if(elements[3]) {
						hoursOriginal < 12 ? elements[3].value = "AM" : elements[3].value = "PM";
					}

				}
			}
		}

		if(elementType == "simple_name") {
			for(var i=0; i<prefillValues.length; i++) {
				if(prefillValues[i] !== "") {
					var firstName = prefillValues[0];					
					var lastName  = prefillValues[1];					
					var elements  = document.querySelectorAll("[element_machine_code='" + elementMachineCode + "']");

					if(elements.length !== 0) {
						elements[0].value = firstName;
						elements[1].value = lastName;
					}
				}
			}
		}

		if(elementType == "name") {
			for(var i=0; i<prefillValues.length; i++) {
				if(prefillValues[i] !== "") {
					var title     = prefillValues[0];					
					var firstName = prefillValues[1];					
					var lastName  = prefillValues[2];					
					var suffix    = prefillValues[3];					
					var elements  = document.querySelectorAll("[element_machine_code='" + elementMachineCode + "']");

					if(elements.length !== 0) {
						elements[0].value = title;
						elements[1].value = firstName;
						elements[2].value = lastName;
						elements[3].value = suffix;
					}
				}
			}
		}

		if(elementType == "simple_name_wmiddle") {
			for(var i=0; i<prefillValues.length; i++) {
				if(prefillValues[i] !== "") {
					var firstName  = prefillValues[0];					
					var middleName = prefillValues[1];					
					var lastName   = prefillValues[2];					
					var elements   = document.querySelectorAll("[element_machine_code='" + elementMachineCode + "']");

					if(elements.length !== 0) {
						elements[0].value = firstName;
						elements[1].value = middleName;
						elements[2].value = lastName;
					}
				}
			}
		}

		if(elementType == "name_wmiddle") {
			for(var i=0; i<prefillValues.length; i++) {
				if(prefillValues[i] !== "") {
					var title      = prefillValues[0];					
					var firstName  = prefillValues[1];					
					var middleName = prefillValues[2];					
					var lastName   = prefillValues[3];					
					var suffix     = prefillValues[4];					
					var elements   = document.querySelectorAll("[element_machine_code='" + elementMachineCode + "']");

					if(elements.length !== 0) {
						elements[0].value = title;
						elements[1].value = firstName;
						elements[2].value = middleName;
						elements[3].value = lastName;
						elements[4].value = suffix;
					}
				}
			}
		}
	}

	function lockOrUnlockFields() {
		var unlockFieldsTimer = performance.now();
		ajax.open("POST", API_Endpoint, true);
		ajax.setRequestHeader('Content-Type', 'application/json');
		ajax.onreadystatechange = function() {
			if (this.readyState === 4 && this.status === 200) {
				console.log("block/unblock fields in use took " + (performance.now() - unlockFieldsTimer) + " milliseconds.");
				var response = parseResponse(this.responseText);
				if(typeof(response) ==  "object") {
					console.log("fieldCodesBlocked =", response);

					// highlight fields that are blocked
					lockedViaThisPage = response.thisPagesLocks.fieldsLockedViaThisPage;

					var blockedElements = response.thisPagesLocks.fieldsToLockOnThisPage;
					var selected_entity_id = $('form').data("selected_entity_id");
					for(blockedElement in blockedElements) {

						var elementMachineCode = blockedElement;
							console.log('selected_entity_id', selected_entity_id, '', response.thisPagesLocks.fieldsToLockOnThisPage[elementMachineCode].entity_id);
						if( response.thisPagesLocks.fieldsToLockOnThisPage[elementMachineCode].entity_id == selected_entity_id ) {
							//show lock message only if the entity_id is same

							disabledFields.push(elementMachineCode);
							var element            = document.querySelector('[element_machine_code="' + elementMachineCode + '"]');

							if(element) {
								// disable all inputs
								var allInputs = document.querySelectorAll('[element_machine_code="' + elementMachineCode + '"]');
								for(var i=0; i<allInputs.length; i++) {
									// allInputs[i].disabled = true;
									allInputs[i].style.pointerEvents   = "none"; // prevents clicks from happening at all
									allInputs[i].classList.add("locked");
								}
								// element.style.pointerEvents   = "none";
								// element.classList.add("locked");

								element_parent = element.closest("[id^='li_']");
								if(element_parent) {
									var classList = JSON.stringify(element_parent.classList);

									if(classList.indexOf("element_id_auto") == -1) {
										element = element.parentElement.parentElement;
									}
								}

								element_parent.style.backgroundColor = "rgb(230, 230, 230)";

								/*element.addEventListener("click", function(e) {
									e.preventDefault();
									var targetHTML = e.target.parentElement.innerHTML.toString();
									if(targetHTML.indexOf("element_machine_code") > -1) {
										var elementMachineCode = targetHTML.split('element_machine_code="')[1].split('"')[0];
										if(response &&
										   response.thisPagesLocks &&
										   response.thisPagesLocks.fieldsToLockOnThisPage &&
										   response.thisPagesLocks.fieldsToLockOnThisPage[elementMachineCode]) {
											var message = "Blocked. Field in use by <strong>" + response.thisPagesLocks.fieldsToLockOnThisPage[elementMachineCode].users_email + "</strong> on <strong>form #" + response.thisPagesLocks.fieldsToLockOnThisPage[elementMachineCode].form_id_where_lock_originated + "</strong>.";
											// alert(message);
											$('.form-element-locked-alert').remove();
											let alertHtml = `<div class="form-element-locked-alert"><h3 class="text-center">Field Blocked</h3><p>${message}</p></div>`;
											$('.form_description').after(alertHtml);
											$("html, body").animate({scrollTop : 0},700);
											if(e.target.checked == true) {
												e.target.checked = false;
											} else if (e.target.checked == false) {
												e.target.checked = true;
											}
										}
									}
								});*/
								let infoHtml = '<p class="socket-info">Field in use by <strong>'+response.thisPagesLocks.fieldsToLockOnThisPage[elementMachineCode].users_email+'</strong> on <strong>form #'+response.thisPagesLocks.fieldsToLockOnThisPage[elementMachineCode].form_id_where_lock_originated +'</strong>!</p>';
							    // var inputElem = $('form').find('#'+data.element_id);
							    var inputElem = $('form').find('*[data-element_machine_code="'+elementMachineCode+'"]');
								// inputElem.prop( "disabled", true );

							    var liElem = inputElem.closest('li');
							    liElem.find('.socket-info').remove();
							    liElem.append(infoHtml);

							}
						}
					}
				} else {
					console.log("error locking/unlocking fields in use");
					console.log("fieldCodesBlocked =", response);
				}
			}
		}
		const form = document.querySelector('form');
		let useremail = form.dataset.useremail;
		ajax.send(JSON.stringify({
			form_id: form_id,
			entity_id: entity_id,
			elementMachineCodesOnThisPage: thisPageElementMachineCodes,
			currentURL: document.location.href,
			email: useremail,
			task: "lock or unlock fields"
		}));
	}
});
