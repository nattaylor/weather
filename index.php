<?php
/**
 * TODO:
 *
 * Weather
 * 	- Hourly
 * 	- AFD
 *  - Responsive, Viewport, Icon, etc
 */

include('src/Weather.php');
include('src/AreaForecastDiscussion.php');
$weather = new Weather(array("location"=>"42.3755,-71.0368"));
$afd = new AreaForecastDiscussion(array("office"=>"BOX"));
echo "<!-- {$weather->getForecast()} -->";
?>
<!DOCTYPE html>
<html>
<head>
	<title>Weather</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<style type="text/css">
		html {
			font-family: sans-serif;
		}

		body {
		    font-weight: lighter;
		    font-size: smaller;
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
	</style>
</head>
<body>
<h1>Weather</h1>
<?php echo $weather->generateForecastHtml(); ?>
<h1>Area Forecast Discussion</h1>
<?php echo $afd->generateAfdHtml(); ?>
</body>
</html>