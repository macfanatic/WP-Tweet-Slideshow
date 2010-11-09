<?

	define('TWITTER_TIMELINE_URL', 'http://twitter.com/statuses/user_timeline/%s.json?count=%d');
	define('TWITTER_SEARCH_URL', 'http://search.twitter.com/search.json?q=@%s&show_user=true');
	define('TWEET_SLIDESHOW_ACTIVATION_MESSAGE', 'tweet_slideshow_activation_message');

	/**
	 * tweet_slideshow_init
	 * Initializer that loads the plugin
	 *
	 * @return void
	 * @author Matt Brewer
	 **/

	add_action('init', 'tweet_slideshow_init');
	function tweet_slideshow_init() {
		do_action('tweet_slideshow_init');
	}


	/**
	 * tweet_slideshow_activation
	 * Called when the plugin is first activated by Wordpress
	 *
	 * @return void
	 * @author Matt Brewer
	 **/
	add_action('tweet_slideshow_parse_twitter_event', 'retrieve_tweets_from_twitter_for_user');
	function tweet_slideshow_activation() {
		
		// Add an option to store error messages from here on out
		update_option(TWEET_SLIDESHOW_ACTIVATION_MESSAGE, "");
		
		// Check to see if this install will support the plugin's needs
		if ( !tweet_slideshow_supported_configuration() ) {
			
			// Store the message to be displayed on the page
			update_option(TWEET_SLIDESHOW_ACTIVATION_MESSAGE, "WP Tweet Slideshow requires Wordpress 3.0 or higher running on a server with PHP version 5.2 or greater.");
			
			// Deactivate the plugin
			// deactivate_plugins(WP_TWEET_SLIDESHOW_DIR."/include.php");
			
		} else {

			// Schedule an action to be called every hour.  Have attached a function to the event above for actually parsing the twitter response data
			wp_schedule_event(time(), 'hourly', 'tweet_slideshow_parse_twitter_event');
			
		}
		
	}
	
	
	/**
	 * tweet_slideshow_deactivation
	 * Called when the plugin is deactivated by Wordpress
	 *
	 * @return void
	 * @author Matt Brewer
	 **/
	function tweet_slideshow_deactivation() {

		global $wpdb, $tweet_slideshow_post_type;
		
		$posts = $wpdb->get_results(sprintf("SELECT * FROM `%s` WHERE `post_type` = '%s'", $wpdb->posts, $tweet_slideshow_post_type), OBJECT); 
		foreach($posts as $post) {
			wp_delete_post($post->ID, true);
		}
		
		$timestamp = wp_next_scheduled('tweet_slideshow_parse_twitter_event');
		wp_unschedule_event($timestamp, 'tweet_slideshow_parse_twitter_event');
		
	}
	
	

	/**
	 * fetch_tweets
	 * Grabs cached tweets from database
	 *
	 * @param int $limit - if not provided, uses global setting for plugin
	 *
	 * @return array $tweets
	 * @author Matt Brewer
	 **/

	function fetch_tweets($limit=3) {
	
		global $tweet_slideshow_options;
		global $tweet_slideshow_post_type;

		$args = array(
			"post_type" => $tweet_slideshow_post_type,
			"showposts" => intval($limit) > 0 ? intval($limit) : $tweet_slideshow_options->global_limit,
			"meta_key" => "tweet_author", 
			"meta_value" => $tweet_slideshow_options->username,
			"meta_compare" => "="
		);
		
		$query = new WP_Query($args);
		foreach($query->posts as &$post) {
			$post->tweet_id = get_post_meta($post->ID, "tweet_id");
			$post->tweet_author = get_post_meta($post->ID, "tweet_author");
		}
		
		return $query->posts;
		
	}
	
	
	/**
	 * tweet_slideshow_options
	 * Retrieves the options object from the database, or creates a default one if it doesn't exist
	 *
	 * @return void
	 * @author Matt Brewer
	 **/
	
	add_action('tweet_slideshow_init', 'tweet_slideshow_options');
	function tweet_slideshow_options() {
		
		global $tweet_slideshow_options;
		$tweet_slideshow_options = new stdClass;
		$tweet_slideshow_options->username = get_option("tweet_slideshow_username", "twitter");
		$tweet_slideshow_options->interval = get_option("tweet_slideshow_interval", 5000);
		$tweet_slideshow_options->global_limit = get_option("tweet_slideshow_global_limit", 9) + 1;
		$tweet_slideshow_options->show_replies = get_option("tweet_slideshow_show_replies", "false") == "true" ? true : false;
		$tweet_slideshow_options->timestamp = get_option("tweet_slideshow_update_timestamp", false);
		
	}
	
	
	/**
	 * tweet_slideshow_username()
	 * Retrieves the username entered for the plugin settings
	 *
	 * @return string $username
	 * @author Matt Brewer
	 **/
	function tweet_slideshow_username() {
		global $tweet_slideshow_options;
		return $tweet_slideshow_options->username;
	}
	
	
	/**
	 * retrieve_tweets_from_twitter_for_user
	 * Retrieves & parses XML from twitter, storing tweets in database for later use
	 *
	 * @param string $username
	 *
	 * @return boolean $success
	 * @author Matt Brewer
	 **/
	function retrieve_tweets_from_twitter_for_user($user) {
		
		global $tweet_slideshow_post_type, $tweet_slideshow_options, $wpdb;
				
		// If set to grab tweets directed at this user, do that now
		if ( $tweet_slideshow_options->show_replies ) {
		
			if ( ($tweets = file_get_contents(sprintf(TWITTER_SEARCH_URL, $user))) !== false && ($tweets = json_decode($tweets)) !== NULL ) {
								
				foreach($tweets->results as $tweet) {
					
					$sql = sprintf("SELECT * FROM %s p, %s pm WHERE p.post_type = '%s' AND pm.post_id = p.ID AND pm.meta_key = 'tweet_id' AND pm.meta_value = '%s' LIMIT 1", $wpdb->posts, $wpdb->postmeta, $tweet_slideshow_post_type, $tweet->id);
					if ( ($count = $wpdb->query($sql, OBJECT)) == 0 ) {

						$date = new DateTime($tweet->created_at);

						// Need to create new article with the provided content
						$post_id = wp_insert_post(array(
							"post_title" => $tweet->source,
							"post_content" => '@'.$tweet->from_user.": ".$tweet->text,
							"post_status" => "publish",
							"post_date" => $date->format("Y-m-d H:i:s"),
							"post_type" => $tweet_slideshow_post_type
						));

						update_post_meta($post_id, "tweet_id", $tweet->id);
						update_post_meta($post_id, "tweet_author", $user);
						
						unset($date);

					}
					
				}
				
			} else return false;
			
		}
		
		// Grab tweets from this user
		if ( ($tweets = file_get_contents(sprintf(TWITTER_TIMELINE_URL, $user, 200))) !== false && ($tweets = json_decode($tweets)) !== NULL ) {
			
			foreach($tweets as $tweet) {
				
				$sql = sprintf("SELECT * FROM %s p, %s pm WHERE p.post_type = '%s' AND pm.post_id = p.ID AND pm.meta_key = 'tweet_id' AND pm.meta_value = '%s' LIMIT 1", $wpdb->posts, $wpdb->postmeta, $tweet_slideshow_post_type, $tweet->id);
				if ( ($count = $wpdb->query($sql, OBJECT)) == 0 ) {
					
					$date = new DateTime($tweet->created_at);

					// Need to create new article with the provided content
					$post_id = wp_insert_post(array(
						"post_title" => $tweet->source,
						"post_content" => $tweet->text,
						"post_status" => "publish",
						"post_date" => $date->format("Y-m-d H:i:s"),
						"post_type" => $tweet_slideshow_post_type
					));
					
					update_post_meta($post_id, "tweet_id", $tweet->id);
					update_post_meta($post_id, "tweet_author", strtolower($tweet->user->screen_name));

				}
		
			}
			
			tweet_slideshow_update_timestamp();
			
			return true;

		} return false;
	} 
	
	
	/**
	 * tweet_slideshow_javascript_include
	 *
	 * @return void
	 * @author Matt Brewer
	 **/
	
	add_action('tweet_slideshow_init', 'tweet_slideshow_register_scripts');
	function tweet_slideshow_register_scripts() {
		if ( !is_admin() ) {
			wp_enqueue_script("tweet_slideshow", WP_PLUGIN_URL.DIRECTORY_SEPARATOR.WP_TWEET_SLIDESHOW_DIR.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'tweet_slideshow.js', array('jquery'), '1.0');
			wp_enqueue_style("tweet_slideshow", WP_PLUGIN_URL.DIRECTORY_SEPARATOR.WP_TWEET_SLIDESHOW_DIR.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'tweet_slideshow.css', array(), '1.0');
		}
	}
	
	
	/**
	 * tweet_slideshow_tweet_filter
	 * Default filter for the tweet text, for proper formatting
	 *
	 * @return void
	 * @author Matt Brewer
	 **/
	
	add_filter(WP_TWEET_SLIDESHOW_TWEET_ITEM_FILTER, 'tweet_slideshow_tweet_filter');
	function tweet_slideshow_tweet_filter($tweet) {
		
		$tweet->output = $tweet->post_content;
		$tweet->output = preg_replace("/#([^\d]*?)(\s|$)/", "<span class=\"hash\">#$1</span>", $tweet->output);
		$tweet->output = preg_replace("/(\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/i", '<a href="$1" target="_blank">$1</a> ', $tweet->output);
		$tweet->output = preg_replace("/@(.*?)(\s|\(|\)|:|$)/", '<a href="http://twitter.com/$1" target="_blank">@$1 </a>$2', $tweet->output);
		
		return $tweet;
	}
	
	
	/**
	 * tweet_slideshow_update_timestamp
	 * Updates the timestamp for the time the tweets were last retrieved
	 *
	 * @return void
	 * @author Matt Brewer
	 **/
	function tweet_slideshow_update_timestamp() {
		global $tweet_slideshow_options;
		$tweet_slideshow_options->timestamp = time();
		update_option("tweet_slideshow_update_timestamp", $tweet_slideshow_options->timestamp);
	}
	
	
	/**
	 * tweet_slideshow_supported_configuration
	 * Determines if this configuration will support this plugin
	 *
	 * @return boolean $supported
	 * @author Matt Brewer
	 **/
	function tweet_slideshow_supported_configuration() {
		return version_compare(phpversion(), "5.2.0") >= 0 && version_compare(get_bloginfo('version'), "3.0") >= 0;
	}
	
		
	/**
	 * tweet_slideshow_admin_notices
	 * Displays the additional admin messages we've defined outside the scope of our page (admin wide)
	 *
	 * @return void
	 * @author Matt Brewer
	 **/
	
	add_action('admin_notices', 'tweet_slideshow_admin_notices');
	function tweet_slideshow_admin_notices() {
		$message = get_option(TWEET_SLIDESHOW_ACTIVATION_MESSAGE);
		if ( $message ) {
			echo sprintf('<div class="error"><p><strong>%s</strong></p></div>', $message);
			update_option(TWEET_SLIDESHOW_ACTIVATION_MESSAGE, "");
		}
	}

?>