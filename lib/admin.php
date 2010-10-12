<?

	define("WP_TWEET_SLIDESHOW_OPTIONS_TAG", "tweet_slideshow_options");
	define("WP_TWEET_SLIDESHOW_PARSE_TWITTER_PAGE", WP_PLUGIN_URL.DIRECTORY_SEPARATOR.WP_TWEET_SLIDESHOW_DIR.DIRECTORY_SEPARATOR.'parse.php');

	/**
	 * register_tweet_slideshow_options_page
	 * Register a callback to display the contents of the options view
	 *
	 * @return void
	 * @author Matt Brewer
	 **/
	
	add_action('admin_menu', 'register_tweet_slideshow_options_page');
	function register_tweet_slideshow_options_page() {
	    add_options_page(__("Tweet Slideshow"), __("Tweet Slideshow"), 'manage_options', __FUNCTION__, "display_tweet_slideshow_options_page");  
	}
	
	
	/**
	 * display_tweet_slideshow_options_page
	 * Actually displays the contents of the options page
	 *
	 * @return void
	 * @author Matt Brewer
	 **/

	function display_tweet_slideshow_options_page() {
		
		global $tweet_slideshow_options;	
		if ( isset($_SESSION['parsed']) ) {
			$parsed = $_SESSION['parsed'] == 'true';
			unset($_SESSION['parsed']);
		}	
		
		
		?>
		
			<? if ( isset($parsed) ): ?>
			<div id="setting-error-settings_updated" class="updated settings-error">
				<p><strong><? if ( $parsed ): ?>Retrieved new tweets.<? else: ?>Error retrieving new tweets.<? endif; ?></strong></p>
			</div>
			<? endif; ?>
			
			<div style="position:relative;">
				
				<? if ( $tweet_slideshow_options->timestamp ): ?>
				<p><strong>Last Updated: <?=date("M jS \a\\t h:i A")?></strong></p>
				<? endif ?>

				<form action="options.php" method="post">
					<?php settings_fields(WP_TWEET_SLIDESHOW_OPTIONS_TAG); ?>
					<?php do_settings_sections('plugin'); ?>

					<br />
					<input class="button-primary" name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
				</form>
				
				<form action="<?=WP_TWEET_SLIDESHOW_PARSE_TWITTER_PAGE?>" method="post" style="position:absolute;top:282px;left:416px;<? if ( isset($parsed) ) echo 'top:282px;' ?>">
					<?php settings_fields('plugin'); ?>
					<br />
					<input class="button" name="Submit" type="submit" value="<?php esc_attr_e('Retrieve Tweets Now'); ?>" />
				</form>
				
			</div>
		<?
		
	}
	
	
	/**
	 * tweet_slideshow_admin_init
	 * Register all the settings fields for the admin page
	 *
	 * @return void
	 * @author Matt Brewer
	 **/
	
	add_action('admin_init', 'tweet_slideshow_admin_init');
	function tweet_slideshow_admin_init() {
		
		session_start();
	
		register_setting(WP_TWEET_SLIDESHOW_OPTIONS_TAG, 'tweet_slideshow_interval');
		register_setting(WP_TWEET_SLIDESHOW_OPTIONS_TAG, 'tweet_slideshow_username');
		register_setting(WP_TWEET_SLIDESHOW_OPTIONS_TAG, 'tweet_slideshow_global_limit');
		register_setting(WP_TWEET_SLIDESHOW_OPTIONS_TAG, 'tweet_slideshow_show_replies');
		
		add_settings_section('plugin_main', 'Twitter Settings', 'tweet_slideshow_plugin_section_text', 'plugin');
		
		add_settings_field('tweet_slideshow_username', 'Username:', 'tweet_slideshow_username_field', 'plugin', 'plugin_main');
		add_settings_field('tweet_slideshow_interval', 'Animation Interval:', 'tweet_slideshow_animation_field', 'plugin', 'plugin_main');
		add_settings_field('tweet_slideshow_global_limit', '# of Tweets:', 'tweet_slideshow_limit_field', 'plugin', 'plugin_main');
		add_settings_field('tweet_slideshow_show_replies', 'Show Public Replies:', 'tweet_slideshow_replies_field', 'plugin', 'plugin_main');
		
	}
	
	function tweet_slideshow_plugin_section_text() {
		echo '<p>Enter the <a href="http://www.twitter.com" target="_blank">www.twitter.com</a> username below.</p><p><em>This plugin looks for new tweets every hour.</em></p>';
	}
	
	function tweet_slideshow_username_field() {
		$value = get_option("tweet_slideshow_username");
		echo "<input id='tweet_slideshow_username' name='tweet_slideshow_username' size='40' type='text' value='{$value}' />";
	}
	
	function tweet_slideshow_animation_field() {
		$value = get_option('tweet_slideshow_interval', 5000);		
		$options = array(
			2000 => "2 Seconds",
			3000 => "3 Seconds",
			4000 => "4 Seconds",
			5000 => "5 Seconds",
			10000 => "10 Seconds",
			15000 => "15 Seconds",
			20000 => "20 Seconds",
			25000 => "25 Seconds",
			30000 => "30 Seconds",
			45000 => "45 Seconds",
			60000 => "1 Minute"
		);
		echo array_to_dropdown($options, $value, "tweet_slideshow_interval", "tweet_slideshow_interval", 'width:320px;');
	}
	
	function tweet_slideshow_replies_field() {
		$value = get_option("tweet_slideshow_show_replies", "false");
		echo '<input type="checkbox" value="true" id="tweet_slideshow_show_replies" name="tweet_slideshow_show_replies" '.($value?'checked="checked"':'').' />';
	}
	
	function tweet_slideshow_limit_field() {
		$value = get_option('tweet_slideshow_global_limit');
		echo array_to_dropdown(range(1,10), $value, "tweet_slideshow_global_limit", "tweet_slideshow_global_limit", 'width:320px;');
	}

?>