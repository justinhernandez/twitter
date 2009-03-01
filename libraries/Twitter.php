<?php defined('SYSPATH') or die('No direct script access.');
/*
 * Copyright (c) <2008> Justin Poliey <jdp34@njit.edu>
 * Adapted from http://jdp.github.com/twitterlibphp
 *
 * Version: 0.1.1
 */

class Twitter_Core {

	// Username:password format string
	private $login;
	// Url to string to call
	private $url;
	// Name of calling application
	private $application;
	// Default format to return, can be overrided
	private $format;
	// Contains the last HTTP status code returned
	private $status;
	// Contains the last API call
	private $last;
	// Http headers
	private $headers = array();
	// Ignore cURL error numbers, useful for destroy methods since it returns
	// NULL and cURL will return error #26: unreadable
	private $ignore_curl_error_numbers = array();

	// Twitter method urls
	const STATUS		= 'http://twitter.com/statuses';
	const USER			= 'http://twitter.com/users';
	const MESSAGE		= 'http://twitter.com/direct_messages';
	const FRIENDSHIP	= 'http://twitter.com/friendships';
	const ACCOUNT		= 'http://twitter.com/account';
	const FAVORITE		= 'http://twitter.com/favorites';
	const NOTIFICATION	= 'http://twitter.com/notifications';
	const BLOCK			= 'http://twitter.com/blocks';
	const HELP			= 'http://twitter.com/help';

	/**
	 * Singleton instance of Twitter.
	 *
	 * @param   string  $username	Twitter username
	 * @param   string  $password	Twitter password for username
	 * @param   string  $application	Twitter application name
	 * @param   string  $format		Encoding format
	 * @return  object
	 */
	public static function instance($username = FALSE, $password = FALSE, $application = FALSE, $format = FALSE)
	{
		static $instance;

		// Create the instance if it does not exist
		empty($instance) and $instance = new Twitter($username, $password, $application, $format);

		return $instance;
	}

	/**
	 * Constructs and returns a new Twitter object.
	 *
	 * @param   string  $username	Twitter username
	 * @param   string  $password	Twitter password for username
	 * @param   string  $application	Twitter application name
	 * @param   string  $format		Encoding format
	 * @return  object
	 */
	public static function factory($username = FALSE, $password = FALSE, $application = FALSE, $format = FALSE)
	{
		return new Twitter($username, $password, $application, $format);
	}

	/**
	 * Twitter class constructor
	 *
	 * @param  string  $username	Twitter username
	 * @param  string  $password	Twitter password for username
	 * @param  string  $application	Twitter application name
	 * @param  string  $format		Encoding format
	 */
	function __construct($username, $password, $application = FALSE, $format = FALSE)
	{
		$this->login = "$username:$password";
		$this->application = ($application) ? $application : (Kohana::config('twitter.application'));
		$this->application = urlencode($this->application);
		$this->format = ($format) ? $format : (Kohana::config('twitter.format'));
	}

	
	/* STATUS METHODS */


	/**
	 * Returns the 20 most recent statuses from non-protected users who have set
	 * a custom user icon.  Does not require authentication.  Note that the public
	 * timeline is cached for 60 seconds so requesting it more often than that is
	 * a waste of resources.
	 *
	 * URL: http://twitter.com/statuses/public_timeline.format
	 * Formats: xml, json, rss, atom
	 * Method(s): GET
	 *
	 * 
	 * @return  string
	 */
	public function public_timeline()
	{
		$this->url = self::STATUS.'/public_timeline.'.$this->format;
		
		return $this->connect();
	}


