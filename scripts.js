/**
 * Changes the graphical forecast image
 *
 * Note: intended to be bound to graphical forecast UI button clicks
 *
 * Makes use of datasets on target elements
 *
 * Notes on graphical forecast (see https://graphical.weather.gov/sectors/massachusetts.php#tabs)
 *
 *  - n ∈ [1,51] for `WindSpd${n}_massachusetts.png`, but close to 51 the even `n` are skipped
 *  
 *  - half-day periods: day or night (8,11,2,5)
 *  - current: today or tonight
 *  - 3hr: periods 0-4
 *  - 6hr: periods 5-12
 * 
 * @param  {event} e  the click event
 * @return {boolean}  always returns true
 */
function changeGraphicalForecastAndControlUI(e) {

	var day, // Targeted day
		hour, // Targeted hour
		n, // Forecast graphic to display
		currentHour = (new Date()).getHours();

	function changeDay() {

		document.querySelectorAll("#graphicalforecast-day button").forEach(e => e.dataset.active=false);

		e.target.dataset.active = true;

		day = parseInt(e.target.dataset.day);

		n = day * 8 // 8 graphics per day
			+ 1 // first day is zero, first image is 1
			+ 2 // show the 2pm image
			- (currentHour > 20 ? 4 : 0); //handle the 8am/8pm switchovers

		document.querySelectorAll("#graphicalforecast-hour button").forEach(e => e.removeAttribute("disabled"));
		if(e.target.dataset.day > 2) {
			document.querySelectorAll("#graphicalforecast-hour button").forEach(e => {
				if ( (parseInt(e.dataset.hour)+1)/3%2 == 0 ) {
					e.setAttribute("disabled","true");
				}
			})
		}
	}

	function changeHour() {

		day = parseInt(document.querySelector("#graphicalforecast-day button[data-active='true']").dataset.day);
		
		hour = parseInt(e.target.dataset.hour);
		
		n = day * 8 // 8 graphics per day
			+ (hour+1)/3 // 1 graphic per 3 hours
			- 2 // Half-day starts at 8am/8pm
			- (currentHour > 20 ? 4 : 0); // Half-day starts at 8am/8pm
	}

	if(e.target.hasAttribute("data-day")) {

		changeDay();

	} else if (e.target.hasAttribute("data-hour")) {

		changeHour();

	}

	// Prevent broken images
	n = n<0 || n>51 ? 1 : n;

	// Primary goal of the function
	document.querySelector("#graphicalforecast-img").src = `https://graphical.weather.gov/images/massachusetts/WindSpd${n}_massachusetts.png`;
	
	if(document.location.search.includes('debug')) {
		console.log({"day": day, "hour": hour, "currentHour": currentHour, "n": n})
	}

	return true;

}

window.addEventListener('DOMContentLoaded', (event) => {

	document.querySelectorAll("#graphicalforecast-day button, #graphicalforecast-hour button")
		.forEach(e => e.addEventListener('click',changeGraphicalForecastAndControlUI));

	document.querySelector("#satelite summary").addEventListener('click',function(){

		if(typeof sateliteInterval !== 'undefined') {
			clearInterval(sateliteInterval);
			sateliteInterval = false;
		} else {
			sateliteInterval = setInterval(function(){
				var img = document.querySelector('#satelite-loop');
				img.src = satelite_images[img.dataset.i%12];
				img.dataset.i++;}, 250);
		}
	})

});