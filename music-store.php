<?php
/*
Plugin Name: Music Store 
Plugin URI: http://wordpress.dwbooster.com/content-tools/music-store
Version: 1.0.1
Author: <a href="http://www.codepeople.net">CodePeople</a>
Description: Music Store is an online store for selling audio files: music, speeches, narratives, everything audio. With Music Store your sales will be safe, with all the security PayPal offers.
 */

 // CONSTANTS
 define( 'MS_FILE_PATH', dirname( __FILE__ ) );
 define( 'MS_URL', plugins_url( '', __FILE__ ) );
 define( 'MS_DOWNLOAD', dirname( __FILE__ ).'/ms-downloads' );
 define( 'MS_OLD_DOWNLOAD_LINK', 3); // Number of days considered old download links
 define( 'MS_CORE_IMAGES_URL',  MS_URL . '/ms-core/images' );
 define( 'MS_CORE_IMAGES_PATH', MS_FILE_PATH . '/ms-core/images' );
 define( 'MS_TEXT_DOMAIN', 'MS_TEXT_DOMAIN' );
 define( 'MS_MAIN_PAGE', false ); // The location to the music store main page
 define( 'MS_SECURE_PLAYBACK_TEXT', 'Audio is played partially for security reasons' );
 
 // PAYPAL CONSTANTS
 define( 'MS_PAYPAL_EMAIL', '' );
 define( 'MS_PAYPAL_ENABLED', true );
 define( 'MS_PAYPAL_CURRENCY', 'USD' );
 define( 'MS_PAYPAL_LANGUAGE', 'EN' );
 define( 'MS_PAYPAL_BUTTON', 'button_d.gif' );
 
 // NOTIFICATION CONSTANTS
 define( 'MS_NOTIFICATION_FROM_EMAIL', 'put_your@email_here.com' );
 define( 'MS_NOTIFICATION_TO_EMAIL', 'put_your@email_here.com' );
 define( 'MS_NOTIFICATION_TO_PAYER_SUBJECT', 'Thank you for your purchase...' );
 define( 'MS_NOTIFICATION_TO_SELLER_SUBJECT','New product purchased...' ); 
 define( 'MS_NOTIFICATION_TO_PAYER_MESSAGE', "We have received your purchase notification with the following information:\n\n%INFORMATION%\n\nThank you.\n\nBest regards." ); 
 define( 'MS_NOTIFICATION_TO_SELLER_MESSAGE', "New purchase made with the following information:\n\n%INFORMATION%\n\nBest regards." );

 // SAFE PLAYBACK
 define('MS_FILE_PERCENT', 50);
 
 // DISPLAY CONSTANTS
 define('MS_ITEMS_PAGE', 10);
 define('MS_ITEMS_PAGE_SELECTOR', true);
 define('MS_FILTER_BY_TYPE', false);
 define('MS_FILTER_BY_GENRE', true);
 define('MS_ORDER_BY_POPULARITY', true);
 define('MS_ORDER_BY_PRICE', true);			
 
 // TABLE NAMES
 define( 'MSDB_POST_DATA', 'msdb_post_data');
 define( 'MSDB_PURCHASE', 'msdb_purchase');
 
 include "ms-core/ms-functions.php";
 include "ms-core/ms-song.php";
 include "ms-core/tpleng.class.php";
 
 if ( !class_exists( 'MusicStore' ) ) {
 	 /**
	 * Main Music_Store Class
	 *
	 * Contains the main functions for Music Store, stores variables, and handles error messages
	 *
	 * @class MusicStore
	 * @version	1.0.1
	 * @since 1.4
	 * @package	MusicStore
	 * @author CodePeople
	 */
		
	class MusicStore{
		
		var $music_store_slug = 'music-store-menu';
		
		/**
		* MusicStore constructor
		*
		* @access public
		* @return void	
		*/
		function __construct(){
			add_action('init', array(&$this, 'init'), 0);
			add_action('admin_init', array(&$this, 'admin_init'), 0);
			// Set the menu link
			add_action('admin_menu', array(&$this, 'menu_links'), 10);
		} // End __constructor

/** INITIALIZE PLUGIN FOR PUBLIC WORDPRESS AND ADMIN SECTION **/
		
		/**
		* Init MusicStore when WordPress Initialize
		*
		* @access public
		* @return void
		*/
		function init(){
			// I18n
			load_plugin_textdomain(MS_TEXT_DOMAIN, false, dirname(plugin_basename(__FILE__)) . '/../languages/');
			
			$this->init_taxonomies(); // Init MusicStore taxonomies
			$this->init_post_types(); // Init MusicStore custom post types
			
			if ( ! is_admin()){
				
				// Set custom post_types on search result
				add_filter('pre_get_posts', array(&$this, 'add_post_type_to_results'));
				add_shortcode('music_store', array(&$this, 'load_store'));
				$this->load_templates(); // Load the music store template for songs display
				
				// Load public resources
				add_action( 'wp_enqueue_scripts', array(&$this, 'public_resources') );
			}
			// Init action
			do_action( 'musicstore_init' );
		} // End init
		
		/**
		* Init MusicStore when the WordPress is open for admin
		*
		* @access public
		* @return void
		*/
		function admin_init(){
			// Init the metaboxs for song
			add_meta_box('ms_song_metabox', __("Song's data", MS_TEXT_DOMAIN), array(&$this, 'metabox_form'), 'ms_song', 'normal', 'high');
			add_action('save_post', array(&$this, 'save_data'));
			
			if (current_user_can('delete_posts')) add_action('delete_post', array(&$this, 'delete_post'));
			
			// Load admin resources
			add_action('admin_enqueue_scripts', array(&$this, 'admin_resources'));
			
			// Set a new media button for music store insertion
			add_action('media_buttons', array(&$this, 'set_music_store_button'), 100);
			
			// Init action
			do_action( 'musicstore_admin_init' );
		} // End init
		
/** MANAGE DATABASES FOR ADITIONAL POST DATA **/
		
		/*
		*  Create database tables
		*
		*  @access public
		*  @return void
		*/
		function register($networkwide){
			global $wpdb;
			
			if (function_exists('is_multisite') && is_multisite()) {
				if ($networkwide) {
					$old_blog = $wpdb->blogid;
					// Get all blog ids
					$blogids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM $wpdb->blogs"));
					foreach ($blogids as $blog_id) {
						switch_to_blog($blog_id);
						$this->_create_db_structure();
					}
					switch_to_blog($old_blog);
					return;
				}
			}
			$this->_create_db_structure();
		
		}  // End register
		
		/*
		* Create the Music Store tables
		*
		* @access private
		* @return void
		*/
		private function _create_db_structure(){
			global $wpdb;
			
			$sql = "CREATE TABLE IF NOT EXISTS ".$wpdb->prefix.MSDB_POST_DATA." (
				id mediumint(9) NOT NULL,
				time VARCHAR(25) NULL,
				plays mediumint(9) NOT NULL DEFAULT 0,
				purchases mediumint(9) NOT NULL DEFAULT 0,
				file VARCHAR(255) NULL,
				demo VARCHAR(255) NULL,
				protect TINYINT(1) NOT NULL DEFAULT 0,
				info VARCHAR(255) NULL,
				cover VARCHAR(255) NULL,
				price FLOAT NULL,
				year VARCHAR(25),
				as_single TINYINT(1) NOT NULL DEFAULT 0,
				UNIQUE KEY id (id)
			 );";             
			$wpdb->query($sql); 
			
			$sql = "CREATE TABLE IF NOT EXISTS ".$wpdb->prefix.MSDB_PURCHASE." (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				product_id mediumint(9) NOT NULL,
				purchase_id varchar(50) NOT NULL UNIQUE,
				date DATETIME NOT NULL,
				email VARCHAR(255) NOT NULL,
				amount FLOAT NOT NULL DEFAULT 0,
				paypal_data TEXT,
				UNIQUE KEY id (id)
			 );";             
			$wpdb->query($sql); 
			
		} // End _create_db_structure 
		