	/**
	 * Returns the 20 most recent statuses posted by the authenticating user and
	 * that user's friends. This is the equivalent of /home on the Web.
	 *
	 * URL: http://twitter.com/statuses/friends_timeline.format
	 * Formats: xml, json, rss, atom
	 * Method(s): GET
	 * 
	 *
	 * @param   string  $since		Since time string
	 * @param   string  $since_id	Since status id
	 * @param	int		$count		# of statuses to retrieve. 200 max.
	 * @param	int		$page		Start from status page #
	 * @return  string
	 */
	public function friends_timeline($since = NULL, $since_id = NULL, $count = NULL, $page = NULL)
	{
		$this->url = self::STATUS.'/friends_timeline.'.$this->format;
		$args = func_get_args();
		$this->add(array('since', 'since_id', 'count', 'page'), $args);

		return $this->connect(TRUE);
	}

	/**
	 * Returns the 20 most recent statuses posted from the authenticating user.
	 * It's also possible to request another user's timeline via the id parameter
	 * below. This is the equivalent of the Web /archive page for your own user,
	 * or the profile page for a third party.
	 *
	 * URL: http://twitter.com/statuses/user_timeline.format
	 * Formats: xml, json, rss, atom
	 * Method(s): GET
	 *
	 * 
	 * @param   mixed	$id			User # or user id, i.e. BizStone
	 * @param   int		$count		# of statuses to retrieve. 200 max.
	 * @param   string	$since		Since time string
	 * @param   int		$since_id	Since status id
	 * @return  string
	 */
	function user_timeline($id = NULL, $count = 20, $since = NULL, $since_id = NULL)
	{
		// check for id
		$this->url = ($id) ? self::STATUS."/user_timeline/$id.$this->format"
						   : self::STATUS."/user_timeline.$this->format";
		$args = func_get_args();
		// shift to ignore id
		array_shift($args);
		$this->add(array('count', 'since', 'since_id'), $args);

		return $this->connect(TRUE);
	}

	/**
	 * Returns a single status, specified by the id parameter below.  The
	 * status's author will be returned inline.
	 *
	 * URL: http://twitter.com/statuses/show/id.format
	 * Formats: xml, json
	 * Method(s): GET
	 *
	 * 
	 * @param	int		$id		Status id #
	 * @return  string
	 */
	function show_status($id)
	{
		$this->url = self::STATUS."/show/$id.$this->format";

		return $this->connect(TRUE);
	}

	/**
	 * Updates the authenticating user's status.  Requires the status parameter
	 * specified below.  Request must be a POST.  A status update with text
	 * identical to the authenticating user's current status will be ignored.
	 *
	 * URL: http://twitter.com/statuses/update.format
	 * Formats: xml, json.  Returns the posted status in requested format when successful.
	 * Method(s): POST
	 *
	 * NOTE: Go here to get your app recognized - http://twitter.com/help/request_source.
	 *
	 * 
	 * @param   string	$status					Status text body
	 * @param   int		$in_reply_to_status_id	Reply to status #
	 * @return  string
	 */
	function update_status($status, $in_reply_to_status_id = NULL)
	{
		// chops status length to 140
		$status = substr(stripslashes($status), 0, 139);
		$this->url = self::STATUS."/update.$this->format";
		$data = array('status' => $status, 'in_reply_to_status_id' => $in_reply_to_status_id);
		$this->add('source', $this->application);

		return $this->connect(TRUE, TRUE, $data);
	}

	/**
	 * Returns the 20 most recent @replies (status updates prefixed with
	 * @username) for the authenticating user.
	 *
	 * URL: http://twitter.com/statuses/replies.format
	 * Formats: xml, json, rss, atom
	 * Method(s): GET
	 * 
	 *
	 * @param   int		$page		Start from page #
	 * @param   string	$since		Since time string
	 * @param   int		$since_id	Since status id
	 * @return  string
	 */
	function replies($page = NULL, $since = NULL, $since_id = NULL)
	{
		$this->url = self::STATUS."/replies.$this->format";
		$args = func_get_args();
		$this->add(array('page', 'since', 'since_id'), $args);

		return $this->connect(TRUE);
	}

