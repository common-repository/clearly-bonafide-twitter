<?php
/**
 * Plugin Name: Clearly Bonafide Twitter
 * Plugin URI: http://github.com/chrisberthe/cb-twitter
 * Description: A WordPress widget that creates a stream of your latest tweets. Very customizable - set the number of tweets to show, display profile thumbnails, exclude replies and more.
 * Version: 1.1
 * Author: Chris Berthe
 * Author URI: http://github.com/chrisberthe
 * License: I don't even know anymore
 */

if ( ! class_exists('cb_twitter')) :

	class cb_twitter
	{
		static $instance;
		
		function __construct()
		{
			// More will go here, eventually
			$this->add_options();
			$this->_slug = 'cb-twitter';
			$this->_plugin_dir = basename(dirname(__FILE__));
			$this->_plugin_path = WP_PLUGIN_DIR.'/'.$this->_plugin_dir;
			$this->_options = 'cb-twitter-options';
			$this->_cache_timeout = 5; // in minutes
			$this->_twitter_api = 'http://api.twitter.com/1/statuses/user_timeline.xml?screen_name=';
		}
		
		/**
		 * Adds all the goodies
		 *
		 * @return void
		 */
		function add_options()
		{
			if ( ! function_exists('add_option'))
			{
				echo "Can't do anything without this little function. I suggest you press the back button if you see this.";
				exit();
			}
			
			add_action('init', array($this, 'init_settings'));
		}
		
		/**
		 * Initialize plugin settings
		 *
		 * @return void
		 */
		function init_settings()
		{
			wp_enqueue_style($this->_options, plugins_url('css/style.css', __FILE__));
		}
		
		/**
		 * Does all the hard work with the Twitter API and fetches the tweets
		 *
		 * @param $username The user's Twitter username
		 * @param $count The number of tweets to show in the feed
		 * @param $widget_id The current widget ID
		 * @param $display_thumbnail Whether to show the profile image
		 * @param $display_date Whether to display the tweet's date
		 * @param $exclude_replies Whether to exclude replies
		 * @return $twitter_output Twitter feed output
		 */
		function get_tweets($username, $count, $widget_id, $display_thumbnail = 'Yes', $display_date = 'Yes', $exclude_replies = 'Yes')
		{	
			$tweets = array();
			$twitter_loop = 0;
			$twitter_output = '';
			$cache = get_transient($this->_slug.'-twittercache-id-'.$username.'-'.$widget_id);
			
			if ($cache)
				$tweets = get_option($this->_slug.'-tweetcache-'.$username.'-'.$widget_id);
			else
			{
				$response = wp_remote_get($this->_twitter_api.$username);
				
				if ( ! is_wp_error($response))
				{
					$xml = simplexml_load_string($response['body']);
					
					if (empty($xml->error))
					{
						if (isset($xml->status[0]))
						{
							foreach ($xml->status as $tweet)
							{
								if ($twitter_loop == $count) break;
								
								$text = (string)$tweet->text;
								
								if ($exclude_replies == 'No' || ($exclude_replies == 'Yes' && $text[0] != '@'))
								{
									$twitter_loop++;
									
									$tweets[] = array(
										'text' => $this->filter_tweet($text),
										'created_at' => strtotime($tweet->created_at),
										'user' => array(
											'name' => (string)$tweet->user->name,
											'screen_name' => (string)$tweet->user->screen_name,
											'profile_image_url' => (string)$tweet->user->profile_image_url,
											'followers_count' => (int)$tweet->user->followers_count
											)
										);
								}
							}
							
							set_transient($this->_slug.'-twittercache-id-'.$username.'-'.$widget_id, 'true', $this->_cache_timeout * 60);
							update_option($this->_slug.'-tweetcache-'.$username.'-'.$widget_id, $tweets);
						}
					}
				}
				else { echo '<p>There was a problem connecting to Twitter.</p>'; return; }
			}
			
			if ( ! isset($tweets[0]))
				$tweets = get_option($this->_slug.'-tweetcache-'.$username.'-'.$widget_id);
			
			if ($tweets && count($tweets) > 0)
			{
				$twitter_output .= '<ul class="tweets">';
				foreach ($tweets as $tweet)
				{
					$twitter_output .= '<li class="tweet">';
					if ($display_thumbnail == 'Yes')
						$twitter_output .= '<div class="tweet-image"><a href="http://twitter.com/'.$username.'" title="'.$username.'"><img src="'.$tweet['user']['profile_image_url'].'" alt="" /></a></div>';
					$twitter_output .= '<div class="tweet-text">'.$tweet['text'];
					if ($display_date == 'Yes')
						$twitter_output .= '<div class="tweet-date">'.$this->time_ago($tweet['created_at'], 1).'</div>';
					$twitter_output .= '</div></li>';
				}
				$twitter_output .= '</ul>';
			}
			
			return $twitter_output;
		}
		
		/**
		 * Filters the tweets and checks for links, hash tags, etc
		 *
		 * @param $text Unfiltered tweet text
		 * @return $text Filtered tweet text
		 */
		function filter_tweet($text) {
			
		    $text = preg_replace('/\b([a-zA-Z]+:\/\/[\w_.\-]+\.[a-zA-Z]{2,6}[\/\w\-~.?=&%#+$*!]*)\b/i',"<a href=\"$1\" class=\"twitter-link\">$1</a>", $text);
		    $text = preg_replace('/\b(?<!:\/\/)(www\.[\w_.\-]+\.[a-zA-Z]{2,6}[\/\w\-~.?=&%#+$*!]*)\b/i',"<a href=\"http://$1\" class=\"twitter-link\">$1</a>", $text);    
		    $text = preg_replace("/\b([a-zA-Z][a-zA-Z0-9\_\.\-]*[a-zA-Z]*\@[a-zA-Z][a-zA-Z0-9\_\.\-]*[a-zA-Z]{2,6})\b/i","<a href=\"mailto://$1\" class=\"twitter-link\">$1</a>", $text);
		    $text = preg_replace("/#(\w+)/", "<a class=\"twitter-link\" href=\"http://search.twitter.com/search?q=\\1\">#\\1</a>", $text);
		    $text = preg_replace("/@(\w+)/", "<a class=\"twitter-link\" href=\"http://twitter.com/\\1\">@\\1</a>", $text);

		    return $text;
		}
		
		/**
		 * Formats the tweet date into a 'time ago' format
		 *
		 * Big thanks to DennisRas for building the method
		 * https://github.com/dennisras
		 *
		 * @todo Incorporate i18n with date formatter
		 * @param $time The time to format
		 * @param $max_units Degree of date precision
		 * @return $future Formatted time lapse
		 */
		function time_ago($time, $max_units = NULL)
		{
			$lengths = array(1, 60, 3600, 86400, 604800, 2630880, 31570560, 315705600);
			$units = array('second', 'minute', 'hour', 'day', 'week', 'month', 'year', 'decade');
			$units_plural = array('seconds', 'minutes', 'hours', 'days', 'weeks', 'months', 'years', 'decades');
			$unit_string_array = array();

			$max_units = (is_numeric($max_units) && in_array($max_units, range(1,8))) ? $max_units : sizeof($lengths);
			$diff = (is_numeric($time) ? time() - $time : time() - strtotime($time));
			$future = ($diff < 0) ? 1 : 0;
			$diff = abs($diff);

			$total_units = 0;
			for ($i = sizeof($lengths) - 1; $i >= 0; $i--)
			{
				if ($diff > $lengths[$i] && $total_units < $max_units)
				{
					$amount = floor($diff / $lengths[$i]);
					$mod = $diff % $lengths[$i];

					$unit_string_array[] = $amount . ' ' . (($amount == 1) ? $units[$i] : $units_plural[$i]);
					$diff = $mod;
					$total_units++;
				}
			}

			return ($future) ? implode($unit_string_array, ', ') . ' to go' : implode($unit_string_array, ', ') . ' ago';
		}
		
		/**
		 * Creates static object if one does not yet exist
		 *
		 * @return self::instance Static object of class
		 */
		function get_instance()
		{
			if ( ! self::$instance)
				self::$instance = new self;
				
			return self::$instance;
		}
	}
	
	$cb_twitter_widget = cb_twitter::get_instance();	
	include $cb_twitter_widget->_plugin_path . '/cb-twitter-widget.php';

endif;