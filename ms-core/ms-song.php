<?php
function debug($mssg){
	$h = fopen(dirname(__FILE__).'/test.txt', 'a');			
	fwrite($h, $mssg.'|');
	fclose($h);
}

if(!class_exists('MSSong')){
	class MSSong{
		/*
		* @var integer
		*/
		private $id;
		
		/*
		* @var object
		*/
		private $song_data 	= array();
		private $post_data 	= array();
		private $artist = array();
		private $album 	= array();
		private $genre	= array();
		
		/**
		* MSSong constructor
		*
		* @access public
		* @return void
		*/
		function __construct($id){
			global $wpdb;
			
			$this->id = $id;
			// Read general data
			$data = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wpdb->prefix.MSDB_POST_DATA." WHERE id=%d", array($id)));
			if($data) $this->song_data = (array)$data;
			
			$this->post_data = get_post($id, 'ARRAY_A');
			
			// Read artist list
			$this->artist = (array)wp_get_object_terms($id, 'ms_artist');
			
			// Read album list
			$this->album = (array)wp_get_object_terms($id, 'ms_album');
			
			// Read associated genres
			$this->genre = (array)wp_get_object_terms($id, 'ms_genre');
			
		} // End __construct
		
		function __get($name){
			switch($name){
				case 'genre':
					return $this->genre;
				break;
				case 'artist':
					return $this->artist;
				break;
				case 'album':
					return $this->album;
				break;
				default:
					if(isset($this->song_data[$name])){
						return $this->song_data[$name];
					}elseif(isset($this->post_data[$name])){
						return $this->post_data[$name];
					}else{
						return null;
					}	
			} // End switch
		} // End __get
		
		function __set($name, $value){
			global $wpdb;

			if(
				isset($this->song_data[$name]) && 
				$wpdb->update(
					$wpdb->prefix.MSDB_POST_DATA, 
					array($name => $value),
					array('id' => $this->id)
				)
			){
				$this->song_data[$name] = $value;
			}
		} // End __set
		
		function __isset($name){
			return isset($this->song_data[$name]) || isset($this->post_data[$name]);
		} // End __isset
		
		/*
		* Display content
		*/
		function display_content($mode, $tpl_engine, $output='echo'){
			$action  = MS_URL.'/ms-core/ms-submit.php';
			$song_arr = array(
				'title' => $this->post_title,
				'price' => $this->price.get_option('ms_paypal_currency', MS_PAYPAL_CURRENCY),
				'cover' => $this->cover,
				'link'	=> $this->guid,
				'popularity' => $this->plays
			);
			
			if($this->time) $song_arr['time'] = $this->time;
			if($this->year) $song_arr['year'] = $this->year;
			if($this->info) $song_arr['info'] = $this->info;
			
			if(count($this->artist)){
				$song_arr['has_artists'] = true;
				$artists = array();
				foreach($this->artist as $artist){
					$artists[] = array('data' => '<a href="'.get_term_link($artist).'">'.$artist->name.'</a>');
				}
				$tpl_engine->set_loop('artists', $artists);
			}
				
			if(get_option('ms_paypal_enabled') && get_option('ms_paypal_email') && !empty($this->file) && !empty($this->price)){
				$paypal_button = MS_URL.'/paypal_buttons/'.get_option('ms_paypal_button', MS_PAYPAL_BUTTON);
				$song_arr['salesbutton'] = '<form action="'.$action.'" method="post"><input type="hidden" name="ms_product_type" value="single" /><input type="hidden" name="ms_product_id" value="'.$this->id.'" /><input type="image" src="'.$paypal_button.'" style="padding-top:5px;" /></form>';
			}
			
			if($mode == 'store' || $mode == 'multiple'){
				if($mode == 'store')
					$tpl_engine->set_file('song', 'song.tpl.html');
				else	
					$tpl_engine->set_file('song', 'song_multiple.tpl.html');
					
				$tpl_engine->set_var('song', $song_arr);
			}elseif($mode == 'single'){
				$this->plays += 1;
				$tpl_engine->set_file('song', 'song_single.tpl.html');
				$ms_main_page = get_option('ms_main_page', MS_MAIN_PAGE);
				if($ms_main_page){
					$song_arr['store_page'] = $ms_main_page;
				}
				
				$demo = $this->demo;
				$song_arr['demo'] 			= ($demo) ? '<audio src="'.$demo.'" style="width:100%;"></audio>' : '';
				
				if(strlen($this->post_content)){
					$song_arr['description'] 	= $this->post_content;
				}	
				
				if(count($this->genre)){
					$song_arr['has_genres'] = true;
					$genres = array();
					foreach($this->genre as $genre){
						$genres[] = array('data' => '<a href="'.get_term_link($genre).'">'.$genre->name.'</a>');
					}
					$tpl_engine->set_loop('genres', $genres);
				}
				
				if(count($this->album)){
					$song_arr['has_albums'] = true;
					$albums = array();
					foreach($this->album as $album){
						$albums[] = array('data' => '<a href="'.get_term_link($album).'">'.$album->name.'</a>');
					}
					$tpl_engine->set_loop('albums', $albums);
				}
				
				$tpl_engine->set_var('song', $song_arr);
			}
			
			return $tpl_engine->parse('song', $output);
		} // End display
		
		/*
		* Class method print_metabox, for metabox generation print
		*
		* @return void
		*/
		public static function print_metabox(){
			global $wpdb, $post;
			
			$query = "SELECT * FROM ".$wpdb->prefix.MSDB_POST_DATA." as data WHERE data.id = {$post->ID};";
			$data = $wpdb->get_row($query);

			$artist_post_list = wp_get_object_terms($post->ID, 'ms_artist');
			$artist_list = get_terms('ms_artist', array( 'hide_empty' => 0 ));
			
			$album_post_list = wp_get_object_terms($post->ID, 'ms_album');
			$album_list = get_terms('ms_album', array( 'hide_empty' => 0 ));
			
			wp_nonce_field( plugin_basename( __FILE__ ), 'ms_song_box_content_nonce' );
			$currency = get_option('ms_paypal_currency', MS_PAYPAL_CURRENCY);
			echo '
				<table class="form-table">
					<tr>
						<td>
							'.__('Sales price:', MS_TEXT_DOMAIN).'
						</td>
						<td>
							<input type="text" name="ms_price" id="ms_price" value="'.(($data && $data->price) ? esc_attr($data->price) : '').'" /> 
							'.(($currency) ? $currency : '').'
						</td>
					</tr>
					<tr>
						<td>
							'.__('Sale as a single:', MS_TEXT_DOMAIN).'
						</td>
						<td>
							<input type="checkbox" name="ms_as_single" id="ms_as_price" CHECKED DISABLED /> <em style="color:#FF0000;">The commercial version of the plugin allows the sale of audio as a single, or only as part of a collection</em>
						</td>
					</tr>
					<tr>
						<td>
							'.__('Audio file for sale:', MS_TEXT_DOMAIN).'
						</td>
						<td>
							<input type="text" name="ms_file_path" class="file_path" id="ms_file_path" value="'.(($data && $data->file) ? esc_attr($data->file) : '').'" placeholder="'.__('File path/URL', MS_TEXT_DOMAIN).'" /> <input type="button" class="button_for_upload button" value="'.__('Upload a file', MS_TEXT_DOMAIN).'" />
						</td>
					</tr>
					<tr>
						<td>
							'.__('Audio file for demo:', MS_TEXT_DOMAIN).'
						</td>
						<td>
							<input type="text" name="ms_demo_file_path" id="ms_demo_file_path" class="file_path"  value="'.(($data && $data->demo) ? esc_attr($data->demo) : '').'" placeholder="'.__('File path/URL', MS_TEXT_DOMAIN).'" /> <input type="button" class="button_for_upload button" value="'.__('Upload a file', MS_TEXT_DOMAIN).'" /><br />
							<input type="checkbox" name="ms_protect" id="ms_protect" disabled />
							'.__('Protect the file', MS_TEXT_DOMAIN).'<em style="color:#FF0000;">'.__('The protection of audio files is only available for commercial version of plugin', MS_TEXT_DOMAIN).'</em>
						</td>
					</tr>
					<tr>
						<td valign="top">
							'.__('Artist:', MS_TEXT_DOMAIN).'
						</td>
						<td><div id="ms_artist_list">';
						
						if($artist_post_list){
							foreach($artist_post_list as $artist){
								echo '<div class="ms-property-container"><input type="hidden" name="ms_artist[]" value="'.esc_attr($artist->name).'" /><input type="button" onclick="ms_remove(this);" class="button" value="'.esc_attr($artist->name).' [x]"></div>';
							}
							
						}
						echo '</div><div style="clear:both;"><select onchange="ms_select_element(this, \'ms_artist_list\', \'ms_artist\');"><option value="none">'.__('Select an Artist', MS_TEXT_DOMAIN).'</option>';
						if($artist_list){
							foreach($artist_list as $artist){
								echo '<option value="'.esc_attr($artist->name).'">'.$artist->name.'</option>';
							}
						}	
						echo '		
								 </select>
								 <input type="text" id="new_artist" placeholder="'.__('Enter a new artist', MS_TEXT_DOMAIN).'">
								 <input type="button" value="'.__('Add artist', MS_TEXT_DOMAIN).'" class="button" onclick="ms_add_element(\'new_artist\', \'ms_artist_list\', \'ms_artist_new\');"/><br />
								 <span class="ms-comment">'.__('Select an Artist from the list or enter new one', MS_TEXT_DOMAIN).'</span>
							</div>	
						</td>
					</tr>
					<tr>
						<td valign="top">
							'.__('Album including the song:', MS_TEXT_DOMAIN).'
						</td>
						<td><div id="ms_album_list">';
						if($album_post_list){
							foreach($album_post_list as $album){
								echo '<div class="ms-property-container"><input type="hidden" name="ms_album[]" value="'.esc_attr($album->name).'" /><input type="button" onclick="ms_remove(this);" class="button" value="'.esc_attr($album->name).' [x]"></div>';
							}
							
						}
						echo '</div><div style="clear:both;"><select onchange="ms_select_element(this, \'ms_album_list\', \'ms_album\');"><option value="none">'.__('Select an Album', MS_TEXT_DOMAIN).'</option>';

						if($album_list){
							foreach($album_list as $album){
								echo '<option value="'.esc_attr($album->name).'">'.$album->name.'</option>';
							}
						}	
						echo '		
								 </select>
								 <input type="text" id="new_album" placeholder="'.__('Enter a new album', MS_TEXT_DOMAIN).'">
								 <input type="button" value="'.__('Add album', MS_TEXT_DOMAIN).'" class="button" onclick="ms_add_element(\'new_album\', \'ms_album_list\', \'ms_album_new\');" /><br />
								 <span class="ms-comment">'.__('Select an Album from the list or enter new one', MS_TEXT_DOMAIN).'</span>
							</div>	
						</td>
					</tr>
					<tr>
						<td>
							'.__('Cover:', MS_TEXT_DOMAIN).'
						</td>
						<td>
							<input type="text" name="ms_cover" class="file_path" id="ms_cover" value="'.(($data && $data->cover) ? $data->cover : '').'" placeholder="'.__('File path/URL', MS_TEXT_DOMAIN).'" /> <input type="button" class="button_for_upload button" value="'.__('Upload a file', MS_TEXT_DOMAIN).'" />
						</td>
					</tr>
					<tr>
						<td>
							'.__('Duration:', MS_TEXT_DOMAIN).'
						</td>
						<td>
							<input type="text" name="ms_time" id="ms_time" value="'.(($data && $data->time) ? $data->time : '').'" /> <span class="ms-comment">'.__('For example 00:00', MS_TEXT_DOMAIN).'</span>
						</td>
					</tr>
					<tr>
						<td>
							'.__('Publication Year:', MS_TEXT_DOMAIN).'
						</td>
						<td>
							<input type="text" name="ms_year" id="ms_time" value="'.(($data && $data->year) ? $data->year : '').'" /> <span class="ms-comment">'.__('For example 1999', MS_TEXT_DOMAIN).'</span>
						</td>
					</tr>
					<tr>
						<td>
							'.__('Additional information:', MS_TEXT_DOMAIN).'
						</td>
						<td>
							<input type="text" name="ms_info" id="ms_info" value="'.(($data && $data->info) ? $data->info : '').'" placeholder="'.__('Page URL', MS_TEXT_DOMAIN).'" /> <span class="ms-comment">'.__('Different webpage with additional information', MS_TEXT_DOMAIN).'</span>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<p style="border:1px solid #E6DB55;margin-bottom:10px;padding:5px;background-color: #FFFFE0;">
								To get commercial version of Music Store, <a href="http://wordpress.dwbooster.com/content-tools/music-store" target="_blank">CLICK HERE</a><br />
								For reporting an issue or to request a customization, <a href="http://wordpress.dwbooster.com/contact-us" target="_blank">CLICK HERE</a>
							</p>
						</td>
					</tr>
				</table>
			';
		} // End print_metabox
		
		/*
		* Save the song data
		*
		* @access public
		* @return void
		*/
		public static function save_data(){
			global $wpdb, $post;

			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return;

			if ( !wp_verify_nonce( $_POST['ms_song_box_content_nonce'], plugin_basename( __FILE__ ) ) )
			return;

			if ( 'page' == $_POST['post_type'] ) {
				if ( !current_user_can( 'edit_page', $post_id ) )
				return;
			} else {
				if ( !current_user_can( 'edit_post', $post_id ) )
				return;
			}

			$id = $post->ID;
			$data = array(
						'time'  	=> $_POST['ms_time'],
						'file'  	=> $_POST['ms_file_path'],
						'demo'  	=> $_POST['ms_demo_file_path'],
						'protect' 	=> (isset($_POST['ms_protect'])) ? 1 : 0,
						'as_single' => 1,
						'info' 		=> $_POST['ms_info'],
						'cover' 	=> $_POST['ms_cover'],
						'price' 	=> $_POST['ms_price'],
						'year'      => $_POST['ms_year']
					);
			$format = array('%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s');
			$table = $wpdb->prefix.MSDB_POST_DATA;
			if(0 < $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE id=$id;") ){
				// Set an update query
				$wpdb->update(
					$table, 
					$data,
					array('id'=>$id),
					$format,
					array('%d')
				);
				
			}else{
				// Set an insert query
				$data['id'] = $id;
				$wpdb->insert(
					$table,
					$data,
					$format
				);
				
			}

			// Clear the artist and album lists and then set the new ones
			wp_set_object_terms($id, null, 'ms_artist');
			wp_set_object_terms($id, null, 'ms_album');
			
			// Set the artists list
			if(isset($_POST['ms_artist'])){
				wp_set_object_terms($id, $_POST['ms_artist'], 'ms_artist', true);
			}	
			
			if(isset($_POST['ms_artist_new'])){
				wp_set_object_terms($id, $_POST['ms_artist_new'], 'ms_artist', true);
			}
			
			// Set the album list
			if(isset($_POST['ms_album'])){
				wp_set_object_terms($id, $_POST['ms_album'], 'ms_album', true);
			}	
			
			if(isset($_POST['ms_album_new'])){
				wp_set_object_terms($id, $_POST['ms_album_new'], 'ms_album', true);
			}
			
		} // End save_data
		
		/*
		* Create the list of properties to display of songs
		* @param array
		* @return array
		*/	
		public static function columns($columns){
			return array(
				'cb'	 => '<input type="checkbox" />',
				'title'	 => __('Song Name', MS_TEXT_DOMAIN),
				'artist' => __('Artists', MS_TEXT_DOMAIN),
				'album'  =>__( 'Albums', MS_TEXT_DOMAIN),
				'genre'  =>__( 'Genres', MS_TEXT_DOMAIN),
				'plays'  =>__('Plays', MS_TEXT_DOMAIN),
				'purchases' => __('Purchases', MS_TEXT_DOMAIN),
				'date'	 => __('Date', MS_TEXT_DOMAIN)
		   );
		} // End columns
		
		/*
		* Extrat the songs data for song list
		*/
		public static function columns_data($column){
			global $post;
			$obj = new MSSong($post->ID);
			
			switch ($column){
				case "artist":
					echo extract_attr_as_str($obj->artist, 'name', ', ');		
				break;
				case "album":
					echo extract_attr_as_str($obj->album, 'name', ', ');		
				break;
				case "genre":
					echo extract_attr_as_str($obj->genre, 'name', ', ');
				break;
				case "plays":
					echo $obj->plays;
				break;
				case "purchases":
					echo $obj->purchases;
				break;
			} // End switch
		} // End columns_data 
		
	}// End MSSong class
} // Class exists check

?>