<?

require_once dirname(__FILE__) . "/../../../wp-config.php";
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'include.php';

session_start();
$referrer = $_POST['_wp_http_referer'];

if ( isset($_POST['Submit']) || (isset($_POST['Submit_x']) && isset($_POST['Submit_y'])) ) {
	
	global $tweet_slideshow_options;
	$_SESSION['parsed'] = retrieve_tweets_from_twitter_for_user($tweet_slideshow_options->username);
	
} else {

	$_SESSION['parsed'] = false;
	
}

wp_redirect($referrer);


?>