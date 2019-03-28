<?php


class AreaForecastDiscussion {
	const USERAGENT = "Mozilla/5.0 (compatible; NatTaylorPersonalHomepageWeatherGovBot; +http://nattaylor.com/bot.html#weathergov)";
	const APIENDPOINT = "https://api.weather.gov/products/types/AFD/locations/%s";
	private $forecast;
	private $options;
	private $afdRaw;

	function __construct($options) {
		$this->options = (object)$options;
		$this->setupHelpers();
		$this->afdRaw = $this->retrieveAreaForecastDiscussion();
		$this->forecast = $this->parseAreaForecastDiscussion($this->afdRaw);
	}

	function setupHelpers() {
		if (!function_exists('curlRetrieve')) {
			function curlRetrieve($url) {
				$ch = curl_init($url); 
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
				curl_setopt($ch, CURLOPT_USERAGENT, USERAGENT);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
				$output = curl_exec($ch); 
				curl_close($ch);
				return $output;
			}
		}

		if (!function_exists('cacheCurlRetrieve')) {
			function cacheCurlRetrieve($url) {
				$filename = getenv('TMPDIR').'weathertmp_'.hash('md5',$url);
				if(!file_exists($filename) || filemtime($filename)<time()-3600) {
					file_put_contents($filename, curlRetrieve($url));
				}
				return file_get_contents($filename);
			}
		}
	}

	function retrieveAreaForecastDiscussion() {
		$latestProductUrl = json_decode(cacheCurlRetrieve(sprintf(self::APIENDPOINT, $this->options->office)))->{"@graph"}[0]->{"@id"};
		return json_decode(cacheCurlRetrieve($latestProductUrl))->productText;

	}

	function parseAreaForecastDiscussion($afdRaw) {
		//Header
		foreach(array('blank','000','id','product','blank','name','office','timestamp') as $data) {
			list($line,$document) = explode("\n", $afdRaw, 2);
			$afdRaw = $document;
			if($data != 'blank') $afd[$data] = $line;
		}
		
		//Footer
		list($afdRaw, $footer) = preg_split('/^\$\$$/m', $afdRaw);
		$afd["footer"] = $footer;

		//Content
		preg_match_all('/^\..*?...$.*?^&&$/sm', $afdRaw, $sections);

		$afd["sections"] = array_map(function($section){
				preg_match_all('/^\.(.*?)...$\n(.*?)\n\n^&&$/sm', $section, $sectionSplit);
				preg_match_all('/(.*?) \/(.*?)\//sm', $sectionSplit[1][0], $headingSplit);
				
				$heading = !isset(array_slice($headingSplit, 1)[0][0]) ? array($sectionSplit[1][0]) : array_reduce(array_slice($headingSplit, 1), function($collect=array(), $item) {$collect[] = $item[0]; return $collect;}) ;
				return array(
					$heading,
					preg_replace('/\n(?!\n)/', ' ', $sectionSplit[2][0])
				);
		}, $sections[0]);

		return json_encode( $afd , JSON_PRETTY_PRINT);

	}

	function generateAfdHtml() {
		foreach(json_decode( $this->forecast )->sections as $section) {
			echo "<h2>{$section[0][0]}</h2>".PHP_EOL;
			if (isset($section[0][1])) echo "<p>{$section[0][1]}</p>".PHP_EOL;
			foreach(explode("\n", $section[1]) as $line) {
				if(count(preg_grep('/\.\.\.\ ?$/', array($line)))>0) {
					if($section[0][0]=='LONG TERM') {
						if(count(preg_grep('/^\ ?\*\//', array($line)))>0) {
							echo "<h3>".str_replace("*/", "", $line);
							if(isset($_GET['debug'])) echo "</h3><!-- $line -->";
							echo PHP_EOL;
						} else {
							echo "<h4>$line</h4>".PHP_EOL;
						}
					} else {
						echo "<h3>$line</h3>".PHP_EOL;
					}
				} else {
					if(count(preg_grep('/^\ +?-/', array($line)))>0) {
						echo "<ul><li>".implode("</li><li>", explode("-", $line))."</li></ul>".PHP_EOL;
					} else {
						echo "<p>$line</p>";
						if(isset($_GET['debug'])) echo "<!-- $line -->";
						echo PHP_EOL;
					}
				}
			}
		}
	}

	function raw() {
		$ch = curl_init("https://forecast-v3.weather.gov/products/locations/BOX/AFD/1?format=text"); 
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
			curl_setopt($ch, CURLOPT_USERAGENT, USERAGENT);
			$output = curl_exec($ch); 
			curl_close($ch);
			return $output;
	}
}
