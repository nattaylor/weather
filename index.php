<?php

if(file_exists('.secrets.php')) include('.secrets.php');
include('config.php');
include('src/Weather.php');
include('src/AreaForecastDiscussion.php');
//$weather = new Weather(array("location"=>"42.3755,-71.0368","reverse_geo_code"=>false));
$weather = new Weather(array("location"=>"40.7146,-74.0071","reverse_geo_code"=>false));
$afd = new AreaForecastDiscussion(array("office"=>"BOX"));
?>
<!DOCTYPE html>
<html>
<head>
	<title>Weather</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="apple-touch-icon" sizes="128x128" href="icon.png">
	<style type="text/css">
		html {
			font-family: sans-serif;
		}

		body {
			font-weight: lighter;
			font-size: smaller;
			margin-top: 1.5em;
		}
		.weather-details, .current-details {
			border-top:1px solid #ccc;
		}

		.weather-period.current-period {
			display: block;
		}
		.weather-summary, .current-summary {
			list-style: none;
			position: relative;
			margin:5px;
		}

		.weather-high, .current-temp {
			position: absolute;
			top:0;
			right:0;
		}

		.weather-low {
			position: absolute;
			bottom:0;
			right:0;
			color: #737373;
		}

		.weather-short, .current-short {
			white-space: nowrap;
			color: #737373;
			width: calc(320px - 65px);
			overflow: hidden;
			display: block;
			font-size: smaller;
		}

		.weather-precip {
			position: absolute;
			right:60px;
			top: 50%;
			transform: translateY(-50%);
			font-weight: bold;
			font-size:smaller;
			color:cyan;
		}

		.weather-icon, .current-icon {
			position: absolute;
			font-size:2em;
			top:-2px;
			right:25px;
		}

		.weather-summary::-webkit-details-marker, .current-summary::-webkit-details-marker {
		  display: none;
		}

		.weather-detailed {
			margin:5px;
		}

		.nav {
			position: fixed;
			top:0;
			left:0;
			right:0;
			background-color: black;
			height:1em;
			text-align: center;
		}

		.nav a {
			color:white;
			text-decoration: none;
		}

		.current-period {
			font-size:2em;
		}

		.current-icon {
			font-size:4em;
			right:0;
			top:-10px;
		}

		.current-temp {
			font-size:4em;
		}

		.current-aside {
			position: absolute;
			top: 0;
			right: 0;
		}

		#config {
			background-color: white;
			position: absolute;
			top:0;
			left:0;
			right:0;
			bottom: 0;
			display: none;
		}

		.section-summary {
			font-size:1.5em;
		}

		#config:target {
			display:block;
		}

		.weather-hour {
			display: inline-block;
			text-align: center;
			margin-right: 1vw;
		}

		.weather-hourly {
			overflow-x: scroll;
			white-space: nowrap;
		}

		.weather-hour-time {
			font-size: smaller;
			color: #737373;
		}

		.weather-hour-icon {
			font-size: x-large;
		}

		@media only screen and (min-width:70em) { 
			body {
				width:320px;
				margin: 5vw auto;
			}
		}

	</style>
</head>
<body>
	<nav class="nav">
		<a href="#weather">Weather</a> <a href="#afd">Discussion</a>
		<a href="#config">&xoplus;</a>
	</nav>

	<details id="weather" open>
		<summary class="section-summary">Weather</summary>
		<?php echo $weather->generateCurrentAndForecastHtml	(); ?>
		<p style="text-align:right"><a href="<?php echo $weather->generateWebUrl(); ?>">Open Forecast on weather.gov</a></p>
	</details>
	<details id="afd">
		<summary class="section-summary">Area Forecast Discussion</summary>
		<div><?php echo $afd->generateAfdHtml(); ?></div>
	</details>

	<details id="radar">
		<summary class="section-summary">Radar</summary>
		<div style="background-image:url('https://radar.weather.gov/ridge/Overlays/Highways/Short/BOX_Highways_Short.gif'), url('https://radar.weather.gov/ridge/Overlays/Cities/Short/BOX_City_Short.gif'), url('https://radar.weather.gov/ridge/Overlays/Topo/Short/BOX_Topo_Short.jpg');background-size:contain;"><a href="https://radar.weather.gov/radar.php?rid=box&product=N0R&overlay=11101111&loop=no"><img src="https://radar.weather.gov/RadarImg/N0R/BOX_N0R_0.gif" style="max-width: 100%"></a></div>
	</details>

	<details id="maps">
		<summary class="section-summary">Weather Map</summary>
		<a href="https://www.weather.gov/forecastmaps"><img src="https://www.wpc.ncep.noaa.gov//noaa/national_forecast.jpg" style="max-width: 100%" /></a>
	</details>

	<details id="satellite">
		<summary class="section-summary">Satellite</summary>
		<div><a href="https://www.weather.gov/satellite"><img src="https://cdn.star.nesdis.noaa.gov/GOES16/ABI/CONUS/GEOCOLOR/625x375.jpg" style="max-width: 100%" /></a></div>
	</details>
	<div id="config">
		<button onclick="document.location.hash='#'">x</button>
		<input type="text" placeholder="Zip Code">
	</div>
	<?php echo $weather->generateDebugHtml(); ?>
</body>
</html>