	/**
	 * Destroys the status specified by the required ID parameter.  The
	 * authenticating user must be the author of the specified status.
	 *
	 * URL: http://twitter.com/statuses/destroy/id.format
	 * Formats: xml, json
	 * Method(s): POST, DELETE
	 *
	 *
	 * @param   int		$id		Status id #
	 * @return  string
	 */
	function destroy_status($id)
	{
		$this->url = self::STATUS."/destroy/$id.$this->format";
		// cURL returns NULL
		$this->ignore_curl_error_numbers[] = 27;

		//return $this->connect(TRUE, TRUE);
		return $this->connect(TRUE, TRUE);
	}


	/* USER METHODS */


	/**
	 * Returns the authenticating user's friends, each with current status
	 * inline. They are ordered by the order in which they were added as friends.
	 * It's also possible to request another user's recent friends list via the
	 * id parameter below.
	 *
	 * URL: http://twitter.com/statuses/friends.format
	 * Formats: xml, json
	 * Method(s): GET
	 *
	 *
	 * @param   mixed	$id		User id # or screen name
	 * @param   int		$page	Retrieves next 100 friends from page #
	 * @return  string
	 */
	function friends($id = NULL, $page = NULL)
	{
		$this->url = ($id) ? self::STATUS."/friends/$id.$this->format"
						   : self::STATUS."/friends.$this->format";
		$this->add('page', $page);

		return $this->connect(TRUE);
	}

	/**
	 * Returns the authenticating user's followers, each with current status
	 * inline.  They are ordered by the order in which they joined Twitter (this
	 * is going to be changed).
	 *
	 * URL: http://twitter.com/statuses/followers.format
	 * Formats: xml, json
	 * Method(s): GET
	 *
	 * 
	 * @param   mixed	$id		User id # or screen name
	 * @param   int		$page	Retrieves next 100 friends from page #
	 * @return  string
	 */
	function followers($id = NULL, $page = NULL)
	{
		$this->url = ($id) ? self::STATUS."/followers/$id.$this->format"
						   : self::STATUS."/followers.$this->format";
		$this->add('page', $page);

		return $this->connect(TRUE);
	}
	
	/**
	 * Returns extended information of a given user, specified by ID or screen
	 * name as per the required id parameter below.  This information includes
	 * design settings, so third party developers can theme their widgets
	 * according to a given user's preferences. You must be properly
	 * authenticated to request the page of a protected user.
	 *
	 * URL: http://twitter.com/users/show/id.format
	 * Formats: xml, json
	 * Method(s): GET
	 *
	 *
	 * @param   mixed	$id				Screen name or user id #
	 * @param   string	$email			Find user by email
	 * @param	int		$user_id		User id #
	 * @param	string	$screen_name	Screen name
	 * @return  string
	 */
	function user($id = NULL, $email = NULL, $user_id = NULL, $screen_name = NULL)
	{
		$this->url = ($id) ? self::USER."/show/$id.$this->format"
						   : self::USER."/show.$this->format";
		$args = func_get_args();
		// shift to ignore id
		array_shift($args);
		$this->add(array('email', 'user_id', 'screen_name'), $args);

		return $this->connect(TRUE);
	}


	/**
	 * Returns a list of the 20 most recent direct messages sent to the
	 * authenticating user.  The XML and JSON versions include detailed
	 * information about the sending and recipient users.
	 *
	 * URL: http://twitter.com/direct_messages.format
	 * Formats: xml, json, rss, atom
	 * Method(s): GET
	 *
	 *
	 * @param   string	$since		Since time string
	 * @param   int		$since_id	Since status id
	 * @param   int		$page		Start from page #
	 * @return  string
	 */
	function direct_messages($since = NULL, $since_id = NULL, $page = NULL)
	{
		$this->url = self::MESSAGE.".$this->format";
		$args = func_get_args();
		$this->add(array('since', 'since_id', 'page'), $args);

		return $this->connect(TRUE);
	}

