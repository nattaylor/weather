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
	<script src="scripts.js"></script>
	<script type="text/javascript">
		var radars = <?php echo $weather->radars(); ?>.slice(-10);
	</script>
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

	<details id="radar">
		<summary class="section-summary">Radar</summary>
		<!--
		<div style="background-image:url('BOX_Topo_Short_merged.jpg');background-size:contain;"><img src="https://radar.weather.gov/RadarImg/N0R/BOX_N0R_0.gif" id="radar" style="max-width: 100%" onclick="setInterval(radarStart,1000,this)" data-i="0"></a></div>-->
		<img src="https://radar.weather.gov/ridge/lite/N0R/BOX_loop.gif" style="max-width: 100%">
		<div><a href="https://radar.weather.gov/ridge/radar_lite.php?rid=box&product=N0R&loop=yes">See more</a></div>
	</details>

	<details id="maps">
		<summary class="section-summary">Weather Map</summary>
		<!--<a href="https://www.weather.gov/forecastmaps"><img src="https://www.wpc.ncep.noaa.gov//noaa/national_forecast.jpg" style="max-width: 100%" /></a>-->
		<div><?php echo $weather->generateWeatherMapsHtml(); ?></div>
		<p><a href="https://origin.wpc.ncep.noaa.gov/basicwx/basic_sfcjpg.shtml">Weather maps on weather.gov</a></p>
	</details>

	<details id="satellite">
		<summary class="section-summary" onclick="setInterval(function(){var img = document.querySelector('#satelite-loop'); img.src = satelite_images[img.dataset.i%12]; img.dataset.i++;},250)">Satellite</summary>
		<!--<div><a href="https://www.weather.gov/satellite"><img src="https://cdn.star.nesdis.noaa.gov/GOES16/ABI/CONUS/GEOCOLOR/625x375.jpg" style="max-width: 100%" /></a></div>-->
		<div><?php echo $weather->generateSateliteHtml(); ?></div>
		<p><a href="https://www.star.nesdis.noaa.gov/GOES/sector.php?sat=G16&sector=ne">Satelite imagery on weather.gov</a></p>
	</details>
	<details id="graphical">
		<summary class="section-summary">NOAA Graphical</summary>
		<div><img src="https://graphical.weather.gov/images/massachusetts/WindSpd1_massachusetts.png" onclick="wind('next')" id="wind" style="max-width: 100%"></div>
		<div><button onclick="wind('first')">Start</button><button onclick="wind('prev')">Prev</button><button onclick="wind('next')">Next</button><button onclick="wind('last')">Last</button><a href="https://graphical.weather.gov/sectors/massachusetts.php#tabs">NOAA Graphical</a></div>
	</details>
	<details id="buoy">
		<summary class="section-summary">NDBC Buoy</summary>
		<div><?php echo $weather->buoy(); ?></div>
		<a href="https://www.ndbc.noaa.gov/station_page.php?station=44013">NDBC 44013 Station Page</a></div>
	</details>
	<div id="config">
		<button onclick="document.location.hash='#'">x</button>
		<input type="text" placeholder="Zip Code">
	</div>
	<details id="afd">
		<summary class="section-summary">Area Forecast Discussion</summary>
		<div><?php echo $afd->generateAfdHtml(); ?></div>
	</details>
	<div>&nbsp;</div>
	<?php if(isset($_GET['debug'])) echo $weather->generateDebugHtml(); ?>
</body>
</html>