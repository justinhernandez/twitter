<?php defined('SYSPATH') OR die('No direct access allowed.');

return array(

	/**
	 * Default application name. ex: "about 11 hours ago from Kohana Twitterlib"
	 * To register your app with Twitter go here - http://twitter.com/help/request_source.
	 * So when tweeting it will say something other than 'from Web'.
	 *
	 * DEFAULT: kohana
	 */
	'application' => 'kohana',

	/**
	 * Default format to return the data. Either xml or json.
	 * rss and atom are other options but not supported by all methods.
	 *
	 * DEFAULT: json
	 */
	'format' => 'json',

	/**
	 * Display extra debug info? IN_PRODUCTION also has to be FALSE.
	 *
	 * DEFAULT: TRUE
	 */
	'debug' => TRUE,


	/* SEARCH OPTIONS */

	/**
	 * restricts tweets to the given language, given by an ISO 639-1 code.
	 * Ex: http://search.twitter.com/search.atom?lang=>en&q=>devo
	 * check here for lang types http://en.wikipedia.org/wiki/ISO_639-1
	 *
	 * DEFAULT: en
	 */
	'lang' => 'en',

	/**
	 * the number of tweets to return per page, up to a max of 100.
	 * Ex: http://search.twitter.com/search.atom?lang=>en&q=>devo&rpp=>15
	 * 
	 * DEFAULT: 25
	 */
	'rpp' => 25,

	/**
	 * the page number (starting at 1) to return, up to a max of roughly 1500
	 * results (based on rpp * page)
	 * 
	 * DEFAULT: 1
	 */
	'page' => 1,

	/**
	 * returns tweets with status ids greater than the given id.
	 *
	 * DEFAULT: NULL
	 */
	'since_id' => NULL,

	/**
	 * returns tweets by users located within a given radius of the given
	 * latitude/longitude, where the user's location is taken from their Twitter
	 * profile. The parameter value is specified by "latitide,longitude,radius",
	 * where radius units must be specified as either "mi" (miles) or "km"
	 * (kilometers).
	 * Ex: http://search.twitter.com/search.atom?geocode=>40.757929%2C-73.985506%2C25km.
	 * Note that you cannot use the near operator via the API to geocode arbitrary
	 * locations, however you can use this geocode parameter to search near geocodes
	 * directly.
	 *
	 * DEFAULT: NULL
	 */
	'geocode' => NULL,

	/**
	 * when "true", adds "<user>:" to the beginning of the tweet. This is useful for
	 * readers that do not display Atom's author field. The default is "false".
	 *
	 * DEFAULT: FALSE
	 */
	'show_user' => FALSE,

);
