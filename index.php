<?php
	if(file_exists('.secrets.php')) include('.secrets.php');
	include('config.php');
	include('src/Weather.php');
	include('src/AreaForecastDiscussion.php');
	$weather = new Weather(array("location"=>"42.3755,-71.0368","reverse_geo_code"=>false));
	$afd = new AreaForecastDiscussion(array("office"=>$weather->getOffice()));
?>
<!DOCTYPE html>
<html>
<head>
	<title>Weather</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="apple-touch-icon" sizes="128x128" href="icon.png">
	<link rel="icon" sizes="128x128" href="icon.png">
	<link rel="stylesheet" href="style.css<?php echo (isset($_GET['purge']) ? "?".time() : ""); ?>">
	<script src="scripts.js"></script>
</head>
<body>	
	<details id="weather" open>
		<summary class="section-summary">Weather</summary>
		<?php echo $weather->generateCurrentAndForecastHtml(); ?>
		<p style="text-align:right"><a href="<?php echo $weather->generateWebUrl(); ?>">Open Forecast on weather.gov</a></p>
	</details>

	<details id="radar">
		<summary class="section-summary">Radar</summary>
		<img src="https://radar.weather.gov/ridge/lite/N0R/BOX_loop.gif" style="max-width: 100%">
		<p><a href="https://radar.weather.gov/ridge/radar_lite.php?rid=box&product=N0R&loop=yes">Open radar on weather.gov</a></p>
	</details>

	<details id="maps">
		<summary class="section-summary">Weather Map</summary>
		<div><?php echo $weather->generateWeatherMapsHtml(); ?></div>
		<p><a href="https://origin.wpc.ncep.noaa.gov/basicwx/basic_sfcjpg.shtml">Open weather maps on weather.gov</a></p>
	</details>

	<details id="satellite">
		<summary class="section-summary" onclick="setInterval(function(){var img = document.querySelector('#satelite-loop'); img.src = satelite_images[img.dataset.i%12]; img.dataset.i++;},250)">Satellite Imagery</summary>
		<div><?php echo $weather->generateSateliteHtml(); ?></div>
		<p><a href="https://www.star.nesdis.noaa.gov/GOES/sector.php?sat=G16&sector=ne">Open satelite imagery on weather.gov</a></p>
	</details>

	<details id="graphical">
		<summary class="section-summary">Graphical Wind Forecast</summary>
		<?php echo $weather->generateGraphicalForecastHtml(); ?>
	</details>

	<details id="buoy">
		<summary class="section-summary">Buoy Observations</summary>
		<div><?php echo $weather->generateBuoyHtml(); ?></div>
		<a href="https://www.ndbc.noaa.gov/station_page.php?station=44013">Open NDBC station page on weather.gov</a></div>
	</details>

	<details id="afd">
		<summary class="section-summary">Area Forecast Discussion</summary>
		<div><?php echo $afd->generateAfdHtml(); ?></div>
	</details>

	<div id="config">
		<button onclick="document.location.hash='#'">x</button>
		<input type="text" placeholder="Zip Code">
	</div>

	<div>&nbsp;</div>
	<?php if(isset($_GET['debug'])) echo $weather->generateDebugHtml(); ?>
</body>
</html>