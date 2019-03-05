<?php

/**
 * Work with NWS Weather Data
 */
class Weather {

	private $forecast;
	private $options;
	private $templates;
	private $geo;
	const FORECAST_DAILY_ENDPOINT = "https://api.weather.gov/points/%s/forecast";
	const FORECAST_HOURLY_ENDPOINT = "https://api.weather.gov/points/%s/forecast/hourly";
	const GEOCODING_ENDPOINT = "https://geoservices.tamu.edu/Services/ReverseGeocoding/WebService/v04_01/Rest/?lat=%s&lon=%s&format=json&notStore=false&version=4.10&apikey=%s";
	const WEBURL = "https://forecast-v3.weather.gov/point/%s";

	function __construct($options) {
		$this->setupHelpers();
		$this->setupTemplates();
		$this->options = (object)$options;
		if(false) {

		} else {
			if($this->options->reverse_geo_code) {
				$this->geo = json_decode( $this->geocode() );
			}
			$this->forecast = $this->retrieveForecast($this->options->location);
		}
	}

	//TODO fixme - probably will regret making these global
	function setupHelpers() {
		function curlRetrieve($url) {
			$ch = curl_init($url); 
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
			curl_setopt($ch, CURLOPT_USERAGENT, USERAGENT);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			//curl_setopt($ch, CURLOPT_VERBOSE, true);
			$output = curl_exec($ch); 
			curl_close($ch);
			return $output;
		}

		function cacheCurlRetrieve($url) {
			$filename = getenv('TMPDIR').hash('md5',$url);
			if(!file_exists($filename) || filemtime($filename)<time()-3600) {
				file_put_contents($filename, curlRetrieve($url));
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
		</div>
	</details>
HTML;
	}

	function retrieveForecast($location) {
		return (object)array(
			"daily" => json_decode( cacheCurlRetrieve( sprintf( self::FORECAST_DAILY_ENDPOINT, $location) ) ),
			"hourly" => json_decode( cacheCurlRetrieve( sprintf( self::FORECAST_HOURLY_ENDPOINT, $location) ) )
		);
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
					$periods[0]->detailedForecast)
				);
		}

		for($i, $len=count($periods)-$i; $i<$len; $i+=2 ) {
			//TODO Clean this up so it doesn't do so much stuff
			$html .= vsprintf($this->templates->weatherDetails, array(
					($i==0) ? " open" : "",
					($i>0) ? $periods[$i]->name . ", ". date ( "M j", strtotime($periods[$i]->startTime) ) : $periods[$i]->name,
					$this->helperShortShortener($periods[$i]->shortForecast),
					$this->helperChanceOfPrecip(array($periods[$i]->detailedForecast,$periods[$i+1]->detailedForecast)),
					$this->helperIconNwsToUnicode($periods[$i]->icon),
					$periods[$i]->temperature."&deg;",
					$periods[$i+1]->temperature."&deg;",
					$periods[$i]->detailedForecast." Overnight: ".$periods[$i+1]->detailedForecast)
				);
		}
		return $html;
	} 

	/**
	 * iconHelper parses the icons from https://w1.weather.gov/xml/current_obs/weather.php into unicode
	 * @param  [type] $iconUrl [description]
	 * @return [type]          [description]
	 */
	function helperIconNwsToUnicode($iconUrl) {
		$iconKey = match_all($iconUrl, '/\/([a-z]+?)(,[0-9]*)?\?/')[0][1];
		switch($iconKey) {
			case 'bkn':   return '⛅'; break; //Mostly Cloudy | Mostly Cloudy with Haze | Mostly Cloudy and Breezy
			case 'skc':   return '☀️'; break; //Fair | Clear | Fair with Haze | Clear with Haze | Fair and Breezy | Clear and Breezy
			case 'few':   return '🌤️'; break; //A Few Clouds | A Few Clouds with Haze | A Few Clouds and Breezy
			case 'sct':   return '🌤️'; break; //Partly Cloudy | Partly Cloudy with Haze | Partly Cloudy and Breezy
			case 'ovc':   return '☁️'; break; //Overcast | Overcast with Haze | Overcast and Breezy
			case 'fg':    return '🌫️'; break; //Fog
			case 'smoke': return '🌨️'; break; //Smoke
			case 'fzra':  return '🌨️'; break; //Freezing Rain
			case 'ip':    return '🌧️'; break; //Ice Pellets
			case 'mix':   return '🌧️'; break; //Freezing Rain Snow
			case 'raip':  return '🌧️'; break; //Rain Ice Pellets
			case 'rasn':  return '🌧️'; break; //Rain Snow
			case 'shra':  return '🌧️'; break; //Rain Showers
			case 'snow':  return '🌨️'; break; //Rain Showers
			case 'rain':  return '🌧️'; break; //Rain Showers
			case 'sleet': return '🌧️'; break;
			default:      return '⁉️';
		} 
		return $iconKey;
	}

	function helperChanceOfPrecip($detailedForecast) {
		//TODO fix me: take the max of either if found
		return match_all($detailedForecast[0], '/Chance of precipitation is ([0-9]+%)\./')[0][1] ?? "";
	}

	function helperShortShortener($shortForecast) {
		return match_all($shortForecast, '/(.*?) then/')[0][1] ?? $shortForecast;
	}

	function reverseGeocode() {
		list($lat, $long) = explode(",", $this->options->location);
		return cacheCurlRetrieve(sprintf(self::GEOCODING_ENDPOINT, $lat, $long, GEOCODING_APIKEY));
	}

	function getGeo() {
		return json_encode( $this->geo, JSON_PRETTY_PRINT);
	}

	function generateWebUrl() {
		return sprintf(self::WEBURL, $this->options->location);
	}
}