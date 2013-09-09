<?php
/*
Plugin Name: Rss Post Importer
Plugin URI: -
Description: This plugin lets you set up an import posts from one or several rss-feeds and save them as posts on your site, simple and flexible.
Author: Jens Waern
Version: 1.0.1
Author URI: http://www.simmalugnt.se
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/


add_action('wp_ajax_rss_pi_add_row', 'rss_pi_add_row');

function rss_pi_add_row()
{
	include( plugin_dir_path( __FILE__ ) . 'parts/table_row.php');
	exit;
}

class rss_pi {

	function __construct() {
		add_action('admin_menu', array(&$this, 'admin_menu'));
		
		$this->settings = array(
			'version'	=>	'1.0',
			'dir'		=>	plugin_dir_path( __FILE__ )
		);

		load_textdomain('rss_pi', $this->settings['dir'] . 'lang/rss_pi-' . get_locale() . '.mo');	
	}
	
	
	// On an early action hook, check if the hook is scheduled - if not, schedule it.
	function rss_pi_setup_schedule() {
		if ( ! wp_next_scheduled( 'rss_pi_cron' ) ) {
			wp_schedule_event( time(), 'hourly', 'rss_pi_cron');
		}
	}
	
	// On the scheduled action hook, run a function.
	function rss_pi_do_this_hourly()
	{
		$this->import_all_feeds();
	}

	// Add to settings-menu
	function admin_menu () {
		add_options_page('Rss Post Importer','Rss Post Importer','manage_options','rss_pi', array($this, 'settings_page'));
	}
	
	function settings_page () {
		// Changes submitted, correct nonce
		if( isset($_POST['info_update']) && wp_verify_nonce($_POST['rss_pi_nonce'],'settings_page')) : 
			
			// Get ids of feed-rows
			$ids =  explode(",", $_POST['ids']);
			
			$feeds = array();
			
			
			$settings = array(
				'frequency' => $_POST['frequency'],
				'post_template' => stripslashes_deep($_POST['post_template']),
				'post_status' => $_POST['post_status'],
				'author_id' => $_POST['author_id'],
				'allow_comments' => $_POST['allow_comments']
			);
					
			// Reset cron
			wp_clear_scheduled_hook( 'rss_pi_cron' );
			wp_schedule_event( time(), $settings['frequency'], 'rss_pi_cron');
			
			foreach($ids as $id)
			{
				if($id)
				{
					array_push($feeds, array(
						'id' => $id,
						'url' => strtolower($_POST[$id . '-url']),
						'name' => $_POST[$id . '-name'],
						'max_posts' =>  $_POST[$id . '-max_posts'],
						'category_id' =>  $_POST[$id . '-category_id'],
						'strip_html' => $_POST[$id . '-strip_html']
					));
				}
			}

			update_option('rss_pi_feeds', array('feeds' => $feeds, 'settings' => $settings, 'latest_import' => ''));
			
			?>
			<div id="message" class="updated">
			    <p><strong><?php _e('Settings saved.') ?></strong></p>
			</div>
			<?php
			
			if($_POST['save_to_db'] == 'true') :
				$imported = $this->import_all_feeds();
				?>
				<div id="message" class="updated">
				    <p><strong><?php echo($imported); ?> <?php _e('new posts imported.') ?></strong></p>
				</div>
				<?php
			endif;
		endif;
		
		$options = get_option('rss_pi_feeds');
		
		$ids = array();
		
		
		$this->input_admin_enqueue_scripts();
		
		include( plugin_dir_path( __FILE__ ) . 'rss_pi-ui.php');
	}
	
	
	function import_feed($url, $feed_title, $max_posts, $category_id, $strip_html, $save_to_db)
	{
		include_once( ABSPATH . WPINC . '/feed.php' );
		
		// Get a SimplePie feed object from the specified feed source.
		$rss = fetch_feed( $url );
		
		$options = get_option('rss_pi_feeds');
		
		// Remove the surrounding <div> from XHTML content in Atom feeds.
		
		if ( ! is_wp_error( $rss ) ) : // Checks that the object is created correctly
		
		    // Figure out how many total items there are, but limit it to 5. 
		    $maxitems = $rss->get_item_quantity( $max_posts ); 
		
		    // Build an array of all the items, starting with element 0 (first element).
		    $rss_items = $rss->get_items( 0, $max_posts );
			
			if($save_to_db)
			{
				$saved_posts = array();
				
				foreach ( $rss_items as $item )
				{
					if (!$this->post_exists($item->get_permalink()))
					{
						$new_post = array(
							'post_title'    => $item->get_title(),
							'post_content'  => $this->parse_content($item, $feed_title, $strip_html),
							'post_status'   => $options['settings']['post_status'],
							'post_author'   => $options['settings']['author_id'],
							'post_category' => array($category_id),
							'comment_status'=> $options['settings']['allow_comments'],
							'post_date'		=> $item->get_date('Y-m-d H:i:s')
						);
						
						$post_id = wp_insert_post( $new_post );
						
						add_post_meta( $post_id, 'rss_pi_source_url', esc_url($item->get_permalink()) );
						
						array_push($saved_posts, $new_post);
					}
				}
				return $saved_posts;
				exit;
			}
			return $rss_items;
		endif;
	}
	
	function return_frequency($seconds)
	{
		$options = get_option('rss_pi_feeds');
		return $options['settings']['frequency'];
	}
	
	function import_all_feeds()
	{
		
		$post_count = 0;
		
		$options = get_option('rss_pi_feeds');

		add_filter( 'wp_feed_cache_transient_lifetime', array(&$this, 'return_frequency' ) );

		foreach($options['feeds'] as $f)
		{
			$rss_items = $this->import_feed($f['url'], $f['name'], $f['max_posts'], $f['category_id'], $f['strip_html'], true);
			$post_count += count($rss_items);
		}
		
		update_option('rss_pi_feeds', array(
			'feeds' => $options['feeds'],
			'settings' => $options['settings'],
			'latest_import' => date("Y-m-d H:i:s")
		));
		
		remove_filter( 'wp_feed_cache_transient_lifetime', array(&$this, 'return_frequency' ) );
		
		return $post_count;
	}
	
	function parse_content($item, $feed_title, $strip_html)
	{
		$options = get_option('rss_pi_feeds');
		$post_template = $options['settings']['post_template'];
		$c = $item->get_content() != "" ? $item->get_content() : $item->get_description();
		
		$parsed_content = str_replace('{$content}', $c, $post_template);
		$parsed_content = str_replace('{$permalink}', esc_url( $item->get_permalink() ), $parsed_content);
		$parsed_content = str_replace('{$feed_title}', $feed_title, $parsed_content);
		$parsed_content = str_replace('{$title}', $item->get_title(), $parsed_content);	
		
		if($strip_html == 'true')
		{
			$parsed_content = strip_tags($parsed_content);
		}
		return $parsed_content;
	}
	
	function post_exists($permalink)
	{
		
		$args = array(
			'meta_key' => 'rss_pi_source_url',
			'meta_value' => esc_url($permalink)
		);
		
		$posts = get_posts( $args );
		
		// Not already imported
		return(count($posts) > 0);
	}
	
	function input_admin_enqueue_scripts()
	{
		// register scripts & styles
		wp_register_script( 'rss_pi', plugins_url( 'js/rss_pi.js', __FILE__ ), array('jquery'), $this->settings['version'] );
		wp_register_style( 'rss_pi', plugins_url( 'css/rss_pi.css', __FILE__ ) , array(), $this->settings['version'] ); 
		wp_localize_script('rss_pi', 'rss_pi_ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		
		// scripts
		wp_enqueue_script(array(
			'rss_pi'
		));

		// styles
		wp_enqueue_style(array(
			'rss_pi'
		));
		
	}

}
new rss_pi;
?>