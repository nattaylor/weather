<?php
/**
 * TODO:
 *
 * Weather
 * 	- Hourly
 *  - Responsive, Viewport, Icon, etc
 *  - Fix 2-stage icon parsing (e.g. "https:\/\/api.weather.gov\/icons\/land\/day\/snow,70\/bkn?size=medium")
 */
if(file_exists('.secrets.php')) include('.secrets.php');
include('config.php');
include('src/Weather.php');
include('src/AreaForecastDiscussion.php');
$weather = new Weather(array("location"=>"42.3755,-71.0368","reverse_geo_code"=>false));
$afd = new AreaForecastDiscussion(array("office"=>"BOX"));
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

	<details id="weather" open>
		<summary>Weather</summary>
		<?php echo $weather->generateForecastHtml(); ?>
		<p style="text-align:right"><a href="<?php echo $weather->generateWebUrl(); ?>">Open Forecast on weather.gov</a></p>
	</details>
	<details id="afd">
		<summary>Area Forecast Discussion</summary>
		<div><?php echo $afd->generateAfdHtml(); ?></div>
	</details>

	<details id="radar">
		<summary>Radar</summary>
		<div style="background-image:url('https://radar.weather.gov/ridge/Overlays/Highways/Short/BOX_Highways_Short.gif'), url('https://radar.weather.gov/ridge/Overlays/Cities/Short/BOX_City_Short.gif'), url('https://radar.weather.gov/ridge/Overlays/Topo/Short/BOX_Topo_Short.jpg');background-size:contain;"><a href="https://radar.weather.gov/radar.php?rid=box&product=N0R&overlay=11101111&loop=no"><img src="https://radar.weather.gov/RadarImg/N0R/BOX_N0R_0.gif" style="max-width: 100%"></a></div>
	</details>

	<details id="maps">
		<summary>Weather Map</summary>
		<a href="https://www.weather.gov/forecastmaps"><img src="https://www.wpc.ncep.noaa.gov//noaa/national_forecast.jpg" style="max-width: 100%" /></a>
	</details>

	<details>
		<summary>Satelite</summary>
		<div><a href="https://www.weather.gov/satellite"><img src="https://cdn.star.nesdis.noaa.gov/GOES16/ABI/CONUS/GEOCOLOR/625x375.jpg" style="max-width: 100%" /></a></div>
	</details>
	<?php echo "<!-- {$weather->getForecast()} -->"; ?>
</body>
</html>