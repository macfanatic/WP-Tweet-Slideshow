<?


	/**
	 * register_tweet_post_type
	 *
	 * @return void
	 * @author Matt Brewer
	 **/
	
	add_action('tweet_slideshow_init', 'register_tweet_post_type');
	function register_tweet_post_type() {
		
		global $tweet_slideshow_post_type;
		$tweet_slideshow_post_type = "tweet_slideshow";
		
		register_post_type($tweet_slideshow_post_type, array(
			'label' => __('Tweets'),
			'singular_label' => __('Tweet'),
			'public' => true,
			'show_ui' => false,
			'capability_type' => 'post',
			'hierarchical' => false,
			'rewrite' => false,
			'query_var' => true
		));
		
	}	

?>