/** REGISTER POST TYPES AND TAXONOMIES **/
		
		/**
		* Init MusicStore post types
		*
		* @access public
		* @return void
		*/
		function init_post_types(){
			if(post_type_exists('ms_song')) return;
			
			// Post Types
			// Create song post type
			register_post_type( 'ms_song', 
				array(
					'description'		   => __('This is where you can add new song to your music store.', MS_TEXT_DOMAIN),		
					'capability_type'      => 'post',
					'supports'             => array( 'title', 'editor' ),
					'exclude_from_search'  => false,
					'public'               => true,
					'show_ui'              => true,
					'show_in_nav_menus'    => true,
					'show_in_menu'    	   => $this->music_store_slug,
					'labels'               => array(
						'name'               => __( 'Songs', MS_TEXT_DOMAIN),
						'singular_name'      => __( 'Song', MS_TEXT_DOMAIN),
						'add_new'            => __( 'Add New', MS_TEXT_DOMAIN),
						'add_new_item'       => __( 'Add New Song', MS_TEXT_DOMAIN),
						'edit_item'          => __( 'Edit Song', MS_TEXT_DOMAIN),
						'new_item'           => __( 'New Song', MS_TEXT_DOMAIN),
						'view_item'          => __( 'View Song', MS_TEXT_DOMAIN),
						'search_items'       => __( 'Search Songs', MS_TEXT_DOMAIN),
						'not_found'          => __( 'No songs found', MS_TEXT_DOMAIN),
						'not_found_in_trash' => __( 'No songs found in Trash', MS_TEXT_DOMAIN),
						'menu_name'          => __( 'Songs for Sale', MS_TEXT_DOMAIN),
						'parent_item_colon'  => '',
					),
					'query_var'            => true,
					'has_archive'		   => true,	
					//'register_meta_box_cb' => 'wpsc_meta_boxes',
					'rewrite'              => false
				)
			);			
			
			add_filter('manage_ms_song_posts_columns' , 'MSSong::columns');
			add_action('manage_ms_song_posts_custom_column', 'MSSong::columns_data', 2 );
		}// End init_post_types
		
		/**
		* Init MusicStore taxonomies
		*
		* @access public
		* @return void
		*/
		function init_taxonomies(){
			
			
			if ( taxonomy_exists('ms_genre') ) return;
			
			do_action( 'musicstore_register_taxonomy' );
			
			// Create Genre taxonomy
			register_taxonomy(
				'ms_genre',
				array(
					'ms_song'
				),
				array(
					'hierarchical'	=> true,
					'label' 	   	=> __('Genres', MS_TEXT_DOMAIN),
					'labels' 		=> array(
						'name' 				=> __( 'Genres', MS_TEXT_DOMAIN),
	                    'singular_name' 	=> __( 'Genre', MS_TEXT_DOMAIN),
						'search_items' 		=> __( 'Search Genres', MS_TEXT_DOMAIN),
	                    'all_items' 		=> __( 'All Genres', MS_TEXT_DOMAIN),
						'edit_item' 		=> __( 'Edit Genre', MS_TEXT_DOMAIN),
	                    'update_item' 		=> __( 'Update Genre', MS_TEXT_DOMAIN),
	                    'add_new_item' 		=> __( 'Add New Genre', MS_TEXT_DOMAIN),
						'new_item_name' 	=> __( 'New Genre Name', MS_TEXT_DOMAIN),
						'menu_name'			=> __( 'Genres', MS_TEXT_DOMAIN)
	                ),
					'public' => true,
					'show_ui' => true,
					'show_admin_column' => true,
					'query_var' => true
				)
			);
			
			// Register artist taxonomy
			register_taxonomy(
				'ms_artist',
				array(
					'ms_song'
				),
				array(
					'hierarchical'	=> false,
					'label' 	   	=> __('Artists', MS_TEXT_DOMAIN),
					'labels' 		=> array(
						'name' 				=> __( 'Artists', MS_TEXT_DOMAIN),
	                    'singular_name' 	=> __( 'Artist', MS_TEXT_DOMAIN),
						'search_items' 		=> __( 'Search Artists', MS_TEXT_DOMAIN),
	                    'all_items' 		=> __( 'All Artists', MS_TEXT_DOMAIN),
						'edit_item' 		=> __( 'Edit Artist', MS_TEXT_DOMAIN),
	                    'update_item' 		=> __( 'Update Artist', MS_TEXT_DOMAIN),
	                    'add_new_item' 		=> __( 'Add New Artist', MS_TEXT_DOMAIN),
						'new_item_name' 	=> __( 'New Artist Name', MS_TEXT_DOMAIN),
						'menu_name'			=> __( 'Artists', MS_TEXT_DOMAIN)
	                ),
					'public' => true,
					'show_ui' => true,
					'show_admin_column' => true,
					'query_var' => true
				)
			);
			
			// Register album taxonomy
			register_taxonomy(
				'ms_album',
				array(
					'ms_song'
				),
				array(
					'hierarchical'	=> false,
					'label' 	   	=> __('Albums', MS_TEXT_DOMAIN),
					'labels' 		=> array(
						'name' 				=> __( 'Albums', MS_TEXT_DOMAIN),
	                    'singular_name' 	=> __( 'Album', MS_TEXT_DOMAIN),
						'search_items' 		=> __( 'Search Albums', MS_TEXT_DOMAIN),
	                    'all_items' 		=> __( 'All Albums', MS_TEXT_DOMAIN),
						'edit_item' 		=> __( 'Edit Album', MS_TEXT_DOMAIN),
	                    'update_item' 		=> __( 'Update Album', MS_TEXT_DOMAIN),
	                    'add_new_item' 		=> __( 'Add New Album', MS_TEXT_DOMAIN),
						'new_item_name' 	=> __( 'New Album Name', MS_TEXT_DOMAIN),
						'menu_name'			=> __( 'Albums', MS_TEXT_DOMAIN)
	                ),
					'public' => true,
					'show_ui' => true,
					'show_admin_column' => true,
					'query_var' => true
				)
			);
			
			add_action( 'admin_menu' , array(&$this, 'remove_meta_box') );
		} // End init_taxonomies
		
		/**
		*	Remove the taxonomies metabox
		*
		* @access public
		* @return void
		*/
		function remove_meta_box(){
			remove_meta_box( 'tagsdiv-ms_artist', 'ms_song', 'side' );
			remove_meta_box( 'tagsdiv-ms_album', 'ms_song', 'side' );
		} // End remove_meta_box

