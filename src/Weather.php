<?php

/**
 * Work with NWS Weather Data
 */
class Weather {

	private $forecast;
	private $options;
	private $templates;
	const USERAGENT = "Mozilla/5.0 (compatible; NatTaylorBot; +http://nattaylor.com/bot.html#weathergov)";
	const FORECAST_ENDPOINT = "https://api.weather.gov/points/%s/forecast";

	function __construct($options) {
		$this->setupHelpers();
		$this->setupTemplates();
		$this->options = (object)$options;
		if(false) {

		} else {
			$this->forecast = $this->retrieveForecast($this->options->location);
		}
	}

	function setupHelpers() {
		function curlRetrieve($url) {
			$ch = curl_init($url); 
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
			curl_setopt($ch, CURLOPT_USERAGENT, USERAGENT);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			$output = curl_exec($ch); 
			curl_close($ch);
			return $output;
		}

		function cacheCurlRetrieve($url) {
			$filename = $_ENV['TMPDIR'].hash('md5',$url);
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
			<span class="weather-precip">%s</span>
			<span class="weather-icon">%s</span>
			<span class="weather-high">%s</span>
			<span class="weather-low">%s</span>
		</summary>
		<div class="weather-detailed">
			<p>%s</p>
		</div>
	</details>
HTML;
	}

	function retrieveForecast($location) {
		return json_decode( cacheCurlRetrieve( sprintf( self::FORECAST_ENDPOINT, $location) ) );
	}

	function getForecast() {
		return json_encode( $this->forecast, JSON_PRETTY_PRINT );
	}

	function generateForecastHtml() {
		$html = "";
		$periods = $this->forecast->properties->periods;
		if(!$periods[0]->isDaytime) {
			$first = array_shift(array_pop($periods));
		}
		for($i=0; $i<count($periods); $i+=2 ) {
			//TODO Clean this up
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
			case 'bkn': return ⛅; break; //Mostly Cloudy | Mostly Cloudy with Haze | Mostly Cloudy and Breezy
			case 'skc': return ☀️; break; //Fair | Clear | Fair with Haze | Clear with Haze | Fair and Breezy | Clear and Breezy
			case 'few': return 🌤️; break; //A Few Clouds | A Few Clouds with Haze | A Few Clouds and Breezy
			case 'sct': return 🌤️; break; //Partly Cloudy | Partly Cloudy with Haze | Partly Cloudy and Breezy
			case 'ovc': return ☁️; break; //Overcast | Overcast with Haze | Overcast and Breezy
			case 'fg': return 🌫️; break; //Fog
			case 'smoke': return 🌨️; break; //Smoke
			case 'fzra': return 🌨️; break; //Freezing Rain
			case 'ip': return 🌧️; break; //Ice Pellets
			case 'mix': return 🌧️; break; //Freezing Rain Snow
			case 'raip': return 🌧️; break; //Rain Ice Pellets
			case 'rasn': return 🌧️; break; //Rain Snow
			case 'shra': return 🌧️; break; //Rain Showers
			case 'snow': return 🌨️; break; //Rain Showers
			case 'rain': return 🌧️; break; //Rain Showers
			default: return ⁉️;
		} 
		return $iconKey;
	}

	function helperChanceOfPrecip($detailedForecast) {
		//TODO fix me
		return match_all($detailedForecast[0], '/Chance of precipitation is ([0-9]+%)\./')[0][1] ?? "";
	}

	function helperShortShortener($shortForecast) {
		return match_all($shortForecast, '/(.*?) then/')[0][1] ?? $shortForecast;
	}
}