	/**
	 * Returns a list of the 20 most recent direct messages sent by the
	 * authenticating user.  The XML and JSON versions include detailed
	 * information about the sending and recipient users.
	 *
	 * URL: http://twitter.com/direct_messages/sent.format
	 * Formats: xml, json
	 * Method(s): GET
	 *
	 *
	 * @param   string	$since		Since time string
	 * @param   int		$since_id	Since status id
	 * @param   int		$page		Start from page #
	 * @return  string
	 */
	function sent_messages($since = NULL, $since_id = NULL, $page = NULL)
	{
		$this->url = self::MESSAGE."/sent.$this->format";
		$args = func_get_args();
		$this->add(array('since', 'since_id', 'page'), $args);
		
		return $this->connect(TRUE);
	}

	/**
	 * Sends a new direct message to the specified user from the authenticating
	 * user. Requires both the user and text parameters below. Request must be a
	 * POST.  Returns the sent message in the requested format when successful.
	 *
	 * URL: http://twitter.com/direct_messages/new.format
	 * Formats: xml, json
	 * Method(s): POST
	 *
	 * @param   string  $user  Twitter username
	 * @param   string  $text  Message body
	 * @return  strint
	 */
	function new_message($user, $text)
	{
		$text = substr(stripslashes($text), 0, 139);
		$this->url = self::MESSAGE."/new.$this->format";
		$data = array('user' => $user, 'text' => $text);
		$this->add('source', $this->application);

		return $this->connect(TRUE, TRUE, $data);
	}

	/**
	 * Destroys the direct message specified in the required ID parameter.  The
	 * authenticating user must be the recipient of the specified direct message.
	 *
	 * URL: http://twitter.com/direct_messages/destroy/id.format
	 * Formats: xml, json
	 * Method(s): POST, DELETE
	 *
	 *
	 * @param   int     $id  Message id #
	 * @return  string
	 */
	function destroy_message($id)
	{
		$this->url = self::MESSAGE."/destroy/$id.$this->format";
		// cURL returns NULL
		$this->ignore_curl_error_numbers[] = 26;
		
		return $this->connect(TRUE, TRUE);
	}


	/* FRIENDSHIP METHODS */


	/**
	 * Befriends the user specified in the ID parameter as the authenticating
	 * user.  Returns the befriended user in the requested format when
	 * successful.  Returns a string describing the failure condition when
	 * unsuccessful.
	 *
	 * URL: http://twitter.com/friendships/create/id.format
	 * Formats: xml, json
	 * Method(s): POST
	 *
	 * NOTE: $follow is supported but it doesn't seem to work correctly.
	 *
	 *
	 * @param   mixed   $id			Twitter username or user id
	 * @param	string  $follow		Notify target user of friendship?
	 * @return  string
	 */
	function create_friendship($id, $follow = 'TRUE' )
	{
		$this->url = self::FRIENDSHIP."/create/$id.$this->format?follow=".$follow;

		return $this->connect(TRUE, TRUE);
	}

	/**
	 * Discontinues friendship with the user specified in the ID parameter as
	 * the authenticating user.  Returns the un-friended user in the requested
	 * format when successful.  Returns a string describing the failure
	 * condition when unsuccessful.
	 *
	 * URL: http://twitter.com/friendships/destroy/id.format
	 * Formats: xml, json
	 * Method(s): POST, DELETE
	 *
	 *
	 * @param   mixed   $id  Twitter username or user id
	 * @return  string
	 */
	function destroy_friendship($id)
	{
		$this->url = self::FRIENDSHIP."/destroy/$id.$this->format";
		// cURL returns NULL
		$this->ignore_curl_error_numbers[] = 26;

		return $this->connect(TRUE, TRUE);
	}

	/**
	 * Tests if a friendship exists between two users.
	 *
	 * URL: http://twitter.com/friendships/exists.format
	 * Formats: xml, json
	 * Method(s): GET
	 *
	 * 
	 * @param   string  $user_a  Twitter username or user id
	 * @param   string  $user_b  Twitter username or user id
	 * @return  boolean
	 */
	function friendship_exists($user_a, $user_b)
	{
		$this->url = self::FRIENDSHIP."/exists.$this->format?user_a=$user_a&user_b=$user_b";

		return $this->connect(TRUE);
	}


