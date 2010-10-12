
	jQuery(function($) {
	
		var interval = $(":hidden[name=tweet_slideshow_interval]").val();
		$("ul.tweetList").each(function() {
		
			var container = $(this);
			var length = container.find("li").length;
			var index = 0;
			
			container.find("li:gt(0)").hide();
			var timer = setInterval(function() {
				
				if ( ++index >= length ) { 
					index = 0;
				}
				
				container.find("li:not(:eq("+index+"))").fadeOut('slow', function() {
					container.find("li:eq("+index+")").fadeIn('slow');
				});
				
			}, interval);
			
		});
		
	});