/** METABOXS FOR ENTERING POST_TYPE ADDITIONAL DATA **/		
		
		/**
		* Save data of store products
		*
		* @access public
		* @return void
		*/
		function save_data(){
			global $post;
			if($post->post_type == 'ms_song'){
				MSSong::save_data();
			}
		} // End save_data
		
		/**
		* Print metabox for post song
		*
		* @access public
		* @return void
		*/
		function metabox_form($obj){
			global $post;
			
			switch($obj->post_type){
				case 'ms_song':
					MSSong::print_metabox();
				break;
			}
			
		} // End metabox_form
		
/** SETTINGS PAGE FOR MUSIC STORE CONFIGURATION AND SUBMENUS**/		
		
		// highlight the proper top level menu for taxonomies submenus
		function tax_menu_correction($parent_file) {
			global $current_screen;
			$taxonomy = $current_screen->taxonomy;
			if ($taxonomy == 'ms_genre' || $taxonomy == 'ms_artist' || $taxonomy == 'ms_album')
				$parent_file = $this->music_store_slug;
			return $parent_file;
		} // End tax_menu_correction
		
		/*
		* Create the link for music store menu, submenus and settings page
		*
		*/
		function menu_links(){
			if(is_admin()){
				add_options_page('Music Store', 'Music Store', 'manage_options', $this->music_store_slug.'-settings1', array(&$this, 'settings_page'));
				
				add_menu_page('Music Store', 'Music Store', 'edit_pages', $this->music_store_slug, null, MS_CORE_IMAGES_URL."/music-store-menu-icon.png", 4.55555555555555);
				
				//Submenu for taxonomies
				add_submenu_page($this->music_store_slug, __( 'Genres', MS_TEXT_DOMAIN), __( 'Set Genres', MS_TEXT_DOMAIN), 'edit_pages', 'edit-tags.php?taxonomy=ms_genre');
				add_submenu_page($this->music_store_slug, __( 'Artists', MS_TEXT_DOMAIN), __( 'Set Artists', MS_TEXT_DOMAIN), 'edit_pages', 'edit-tags.php?taxonomy=ms_artist');
				add_submenu_page($this->music_store_slug, __( 'Albums', MS_TEXT_DOMAIN), __( 'Set Albums', MS_TEXT_DOMAIN), 'edit_pages', 'edit-tags.php?taxonomy=ms_album');
				
				add_action('parent_file', array(&$this, 'tax_menu_correction'));
				
				// Settings Submenu
				add_submenu_page($this->music_store_slug, 'Music Store Settings', 'Store Settings', 'edit_pages', $this->music_store_slug.'-settings', array(&$this, 'settings_page'));
				
				// Sales report submenu
				add_submenu_page($this->music_store_slug, 'Music Store Sales Report', 'Sales Report', 'edit_pages', $this->music_store_slug.'-reports', array(&$this, 'settings_page'));
				
			}	
		} // End menu_links
		
		/*
		*	Create tabs for setting page and payment stats
		*/
		function settings_tabs($current = 'reports'){
			$tabs = array( 'settings' => 'Music Store Settings', 'song' => 'Music Store Songs', 'collection' => 'Music Store Collections','reports' => 'Sales Report');
			echo '<h2 class="nav-tab-wrapper">';
			foreach( $tabs as $tab => $name ){
				$class = ( $tab == $current ) ? ' nav-tab-active' : '';
				if($tab == 'song' )
					echo "<a class='nav-tab$class' href='edit.php?post_type=ms_$tab'>$name</a>";
				elseif($tab == 'collection')
					echo "<a class='nav-tab$class' href='javascript:void(0);' onclick='window.alert(\"Collections only available for commercial version of plugin\")'>$name</a>";
				else
					echo "<a class='nav-tab$class' href='admin.php?page={$this->music_store_slug}-$tab&tab=$tab'>$name</a>";

			}
			echo '</h2>';
		} // End settings_tabs 	
		
		/**
		* Get the list of possible paypal butt
		*/
		function _paypal_buttons(){
			$b = get_option('ms_paypal_button', MS_PAYPAL_BUTTON);
			$p = MS_FILE_PATH.'/paypal_buttons';
			$d = dir($p);
			$str = "";
			while (false !== ($entry = $d->read())) {
				if($entry != "." && $entry != ".." && is_file("$p/$entry"))
					$str .= "<input type='radio' name='ms_paypal_button' value='$entry' ".(($b == $entry) ? "checked" : "")." />&nbsp;<img src='".MS_URL."/paypal_buttons/$entry'/>&nbsp;&nbsp;";
			}
			$d->close();
			return $str;
		} // End _paypal_buttons
		
		/*
		* Set the music store settings
		*/
		function settings_page(){
			global $wpdb;
			
			if ( wp_verify_nonce( $_POST['ms_settings'], plugin_basename( __FILE__ ) ) ){
				update_option('ms_main_page', $_POST['ms_main_page']);
				update_option('ms_filter_by_genre', ((isset($_POST['ms_filter_by_genre'])) ? true : false));
				update_option('ms_items_page_selector', ((isset($_POST['ms_items_page_selector'])) ? true : false));
				update_option('ms_items_page', $_POST['ms_items_page']);
				update_option('ms_paypal_email', $_POST['ms_paypal_email']);
				update_option('ms_paypal_button', $_POST['ms_paypal_button']);
				update_option('ms_paypal_currency', $_POST['ms_paypal_currency']);
				update_option('ms_paypal_language', $_POST['ms_paypal_language']);
				update_option('ms_paypal_enabled', ((isset($_POST['ms_paypal_enabled'])) ? true : false));
				update_option('ms_notification_from_email', $_POST['ms_notification_from_email']);
				update_option('ms_notification_to_email', $_POST['ms_notification_to_email']);
				update_option('ms_notification_to_payer_subject', $_POST['ms_notification_to_payer_subject']);
				update_option('ms_notification_to_payer_message', $_POST['ms_notification_to_payer_message']);
				update_option('ms_notification_to_seller_subject', $_POST['ms_notification_to_seller_subject']);
				update_option('ms_notification_to_seller_message', $_POST['ms_notification_to_seller_message']);				
				update_option('ms_old_download_link', $_POST['ms_old_download_link']);				
				update_option('ms_secure_playback_text', $_POST['ms_secure_playback_text']);				
				update_option('ms_file_percent', $_POST['ms_file_percent']);				
?>				
				<div class="updated" style="margin:5px 0;"><strong><?php _e("Settings Updated", MS_TEXT_DOMAIN); ?></strong></div>
<?php				
			}
			
			$current_tab = (isset($_REQUEST['tab'])) ? $_REQUEST['tab'] : (($_REQUEST['page'] == 'music-store-menu-reports') ? 'reports' : 'settings');
			
			$this->settings_tabs( 
				$current_tab
			);
?>
			<p style="border:1px solid #E6DB55;margin-bottom:10px;padding:5px;background-color: #FFFFE0;">
				To get commercial version of Music Store, <a href="http://wordpress.dwbooster.com/content-tools/music-store" target="_blank">CLICK HERE</a><br />
				For reporting an issue or to request a customization, <a href="http://wordpress.dwbooster.com/contact-us" target="_blank">CLICK HERE</a>
			</p>
<?php			
			switch($current_tab){
				case 'settings':
?>
					<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
					<input type="hidden" name="tab" value="settings" />
					<!-- STORE CONFIG -->
					<div class="postbox">
						<h3 class='hndle' style="padding:5px;"><span><?php _e('Store page config', MS_TEXT_DOMAIN); ?></span></h3>
						<div class="inside">
							<table class="form-table">
								<tr valign="top">
									<th><?php _e('URL of store page', MS_TEXT_DOMAIN); ?></th>
									<td>
										<input type="text" name="ms_main_page" size="40" value="<?php echo esc_attr(get_option('ms_main_page', MS_MAIN_PAGE)); ?>" />
										<br />
										<em><?php _e('Set the URL of page where the music store was inserted', MS_TEXT_DOMAIN); ?></em>
									</td>
								</tr>
								<tr valign="top">
									<th><?php _e('Allow to filter by type', MS_TEXT_DOMAIN); ?></th>
									<td>
										<input type="checkbox" name="ms_filter_by_type" disabled  />
										<em style="color:#FF0000;"><?php _e('Only available for commercial version of plugin', MS_TEXT_DOMAIN); ?></em>
									</td>
								</tr>
								<tr valign="top">
									<th><?php _e('Allow to filter by genre', MS_TEXT_DOMAIN); ?></th>
									<td><input type="checkbox" name="ms_filter_by_genre" size="40" value="1" <?php if (get_option('ms_filter_by_genre', MS_FILTER_BY_GENRE)) echo 'checked'; ?> /></td>
								</tr>
								<tr valign="top">
									<th><?php _e('Allow multiple pages', MS_TEXT_DOMAIN); ?></th>
									<td><input type="checkbox" name="ms_items_page_selector" size="40" value="1" <?php if (get_option('ms_items_page_selector', MS_ITEMS_PAGE_SELECTOR)) echo 'checked'; ?> /></td>
								</tr>
								<tr valign="top">
									<th><?php _e('Items for page', MS_TEXT_DOMAIN); ?></th>
									<td><input type="text" name="ms_items_page" value="<?php echo esc_attr(get_option('ms_items_page', MS_ITEMS_PAGE)); ?>" /></td>
								</tr>
								<tr valign="top">
									<th><?php _e('Percent of audio used for protected playbacks', MS_TEXT_DOMAIN); ?></th>
									<td>
										<input type="text" name="ms_file_percent" disabled /> % <br />
										<em><?php _e('To prevent unauthorized copying of audio files, the files will be partially accessible.',MS_TEXT_DOMAIN);?>
										</em>
										<em style="color:#FF0000;"><?php _e('Only available for commercial version of plugin', MS_TEXT_DOMAIN); ?>
										</em>
									</td>
								</tr>
								
								<tr valign="top">
									<th><?php _e('Explain text for protected playbacks', MS_TEXT_DOMAIN); ?></th>
									<td>
										<input type="text" name="ms_secure_playback_text" size="40" disabled /><br />
										<em><?php _e('The text will be shown below of the music player when secure playback is checked.', MS_TEXT_DOMAIN); ?>
										</em>
										<em style="color:#FF0000;">
										<?php _e('Only available for commercial version of plugin', MS_TEXT_DOMAIN); ?>
										</em>
										
									</td>
								</tr>
							</table>
						</div>
					</div>
					
					<!-- PAYPAL BOX -->
					<div class="postbox">
						<h3 class='hndle' style="padding:5px;"><span><?php _e('Paypal Payment Configuration', MS_TEXT_DOMAIN); ?></span></h3>
						<div class="inside">

						<table class="form-table">
							<tr valign="top">        
							<th scope="row"><?php _e('Enable Paypal Payments?', MS_TEXT_DOMAIN); ?></th>
							<td><input type="checkbox" name="ms_paypal_enabled" size="40" value="1" <?php if (get_option('ms_paypal_enabled', MS_PAYPAL_ENABLED)) echo 'checked'; ?> /></td>
							</tr>    
						
							<tr valign="top">        
							<th scope="row"><?php _e('Paypal email', MS_TEXT_DOMAIN); ?></th>
							<td><input type="text" name="ms_paypal_email" size="40" value="<?php echo esc_attr(get_option('ms_paypal_email', MS_PAYPAL_EMAIL)); ?>" /></td>
							</tr>
							 
							<tr valign="top">
							<th scope="row"><?php _e('Currency', MS_TEXT_DOMAIN); ?></th>
							<td><input type="text" name="ms_paypal_currency" value="<?php echo esc_attr(get_option('ms_paypal_currency', MS_PAYPAL_CURRENCY)); ?>" /></td>
							</tr>
							
							<tr valign="top">
							<th scope="row"><?php _e('Paypal language', MS_TEXT_DOMAIN); ?></th>
							<td><input type="text" name="ms_paypal_language" value="<?php echo esc_attr(get_option('ms_paypal_language', MS_PAYPAL_LANGUAGE)); ?>" /></td>
							</tr>  
							
							<tr valign="top">
							<th scope="row"><?php _e('Paypal button', MS_TEXT_DOMAIN); ?></th>
							<td><?php print $this->_paypal_buttons(); ?></td>
							</tr>  
							
							
							<tr valign="top">
							<th scope="row"><?php _e('Download link valid for', MS_TEXT_DOMAIN); ?></th>
							<td><input type="text" name="ms_old_download_link" value="<?php echo esc_attr(get_option('ms_old_download_link', MS_OLD_DOWNLOAD_LINK)); ?>" /> <?php _e('day(s)', MS_TEXT_DOMAIN)?></td>
							</tr>  
						 </table>  
					  </div>
					</div>
					
					<!-- NOTIFICATIONS BOX -->
					<div class="postbox">
						<h3 class='hndle' style="padding:5px;"><span><?php _e('Notification Settings', MS_TEXT_DOMAIN); ?></span></h3>
						<div class="inside">

						<table class="form-table">
							<tr valign="top">        
							<th scope="row"><?php _e('Notification "from" email', MS_TEXT_DOMAIN); ?></th>
							<td><input type="text" name="ms_notification_from_email" size="40" value="<?php echo esc_attr(get_option('ms_notification_from_email', MS_NOTIFICATION_FROM_EMAIL)); ?>" /></td>
							</tr>    
						
							<tr valign="top">        
							<th scope="row"><?php _e('Send notification to email', MS_TEXT_DOMAIN); ?></th>
							<td><input type="text" name="ms_notification_to_email" size="40" value="<?php echo esc_attr(get_option('ms_notification_to_email', MS_NOTIFICATION_TO_EMAIL)); ?>" /></td>
							</tr>
							 
							<tr valign="top">
							<th scope="row"><?php _e('Email subject confirmation to user', MS_TEXT_DOMAIN); ?></th>
							<td><input type="text" name="ms_notification_to_payer_subject" size="40" value="<?php echo esc_attr(get_option('ms_notification_to_payer_subject', MS_NOTIFICATION_TO_PAYER_SUBJECT)); ?>" /></td>
							</tr>
							
							<tr valign="top">
							<th scope="row"><?php _e('Email confirmation to user', MS_TEXT_DOMAIN); ?></th>
							<td><textarea name="ms_notification_to_payer_message" cols="60" rows="5"><?php echo esc_attr(get_option('ms_notification_to_payer_message', MS_NOTIFICATION_TO_PAYER_MESSAGE)); ?></textarea></td>
							</tr>
							
							<tr valign="top">
							<th scope="row"><?php _e('Email subject notification to admin', MS_TEXT_DOMAIN); ?></th>
							<td><input type="text" name="ms_notification_to_seller_subject" size="40" value="<?php echo esc_attr(get_option('ms_notification_to_seller_subject', MS_NOTIFICATION_TO_SELLER_SUBJECT)); ?>" /></td>
							</tr>
							
							<tr valign="top">
							<th scope="row"><?php _e('Email notification to admin', MS_TEXT_DOMAIN); ?></th>
							<td><textarea name="ms_notification_to_seller_message"  cols="60" rows="5"><?php echo esc_attr(get_option('ms_notification_to_seller_message', MS_NOTIFICATION_TO_SELLER_MESSAGE)); ?></textarea></td>
							</tr>
						 </table>  
					  </div>
					</div>
					<?php wp_nonce_field( plugin_basename( __FILE__ ), 'ms_settings' ); ?>
					<div class="submit"><input type="submit" class="button-primary" value="<?php _e('Update Settings', MS_TEXT_DOMAIN); ?>" />
					</form>

<?php				
				break;
				case 'reports':
					if ( wp_verify_nonce( $_POST['ms_purchase_stats'], plugin_basename( __FILE__ ) ) ){
						if(isset($_POST['purchase_id'])){ // Delete the purchase
							$wpdb->query($wpdb->prepare(
								"DELETE FROM ".$wpdb->prefix.MSDB_PURCHASE." WHERE id=%d",
								$_POST['purchase_id']
							));
						}
					}
					
					$from_day = (isset($_POST['from_day'])) ? $_POST['from_day'] : date('j');
					$from_month = (isset($_POST['from_month'])) ? $_POST['from_month'] : date('m');
					$from_year = (isset($_POST['from_year'])) ? $_POST['from_year'] : date('Y');
					
					$to_day = (isset($_POST['to_day'])) ? $_POST['to_day'] : date('j');
					$to_month = (isset($_POST['to_month'])) ? $_POST['to_month'] : date('m');
					$to_year = (isset($_POST['to_year'])) ? $_POST['to_year'] : date('Y');
					
					$purchase_list = $wpdb->get_results("SELECT purchase.*, posts.* FROM ".$wpdb->prefix.MSDB_PURCHASE." AS purchase, ".$wpdb->prefix."posts AS posts WHERE posts.ID = purchase.product_id AND DATEDIFF(purchase.date, '{$from_year}-{$from_month}-{$from_day}')>=0 AND DATEDIFF(purchase.date, '{$to_year}-{$to_month}-{$to_day}')<=0;");
					
?>
					<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" id="purchase_form">
					<?php wp_nonce_field( plugin_basename( __FILE__ ), 'ms_purchase_stats' ); ?>
					<input type="hidden" name="tab" value="reports" />
					<!-- FILTER REPORT -->
					<div class="postbox">
						<h3 class='hndle' style="padding:5px;"><span><?php _e('Filter from date', MS_TEXT_DOMAIN); ?></span></h3>
						<div class="inside">
							<?php
								$months_list = array(
									'01' => __('January', MS_TEXT_DOMAIN),
									'02' => __('February', MS_TEXT_DOMAIN),
									'03' => __('March', MS_TEXT_DOMAIN),
									'04' => __('April', MS_TEXT_DOMAIN),
									'05' => __('May', MS_TEXT_DOMAIN),
									'06' => __('June', MS_TEXT_DOMAIN),
									'07' => __('July', MS_TEXT_DOMAIN),
									'08' => __('August', MS_TEXT_DOMAIN),
									'09' => __('September', MS_TEXT_DOMAIN),
									'10' => __('October', MS_TEXT_DOMAIN),
									'11' => __('November', MS_TEXT_DOMAIN),
									'12' => __('December', MS_TEXT_DOMAIN),
								);
							?>
							<label><?php _e('From: ', MS_TEXT_DOMAIN); ?></label>
							<select name="from_day">
							<?php
								for($i=1; $i <=31; $i++) print '<option value="'.$i.'"'.(($from_day == $i) ? ' SELECTED' : '').'>'.$i.'</option>';
							?>
							</select>
							<select name="from_month">
							<?php
								foreach($months_list as $month => $name) print '<option value="'.$month.'"'.(($from_month == $month) ? ' SELECTED' : '').'>'.$name.'</option>';
							?>
							</select>
							<input type="text" name="form_year" value="<?php print $from_year; ?>" />
							
							<label><?php _e('To: ', MS_TEXT_DOMAIN); ?></label>
							<select name="to_day">
							<?php
								for($i=1; $i <=31; $i++) print '<option value="'.$i.'"'.(($to_day == $i) ? ' SELECTED' : '').'>'.$i.'</option>';
							?>
							</select>
							<select name="to_month">
							<?php
								foreach($months_list as $month => $name) print '<option value="'.$month.'"'.(($to_month == $month) ? ' SELECTED' : '').'>'.$name.'</option>';
							?>
							</select>
							<input type="text" name="to_year" value="<?php print $from_year; ?>" />
							
							<input type="submit" value="<?php _e('Search', MS_TEXT_DOMAIN); ?>" class="button-primary" />
						</div>
					</div>	
					<!-- PURCHASE LIST -->
					<div class="postbox">
						<h3 class='hndle' style="padding:5px;"><span><?php _e('Store page config', MS_TEXT_DOMAIN); ?></span></h3>
						<div class="inside">
							<table class="form-table" style="border-bottom:1px solid #CCC;margin-bottom:10px;">
								<THEAD>
									<TR style="border-bottom:1px solid #CCC;">
										<TH>Product</TH><TH>Buyer</TH><TH>Amount</TH><TH>Currency</TH><TH>Download link</TH><TH></TH>
									</TR>
								</THEAD>
								<TBODY>
								<?php
								$totals = array('UNDEFINED'=>0);
								if(count($purchase_list)){	
									foreach($purchase_list as $purchase){
										
										if(preg_match('/mc_currency=([^\s]*)/', $purchase->paypal_data, $matches)){
											$currency = strtoupper($matches[1]);
											if(!isset($totals[$currency])) $totals[$currency] = $purchase->amount;
											else $totals[$currency] += $purchase->amount;
										}else{
											$currency = '';
											$totals['UNDEFINED'] += $purchase->amount;
										}
										echo '
											<TR>
												<TD><a href="'.$purchase->guid.'" target="_blank">'.$purchase->post_title.'</a></TD>
												<TD>'.$purchase->email.'</TD>
												<TD>'.$purchase->amount.'</TD>
												<TD>'.$currency.'</TD>
												<TD><a href="'.MS_URL.'/ms-core/ms-download.php?purchase_id='.$purchase->purchase_id.'" target="_blank">Download Link</a></TD>
												<TD><input type="button" class="button-primary" onclick="delete_purchase('.$purchase->id.');" value="Delete"></TD>
											</TR>
										';
									}
								}else{
									echo '
										<TR>
											<TD COLSPAN="6">
												'.__('No sales yet', MS_TEXT_DOMAIN).'
											</TD>
										</TR>
									';
								}	
								?>
								</TBODY>
							</table>
							
							<?php
								if(count($totals) > 1 || $totals['UNDEFINED']){
							?>
									<table style="border: 1px solid #CCC;">
										<TR><TD COLSPAN="2" style="border-bottom:1px solid #CCC;">TOTALS</TD></TR>
										<TR><TD style="border-bottom:1px solid #CCC;">CURRENCY</TD><TD style="border-bottom:1px solid #CCC;">AMOUNT</TD></TR>
									<?php
										foreach($totals as $currency=>$amount)
											if($amount)
												print "<TR><TD><b>{$currency}</b></TD><TD>{$amount}</TD></TR>";
									?>	
									</table>
							<?php	
								}
							?>
						</div>
					</div>
					</form>
<?php					
				break;
			}	
		} // End settings_page