	/* SOCIAL GRAPH METHODS */


	/**
	 * Returns an array of numeric IDs for every user the specified user is
	 * following.
	 *
	 * URL: http://twitter.com/friends/ids.xml
	 * Formats: xml, json
	 * Method(s): GET
	 *
	 *
	 * @param   mixed   $id  Twitter username or user id
	 * @return  string
	 */
	function friend_ids($id = NULL)
	{
		$this->url = ($id) ? "http://twitter.com/friends/ids/$id.$this->format"
						   : "http://twitter.com/friends/ids.$this->format";

		$this->connect(TRUE);
	}

	/**
	 * Returns an array of numeric IDs for every user the specified user is
	 * followed by.
	 *
	 * URL: http://twitter.com/followers/ids.format
	 * Formats: xml, json
	 * Method(s): GET
	 *
	 * @param   mixed   $id  Twitter username or user id
	 * @return  string
	 */
	function follower_ids($id = NULL)
	{
		$this->url = ($id) ? "http://twitter.com/followers/ids/$id.$this->format"
						   : "http://twitter.com/followers/ids.$this->format";

		$this->connect(TRUE);
	}


	/* ACCOUNT METHODS */


	/**
	 * Returns an HTTP 200 OK response code and a representation of the
	 * requesting user if authentication was successful; returns a 401 status
	 * code and an error message if not.  Use this method to test if supplied
	 * user credentials are valid.
	 *
	 * URL: http://twitter.com/account/verify_credentials.format
	 * Formats: xml, json
	 * Method(s): GET
	 *
	 * @return  int  200 for valid, 401 for invalid
	 */
	function verify_credentials()
	{
		$this->url = self::ACCOUNT."/verify_credentials.$this->format";
		
		return $this->connect(TRUE);
	}

	/**
	 * Ends the session of the authenticating user, returning a null cookie.
	 * Use this method to sign users out of client-facing applications like
	 * widgets.
	 *
	 * URL: http://twitter.com/account/end_session.format
	 * Formats: xml, json
	 * Method(s): POST
	 *
	 * 
	 * @return  string, null cookie
	 */
	function end_session()
	{
		$this->url = self::ACCOUNT."/end_session.$this->format";
		
		return $this->connect(TRUE);
	}

	/**
	 * Sets which device Twitter delivers updates to for the authenticating user.
	 * Sending none as the device parameter will disable IM or SMS updates.
	 *
	 * URL: http://twitter.com/account/update_delivery_device.format
	 * Formats: xml, json
	 * Method(s): POST
	 *
	 * 
	 * @param   string  $device  Update device. Must be sms, im, none
	 * @return  string
	 */
	function update_delivery_device($device)
	{
		$this->url = self::ACCOUNT."/update_delivery_device.$this->format?device=$device";

		return $this->connect(TRUE, TRUE);
	}

	/**
	 * Sets one or more hex values that control the color scheme of the
	 * authenticating user's profile page on twitter.com.  These values are
	 * also returned in the /users/show API method.
	 *
	 * URL: http://twitter.com/account/update_profile_colors.format
	 * Formats: xml, json
	 * Method(s): POST
	 *
	 * NOTE: Pass hex colors without #
	 *
	 * @param   string  $profile_background_color		Hex color value
	 * @param   string  $profile_text_color				Hex color value
	 * @param   string  $profile_link_color				Hex color value
	 * @param   string  $profile_sidebar_fill_color		Hex color value
	 * @param   string  $profile_sidebar_border_color	Hex color value
	 * @return  string
	 */
	function update_profile_colors($profile_background_color = NULL, 
		$profile_text_color = NULL, $profile_link_color = NULL,
		$profile_sidebar_fill_color = NULL, $profile_sidebar_border_color = NULL)
	{
		$this->url = self::ACCOUNT."/update_profile_colors.$this->format";
		$args = func_get_args();
		$this->add(array(
					'profile_background_color',
					'profile_text_color',
					'profile_link_color',
					'profile_sidebar_fill_color',
					'profile_sidebar_border_color'),
					$args);

		return $this->connect(TRUE, TRUE);
	}

