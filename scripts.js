function wind(action) {
	const MAX3HR = 21;
	const MAX = 51;
	let e = document.querySelector("#wind");
	let idx = parseInt(e.src.match(/WindSpd(?<idx>[0-9]{1,2})_/).groups.idx);
	let increment = idx < MAX3HR || idx == MAX ? 1 : 2;
	if(action == 'next') {
		next = (idx%MAX)+increment;
	} else if (action == 'prev') {
		next = (idx%MAX)-increment;
	} else if (action == 'first') {
		next = 1;
	} else if (action == 'last') {
		next = MAX;
	}
	e.src = `https://graphical.weather.gov/images/massachusetts/WindSpd${next}_massachusetts.png`
}

function radarStart(e) {
	e.dataset.i++;
	e.src = "https://radar.weather.gov/RadarImg/NCR/BOX/"+radars[e.dataset.i%radars.length];
}


