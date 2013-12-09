'use strict';
angular.module('pinwheelApp')
	.value("QuickAdd", function (stringArg, afterQuickAdd) {
			(stringArg || (stringArg = ''));

			var timeCoordArray = [];
			var eventTitle = "";
			var timeTimestamp = '';
			var allDayEvent = false;
			var x, y, j, key, dateTimestamp,
					timeTimepart,  jsonOutput, eventLocation;
			var calendarID = "";
			

			// normalize case
			//stringArg = stringArg.toLowerCase();
			
			
			// remove from, "-" - assume 1st time is start of range
			stringArg = stringArg.replace(" - "," to ");
			stringArg = stringArg.replace("-"," to ");
			stringArg = stringArg.replace(/[.]/ig,"");
			stringArg = stringArg.replace(/from/ig,"at");
			stringArg = stringArg.replace("@"," at ");

			
			// normalize white space
			stringArg = stringArg.replace(/ \s+/g,' ');
			
			// merge am/pm with times for easier parsing
			stringArg = stringArg.replace(/\sam/ig,'am');
			stringArg = stringArg.replace(/\spm/ig,'pm');
			
			// check for "all day"
			if (stringArg.search(/all day/i) > -1) {
				allDayEvent = true;
				stringArg = stringArg.replace(/all day/ig,"");
			}
			
			// tokenize arg
			var argTokens = stringArg.split(" ");
			
			// identify valid time phrases in string
			var timeTokenFound = false;
			for (x = 0; x < argTokens.length; x++) {
			
				var validString = "";
				var testString = argTokens[x];
				var tokensToUnsetStart = x;
				var tokensToUnsetEnd;
				if (Date.parse(testString) != null) {
					validString = testString;
					timeTokenFound = true;
					tokensToUnsetStart = x;
					tokensToUnsetEnd = x+1;
				}
				for (y = x + 1; y < argTokens.length; y++) {
					testString += " "+argTokens[y];
					if (Date.parse(testString) != null) {
						validString = testString;
						timeTokenFound = true;
						tokensToUnsetEnd = y+1;
					}
				}
				if (validString != "") {
													
					timeCoordArray.push(validString);
					
					if ((timeCoordArray.length > 0) && (argTokens[tokensToUnsetStart-1] == "to"))
						tokensToUnsetStart--;
								
					for (j = 0; j < (tokensToUnsetEnd - tokensToUnsetStart); j++) {
						argTokens.splice(tokensToUnsetStart,1);
					}
					x--;
					
				}
				
							
			}
			
			// time coordinates have now been extracted, and removed from token list
			
			// check for location (simple "at" check)
			if (argTokens.indexOf("at") > -1) {
				eventLocation = "";
				var numTokensToRemove = 1;
				for (x = argTokens.indexOf("at")+1; x < argTokens.length; x++) {
					eventLocation += argTokens[x] + " ";
					numTokensToRemove++;
				}
				eventLocation = eventLocation.replace(/^\s+|\s+$/g,'');
				argTokens.splice(argTokens.indexOf("at"),numTokensToRemove);
			}
			
			// clean eventTitle out of remaining tokens
			if ((eventTitle == "") && (argTokens.length > 0)) {
				for (key in argTokens) {
					eventTitle += argTokens[key]+" ";		
				}
			}
			eventTitle = eventTitle.replace(/^\s+|\s+$/g,'');
			
			// scrub time coordinates, attempt to set specific dates, am/pm
			var sharedDate;
			var sharedTime;
			var sharedAmpm;
			var timeCoordObjects = [];
			for (key in timeCoordArray) {
				
				var thisTimeCoord = timeCoordArray[key];
				thisTimeCoord = thisTimeCoord.replace(/^\s+|\s+$/g,'');
				timeCoordObjects[key] = new Array();
				
				//timeCoordObjects[key]["datePart"];
				//timeCoordObjects[key]["timePart"];
				//timeCoordObjects[key]["ampmPart"];

				// extract time w/ am/pm, remove from time coord to isolate date
				if (thisTimeCoord.search(":") < 0) {
					// find am/pm, append :00		
					if (thisTimeCoord.search(/\d+am/) > -1) {
						timeCoordObjects[key]["ampmPart"] = "am";
						timeCoordObjects[key]["timePart"] = parseInt(thisTimeCoord.substr(thisTimeCoord.search(/\d+am/),(thisTimeCoord.search(/am/)-thisTimeCoord.search(/\d+am/))))+":00";
						thisTimeCoord = thisTimeCoord.replace(/\d+am/,"");
					} else if (thisTimeCoord.search(/\d+pm/) > -1) {
						timeCoordObjects[key]["ampmPart"] = "pm";
						timeCoordObjects[key]["timePart"] = parseInt(thisTimeCoord.substr(thisTimeCoord.search(/\d+pm/),(thisTimeCoord.search(/pm/)-thisTimeCoord.search(/\d+pm/))))+":00";
						thisTimeCoord = thisTimeCoord.replace(/\d+pm/,"");
					} else if (thisTimeCoord.search(/\d+$/) > -1) {
						if (thisTimeCoord.search("/") < 0) {
							timeCoordObjects[key]["timePart"] = parseInt(thisTimeCoord.substr(thisTimeCoord.search(/\d+\z/),thisTimeCoord.length))+":00";
							thisTimeCoord = thisTimeCoord.replace(/\d+$/,"");
						}
					}
				} else {
					if (thisTimeCoord.search(/\d+:\d+am/) > -1) {
						timeCoordObjects[key]["ampmPart"] = "am";
						timeCoordObjects[key]["timePart"] = thisTimeCoord.match(/\d+:\d+/);
						thisTimeCoord = thisTimeCoord.replace(/\d+:\d+am/,"");
					} else if (thisTimeCoord.search(/\d+:\d+pm/) > -1) {
						timeCoordObjects[key]["ampmPart"] = "pm";
						timeCoordObjects[key]["timePart"] = thisTimeCoord.match(/\d+:\d+/);
						thisTimeCoord = thisTimeCoord.replace(/\d+:\d+pm/,"");
					} if (thisTimeCoord.search(/\d+:\d+/) > -1) {
						timeCoordObjects[key]["timePart"] = thisTimeCoord.match(/\d+:\d+/);
						thisTimeCoord = thisTimeCoord.replace(/\d+:\d+/,"");
					}
						
				}
				
				thisTimeCoord = thisTimeCoord.replace("at","");
				thisTimeCoord = thisTimeCoord.replace(/^\s+|\s+$/g,'');
				
				if (thisTimeCoord != "")
					timeCoordObjects[key]["datePart"] = thisTimeCoord;
				
				// special month/day case where no time is specified, date could be mistaken for time
				if ((Date.getMonthNumberFromName(thisTimeCoord) > -1) && (timeCoordObjects[key]["ampmPart"] == null)) {
					timeCoordObjects[key]["datePart"] = thisTimeCoord + " " + timeCoordObjects[key]["timePart"].match(/\d+:/);
					timeCoordObjects[key]["datePart"] = timeCoordObjects[key]["datePart"].replace(/:/,'');
					timeCoordObjects[key]["timePart"] = null;
				}
				
				// share missing dates/ampm
				if ((sharedDate == null) && (timeCoordObjects[key]["datePart"] != null)) {
					sharedDate = timeCoordObjects[key]["datePart"];
				}
				if ((sharedAmpm == null) && (timeCoordObjects[key]["ampmPart"] != null)) {
					sharedAmpm = timeCoordObjects[key]["ampmPart"];
				}
				if ((sharedTime == null) && (timeCoordObjects[key]["timePart"] != null)) {
					sharedTime = timeCoordObjects[key]["timePart"];
				}
				
			}
			// calculate timestamps
			for (key in timeCoordObjects) {
			
				if (timeCoordObjects[key]["datePart"]) {
					dateTimestamp = Date.parse(timeCoordObjects[key]["datePart"]).getTime();
				} else if (sharedDate != null) {
					dateTimestamp = Date.parse(sharedDate).getTime();
				} else {
					dateTimestamp = Date.parse("today").getTime();
				}
			
				if (timeCoordObjects[key]["timePart"])
					timeTimepart = timeCoordObjects[key]["timePart"];
				else if (sharedTime != null)
					timeTimepart = sharedTime;
				else
					timeTimepart = new Date().toString("h:mm");
						
				if (timeCoordObjects[key]["ampmPart"]) {
											
					
					timeTimestamp = Date.parse(timeTimepart+timeCoordObjects[key]["ampmPart"]).getTime();
					
					
				} else if (sharedAmpm != null) {
					timeTimestamp = Date.parse(timeTimepart+sharedAmpm).getTime();
				} else {
					timeTimestamp = Date.parse(timeTimepart+(new Date().toString("tt"))).getTime();
				}

				timeCoordObjects[key]["timestamp"] = timeTimestamp - Date.parse("today").getTime() + dateTimestamp;	
				
				timeCoordObjects[key]["timestamp"] = parseInt(timeCoordObjects[key]["timestamp"]) / 1000;
			
			}
			
			
			jsonOutput = new Object();
			if (eventTitle != "")
				jsonOutput["title"] = eventTitle;
			else
				jsonOutput["title"] = null;
			if (timeCoordObjects[0] && timeCoordObjects[0]["timestamp"] != "")
				jsonOutput["start"] = timeCoordObjects[0]["timestamp"];
			else
				jsonOutput["start"] = null;
			if (timeCoordObjects.length > 1) {
				if (timeCoordObjects[0]["timestamp"] != timeCoordObjects[1]["timestamp"]) {
					jsonOutput["end"] = timeCoordObjects[1]["timestamp"];
				} else {
					jsonOutput["end"] = parseInt(timeCoordObjects[0]["timestamp"]) + 3600;
				}
			} else {
				if (timeCoordObjects[0] && timeCoordObjects[0]["timestamp"] != "")
					jsonOutput["end"] = parseInt(timeCoordObjects[0]["timestamp"]) + 3600;
				else
					jsonOutput["end"] = null;
			}
			if (eventLocation != null)
				jsonOutput["event_location"] = eventLocation;
			if (allDayEvent)
				jsonOutput["allDay"] = true;
			else
				jsonOutput["allDay"] = false;
				
			//get user obj for default calendar
			var userObj = JSON.parse(localStorage.User);	
				
			if (calendarID == "") {
				jsonOutput["calendar_id"] = userObj.settings.default_calendar;
			}
			
			return(jsonOutput);

		});