	/**
	 * Updates the authenticating user's profile image.  Expects raw multipart
	 * data, not a URL to an image.
	 *
	 * URL: http://twitter.com/account/update_profile_image.format
	 * Formats: xml, json
	 * Method(s): POST
	 *
	 * NOTE: Must be a valid GIF, JPG, or PNG image of less than 700 kilobytes
	 * in size.  Images with width larger than 500 pixels will be scaled down.
	 * Twitter is buggy on this function.
	 *
	 *
	 * @param   string  $raw_data   Raw image data
	 * @param   string  $mime       Image mime type
	 * @return  string
	 */
	function update_profile_image($raw_data, $mime)
	{
		$this->url = self::ACCOUNT."/update_profile_image.$this->format";
		$data = array('image' => $raw_data);
		$this->headers[] = 'Content-Type: '.$mime;

		return $this->connect(TRUE, TRUE, $data);
	}

	/**
	 * Updates the authenticating user's profile background image.  Expects raw
	 * multipart data, not a URL to an image.
	 *
	 * URL: http://twitter.com/account/update_profile_background_image.format
	 * Formats: xml, json
	 * Method(s): POST
	 *
	 * NOTE: Must be a valid GIF, JPG, or PNG image of less than 800 kilobytes
	 * in size.  Images with width larger than 2048 pixels will be scaled down.
	 * Twitter is buggy on this function.
	 *
	 *
	 * @param   string  $raw_data   Raw image data
	 * @param   string  $mime       Image mime type
	 * @return  string
	 */
	function update_profile_background_image($raw_data, $mime)
	{
		$this->url = self::ACCOUNT."/update_profile_background_image.$this->format";
		$data = array('image' => $raw_data);
		$this->headers[] = 'Content-Type: '.$mime;

		return $this->connect(TRUE, TRUE, $data);
	}

	/**
	 * Returns the remaining number of API requests available to the requesting
	 * user before the API limit is reached for the current hour. Calls to
	 * rate_limit_status do not count against the rate limit.  If authentication
	 * credentials are provided, the rate limit status for the authenticating
	 * user is returned.  Otherwise, the rate limit status for the requester's
	 * IP address is returned.
	 *
	 * URL: http://twitter.com/account/rate_limit_status.format
	 * Formats: xml, json
	 * Method(s): GET
	 *
	 *
	 * @return  string
	 */
	function rate_limit_status()
	{
		$this->url = self::ACCOUNT."/rate_limit_status.$this->format";

		return $this->connect(TRUE);
	}

	/**
	 * Sets values that users are able to set under the "Account" tab of their
	 * settings page. Only the parameters specified will be updated; to only
	 * update the "name" attribute, for example, only include that parameter in
	 * your request.
	 *
	 * URL: http://twitter.com/account/update_profile.format
	 * Formats: xml, json
	 * Method(s): POST
	 *
	 *
	 * @param <type> $name
	 * @param <type> $email
	 * @param <type> $url
	 * @param <type> $location
	 * @param <type> $description
	 * @return <type>
	 */
	function update_profile($name = NULL, $email = NULL, $url = NULL,
		$location = NULL, $description = NULL)
	{
		$this->url = self::ACCOUNT."/update_profile.$this->format";
		$args = func_get_args();
		$this->add(array('name', 'email', 'url', 'location', 'description'), $args);

		return $this->connect(TRUE, TRUE);
	}


	/* FAVORITE METHODS */


	/**
	 * Returns the 20 most recent favorite statuses for the authenticating user
	 * or user specified by the ID parameter in the requested format.
	 *
	 * URL: http://twitter.com/favorites.format
	 * Formats: xml, json, rss, atom
	 * Method(s): GET
	 *
	 * 
	 * @param   string  $id    Screen name or user id #
	 * @param   string  $page  Page #, 20 per page
	 * @return  string
	 */
	function favorites($id = NULL, $page = NULL)
	{
		$this->url = ($id) ? self::FAVORITE."/$id.$this->format"
						   : self::FAVORITE.".$this->format";
		$this->add('page', $page);

		return $this->connect(TRUE);
	}

