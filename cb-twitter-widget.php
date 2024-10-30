<?php

if ( ! class_exists('cb_twitter_widget')) :

	class cb_twitter_widget extends WP_Widget
	{
		function cb_twitter_widget()
		{
			$cb_twitter_widget = cb_twitter::get_instance();
			$widget_ops = array('classname' => $cb_twitter_widget->_slug, 'description' => 'A customizable widget that displays your latest tweets');
			$this->WP_Widget($cb_twitter_widget->_slug, 'Twitter (Clearly Bonafide)', $widget_ops);
		}
		
		function widget($args, $instance)
		{
			$cb_twitter_widget = cb_twitter::get_instance();
			extract($args, EXTR_SKIP);
			
			$title = ( ! empty($instance['title'])) ? apply_filters('widget_title', $instance['title']) : '';
			$username = ( ! empty($instance['username'])) ? apply_filters('widget_title', $instance['username']) : '';
			$count = ( ! empty($instance['count'])) ? apply_filters('widget_title', $instance['count']) : '';
			$display_thumbnail = ( ! empty($instance['display_thumbnail'])) ? apply_filters('widget_title', $instance['display_thumbnail']) : '';
			$display_date = ( ! empty($instance['display_date'])) ? apply_filters('widget_title', $instance['display_date']) : '';
			$exclude_replies = ( ! empty($instance['exclude_replies'])) ? apply_filters('widget_title', $instance['exclude_replies']) : '';
			
			// No username entered, so it's pointless to continue.
			if (empty($username)) return;
			
			echo $before_widget;
			
			// If there's a title, output it
			if ( ! empty($title))
				echo $before_title.'<a href="http://twitter.com/'.$username.'/" title="'.$title.'">'.$title.'</a>'.$after_title;
			
			// It's time to put it all together...
			echo $cb_twitter_widget->get_tweets($username, $count, $widget_id, $display_thumbnail, $display_date, $exclude_replies);
			
			echo $after_widget;
		}
		
		function update($new_instance, $old_instance)
		{
			$instance = $old_instance;
			$cb_twitter_widget = cb_twitter::get_instance();
			
			foreach ($new_instance as $key => $value)
			{
				$instance[$key] = strip_tags($new_instance[$key]);
			}
			
			// Flush the cache on any new update
			delete_transient($cb_twitter_widget->_slug.'-twittercache-id-'.$instance['username'].'-'.$this->id_base.'-'.$this->number);
			
			return $instance;
		}
		
		function form($instance)
		{
			$instance = wp_parse_args((array)$instance, array('title' => 'Latest Tweets', 'count' => '3'));
			
			$title = isset($instance['title']) ? strip_tags($instance['title']) : '';
			$username = isset($instance['username']) ? strip_tags($instance['username']) : '';
			$count = isset($instance['count']) ? strip_tags($instance['count']) : '';
			$display_thumbnail = isset($instance['display_thumbnail']) ? strip_tags($instance['display_thumbnail']) : '';
			$display_date = isset($instance['display_date']) ? strip_tags($instance['display_date']) : '';
			$exclude_replies = isset($instance['exclude_replies']) ? strip_tags($instance['exclude_replies']) : '';
?>
			<p>
				<label for="<?php echo $this->get_field_id('title') ?>">Title:
					<input class="widefat" id="<?php echo $this->get_field_id('title') ?>" name="<?php echo $this->get_field_name('title') ?>" type="text" value="<?php echo esc_attr($title) ?>" />
				</label>
			</p>
			
			<p>
				<label for="<?php echo $this->get_field_id('username') ?>">Username:
					<input class="widefat" id="<?php echo $this->get_field_id('username') ?>" name="<?php echo $this->get_field_name('username') ?>" type="text" value="<?php echo esc_attr($username) ?>" />
				</label>
			</p>
			
			<p>
				<label for="<?php echo $this->get_field_id('count') ?>">Number of tweets to show:
				<select class="widefat" id="<?php echo $this->get_field_id('count') ?>" name="<?php echo $this->get_field_name('count') ?>">
				<?php
				$choice_list = '';
				
				for ($i = 1; $i <= 15; $i++)
				{
					$selected = '';
					if ($count == $i)
						$selected = 'selected="selected"';
					$choice_list .= '<option '.$selected.' value="'.$i.'">'.$i.'</option>';
				}
				$choice_list .= '</select>';
				
				echo $choice_list;
				?>
			</p>
			
			<p>
				<label for="<?php echo $this->get_field_id('display_thumbnail') ?>">Display Profile Image?
				<select class="widefat" id="<?php echo $this->get_field_id('display_thumbnail') ?>" name="<?php echo $this->get_field_name('display_thumbnail') ?>">
				<?php
				$choice_list = '';
				$choices = array('Yes', 'No');
				
				foreach ($choices as $choice)
				{
					$selected = '';
					if ($display_thumbnail == $choice)
						$selected = 'selected="selected"';
					$choice_list .= '<option '.$selected.' value="'.$choice.'">'.$choice.'</option>';
				}
				$choice_list .= '</select>';
				
				echo $choice_list;
				?>
			</p>
			
			<p>
				<label for="<?php echo $this->get_field_id('display_date') ?>">Display the Tweet's Date?
				<select class="widefat" id="<?php echo $this->get_field_id('display_date') ?>" name="<?php echo $this->get_field_name('display_date') ?>">
				<?php
				$choice_list = '';
				$choices = array('Yes', 'No');
				
				foreach ($choices as $choice)
				{
					$selected = '';
					if ($display_date == $choice)
						$selected = 'selected="selected"';
					$choice_list .= '<option '.$selected.' value="'.$choice.'">'.$choice.'</option>';
				}
				$choice_list .= '</select>';
				
				echo $choice_list;
				?>
			</p>
			
			<p>
				<label for="<?php echo $this->get_field_id('exclude_replies') ?>">Exclude @replies?
				<select class="widefat" id="<?php echo $this->get_field_id('exclude_replies') ?>" name="<?php echo $this->get_field_name('exclude_replies') ?>">
				<?php
				$choice_list = '';
				$choices = array('Yes', 'No');
				
				foreach ($choices as $choice)
				{
					$selected = '';
					if ($exclude_replies == $choice)
						$selected = 'selected="selected"';
					$choice_list .= '<option '.$selected.' value="'.$choice.'">'.$choice.'</option>';
				}
				$choice_list .= '</select>';
				
				echo $choice_list;
				?>
			</p>
			
<?php
		}
	}
	
	if ( ! function_exists('cb_register_widget') && function_exists('register_widget'))
	{
		function cb_register_widget() 
		{
			register_widget('cb_twitter_widget');
		}
		add_action('widgets_init', 'cb_register_widget');
	}

endif;