/** LOADING PUBLIC OR ADMINSITRATION RESOURCES **/		

		/**
		* Load public scripts and styles
		*/
		function public_resources(){
			wp_enqueue_style('ms-mediacore-style', plugin_dir_url(__FILE__).'ms-styles/mediaelementplayer.min.css');
			wp_enqueue_style('ms-style', plugin_dir_url(__FILE__).'ms-styles/ms-public.css');
			
			wp_enqueue_script('ms-mediacore-script', plugin_dir_url(__FILE__).'ms-script/mediaelement-and-player.min.js', array('jquery'));
			wp_enqueue_script('ms-media-script', plugin_dir_url(__FILE__).'ms-script/codepeople-plugins.js', array('ms-mediacore-script'), false, true);
		} // End public_resources
		
		/**
		* Load admin scripts and styles
		*/
		function admin_resources($hook){
			global $post;
			if($hook == "settings_page_music-store"){
				wp_enqueue_script('ms-admin-script', plugin_dir_url(__FILE__).'ms-script/ms-admin.js', array('jquery'));
			}
			if ( $hook == 'post-new.php' || $hook == 'post.php' ) {
				wp_enqueue_script('ms-admin-script', plugin_dir_url(__FILE__).'ms-script/ms-admin.js', array('jquery'));
				
				if($post->post_type == "ms_song"){
					// Scripts and styles required for metaboxs
					wp_enqueue_style('ms-admin-style', plugin_dir_url(__FILE__).'ms-styles/ms-admin.css');
					wp_localize_script('ms-admin-script', 'music_store', array('post_id'  	=> $post->ID));	
				}else{
					// Scripts required for music store insertion
					wp_enqueue_style('wp-jquery-ui-dialog');
					
					// Set the variables for insertion dialog
					$tags = '';
					// Load genres
					$genre_list = get_terms('ms_genre', array( 'hide_empty' => 0 ));
					// Load artists
					$artist_list = get_terms('ms_artist', array( 'hide_empty' => 0 ));
					// Album
					$album_list = get_terms('ms_album', array( 'hide_empty' => 0 ));
					
					$tags .= '<div title="'.__('Insert Music Store', MS_TEXT_DOMAIN).'"><div style="padding:20px;">';
					
					$tags .= '<div>'.__('Filter results by products type:', MS_TEXT_DOMAIN).'<br /><select id="load" name="load" style="width:100%"><option value="all">'.__('All types', MS_TEXT_DOMAIN).'</option></select><br /><em style="color:#FF0000;">'.__('Filter by product types is only available for commercial version of plugin').'</em></div><div>'.__('Columns:', MS_TEXT_DOMAIN).' <br /><input type="text" name="columns" id="columns" style="width:100%" value="1" /></div>';
					
					$tags .= '<div>'.__('Filter results by genre:', MS_TEXT_DOMAIN).'<br /><select id="genre" name="genre" style="width:100%"><option value="all">'.__('All genres', MS_TEXT_DOMAIN).'</option>';
					
					foreach($genre_list as $genre){
							$tags .= '<option value="'.$genre->term_id.'">'.$genre->name.'</option>';
					}
					
					$tags .= '</select></div><div>'.__('-or- filter results by artist:', MS_TEXT_DOMAIN).'<br /><select id="artist" name="artis" style="width:100%"><option value="all">'.__('All artists', MS_TEXT_DOMAIN).'</option>';
					
					foreach($artist_list as $artist){
							$tags .= '<option value="'.$artist->term_id.'">'.$artist->name.'</option>';
					}
					$tags .= '</select></div><div>'.__('-or- filter results by album:', MS_TEXT_DOMAIN).'<br /><select id="album" name="album" style="width:100%"><option value="all">'.__('All albums', MS_TEXT_DOMAIN).'</option>';
					
					foreach($album_list as $album){
							$tags .= '<option value="'.$album->term_id.'">'.$album->name.'</option>';
					}
					$tags .= '</select></div></div></div>';
					
					wp_localize_script('ms-admin-script', 'music_store', array('tags' => $tags));	
				}	
			}
		} // End admin_resources
		

