<?php

/**
 * Work with NWS Weather Data
 *
 * Usage: `$weather = new Weather(array("location"=>"42.3755,-71.0368","reverse_geo_code"=>false)); echo $weather->generateCurrentAndForecastHtml();`
 */
class Weather {

	private $forecast;
	private $options;
	private $templates;
	private $current;
	private $geo;
	private $debug;

	const STATIONS_ENDPOINT = "https://api.weather.gov/points/%s/stations";
	const STATION_CURRENT_ENDPOINT = "https://api.weather.gov/stations/%s/observations/current";
	const FORECAST_DAILY_ENDPOINT = "https://api.weather.gov/points/%s/forecast";
	const FORECAST_HOURLY_ENDPOINT = "https://api.weather.gov/points/%s/forecast/hourly";
	const WEBURL = "https://forecast-v3.weather.gov/point/%s";
	const GEOCODING_ENDPOINT = "https://geoservices.tamu.edu/Services/ReverseGeocoding/WebService/v04_01/Rest/?lat=%s&lon=%s&format=json&notStore=false&version=4.10&apikey=%s";
	// For frequently updated resources like forecasts and observations
	const SHORT_TTL = 3600;
	// For infrequently updated resources like metadata
	const LONG_TTL = 604800;

	function __construct($options) {
		$this->setupTemplates();
		$this->options = (object)$options;
		if(false) {

		} else {
			if($this->options->reverse_geo_code) {
				$this->geo = json_decode( $this->geocode() );
			}
			$this->forecast = $this->retrieveForecast($this->options->location);
			$this->current  = $this->retrieveCurrentObservation($this->options->location);
		}
	}

	function curlRetrieve($url) {
		$ch = curl_init($url); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($ch, CURLOPT_USERAGENT, USERAGENT);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_VERBOSE, true);
		$output = curl_exec($ch); 
		curl_close($ch);
		return $output;
	}

	function cacheCurlRetrieve($url) {
		$filename = getenv('TMPDIR').'weathertmp_'.hash('md5',$url);
		$this->debug['TMPDIR'] = getenv('TMPDIR');
		$ttl = (strpos($url, 'forecast') || strpos($url, 'observation')) ? self::SHORT_TTL : self::LONG_TTL;
		if(!file_exists($filename) || filemtime($filename)<time()-$ttl) {
			file_put_contents($filename, $this->curlRetrieve($url));
		}
		return file_get_contents($filename);
	}

	function match_all($str, $regex, $trim = true) {
		preg_match_all($regex, $str, $results, PREG_SET_ORDER);
		foreach ($results as $key => $result) {
			$results[$key] = $result;
		}
		return $results;
	}

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
<details class="current-details" open>
	<summary class="current-summary">
		<span class="current-period">%s</span>
		<span class="weather-short">%s</span>
		<div class="current-aside">
			<span class="current-icon">%s</span>
		</div>
	</summary>
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

	function retrieveForecast($location) {
		return (object)array(
			"daily" => json_decode( $this->cacheCurlRetrieve( sprintf( self::FORECAST_DAILY_ENDPOINT, $location) ) ),
			"hourly" => json_decode( $this->cacheCurlRetrieve( sprintf( self::FORECAST_HOURLY_ENDPOINT, $location) ) )
		);
	}

	function retrieveCurrentObservation($location) {
		$stations  = json_decode( $this->cacheCurlRetrieve( sprintf( self::STATIONS_ENDPOINT, $location) ) );
		$stationId = $stations->features[0]->properties->stationIdentifier;
		return json_decode( $this->cacheCurlRetrieve( sprintf( self::STATION_CURRENT_ENDPOINT, $stationId) ) );
	}

	function getForecast() {
		return json_encode( $this->forecast, JSON_PRETTY_PRINT );
	}

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

	function generateCurrentObservationHtml() {
		$html = "";
		$windDirection = function($w) {
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

	function generateCurrentAndForecastHtml() {
		if($this->forecast->daily->status == 404) {
			return $this->generateCurrentObservationHtml() . "<p>Sorry, no forecast is currently available as the National Weather Service API is currently not returning forecast results for this location.  Typically this lasts a few hours.</p>";
		}
		return $this->generateCurrentObservationHtml() . $this->generateForecastHtml();
	}

	/**
	 * iconHelper parses the icons from https://w1.weather.gov/xml/current_obs/weather.php into unicode
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
		$html = "<script>";
		$html .= "console.log('TMPDIR: {$this->debug['TMPDIR']}')".PHP_EOL;
		$html .= "console.log('Declared `forecast`')".PHP_EOL;
		$html .= sprintf("let forecast = %s", json_encode( $this->forecast, JSON_PRETTY_PRINT ));
		$html .= "</script>";
		return $html;
	}
}