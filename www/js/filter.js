/*
Given a search string search inside the list of inventory items and if there is a match call callbackShow, call callbackHide otherwise
*/


var FilterSearch = (function(){
	function filterAndSort(text, lista, callbackShow, callbackHide) {
		// For each word remove tildes and lower the case
		var search = text.trim().latinize().toLowerCase().split(" ");
		RecursiveSearch(lista, search, callbackShow, callbackHide);
	}
	
	// Recursively find matches. callbackShow and callbackHide receive the dom element that should be hidden/showed if there is a match. Returns true if there is a match
	function RecursiveSearch(json, search, callbackShow, callbackHide) {
		var somethingFound = false;
		for (var i = 0; i < json.length; i++) {
			var lastLevel = undefined === json[i].contenido;
			var somethingFoundInside = !lastLevel ? RecursiveSearch(json[i].contenido, search, callbackShow, callbackHide) : false;
			var hereFound = lastLevel && Test(search, json[i].nombre);
			if (undefined !== json[i].Tags) {
				for (var j = 0; !hereFound && j < json[i].Tags.length; j++) {
					hereFound = hereFound || Test(search, json[i].Tags[j]);
				}
			}
			somethingFound = somethingFound || somethingFoundInside || hereFound;
			
			// Execute callbacks
			var callback = somethingFoundInside || hereFound ? callbackShow : callbackHide;
			callback(json[i].DOM);
		}
		return somethingFound;
	}
	
	// True if text contains all of the strings in the search array. False otherwise
	function Test(search, text) {
		text = text.latinize().toLowerCase();
		for (var i = 0; i < search.length; i++)
			if (text.indexOf(search[i]) == -1)
				return false;
		
		return true;
	}
	
	
	
	// API
	return {
		process: filterAndSort
	};
})();