/** LOADING MUSIC STORE AND ITEMS ON WORDPRESS SECTIONS **/		
				
		/**
		*	Add custom post type to the search result
		*/
		function add_post_type_to_results($query){
			global $wpdb;
			if ( $query->is_search){
				$not_in = array();
				$restricted_list = $wpdb->get_results("SELECT posts.ID FROM ".$wpdb->prefix.MSDB_POST_DATA." as post_data,".$wpdb->prefix."posts as posts  WHERE posts.post_type='ms_song' AND posts.ID=post_data.id AND posts.post_status='publish' AND post_data.as_single=0");
				
				foreach($restricted_list as $restricted){
					$not_in[] = $restricted->ID;
				}
				
				if(!empty($not_in))
					$query->set('post__not_in', $not_in);
			}	
			return $query;
		} // End add_post_type_to_results
		
		/**
		* Replace the music_store shortcode with correct items
		*
		*/
		function load_store($atts, $content, $tag){
			global $wpdb;
			
			// Generated music store
			$music_store = "";
			$page_links = "";
			$header = "";
			
			// Extract the music store attributes
			extract(shortcode_atts(array(
					'load' 		=> 'all',
					'genre' 	=> 'all',
					'artist'	=> 'all',
					'album'		=> 'all',
					'columns'  	=> 1
				), $atts)
			);
			
			// Extract query_string variables correcting music store attributes
			if(isset($_REQUEST['filter_by_type']) && in_array($_REQUEST['filter_by_type'], array('all', 'singles'))){
				$_SESSION['ms_post_type'] = $_REQUEST['filter_by_type'];
			}
			
			if(isset($_SESSION['ms_post_type'])){
				$load = $_SESSION['ms_post_type'];
			}
			
			if(isset($_REQUEST['filter_by_genre'])){
				$_SESSION['ms_genre'] = $_REQUEST['filter_by_genre'];
			}
			
			if(isset($_SESSION['ms_genre'])){
				$genre = $_SESSION['ms_genre'];
			}
			
			if(isset($_REQUEST['ordering_by']) && in_array($_REQUEST['ordering_by'], array('plays', 'price'))){
				$_SESSION['ms_ordering'] = $_REQUEST['ordering_by'];
			}else{
				$_SESSION['ms_ordering'] = "plays";
			}
			
			// Extract info from music_store options
			$allow_filter_by_type = get_option('ms_filter_by_type', MS_FILTER_BY_TYPE);
			$allow_filter_by_genre = get_option('ms_filter_by_genre', MS_FILTER_BY_GENRE);
			
 
			// Items for page
			$items_page 			= max(get_option('ms_items_page', MS_ITEMS_PAGE), 1);
			// Display pagination
			$items_page_selector 	= get_option('ms_items_page_selector', MS_ITEMS_PAGE_SELECTOR);
			
			// Query clauses 
			$_select 	= "SELECT DISTINCT posts.ID, posts.post_type";
			$_from 		= "FROM ".$wpdb->prefix."posts as posts,".$wpdb->prefix.MSDB_POST_DATA." as posts_data"; 
			$_where 	= "WHERE posts.post_status='publish'";
			$_order_by 	= "ORDER BY posts_data.".$_SESSION['ms_ordering']." ".(($_SESSION['ms_ordering'] == 'plays') ? "DESC" : "ASC");
			$_limit 	= "";
			
			
			if($artist !== 'all' || $genre !== 'all' || $album !== 'all'){
				// Load the taxonomy tables
				$_from .= ", ".$wpdb->prefix."term_taxonomy as taxonomy, ".$wpdb->prefix."term_relationships as term_relationships, ".$wpdb->prefix."terms as terms";
				
				$_where .= " AND taxonomy.term_taxonomy_id=term_relationships.term_taxonomy_id AND term_relationships.object_id=posts.ID AND taxonomy.term_id=terms.term_id AND (";
				
				$connector = "";
				if($genre !== 'all'){
					// Search for genres assigned directly to the posts
					$_where .= $connector."(taxonomy.taxonomy='ms_genre' AND terms.slug='$genre')";
					$connector = " OR ";
				}
				
				if($artist !== 'all'){
					// Search for artist assigned directly to the posts
					$_where .= $connector."(taxonomy.taxonomy='ms_artist' AND terms.slug='$artist')";
					$connector = " OR ";
				}
				
				if($album !== 'all'){
					// Search for albums assigned directly to the posts
					$_where .= $connector."(taxonomy.taxonomy='ms_album' AND terms.slug='$album')";
				}
				
				$_where .= ")";
				
				// End taxonomies
			} 
			
			$_where .= " AND (";
			
			if($load == 'all' || $load == 'singles'){
				$_where .= "(post_type='ms_song' AND posts_data.as_single=1)";
			}
			$_where .= ")";
			
			
			// Create pagination section
			if($items_page_selector && $items_page){
				// Checking for page parameter or get page from session variables
				if(isset($_GET['page_number'])){
					$_SESSION['ms_page_number'] = $_GET['page_number'];
				}if(!isset($_SESSION['ms_page_number'])){
					$_SESSION['ms_page_number'] = 0;
				}
				
				$_limit = "LIMIT ".($_SESSION['ms_page_number']*$items_page).", $items_page";
				
				// Make page links
				$page_links .= "<DIV class='music-store-pagination'>";
				$page_href = '?'.((strlen($_SERVER['QUERY_STRING'])) ? preg_replace('/(&)?page_number=\d+/', '', $_SERVER['QUERY_STRING']).'&' : '');	
				
				// Get total records for pagination
				$query = "SELECT COUNT(DISTINCT posts.ID) ".$_from." ".$_where;
				$total = $wpdb->get_var($query);
			
				for($i=0, $h = ceil($total/max($items_page,1)); $i < $h; $i++){
					if($_SESSION['ms_page_number'] == $i)
						$page_links .= "<span class='page-selected'>".($i+1)."</span>";
					else	
						$page_links .= "<a class='page-link' href='".$page_href."page_number=".$i."'>".($i+1)."</a>";
				}
				$page_links .= "</DIV>";
			}
			
			// Create items section
			$query = $_select." ".$_from." ".$_where." ".$_order_by." ".$_limit;
			$results = $wpdb->get_results($query);
			$tpl = new tpleng(dirname(__FILE__).'/ms-templates/', 'comment');
			
			$width = floor(100/min($columns, max(count($results),1)));
			$music_store .= "<div class='music-store-items'>";
			foreach($results as $result){
				$obj = new MSSong($result->ID);
				$music_store .= "<div style='width:{$width}%;' class='music-store-item'>".$obj->display_content('store', $tpl, 'return')."</div>";
			}
			$music_store .= "</div>";
			$header .= "
						<form method='post'>
						<div class='music-store-header'>
						";
			// Create filter section
			if($allow_filter_by_genre){
			
				$header .= "<div class='music-store-filters'>".__('Filter by', MS_TEXT_DOMAIN);
				if($allow_filter_by_genre){
					$header .= __(' genre: ', MS_TEXT_DOMAIN).
							"<select id='filter_by_genre' name='filter_by_genre' onchange='this.form.submit();'>
							<option value='all'>".__('All genres', MS_TEXT_DOMAIN)."</option>
							";
					$genres = get_terms("ms_genre");
					foreach($genres as $genre_item){
						$header .= "<option value='".$genre_item->slug."' ".(($genre == $genre_item->slug) ? "SELECTED" : "").">".$genre_item->name."</option>";
					}
					$header .= "</select>";
				}
				$header .="</div>";
			}
			
			// Create order filter
			$header .= "<div class='music-store-ordering'>".
							__('Order by: ', MS_TEXT_DOMAIN).
							"<select id='ordering_by' name='ordering_by' onchange='this.form.submit();'>
								<option value='plays' ".(($_SESSION['ms_ordering'] == 'plays') ? "SELECTED" : "").">".__('Popularity', MS_TEXT_DOMAIN)."</option>
								<option value='price' ".(($_SESSION['ms_ordering'] == 'price') ? "SELECTED" : "").">".__('Price', MS_TEXT_DOMAIN)."</option>
							</select>
						</div>";
						
			$header .= "
						</div>
						</form>
						";
			return $header.$music_store.$page_links;
		} // End load_store
			
