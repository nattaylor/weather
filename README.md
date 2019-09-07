# Weather

Display weather from the National Weather Service

## Getting Started

1. Edit the Bot name and rename `config.example.php` to `config.php`
2. If you want to use reverse GeoCoding create `.secrets.php` and add `<?php const GEOCODING_APIKEY = "YourKey";`
3. Deploy somewhere that runs PHP.

For litespeed, I use the following `.htaccess` caching configuration:

```
<IfModule LiteSpeed>
RewriteEngine On
RewriteRule (.*\.php)?$ - [E=cache-control:max-age=60]
</IfModule>
```

## Motivation

This project started as an experiment to learn about the National Weather Service APIs and present them conviently.

Then, since I am a sailor and I like to be my own weatherman, I started adding additional elements to make that workflow more convient too.

The primary goal is smartphone usability, especially loading time, presentation and convienience.

## TODO

- Implement location configuration
- Look for AFD warnings of "disagreement"
- Diff the AFD between issuances
- Add SailFlow https://api.weatherflow.com/wxengine/rest/graph/getGraph?spot_id=1788&time_start=2019-09-07%2000:00:00&time_end=2019-09-07%2024:00:00&units_wind=kts&fields=wind&wf_token=27c8cfd62708b58ac7fa8d1442326751&color_plot_bg=0xFAFAFA&wind_speed_floor=31&graph_height=330&graph_width=635&type=line4&format=raw&v=1.1&cb=1567869210596
- Add `manifest.json` for PWA
- Use a cookie after releases to clear the cache
- Refactor Javascript into PHP
- Refactor templating to `p()`
- Refactor helpers out of classes (should it be a static?)
- Refactor constants to `config.php`
- Refector separate data from view
- Refactor DEBUG --> switch to logging approach?  add email alerts?  Avoid frequent emails by tracking "last sent time"
- Refactor AFD parser to make it less brittle
- ~Create generalized loop-er (Support navigation, Allow "skip nights" toggle,  Support "Jump to day")!~
- ~Parameterize TTL on curlCatchRetrieve~
- ~Clean up HTML comments~

## Notes

### AFD

The current code is bad and in the very least needs to be refactored with better error handling.

At the risk of over-engineering (for the sake of learning!) I'm contemplating:

* It seems like there is an opportunity to define a grammar like [YACC](http://dinosaur.compilertools.net/yacc/) and then generate the parser from it https://github.com/ircmaxell/PHP-Yacc
* It also seems like this should be its own library that works with PSR-4: Autoloader

## References

* [API Documentation](https://forecast-v3.weather.gov/documentation) & https://www.weather.gov/documentation/services-web-api

### Weather Map Loop
https://origin.wpc.ncep.noaa.gov/basicwx/day0-7loop.html

```
https://origin.wpc.ncep.noaa.gov/basicwx/
https://origin.wpc.ncep.noaa.gov/sfc/loopimagesfcwbg.gif
https://origin.wpc.ncep.noaa.gov/medr/9jhwbgloop.gif

/sfc/loopimagesfcwbg.gif
/basicwx/93fndfd_loop.gif
/basicwx/94fndfd_loop.gif
/basicwx/95fndfd_loop.gif
/basicwx/96fndfd_loop.gif
/basicwx/98fndfd_loop.gif
/basicwx/99fndfd_loop.gif
/medr/9jhwbgloop.gif
/medr/9khwbgloop.gif
/medr/9lhwbgloop.gif
/medr/9mhwbgloop.gif
/medr/9nhwbgloop.gif
```

### Satelite Loop

https://www.star.nesdis.noaa.gov/GOES/sector_band.php?sat=G16&sector=ne&band=GEOCOLOR&length=12

```
src='https://cdn.star.nesdis.noaa.gov/GOES16/ABI/SECTOR/ne/GEOCOLOR/
```

### WindFinder

WindFinder The Superforecast is based on the newest versions of high resolution weather prediction models. The Superforecast is available for Europe, North America, South Africa, Egypt and the Canary Islands. The horizontal resolution is 5 kilometers. Forecasts are computed 4 times per day. Predictions are available in time steps of 1 hour for up to 3 days into the future. Forecast and Superforecast are based on different physical models and therefore may cause divergent predictions. Due to its higher horizontal resolution the Superforecast should be more accurate, especially for locations with a complex topography and local thermal effects. The arrows point in the direction that the wind is blowing.
https://www.windfinder.com/forecast/marblehead_coolidge_road
https://www.windfinder.com/forecast/salem_harbor_pitman_road
https://www.windfinder.com/forecast/marblehead_neck
https://ocean.weather.gov/Atl_tab.shtml
Currents: OSCAR