<?php

/**
 * Work with NWS Weather Data
 *
 * Retrieves data from the NWS and generates HTML to present it.
 *
 * Usage: instantiate then call one of the methods for generating HTML
 *
 * ```
 * $weather = new Weather(array("location"=>"42.3755,-71.0368","reverse_geo_code"=>false));
 * echo $weather->generateCurrentAndForecastHtml();
 * ```
 *
 * https://www.weather.gov/documentation/services-web-api
 */

class Weather {

	private $forecast;
	private $options;
	private $templates;
	private $current;
	private $geo;
	private $debug = ["logs"=>[]];
	private $stations;
	private $stationId;
	private $healthCheck;

	const POINT_ENDPOINT            = "https://api.weather.gov/points/%s";
	const STATIONS_ENDPOINT         = "https://api.weather.gov/points/%s/stations";
	const STATION_CURRENT_ENDPOINT  = "https://api.weather.gov/stations/%s/observations/latest";
	const FORECAST_DAILY_ENDPOINT   = "https://api.weather.gov/gridpoints/BOX/%s/forecast";
	const FORECAST_HOURLY_ENDPOINT  = "https://api.weather.gov/gridpoints/BOX/%s/forecast/hourly";
	const WEBURL                    = "https://forecast.weather.gov/MapClick.php?lat=42.37458823179665&lon=-71.03582395942152";
	const GEOCODING_ENDPOINT        = "https://geoservices.tamu.edu/Services/ReverseGeocoding/WebService/v04_01/Rest/?lat=%s&lon=%s&format=json&notStore=false&version=4.10&apikey=%s";
	const RADAR_BASE                = "https://radar.weather.gov/RadarImg/NCR/BOX/";
	const BUOY_ENDPOINT             = "https://www.ndbc.noaa.gov/data/latest_obs/%s.rss";
	// For frequently updated resources like forecasts and observations
	const SHORT_TTL                 = 3600;
	// For infrequently updated resources like metadata
	const LONG_TTL                  = 604800;
	const WEATHER_MAPS2             = array("/sfc/91fndfd_loop.gif", "/basicwx/93fndfd_loop.gif", "/basicwx/94fndfd_loop.gif", "/basicwx/95fndfd_loop.gif", "/basicwx/96fndfd_loop.gif", "/basicwx/98fndfd_loop.gif", "/basicwx/99fndfd_loop.gif", "/medr/9jhwbgloop.gif", "/medr/9khwbgloop.gif", "/medr/9lhwbgloop.gif", "/medr/9mhwbgloop.gif", "/medr/9nhwbgloop.gif");
	const WEATHER_MAPS              = array("/basicwx/91fndfd_loop.gif", "/basicwx/94fndfd_loop.gif", "/basicwx/98fndfd_loop.gif", "/medr/9jhwbgloop.gif", "/medr/9khwbgloop.gif", "/medr/9lhwbgloop.gif", "/medr/9mhwbgloop.gif", "/medr/9nhwbgloop.gif");
	const WEATHER_MAP_BASE          = "https://origin.wpc.ncep.noaa.gov%s";
	const SATELITE_LISTING          = "https://cdn.star.nesdis.noaa.gov/GOES16/ABI/SECTOR/ne/GEOCOLOR/";
	const GRAPHICAL_BASE            = "https://graphical.weather.gov/images/massachusetts/WindSpd%s_massachusetts.png";
	const TIDES_API                 = "https://tidesandcurrents.noaa.gov/api/datagetter?begin_date=%s&end_date=%s&station=8442645&product=predictions&interval=hilo&datum=mllw&units=english&time_zone=lst_ldt&application=NatTaylorDotCom&format=json";