	/**
	 * Favorites the status specified in the ID parameter as the authenticating user.  Returns the favorite status when successful.
	 * 
	 * URL: http://twitter.com/favorites/create/id.format
	 * Formats: xml, json
	 * Method(s): POST
	 *
	 * @param  string  $id  Status id #
	 * @return string
	 */
	function create_favorite($id)
	{
		$this->url = self::FAVORITE."/create/$id.$this->format";

		return $this->connect(TRUE, TRUE);
	}

	/**
	 * Un-favorites the status specified in the ID parameter as the
	 * authenticating user.  Returns the un-favorited status in the requested
	 * format when successful.
	 *
	 * URL: http://twitter.com/favorites/destroy/id.format
	 * Formats: xml, json
	 * Method(s): POST, DELETE
	 *
	 *
	 * @param  string  $id  Status id #
	 * @return string
	 */
	function destroy_favorite($id)
	{
		$this->url = self::FAVORITE."/destroy/$id.$this->format";
		// cURL returns NULL
		$this->ignore_curl_error_numbers[] = 26;

		return $this->connect(TRUE, TRUE);
	}


	/* NOTIFICATION METHODS */


	/**
	 * Enables notifications for updates from the specified user to the
	 * authenticating user.  Returns the specified user when successful.
	 *
	 * URL:http://twitter.com/notifications/follow/id.format
	 * Formats: xml, json
	 * Method(s): POST
	 *
	 * NOTE: Doesn't seem like this works. Deprecated?
	 * 
	 *
	 * @param   string  $id  Screen name or user id #
	 * @return  string
	 */
	function follow($id)
	{
		$this->url = self::NOTIFICATION."/follow/$id.$this->format";

		return $this->connect(TRUE, TRUE);
	}

	/**
	 * Disables notifications for updates from the specified user to the
	 * authenticating user.  Returns the specified user when successful.
	 *
	 * URL: http://twitter.com/notifications/leave/id.format
	 * Formats: xml, json
	 * Method(s): POST
	 *
	 * NOTE: Doesn't seem like this works. Deprecated?
	 *
	 *
	 * @param   string  $id    Screen name or user id #
	 * @return  string
	 */
	function leave($id)
	{
		$this->url = self::NOTIFICATION."/leave/$id.$this->format";

		return $this->connect(TRUE, TRUE);
	}


	/* BLOCK METHODS */


	/**
	 * Blocks the user specified in the ID parameter as the authenticating user.
	 * Returns the blocked user in the requested format when successful.  You
	 * can find out more about blocking in the Twitter Support Knowledge Base.
	 *
	 * URL: http://twitter.com/blocks/create/id.format
	 * Formats: xml, json
	 * Method(s): POST
	 *
	 * NOTE: Creating a block on a user makes you unfollow them.
	 *
	 * @param  string  $id  Screen name or user id #
	 * @return string
	 */
	function create_block($id)
	{
		$this->url = self::BLOCK."/create/$id.$this->format";

		return $this->connect(TRUE, TRUE);
	}

	/**
	 * Un-blocks the user specified in the ID parameter as the authenticating
	 * user.  Returns the un-blocked user in the requested format when successful.
	 *
	 * URL: http://twitter.com/blocks/destroy/id.format
	 * Formats: xml, json
	 * Method(s): POST, DELETE
	 *
	 * 
	 * @param  string  $id  Screen name or user id #
	 * @return string
	 */
	function destroy_block($id)
	{
		$this->url = self::BLOCK."/destroy/$id.$this->format";

		return $this->connect(TRUE, TRUE);
	}

	/**
	 * Returns the string "ok" in the requested format with a 200 OK HTTP status
	 * code.
	 *
	 * URL: http://twitter.com/help/test.format
	 * Formats: xml, json
	 * Method(s): GET
	 *
	 *
	 * @return  string
	 */
	function test()
	{
		$this->url = self::HELP."/test.$this->format";

		return $this->connect(TRUE);
	}

