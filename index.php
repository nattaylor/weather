<?php
	date_default_timezone_set('America/New_York');

	if(file_exists('.secrets.php')) include('.secrets.php');
	include('config.php');
	include('src/Weather.php');
	include('src/AreaForecastDiscussion.php');

	$url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";

	if (isset($_GET['purge'])) {
		foreach (glob("cache/*") as $filename) {
			unlink($filename);
		}
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => $url,
		  CURLOPT_RETURNTRANSFER => false,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 1,
		  CURLOPT_HEADER => 1,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "PURGE",
		));

		$response = curl_exec($curl);
		echo sprintf("<!-- %s -->", $response);
		curl_close($curl);
	}

	//header("Location: $url")
	
	$weather = new Weather(array(
		"location"=>"42.3755,-71.0368",
		"reverse_geo_code"=>false,
		"debug"=>isset($_GET['debug'])
	));

	$afd = new AreaForecastDiscussion(array("office"=>$weather->getOffice()));

?>
<!DOCTYPE html>
<html lang="en-US">
<head>
	<title>Weather</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="apple-touch-icon" sizes="128x128" href="icon.png">
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<link rel="icon" sizes="128x128" href="icon.png">
	<link rel="stylesheet" href="style.css">
</head>
<body>
	<details id="weather" open>
		<summary class="section-summary">Weather: Boston, MA, <?php echo $weather->generateTimestamp(); ?></summary>
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

	<details id="satelite">
		<summary class="section-summary">Satellite Imagery</summary>
		<div><?php echo $weather->generateSateliteHtml(); ?></div>
		<p><a href="https://www.star.nesdis.noaa.gov/GOES/sector.php?sat=G16&sector=ne">Open satelite imagery on weather.gov</a></p>
	</details>

	<details id="graphical">
		<summary class="section-summary">Graphical Forecast</summary>
		<?php echo $weather->generateGraphicalForecastHtml(); ?>
		<p><a href="https://graphical.weather.gov/sectors/massachusetts.php#tabs">Open graphical forecast on weather.gov</a></p>
	</details>

	<details id="buoy">
		<summary class="section-summary">Buoy Observations</summary>
		<div><?php echo $weather->generateBuoyHtml(); ?></div>
		<a href="https://www.ndbc.noaa.gov/station_page.php?station=44013">Open NDBC station page on weather.gov</a></div>
	</details>

	<details id="tides">
		<summary class="section-summary">Tides</summary>
		<div><?php echo $weather->generateTidesHtml(); ?></div>
		<a href="https://tidesandcurrents.noaa.gov/stationhome.html?id=8442645">Open CO-OPS Station page on weather.gov</a>
	</details>

	<details id="afd">
		<summary class="section-summary">Area Forecast Discussion</summary>
		<div><?php echo $afd->generateAfdHtml(); ?></div>
		<p><a href="https://forecast.weather.gov/product.php?site=BOX&issuedby=BOX&product=AFD&format=TXT&version=1&glossary=0&highlight=off">Open area forecast discussion on weather.gov</a></p>
	</details>

	<details id="links">
		<summary class="section-summary">Links</summary>
		<ul class="links">
			<li><a href="https://www.windy.com/?42.428,-70.800,7,i:pressure">Windy</a></li>
			<li><a href="https://www.windfinder.com/forecast/marblehead_neck">https://www.windfinder.com/forecast/marblehead_neck</a></li>
			<li><a href="https://sailflow.com/spot/1788">SaiFlow: Children's Island</a></li>
		</ul>
	</details>

	<details id="about">
		<summary class="section-summary">About</summary>
		<p>This weather webapp was built by Nat Taylor &lt;<a href="mailto:nattaylor@gmail.com">nattaylor@gmail.com</a>&gt;.  The data is retrieved from APIs offered by the National Weather Service.</p>
		<p>The current version is for Boston, MA.</p>
		<p>Share by clicking this button <button onclick="if(navigator.share) {navigator.share({url: document.location})} else {alert('Share not supported.')}">Share Page</button> or copying this link <a href="<?php echo $url; ?>"><?php echo $url; ?></a></p>
		<p>Reload with a <a href="<?php echo "$url?purge";?>">cache buster</a> or <a href="<?php echo "$url?debug";?>">debug output</a>.</p>
		<p>The source is available at <a href="https://github.com/nattaylor/weather">https://github.com/nattaylor/weather</a>.</p>
	</details>

	<div id="config">
		<button onclick="document.location.hash='#'">x</button>
		<input type="text" placeholder="Zip Code">
	</div>
<?php
		if (isset($_GET['debug'])) {
			$html = $weather->generateDebugHtml();
			echo <<<HTML
	<details>
		<summary class="section-summary">Debug</summary>
		$html
	</details>
HTML;
		}
	?>
	<div>&nbsp;</div>
	
	<script type="text/javascript">
		window.addEventListener('DOMContentLoaded', (event) => {
			let content = document.querySelector("#afd > div > p:nth-child(2)").innerText;
			document.querySelector("#afd-summary").innerText = content;
		});
		document.querySelectorAll("button")
			.forEach(function(e) {
				e.addEventListener("click", function() {
					var toast = document.createElement("div");
					toast.classList.add("toast");
					toast.innerText = "Loading...";
					document.body.appendChild(toast);
				})
			});
		document.querySelectorAll("#graphicalforecast-img, #weathermaps-img").forEach(function(e) {
			e.addEventListener("load", function() {
				if (document.querySelector(".toast") !== null) {
					document.body.removeChild(document.querySelector(".toast"));
				}
			})
			e.addEventListener("error", function() {
				if (document.querySelector(".toast") !== null) {
					document.body.removeChild(document.querySelector(".toast"));
				}
			})
		});
	</script>
</body>
</html>