	/**
	 * Setup the Weather object by retrieving the weather and the current observation
	 * @param Array $options
	 */
	function __construct($options) {
		$this->forecast = (object)[];
		$this->setupTemplates();
		$this->options = (object)$options;
		$this->expireCache();

		//TODO: Stop if the first one fails
		$this->point = json_decode($this->cacheCurlRetrieve(sprintf(self::POINT_ENDPOINT, $this->options->location)));
		$this->forecast->daily = json_decode($this->cacheCurlRetrieve($this->point->properties->forecast));
		$this->forecast->hourly = json_decode($this->cacheCurlRetrieve($this->point->properties->forecastHourly));
		$this->current  = $this->retrieveCurrentObservation($this->point->properties->observationStations);
	}

	private function x() {

	}

	private function curlRetrieve($url) {
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, USERAGENT);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
		$output = curl_exec($ch);
		$size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		curl_close($ch);
		return $this->parseResponse($output, $size);
	}

	private function cacheCurlRetrieve($url, $ttl = null) {
		$filename = hash('md5', $url);
		$files = file_exists("cache/cache.dat") ? json_decode(file_get_contents("cache/cache.dat")) : (object) [];
		//var_dump($files);
		/*
		$filename = getenv('TMPDIR').'weathertmp_'.hash('md5',$url);
		
		if (is_null($ttl)) {
			$ttl = (strpos($url, 'forecast') || strpos($url, 'observation') || strpos($url, 'radar')) ? self::SHORT_TTL : self::LONG_TTL;
		}
		*/
		$this->debug['TMPDIR'] = getcwd();
		array_push($this->debug["logs"], "Tried retrieving $url");

		if (!file_exists("cache/".$filename)) {
			$response = $this->curlRetrieve($url);
			if (json_decode($response[0])->status == 404) {
				array_push($this->debug["logs"], "404 status for $url");
				return false;
			}
			if (isset($response[1]['cache-control'])) {
				if (preg_match('/max-age=(?<max_age>[0-9]+)/', $response[1]['cache-control'], $match)) {
				}
			}
			$expire = isset($match['max_age']) ? intval($match['max_age']) : self::SHORT_TTL;
			if (file_put_contents("cache/".$filename, $response[0])) {
				$files->$filename = $expire;
			}
		}

		//TODO: Check for 404s etc

		try {
			$write = file_put_contents("cache/cache.dat", json_encode($files, JSON_PRETTY_PRINT));
			if ($write === false) {
				throw new Exception("Failed to write $filename");
			}
		} catch (Exception $e) {
			array_push($this->debug["logs"], "{$e->getMessage()}");
		}

		try {
			$content = file_get_contents("cache/".$filename);
			if ($content === false) {
				throw new Exception("Failed to get contents");
			}
		} catch (Exception $e) {
			array_push($this->debug["logs"], "{$e->getMessage()}");
		}
		return $content;
	}

	private function parseResponse($output, $size) {
		$headersStr = substr($output, 0, $size);
		$body = substr($output, $size);
		$headers = [];
		$statusLine;
		foreach (explode("\r\n", trim($headersStr)) as $header) {
			if (!isset($statusLine)) {
				$statusLine = $header;
				continue;
			}
			list($name, $value) = explode(":", $header);
			$headers[strtolower($name)] = trim($value);
		}
		return [$body, $headers];
	}

	private function expireCache() {
		if (!file_exists("cache/cache.dat")) {
			return true;
		}
		$files = json_decode(file_get_contents("cache/cache.dat"));
		foreach ((array)$files as $filename => $ttl) {
			if (filemtime("cache/".$filename) <= time()-$ttl) {
				if (unlink("cache/".$filename)) {
					unset($files->$filename);
				}
			}
		}
		file_put_contents("cache/cache.dat", json_encode($files));
		return true;
	}

	/** Helper to return regex matches */
	function match_all($str, $regex, $trim = true) {
		preg_match_all($regex, $str, $results, PREG_SET_ORDER);
		foreach ($results as $key => $result) {
			$results[$key] = $result;
		}
		return $results;
	}

	/** Helper to instantiate the templates */
	function setupTemplates() {
		$this->templates = (object)array();
		$this->templates->weatherDetails = <<<HTML
<details class="weather-details"%s>
		<summary class="weather-summary">
			<span class="weather-period">%s</span>
			<span class="weather-short">%s</span>
			<div class="weather-aside">
				<span class="weather-precip">%s</span>
				<span class="weather-icon">%s</span>
				<span class="weather-high">%s</span>
				<span class="weather-low">%s</span>
			</div>
		</summary>
		<div class="weather-detailed">
			<p>%s</p>
			<div class="weather-hourly">
				%s
			</div>
		</div>
	</details>
HTML;
		$this->templates->weatherCurrent = <<<HTML
<details class="current-details">
	<summary class="current-summary">
		<span class="current-period">%s</span>
		<span class="weather-short">%s</span>
		<div class="current-aside">
			<span class="current-icon">%s</span>
		</div>
	</summary>
	<div id="afd-summary"></div>
</details>
HTML;
		$this->templates->hourly = <<<HTML
<div class="weather-hour">
	<div class="weather-hour-temp">%s</div>
	<div class="weather-hour-icon">%s</div>
	<div class="weather-hour-time">%s</div>
</div>
HTML;
	}

	public function getOffice() {
		return "BOX";
	}

	function retrieveForecast($location) {
		return (object)array(
			"daily" => json_decode( $this->cacheCurlRetrieve( sprintf( self::FORECAST_DAILY_ENDPOINT, $location) ) ),
			"hourly" => json_decode( $this->cacheCurlRetrieve( sprintf( self::FORECAST_HOURLY_ENDPOINT, $location) ) )
		);
	}

	/**
	 * Retrieve the current weather observation for a station near a location
	 *
	 * @see  https://www.weather.gov/documentation/services-web-api
	 *
	 * Note: Observations are retrieved for stations, not locations.
	 * The first call retrives the stations near a location.
	 *
	 * @param  String $location latlong
	 * @return Object the station current observation
	 */
	private function retrieveCurrentObservation($url) {
		$this->stations = json_decode($this->cacheCurlRetrieve($url));
		$this->stationId = $this->stations->features[0]->properties->stationIdentifier;
		$observation = json_decode($this->cacheCurlRetrieve(sprintf(self::STATION_CURRENT_ENDPOINT, $this->stationId)));
		return $observation;
	}

	function getForecast() {
		return json_encode( $this->forecast, JSON_PRETTY_PRINT );
	}

	/**
	 * Generate the HTML to present the forecast by iterating over the forecast JSON
	 *
	 * Note: Periods are 12 hours long, so during daytime we show 2 periods-worth, but at night we show just one.
	 *
	 * @return String The HTML to present the forecast
	 */
	function generateForecastHtml() {
		$html = "";
		$periods = $this->forecast->daily->properties->periods;
		$i=0;
		if(!$periods[0]->isDaytime) {
			$i=1;
			$html .= vsprintf($this->templates->weatherDetails, array(
					" open",
					$periods[0]->name,
					$this->helperShortShortener($periods[0]->shortForecast),
					$this->helperChanceOfPrecip(array($periods[0]->detailedForecast,"")),
					$this->helperIconNwsToUnicode($periods[0]->icon),
					"",
					$periods[0]->temperature."&deg;",
					$periods[0]->detailedForecast,
					"",
					)
				);
		}

		for($i, $len=count($periods)-$i; $i<$len; $i+=2 ) {
			//TODO Clean this up so it doesn't do so much stuff
			$hourlyHtml = $this->generateHourlyHtml($periods[$i]);
			$html .= vsprintf($this->templates->weatherDetails, array(
					($i==0) ? " open" : "",
					($i>0) ? $periods[$i]->name . ", ". date ( "M j", strtotime($periods[$i]->startTime) ) : $periods[$i]->name,
					$this->helperShortShortener($periods[$i]->shortForecast),
					$this->helperChanceOfPrecip(array($periods[$i]->detailedForecast,$periods[$i+1]->detailedForecast)),
					$this->helperIconNwsToUnicode($periods[$i]->icon),
					$periods[$i]->temperature."&deg;",
					$periods[$i+1]->temperature."&deg;",
					$periods[$i]->detailedForecast." Overnight: ".$periods[$i+1]->detailedForecast,
					$hourlyHtml)
				);
		}
		return $html;
	}

	/** Helper to generate the hourly forecast widgets */

	function generateHourlyHtml($dayPeriod) {
		$html = "";
		$date = date('Y-m-d', strtotime($dayPeriod->startTime));
		foreach($this->forecast->hourly->properties->periods as $period) {
			$datetime = new DateTime($period->startTime);
			if($datetime->format('Y-m-d') == $date && $datetime->format('h')%2 == 0) {
				$html .= vsprintf($this->templates->hourly, array(
								"{$period->temperature}&deg;",
								$this->helperIconNwsToUnicode($period->icon),
								$datetime->format('g A')
							)
						);
			}
		}
		return $html;
	}

	/**
	 * Generate the HTML to present the current observation
	 *
	 * @return String HTML to present the observation
	 */
	function generateCurrentObservationHtml() {
		if(!isset($this->current->properties)) {
			unlink('weathertmp_'.hash('md5',sprintf( self::STATION_CURRENT_ENDPOINT, $this->stationId)));
			return sprintf("Error retrieving current conditions. <!-- %s -->",json_encode( $this->current , JSON_PRETTY_PRINT));
		}
		$html = "";
		$windDirection = function($w) {
			$w = $w == 360 ? 0 : $w;
			switch(true) {
				case $w >=   0 && $w <  45: return 'N'; break;
				case $w >=  45 && $w <  90: return 'NE'; break;
				case $w >=  90 && $w < 135: return 'E'; break;
				case $w >= 135 && $w < 180: return 'SE'; break;
				case $w >= 180 && $w < 225: return 'S'; break;
				case $w >= 225 && $w < 270: return 'SW'; break;
				case $w >= 270 && $w < 315: return 'W'; break;
				case $w >= 315 && $w < 360: return 'NW'; break;
				default: return '?';
			}
		};

		$html .= vsprintf($this->templates->weatherCurrent, array(
			implode(" ",array(
				strval(round($this->current->properties->temperature->value*9/5+32))."&deg;",
				$this->current->properties->textDescription)
			),
			"Wind ".$windDirection($this->current->properties->windDirection->value)." ".strval(round($this->current->properties->windSpeed->value*3600/1609))." MPH",
			$this->helperIconNwsToUnicode($this->current->properties->icon)
		));
		return $html;
	}

	/** Time */
	public function generateTimestamp() {
		return strftime("%a %l:%M%p", strtotime($this->current->properties->timestamp));
	}

	/** Wrapper to generate current conditions and forecast HTML */
	function generateCurrentAndForecastHtml() {
		if($this->forecast->daily->status == 404) {
			return $this->generateCurrentObservationHtml() . "<p>Sorry, no forecast is currently available as the National Weather Service API is currently not returning forecast results for this location.  Typically this lasts a few hours.</p>";
		}
		return $this->generateCurrentObservationHtml() . $this->generateForecastHtml();
	}

	/**
	 * iconHelper parses the icons from https://w1.weather.gov/xml/current_obs/weather.php into unicode
	 * see https://www.weather.gov/documentation/services-web-api#/default/get_icons
	 * @param  [type] $iconUrl [description]
	 * @return [type]          [description]
	 */
	function helperIconNwsToUnicode($iconUrl) {
		$iconKey = $this->match_all($iconUrl, '/\/([a-z_]+?)(,[0-9]*)?\?/')[0][1];
		switch($iconKey) {
			case 'bkn':          return '⛅'; break; //Mostly Cloudy | Mostly Cloudy with Haze | Mostly Cloudy and Breezy
			case 'skc':          return '☀️'; break; //Fair | Clear | Fair with Haze | Clear with Haze | Fair and Breezy | Clear and Breezy
			case 'few':          return '☀️'; break; //A Few Clouds | A Few Clouds with Haze | A Few Clouds and Breezy
			case 'sct':          return '🌤️'; break; //Partly Cloudy | Partly Cloudy with Haze | Partly Cloudy and Breezy
			case 'ovc':          return '☁️'; break; //Overcast | Overcast with Haze | Overcast and Breezy
			case 'fg':           return '🌫️'; break; //Fog
			case 'fog':          return '🌫️'; break; //Fog
			case 'smoke':        return '🌨️'; break; //Smoke
			case 'fzra':         return '🌨️'; break; //Freezing Rain
			case 'ip':           return '🌧️'; break; //Ice Pellets
			case 'mix':          return '🌧️'; break; //Freezing Rain Snow
			case 'raip':         return '🌧️'; break; //Rain Ice Pellets
			case 'rasn':         return '🌧️'; break; //Rain Snow
			case 'shra':         return '🌧️'; break; //Rain Showers
			case 'snow':         return '🌨️'; break; //Rain Showers
			case 'rain':         return '🌧️'; break; //Rain Showers
			case 'sleet':        return '🌧️'; break;
			case 'rain_snow':    return '🌧️'; break;
			case 'rain_showers': return '🌧️'; break;
			case 'tsra':         return '⛈️'; break;
			case 'wind_few':     return '🌬️'; break;
			case 'wind_bkn':     return '🌬️'; break;
			case 'tsra_hi':      return '⛈️'; break;
			case 'tsra_sct':     return '⛈️'; break;
			case 'wind_sct':     return '🌤️'; break;
			case 'wind_skc':     return '🌬️'; break;
			case 'hot':          return '🌡️'; break;
			default:             return '⁉️'; break;
		} 
		return $iconKey;
	}

	function helperChanceOfPrecip($detailedForecast) {
		//TODO fix me: take the max of either if found
		return $this->match_all($detailedForecast[0], '/Chance of precipitation is ([0-9]+%)\./')[0][1] ?? "";
	}

	function helperShortShortener($shortForecast) {
		return $this->match_all($shortForecast, '/(.*?) then/')[0][1] ?? $shortForecast;
	}

	function reverseGeocode() {
		list($lat, $long) = explode(",", $this->options->location);
		return $this->cacheCurlRetrieve(sprintf(self::GEOCODING_ENDPOINT, $lat, $long, GEOCODING_APIKEY));
	}

	function getGeo() {
		return json_encode( $this->geo, JSON_PRETTY_PRINT);
	}

	function generateWebUrl() {
		return sprintf(self::WEBURL, $this->options->location);
	}

	function generateDebugHtml() {
		$html = implode("<br>", $this->debug["logs"]);
		$html .= implode(PHP_EOL,array(
			"<script>",
			sprintf("console.log('TMPDIR: %s')", $this->debug['TMPDIR'] ?? "current"),
			sprintf("let forecast = %s", json_encode( $this->forecast, JSON_PRETTY_PRINT )),
			sprintf("let stations = %s", json_encode( $this->stations, JSON_PRETTY_PRINT )),
			"</script>"
		));
		return $html;
	}

	/**
	 * Retrieve the HTML to present the latest buoy observations
	 *
	 * Station pages (e.g. https://www.ndbc.noaa.gov/station_page.php?station=44013) have a link to an XML feed
	 *
	 * @return  String HTML to present latest buoy observations
	 */
	function generateBuoyHtml() {
		$response = $this->cacheCurlRetrieve(sprintf(self::BUOY_ENDPOINT, 44013), 3600);
		$xml = simplexml_load_string($response,null,LIBXML_NOCDATA);
		return sprintf("<!-- %s -->", $response).$xml->channel->item->description;
	}

	/**
	 * Generate the HTML to present current and forecast weather maps
	 *
	 * @see https://www.weather.gov/forecastmaps
	 * @see https://origin.wpc.ncep.noaa.gov/basicwx/day0-7loop.html
	 *
	 *
	 * @return String The HTML to present
	 */
	function generateWeatherMapsHtml() {

		$html = "<nav id=\"weathermaps-nav\">";
		$i = 0;
		$html .= array_reduce(self::WEATHER_MAPS, function($str, $item) use (&$i) {
			$url = sprintf(self::WEATHER_MAP_BASE, $item);
			$i++;
			$day = strftime("%a",strtotime("+".($i-1)." day"));
			return $str.="<button onclick=\"document.querySelector(&quot;#weathermaps-img&quot;).src=&quot;$url&quot;\">$day</button>";
		});
		$html .= "<img id=\"weathermaps-img\" src=\"".sprintf(self::WEATHER_MAP_BASE,self::WEATHER_MAPS[0])."\" style=\"max-width:100%\" />";
		$html .= "</nav>";
		return $html;
	}

	function generateSateliteHtml(){
		$html = "";
		$html .= <<<HTML
<script>
var sateliteInterval;
window.addEventListener('DOMContentLoaded', (event) => {

	document.querySelectorAll("#graphicalforecast-day button, #graphicalforecast-hour button")
		.forEach(e => e.addEventListener('click',changeGraphicalForecastAndControlUI));

	document.querySelector("#satelite summary").addEventListener('click',function(){
		if(sateliteInterval != null) {
			clearInterval(sateliteInterval);
			sateliteInterval = null;
		} else {
			sateliteInterval = setInterval(function(){
				var img = document.querySelector('#satelite-loop');
				img.src = satelite_images[img.dataset.i%12];
				img.dataset.i++;}, 250);
		}
	})

});
</script>
HTML;
		$listing = $this->cacheCurlRetrieve(self::SATELITE_LISTING);

		preg_match_all('/([0-9]{11}_GOES16-ABI-ne-GEOCOLOR-300x300\.jpg)/', $listing, $results);

		$html .= sprintf("<img src=\"%s\" data-i=\"0\" id=\"satelite-loop\" style=\"width:100%%;width:100%%\" />", self::SATELITE_LISTING.array_slice($results[0], -12, 1)[0]);

		$html .= "<script> var satelite_images = [";

		for ($i=24; $i > 0; $i-=2) {
			$url = self::SATELITE_LISTING.array_slice($results[0], -$i, 1)[0]; 
			$html .= "\"$url\", ";
		}

		$html .= "];</script>";

		return $html;
	}

	/**
	 * Present the Graphical Forecast for Wind
	 *
	 * @return String The HTML to present the forecast
	 */
	function generateGraphicalForecastHtml(){
		/**
		 * periods: day or night (8,11,2,5)
		 * current: today or tonight
		 * 3hr: periods 0-4
		 * 6hr: periods 5-12
		 */
		$html = "";
		$html .= <<<HTML
<script>
/**
 * Changes the graphical forecast image
 *
 * Note: intended to be bound to graphical forecast UI button clicks
 *
 * Makes use of datasets on target elements
 *
 * Notes on graphical forecast (see https://graphical.weather.gov/sectors/massachusetts.php#tabs)
 *
 *  - n ∈ [1,51] for `WindSpd${n}_massachusetts.png`, but close to 51 the even `n` are skipped
 *
 *  - half-day periods: day or night (8,11,2,5)
 *  - current: today or tonight
 *  - 3hr: periods 0-4
 *  - 6hr: periods 5-12
 *
 * @param  {event} e  the click event
 * @return {boolean}  always returns true
 */
function changeGraphicalForecastAndControlUI(e) {

	var day, // Targeted day
		hour, // Targeted hour
		n, // Forecast graphic to display
		currentHour = (new Date()).getHours();

	function changeDay() {

		document.querySelectorAll("#graphicalforecast-day button").forEach(e => e.dataset.active=false);

		e.target.dataset.active = true;

		day = parseInt(e.target.dataset.day);

		n = day * 8 // 8 graphics per day
			+ 1 // first day is zero, first image is 1
			+ 2 // show the 2pm image
			- (currentHour > 20 ? 4 : 0); //handle the 8am/8pm switchovers

		document.querySelectorAll("#graphicalforecast-hour button").forEach(e => e.removeAttribute("disabled"));
		if(e.target.dataset.day > 2) {
			document.querySelectorAll("#graphicalforecast-hour button").forEach(e => {
				if ( (parseInt(e.dataset.hour)+1)/3%2 == 0 ) {
					e.setAttribute("disabled","true");
				}
			})
		}
	}

	function changeHour() {

		day = parseInt(document.querySelector("#graphicalforecast-day button[data-active='true']").dataset.day);

		hour = parseInt(e.target.dataset.hour);

		n = day * 8 // 8 graphics per day
			+ (hour+1)/3 // 1 graphic per 3 hours
			- 2 // Half-day starts at 8am/8pm
			- (currentHour > 20 ? 4 : 0); // Half-day starts at 8am/8pm
	}

	if(e.target.hasAttribute("data-day")) {

		changeDay();

	} else if (e.target.hasAttribute("data-hour")) {

		changeHour();

	}

	// Prevent broken images
	n = n<0 || n>51 ? 1 : n;

	// Primary goal of the function
	document.querySelector("#graphicalforecast-img").src = `https://graphical.weather.gov/images/massachusetts/WindSpd\${n}_massachusetts.png`;

	if(document.location.search.includes('debug')) {
		console.log({"day": day, "hour": hour, "currentHour": currentHour, "n": n})
	}

	return true;

}
</script>
HTML;
		$html .= implode("",array(
			"<nav id=\"graphicalforecast-day\">",
			array_reduce( range(0,6), function($str, $i) { return
				$str .= "<button data-day=\"$i\""
					.($i == 0 ? " data-active=\"true\"" : "")
					.">"
					.strftime("%a",strtotime("+$i day"))
					."</button>";}),
			"</nav>"
		));

		$k = 0;

		$html .= implode("",array(
			"<nav id=\"graphicalforecast-hour\">",
			array_reduce([2,5,8,11,2,5,8,11],function($str, $i) use (&$k) {
				$k++;
				return $str
					.="<button data-hour=\""
					.(intval($i)+intval($k>4 ? 12 : 0))
					."\">$i</button>";}),
			"</nav>"
		));

		$html .= "<img id=\"graphicalforecast-img\" src=\"".sprintf(self::GRAPHICAL_BASE, "1")."\">";

		return $html;
	}

	/**
	 * Present tide predictions
	 *
	 * @see https://tidesandcurrents.noaa.gov/api/
	 */
	public function generateTidesHtml() {
		$tides = $this->cacheCurlRetrieve(
			vsprintf(self::TIDES_API, array(
				strftime("%Y%m%d"),
				strftime("%Y%m%d", strtotime("+6 day"))
			))
		);

		$predictions = json_decode($tides)->predictions;

		$html = "<table>";

		for($i=0; $i<count($predictions); $i+=4) {
			$data = array(
				strftime("%a",strtotime($predictions[$i]->t)),
				$predictions[$i]->type.strftime("%l:%M",strtotime($predictions[$i]->t)),
				$predictions[$i+1]->type.strftime("%l:%M",strtotime($predictions[$i+1]->t)),
				$predictions[$i+2]->type.strftime("%l:%M",strtotime($predictions[$i+2]->t)),
				$predictions[$i+3]->type.strftime("%l:%M",strtotime($predictions[$i+3]->t))
			);
			$html.="<tr><td>".implode("</td><td>", $data)."</td></tr>";
		}

		$html.="</table>";

		return $html;
	}
}