	/**
	 * Return http status stored in private class variable $status.
	 *
	 * @return  string
	 */
	public function status()
	{
		return $this->status;
	}

	/**
	 * Return last api call stored in private class variable $last.
	 *
	 * @return  string
	 */
	public function last() {
		return $this->last;
	}

	/**
	 * Set format type to return. Chainable.
	 *
	 * @param	string  $enc
	 * @return	object
	 */
	public function format($enc)
	{
		if ($enc) $this->format = $enc;

		return $this;
	}

	/**
	 * Adds parameters to url string. If ? already exists then use &.
	 * If passed param is not an array then it will convert to one.
	 *
	 * @param   string  $param
	 * @param   string  $value
	 * @return  string
	 */
	private function add($params, $values)
	{
		if (empty($values)) return FALSE;

		if (!is_array($params))
		{
			$params = array($params);
			$values = array($values);
		}

		// iterate over parameters
		foreach($params AS $i => $param)
		{
			$val = @$values[$i];
			if ($val)
			{
				$this->url .= (strpos($this->url, '?')) ? "&$param=" . $val
													   : "?$param=" . $val;
			}
		}
	}

	/**
	 * cURL connection function. Returns data from Twitter and interfaces with
	 * debug function.
	 *
	 * @param   bool    $login			Login to Twitter?
	 * @param   bool    $post			Use POST instead of GET?
	 * @param   array    $post_data		Array data for POST.
	 * @return  string
	 */
	private function connect($login = FALSE, $post = FALSE, $post_data = NULL)
	{
		// If credentials are required add them
		if ($login) $login = $this->login;
		// add default header info
		$this->headers[] = 'Expect:';

		$curl_options = array(
						CURLOPT_RETURNTRANSFER  => TRUE,
						CURLOPT_URL             => $this->url
						);

		// curl init
		$curl = new Curl($curl_options);
		// add curl options
		if (count($this->headers)>0) $curl->addOption(CURLOPT_HTTPHEADER, $this->headers);
		if ($login) $curl->addOption(CURLOPT_USERPWD, $this->login);
		if ($post) $curl->addOption(CURLOPT_POST, TRUE);
		if (is_array($post_data)) $curl->addOption(CURLOPT_POSTFIELDS, $post_data);
		
		// retrieve data
		$data = $curl->execute($this->ignore_curl_error_numbers);
		// set curl http status
		$this->status = $curl->status();
		// set last call
		$this->last = $this->url;
		// clear settings
		$this->url = '';
		$this->headers = '';

		// debug output
		if (!IN_PRODUCTION AND Kohana::config('twitter.debug')) $this->debugo($data, $post_data);

		return $data;
	}

	/**
	 * Debug function
	 *
	 * @param  string  $data  Data returned from cURL connection.
	 * @param  array   $post  Post data array
	 */
	private function debugo($data, $post)
	{
		$method = (!$post) ? 'Get' : 'Post';

		print '<a href="'.request::referrer().'">Back</a><br/>';
		print '<h3>Status:</h3>'.$this->status;
		print '<h3>Url:</h3>'.$this->last;
		if (strpos($this->last, ' ')) print '<br/><b style="color:red">Warning:</b> You have a space in your url';
		print '<h3>Method:</h3>'.$method;
		if ($method == 'Post')
		{
			print '<h2>Post Data:</h2>';
			foreach($post as $key => $val)
			{
				print "[<em>$key</em>] => $val<br/>";
			}
		}
		print '<h3>Format:</h3>'.$this->format;
		print '<h3>Response Data:</h3>';

		// print data
		if ($this->format == 'json')
		{
			print Kohana::debug(json_decode($data));
		}
		else if ($this->format == 'xml')
		{
			print nl2br(htmlentities($data));
		}
		else
		{
			print $data;
		}

		// call Profiler
		new Profiler;
	}

}