/** MODIFY TITLE AND CONTENT OF POSTS LOADED **/
		
		/**
		* Remove title from songs
		*/
		function display_title($title){
			global $post;
			if($post->post_type == 'ms_song'){
				return '';
			}else{
				return $title;
			}
			
		} // End display_title
		
		/*
		* Load the music store templates for songs display
		*/
		function load_templates(){
			add_filter('the_content', array(&$this, 'display_content'));
			add_filter('the_title', array(&$this, 'display_title'));
		} // End load_templates
		
		/**
		* Display content of songs through templates
		*/
		function display_content($content){
			global $post;
			$tpl = new tpleng(dirname(__FILE__).'/ms-templates/', 'comment');
			
			switch($post->post_type){
				case "ms_song":
					$song = new MSSong($post->ID);
					$song->display_content(((is_singular()) ? 'single' : 'multiple'), $tpl);
				break;
				default:
					return $content;
				break;
			}
		} // End display_content
		

		/**
		* Set a media button for music store insertion
		*/
		function set_music_store_button(){
			global $post;
			
			if($post->post_type != 'ms_song')
			print '<a href="javascript:open_insertion_music_store_window();" title="'.__('Insert Music Store').'"><img src="'.MS_CORE_IMAGES_URL.'/music-store-icon.gif'.'" alt="'.__('Insert Music Store').'" /></a>';
		} // End set_music_store_button
		
		
		/**
		*	Check for post to delete and remove the metadata saved on additional metadata tables
		*/
		function delete_post($pid){
			global $wpdb;
			if($wpdb->get_var($wpdb->prepare("SELECT id FROM ".$wpdb->prefix.MSDB_POST_DATA." WHERE id=%d;", $pid))){
				return  $wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix.MSDB_POST_DATA." WHERE id=%d;",$pid));
			}
			return false;
		} // End delete_post

	} // End MusicStore class
	
	// Initialize MusicStore class
	session_start();
	$GLOBALS['music_store'] = new MusicStore;
	
	register_activation_hook(__FILE__, array(&$GLOBALS['music_store'], 'register'));
	
} // Class exists check

 
?>
