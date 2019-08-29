# Weather

Display weather from the National Weather Service

## Getting Started

1. Edit the Bot name and rename `config.example.php` to `config.php`
2. If you want to use reverse GeoCoding create `.secrets.php` and add `<?php const GEOCODING_APIKEY = "YourKey";` 

## TODO

- Refactor templating to `p()`
- Refactor helpers out of classes (should it be a static?)
- Refactor constants to `config.php`
- Refector separate data from view
- Refactor DEBUG --> switch to logging approach?  add email alerts?  Avoid frequent emails by tracking "last sent time"
- ~Create generalized loop-er (Support navigation, Allow "skip nights" toggle,  Support "Jump to day")!
- ~Parameterize TTL on curlCatchRetrieve~
- ~Clean up HTML comments~

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