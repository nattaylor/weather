<?php
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
	<link rel="apple-touch-icon" sizes="128x128" href="icon.png">
	<link rel="icon" sizes="128x128" href="icon.png">
	<link rel="stylesheet" href="style.css">
</head>
<body>
	<!--
	<nav class="nav">
		<a href="#weather">Weather</a> <a href="#afd">Discussion</a>
		<a href="#config">&xoplus;</a>
	</nav>
	-->

	<details id="weather" open>
		<summary class="section-summary">Weather</summary>
		<?php echo $weather->generateCurrentAndForecastHtml(); ?>
		<p style="text-align:right"><a href="<?php echo $weather->generateWebUrl(); ?>">Open Forecast on weather.gov</a></p>
	</details>
	<details id="afd">
		<summary class="section-summary">Area Forecast Discussion</summary>
		<div><?php echo $afd->generateAfdHtml(); ?></div>
	</details>

	<details id="radar">
		<summary class="section-summary">Radar</summary>
		<div style="background-image:url('BOX_Topo_Short_merged.jpg');background-size:contain;"><a href="https://radar.weather.gov/radar.php?rid=box&product=N0R&overlay=11101111&loop=no"><img src="https://radar.weather.gov/RadarImg/N0R/BOX_N0R_0.gif" style="max-width: 100%"></a></div>
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
	<?php if(isset($_GET['debug'])) echo $weather->generateDebugHtml(); ?>
</body>
</html>