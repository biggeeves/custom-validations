/*global validation, requireSubmit, validMessage, invalidMessage
$, jQuery, &,
app_path_webroot, page, pid, recordNameValid,
charMin, beginsWith, endsWith, charCustom
*/
/*jslint devel: true */

/*jshint esversion: 6 */

/*  A REDCap record_id is often in a specific format, often a prefixed number, and should be validated.
This module expands on REDCap's built in validation types.
Regular expressions can be used to validate the record_id
A simplified version of Regular expression is also allowed:
    ? = any character
    # = any numeric character [0-9]
    $ = any string character [a-z, A-Z]
    X = the literal character X.  All characters not mention above are literal.
 */

$(window).on("load", function () {
    if (validation.debug) {
        console.log("Initialized");
    }


    // REDCap will return null for empty settings when the value has been cleared.
    function hasFormats(settings) {
        if (settings === null) {
            return false;
        }
        if ($.isEmptyObject(settings)) {
            return false;
        }
        return true;
    }


    // TODO, this is returning something, but it may not be correct.
    validation.hasFormats = hasFormats(validation.formats);
    console.log("Should be false " + validation.hasFormats); // currently returning false when true.

    /** The form is submitted when the user leaves the field.
     * Remove this and make enter the only way to submit form
     *
     * The setTimeout trick in particular is also great if you’re waiting on other JavaScript code.
     * When your setTimeout function is triggered,
     * you know any other pending JavaScript on the page will have executed.
     *
     * The blur event is added later by REDCap.  Removing it must be done after REDCap has added it.  Thus setTimeout!
     * */
    if (validation.requireSubmit) {
        if (validation.debug) {
            console.log("Blur event turned off");
        }
        setTimeout(disableREDCapBlur);
    }

    function disableREDCapBlur() {
        $("#inputString").off("blur");
    }

    function defineCharacters() {
        if (validation.debug) {
            console.log("Defining Chars");
        }

        if (validation.charCustom !== null && validation.charCustom !== undefined) {
            if (validation.debug) {
                console.log("User defined custom chars: " + validation.charCustom);
            }
            validation.strings = validation.charCustom;
        } else {
            if (validation.debug) {
                console.log("default value for strings");
            }
            validation.strings = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        }
        validation.numbers = "0123456789";
        validation.anyCharSymbol = "?";
        validation.numberSymbol = "#";
        validation.letterSymbol = "$";
    }

    /** Locate the input field for new IDs */
    validation.idNode = document.querySelector("#inputString");

    function makeElements() {
        /** Create Validation Results Area */
        validation.resultDiv = document.createElement("div");
        validation.resultDiv.setAttribute("id", "resultDiv");
        validation.resultDiv.setAttribute("class", "p-2");


        /** Create Validation Div for displaying results of validation */
        validation.formatDiv = document.createElement("div");
        validation.formatDiv.setAttribute("id", "validation.formatDiv");

        /** Create Div for displaying regular expression*/
        validation.regexDiv = document.createElement("div");
        validation.regexDiv.setAttribute("id", "validation.regexDiv");

        /** Create submit button */
        validation.submitDiv = document.createElement("div");
        validation.submitDiv.setAttribute("id", "submit");

        validation.submitBtn = document.createElement("button");
        validation.submitBtn.setAttribute("id", "validationBtn");
        validation.submitBtn.setAttribute("disabled", "true");
        validation.submitBtn.setAttribute("class", "btn btn-danger m-2 d-inline");
        validation.submitBtn.setAttribute("onclick", "validation.requireSubmit = 0;");
        validation.submitBtn.innerHTML = "Submit";


        // display the formats
        validation.formatDisplay = "";
        if (validation.formats !== null) {
            validation.formats.forEach(function (entry) {
                validation.formatDisplay += entry;
            });
        }
        validation.formatDiv.innerHTML = validation.formatDisplay;

    }


    /** insert all nodes */
    function insertNodes() {
        validation.idNode.after(validation.formatDiv);
        validation.idNode.after(validation.resultDiv);
        validation.idNode.after(validation.regexDiv);
        validation.idNode.after(validation.submitBtn);
    }

    /** Add event listeners for keystrokes and submit button */
    function addEventListeners() {
        /** Add event listeners to the ID field.   Validate as often as possible. */
        validation.idNode.addEventListener("input", controller);
        validation.submitBtn.addEventListener("click", controller);
    }


    /** Create all of the elements on the page */
    makeElements();

    /** Insert the elements into the page */
    insertNodes();


    /** Listen for necessary events */
    addEventListeners();

    defineCharacters();


    if (validation.debug) {
        console.log("Ran after add event listener");
        if (validation.testValue) {
            validation.idNode.value = validation.testValue;
        }
    }

    /** Listen to events and respond to them.
     *  One set_up_dark_mode to rule them all.
     */
    controller();

    /** Every event listener will use this set_up_dark_mode. */
    function controller() {
        if (validation.debug) {
            // console.log(console.clear());
            // console.log("In Controller");
        }
        validation.results = [];

        validation.inputString = document.querySelector("#inputString");
        // do not continue if the ID field is blank.
        if (validation.inputString.value.length === 0 || validation.inputString.value.length === undefined) {
            nonValidated();
            return;
        }
        // verify that it meets REDCaps validation.
        validation.results.redcap = validateREDCap(validation.inputString.value);

        // TODO ends with and begins with has an issue.

        if (validation.hasFormats) {
            console.log("has formats: " + validation.hasFormats);
            validation.results.formats = validateFormats(validation.inputString.value, validation.formats);
        } else if (validation.charCustom) {
            // If there are no formats specified the ID must still use custom define characters, if defined.
            validation.results.customChars = validateCustomChars(validation.inputString.value);
        }

        if (validation.beginsWithFormat) {
            validation.results.beginsWith = beginsWith();
        }

        if (validation.endsWith) {
            console.log("In endsWith");
            let formatLength = validation.endsWith.length;
            validation.results.endsWith = validateFormats(validation.inputString.value.substr(0, formatLength), validation.endsWith);
        }


        // REGEX TODO
        if (validation.regex) {
            console.log("In REGEX");
            validation.results.regex = validateRegex(validation.inputString.value, validation.regex);
        }

        if (validation.charMin) {
            validation.results.hasMinChars = hasMinChars(validation.inputString.value);
        }

        if (validation.charExactNum) {
            validation.results.hasExactNumChars = hasExactNumChars(validation.inputString.value);
        }

        if (validation.charLimit) {
            validation.results.lessThanCharLimit = lessThanCharLimit(validation.inputString.value);
        }

        validation.isValid = Object.values(validation.results)
            .every(item => item === true);

        if (validation.debug) {
            console.log(validation.results);
            console.log("Final Validation Result in Controller: " + validation.isValid);
        }

        // If all values in the array are true than isValid is true.
        // Note that only validations which are checked are included.
        isValidJSResult(validation.isValid);
    }

    /*
    return true if minimum number of characters is meet
    */
    function hasMinChars(value = null) {
        return (value.length >= validation.charMin);
    }

    /* return true if input has the exact number of characters
    *
    * */
    function hasExactNumChars(value = null) {
        return (value.length === validation.charExactNum);
    }

    /* return true if input is less than the character limit in REDCap schema */
    function lessThanCharLimit(value = null) {
        return (value.length <= validation.charLimit);
    }

    function validateCustomChars(value) {
        console.log("Custom Chars " + value);
        let inputChar;
        for (let i = 0; i < value.length; i++) {
            inputChar = value.charAt(i);
            console.log("Custom character" + inputChar);
            //
            // exclude checking numbers
            if (validation.numbers.includes(inputChar)) {
                continue;
            }
            //  Character must be in the approved character set.
            if (!validation.strings.includes(inputChar)) {
                return false;
            }
        }
        return true;
    }

    function validateFormats(value, validationFormats) {
        let atLeastOneTrue;
        let allFormatResults = [];
        if (validation.debug) {
            console.log("validationFormats:");
            console.log(validationFormats);
            // console.clear();
        }
        // Return true if no formats are specified
        if (!validationFormats || validationFormats === null) {
            console.log("ValidateFormats: No Formats");
            return true;
        }
        if (validation.debug) {
            console.log("the value is " + value);
            if (validation.hasFormats) {
                console.log("format(s): " + validation.formats.join(", "));
            }
        }

        // Check each format.
        validation.formats.forEach(function (format) {
            allFormatResults.push(validateSpecificFormat(value, format));
        });
        // If one is true than this passes.
        atLeastOneTrue = allFormatResults.some(function (e) {
            return e;
        });

        if (validation.debug) {
            console.log("Format Results: " + allFormatResults);
            console.log("Final result of validateFormats: " + atLeastOneTrue);
        }
        return atLeastOneTrue;
    }

    function validateSpecificFormat(value, format) {
        if (validation.debug) {
            console.log("Checking: " + value + " format: " + format);
            console.log("# Characters: " + value.length);
        }
        let isValid = true;  // True until proven false
        let checkChar;
        let inputChar;
        // If no formats to check return early.
        if (!format) {
            return;
        }
        // return false if the length of the input and the specified format are not the same.
        if (value.length !== format.length) {
            if (validation.debug) {
                console.log("Validation and input length must be the same length");
            }
            isValid = false;
            return isValid;
        }

        // Cycle through each character in input string and validate it against format criteria.
        for (let i = 0; i < value.length; i++) {
            checkChar = format.charAt(i);
            inputChar = value.charAt(i);
            console.log("Checking Character: " + i + " " + inputChar + "/" + checkChar);
            if (checkChar === validation.anyCharSymbol) {
                console.log("?OK: " + i + " " + validation.anyCharSymbol);
            } else if (checkChar === validation.numberSymbol && validation.numbers.includes(inputChar)) {
                // Check Numbers
                console.log("$OK: " + i);
                isValid = true;
            } else if (checkChar === validation.letterSymbol && validation.strings.includes(inputChar)) {
                // Check Strings
                console.log("#OK: " + i);
                isValid = true;
            } else if (inputChar === checkChar) {
                // Does not match the exact character
                console.log("ExactOK: " + i);
                isValid = true;
            } else {
                isValid = false;
            }
            if (validation.debug) {
                console.log("Loc: " + i + ": input: " + inputChar + " format: " + checkChar + " isValid: " + isValid);
            }

            if (!isValid) {
                break;
            }
        }
        if (validation.debug) {
            console.log("Final result of validateEachFormat:" + isValid);
        }

        return isValid;
    }

    function validateRegex(str, expression) {
        if (validation.debug) {
            console.log("Regex: " + expression);
            console.log("STR: " + str);
        }
        return expression.test(str);
    }

    function isValidJSResult(isValid) {
        if (isValid) {
            validated();
        } else {
            nonValidated();
        }
    }

    function validateREDCap(value) {
        if (value.length < 1 || value.length > 100) {
            return false;
        } else {
            return true;
        }
    }

    function validated() {
        if (validation.debug) {
            console.log("Passed Validation");
        }
        if (validation.validMessage.length > 0) {
            validation.resultDiv.innerHTML = validation.validMessage;
            validation.resultDiv.setAttribute("style", "background-color:lightgreen");
        } else {
            validation.resultDiv.setAttribute("style", "background-color:blue");
            validation.resultDiv.style.display = "none";
        }
        validation.submitBtn.removeAttribute("disabled");
        validation.submitBtn.classList.remove("btn-danger");
        validation.submitBtn.classList.add("btn-success");

        /**
         * REDCap does it thing to redirect the user to the page they need to be at.
         * Copied code from REDCap.
         */
        if (!validation.requireSubmit) {
            REDCapRedirct();
        }

    }

    function nonValidated() {
        if (validation.debug) {
            console.log("NonValidated Action");
        }
        if (validation.invalidMessage.length > 0) {
            validation.resultDiv.innerHTML = validation.invalidMessage;
            validation.resultDiv.setAttribute("style", "background-color:#FFFFE0");
        } else {
            validation.resultDiv.style.display = "none";
        }
        validation.submitBtn.setAttribute("disabled", "true");
        validation.submitBtn.classList.remove("btn-success");
        validation.submitBtn.classList.add("btn-danger");
    }

    function REDCapRedirct() {
        let idVal = trim($("#inputString").val());
        /*$("#inputString").val(idVal);*/
        setTimeout(function () {
            idVal = $("#inputString").val();
            idVal = idVal.replace(/&quot;/g, "");
            let validRecordName = recordNameValid(idVal);
            if (validRecordName !== true) {
                $("#inputString").val("");
                alert(validRecordName);
                $("#inputString").focus();
                return false;
            }
            // Redirect, but NOT if the validation pop-up is being displayed (for range check errors)
            if (!$(".simpleDialog.ui-dialog-content:visible").length) {
                window.location.href = app_path_webroot + page +
                    '?pid=' + pid +
                    '&arm=1&id=' + idVal +
                    addGoogTrans();
            }
        }, 200);
    }

    function beginsWith() {
        let formatLength;
        let inputBeginsWith;
        let result;
        formatLength = validation.beginsWithFormat[0].length;
        inputBeginsWith = validation.inputString.value.substr(0, formatLength);
        console.log("In beginsWith: " + inputBeginsWith + " Length: " + formatLength + " Format: " + validation.beginsWithFormat);
        result = validateFormats(inputBeginsWith, validation.beginsWithFormat);
        return result;
    }
});

