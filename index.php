<?php
/**
 * TODO:
 *
 * Weather
 * 	- Hourly
 *  - Responsive, Viewport, Icon, etc
 */
if(file_exists('.secrets.php')) include('.secrets.php');
include('config.php');
include('src/Weather.php');
include('src/AreaForecastDiscussion.php');
$weather = new Weather(array("location"=>"42.3755,-71.0368","reverse_geo_code"=>false));
$afd = new AreaForecastDiscussion(array("office"=>"BOX"));
echo "<!-- {$weather->getForecast()} -->";
?>
<!DOCTYPE html>
<html>
<head>
	<title>Weather</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="icon" href="icon.png">
	<style type="text/css">
		html {
			font-family: sans-serif;
		}

		body {
		    font-weight: lighter;
		    font-size: smaller;
		    margin-top: 1.5em;
		}
		.weather-details {
			border-top:1px solid #ccc;
		}

		.weather-period {
			display: block;
		}
		.weather-summary {
			list-style: none;
			position: relative;
			margin:5px;
		}

		.weather-high {
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

		.weather-short {
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

		.weather-icon {
			position: absolute;
			font-size:2em;
			top:-2px;
			right:25px;
		}

		.weather-summary::-webkit-details-marker {
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

	</style>
</head>
<body>
	<nav class="nav">
		<a href="#weather">Weather</a> <a href="#afd">Discussion</a>
	</nav>

	<h1 id="weather">Weather</h1>
	<?php echo $weather->generateForecastHtml(); ?>
	<h1 id="afd">Area Forecast Discussion</h1>
	<?php echo $afd->generateAfdHtml(); ?>
<!--
<div style="background-image:url('http://radar.weather.gov/ridge/Overlays/Highways/Short/BOX_Highways_Short.gif'), url('https://radar.weather.gov/ridge/Overlays/Cities/Short/BOX_City_Short.gif'), url('https://radar.weather.gov/ridge/Overlays/Topo/Short/BOX_Topo_Short.jpg');background-size:contain;"><img src="https://radar.weather.gov/RadarImg/N0R/BOX_N0R_0.gif" style="max-width: 100%"></div>
	<a href="https://www.weather.gov/forecastmaps"><img src="https://www.wpc.ncep.noaa.gov//noaa/national_forecast.jpg" style="max-width: 100%" /></a>
	<a href="https://www.weather.gov/satellite"><img src="https://cdn.star.nesdis.noaa.gov/GOES16/ABI/CONUS/GEOCOLOR/1250x750.jpg" style="max-width: 100%" /></a>
-->
</body>
</html>