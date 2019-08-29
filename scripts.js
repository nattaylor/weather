function changeGraphicalForecast(e) {
	/**
	 * periods: day or night (8,11,2,5)
	 * current: today or tonight
	 * 3hr: periods 0-4
	 * 6hr: periods 5-12
	 */
	var h = (new Date()).getHours();
	if(e.target.hasAttribute("data-day")) {
		document.querySelectorAll("#graphicalforecast-day button").forEach(e => e.dataset.active=false);
		e.target.dataset.active = true;
		var i = e.target.dataset.day * 8 + 1 + 2 - (h>20 ? 4 : 0);
		i = i>0 ? i : 1;
		console.log([day, h])
		document.querySelectorAll("#graphicalforecast-hour button").forEach(e => e.removeAttribute("disabled"));
		if(e.target.dataset.day > 2) {
			document.querySelectorAll("#graphicalforecast-hour button").forEach(e => {
				if ( (parseInt(e.dataset.hour)+1)/3%2 == 0 ) {
					e.setAttribute("disabled","true");
				}
			})
		}
	} else if (e.target.hasAttribute("data-hour")) {
		var day = parseInt(document.querySelector("#graphicalforecast-day button[data-active='true']").dataset.day);
		window.day = day;
		window.hour = parseInt(e.target.dataset.hour);
		var i = day * 8 + (parseInt(e.target.dataset.hour)+1)/3 - 2 - (h>20 ? 4 : 0);
		console.log([day, hour, i])
	}
	document.querySelector("#graphicalforecast-img").src = `https://graphical.weather.gov/images/massachusetts/WindSpd${i}_massachusetts.png`
}

window.addEventListener('DOMContentLoaded', (event) => {
	document.querySelectorAll("#graphicalforecast-day button, #graphicalforecast-hour button").forEach(e => e.addEventListener('click',changeGraphicalForecast));
	document.querySelector("#graphicalforecast-day button").dataset.active=true;
})