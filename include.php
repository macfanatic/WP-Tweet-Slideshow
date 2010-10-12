<?

/*
Plugin Name: Tweet Slideshow
Description: Creates a slideshow of tweets for a particular user wherever you want in your theme
Version: 1.0
Author: Matt Brewer, http://www.dmgx.com

*/

define('WP_TWEET_SLIDESHOW_DIR', basename(dirname(__FILE__)));
define('WP_TWEET_SLIDESHOW_TWEET_ITEM_FILTER', "wp_tweet_slideshow_tweet_item_filter");
define('WP_TWEET_SLIDESHOW_NO_TWEETS_FILTER', "wp_tweet_slideshow_no_tweets_filter");


require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'functions.php';
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'post_type.php';
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'admin.php';
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'output.php';

register_activation_hook(__FILE__, 'tweet_slideshow_activation');
register_deactivation_hook(__FILE__, 'tweet_slideshow_deactivation');


/**
 * tweet_slideshow
 * Display the tweet slideshow in your theme
 *
 * @param int $limit - if not provided, will use the value set in the database for the plugin globally
 *
 * @uses WP_TWEET_SLIDESHOW_TWEET_ITEM_FILTER
 * @uses WP_TWEET_SLIDESHOW_NO_TWEETS_FILTER
 * @uses 'the_content' filer
 *
 * @return void
 * @author Matt Brewer
 **/

function tweet_slideshow($limit=null) {
	
	global $tweet_slideshow_options;
	
	echo '<ul class="tweetList"><input type="hidden" name="tweet_slideshow_interval" value="'.$tweet_slideshow_options->interval.'" />';
	
	$tweets = fetch_tweets($limit);
	if ( !empty($tweets) ) {
		foreach($tweets as $index => $tweet) {
			$tweet->post_content = apply_filters('the_content', $tweet->post_content);
			$obj = apply_filters(WP_TWEET_SLIDESHOW_TWEET_ITEM_FILTER, $tweet);
			echo '<li class="tweet" rel="'.$tweet->ID.'">'.$obj->output.'</li>';
		}
	} else {
		echo apply_filters(WP_TWEET_SLIDESHOW_NO_TWEETS_FILTER, '<li class="no-tweets">No Tweets!</li>');
	}
	
	echo '</ul>';
	
}





?>