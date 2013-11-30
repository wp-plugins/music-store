<?php
    error_reporting( E_ERROR | E_PARSE );
    global $htaccess_accepted;
    $htaccess_accepted = false;
    
	function ms_check_download_permissions(){
		global $wpdb;
		
		// If not session, create it
		if( session_id() == "" ) session_start();

		// Check if download for free or the user is an admin
		if(	!empty( $_SESSION[ 'download_for_free' ] ) || current_user_can( 'manage_options' ) ) return true;

		// and check the existence of a parameter with the purchase_id
		if( empty( $_REQUEST[ 'purchase_id' ] ) ){ 
			music_store_setError( 'The purchase id is required' );
			return false;
		}	

		if( get_option( 'ms_safe_download', MS_SAFE_DOWNLOAD ) ){
			// Check if the user has typed the email used to purchase the product 
			if( empty( $_SESSION[ 'ms_user_email' ] ) ){ 
				$dlurl = $GLOBALS['music_store']->_ms_create_pages( 'ms-download-page', 'Download Page' ); 
				$dlurl .= ( ( strpos( $dlurl, '?' ) === false ) ? '?' : '&' ).'ms-action=download&purchase_id='.$_REQUEST[ 'purchase_id' ];
				music_store_setError( "Please, go to the download page, and enter the email address used in product's purchasing <a href='{$dlurl}'>CLICK HERE</a>" );
				return false;
			}	
			$days = $wpdb->get_var( $wpdb->prepare( 'SELECT DATEDIFF(NOW(), date) FROM '.$wpdb->prefix.MSDB_PURCHASE.' WHERE purchase_id=%s AND email=%s', array( $_REQUEST[ 'purchase_id' ], $_SESSION[ 'ms_user_email' ] ) ) );
		}else{
			$days = $wpdb->get_var( $wpdb->prepare( 'SELECT DATEDIFF(NOW(), date) FROM '.$wpdb->prefix.MSDB_PURCHASE.' WHERE purchase_id=%s', array( $_REQUEST[ 'purchase_id' ] ) ) );
		}

		if( is_null( $days ) ){
			music_store_setError( 'There is no product associated with the entered data' );
			return false;
		}elseif( get_option('ms_old_download_link', MS_OLD_DOWNLOAD_LINK) < $days ){ 
			music_store_setError( 'The download link has expired, please contact to the vendor' );
			return false;	
		}

		return true;
	} // End ms_check_download_permissions
	
	function ms_copy_download_links($file){
		$ext  = pathinfo($file, PATHINFO_EXTENSION);
		$new_file_name = md5($file).'.'.$ext;
		$dest = MS_DOWNLOAD.'/'.$new_file_name;
		$rand = rand(1000, 1000000);
		if(file_exists($dest)) return $new_file_name;
        
        if( !music_store_check_memory( array( $file ) ) ) return $file;

		if( ( $path = music_store_is_local( $file ) ) !== false ){
			if( copy( $path, $dest) ) return $new_file_name;
		}else{	
			$response = wp_remote_get($file);
			if( !is_wp_error( $response ) && $response['response']['code'] == 200 && file_put_contents($dest, $response['body'])) return $new_file_name;
		}	
        return $file;
	}
	
	function ms_remove_download_links(){
        global $htaccess_accepted;
        
		$now = time();
		$dif = get_option('ms_old_download_link', MS_OLD_DOWNLOAD_LINK)*86400;
		$d = @dir(MS_DOWNLOAD);
		while (false !== ($entry = $d->read())) {
            // The music-store-icon.png file allow to know that htaccess file is supported, so it should not be deleted
			if($entry != '.' && $entry != '..' && $entry != 'music-store-icon.png'){
                if($entry == '.htaccess'){
                    if(!$htaccess_accepted){ // Remove the htaccess if it is not accepted
                        @unlink(MS_DOWNLOAD.'/'.$entry);
                    }
                }else{
                    $file_name = MS_DOWNLOAD.'/'.$entry;
                    $date = filemtime($file_name);
                    if($now-$date >= $dif){ // Delete file
                        @unlink($file_name);
                    }
                }    
			}
		}
		$d->close();
	} // End ms_remove_download_links
	
    function ms_song_title($song_obj){
		if(isset($song_obj->post_title)) return $song_obj->post_title;
		return pathinfo($song_obj->file, PATHINFO_FILENAME);
	}
	
	function ms_generate_downloads(){
		global $wpdb, $download_links_str;
		ms_remove_download_links();
		
		if( ms_check_download_permissions() ){
			$purchase = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wpdb->prefix.MSDB_PURCHASE." WHERE purchase_id=%s", $_GET['purchase_id']));	
			$download_links_str = '';
				
			if($purchase){ // Exists the purchase
					
				$id = $purchase->product_id;
				
				$_post = get_post($id);
				if(is_null($_post)){ 
					$download_links_str = __( 'The product is no longer available in our Music Store', MS_TEXT_DOMAIN );
					return;
				}	
				if( $_post->post_type == 'ms_song' ) $obj = new MSSong($id);
				else{
					$download_links_str = __( 'The product is not valid', MS_TEXT_DOMAIN );
					return;
				}
				
				$urls = array();
				$songObj = new stdClass();
				if(isset($obj->file)){ 
					$songObj->title = ms_song_title($obj);
					$songObj->link  = $obj->file;
					$urls[] = $songObj;
				}
				
				foreach($urls as $url){
					$download_link = ms_copy_download_links($url->link);
					if( $download_link !== $url->link ) $download_link = MS_H_URL.'?ms-action=f-download&f='.$download_link.( ( !empty( $_REQUEST[ 'purchase_id' ] ) ) ?  '&purchase_id='.$_REQUEST[ 'purchase_id' ] : '' );
					$download_links_str .= '<div> <a href="'.$download_link.'">'.$url->title.'</a></div>';
				}
				
				if(empty($download_links_str)){
					$download_links_str = __('The list of purchased products is empty', MS_TEXT_DOMAIN);
				}
			} // End purchase checking	
		}	
	}
	
	function ms_download_file(){
		global $wpdb, $ms_errors;
		
		if( isset( $_REQUEST[ 'f' ] ) && ms_check_download_permissions() ){
			if( isset( $_REQUEST[ 'purchase_id' ]) )
			header( 'Content-Disposition: attachment; filename="'.$_REQUEST[ 'f' ].'"' );
			readfile( MS_DOWNLOAD.'/'.$_REQUEST[ 'f' ] );
		}else{
			$dlurl = $GLOBALS['music_store']->_ms_create_pages( 'ms-download-page', 'Download Page' ); 
			$dlurl .= ( ( strpos( $dlurl, '?' ) === false ) ? '?' : '&' ).'ms-action=download&error_mssg='.urlencode( '<li>'.implode( '</li><li>', $ms_errors ).'</li>' ).( ( !empty( $_REQUEST[ 'purchase_id' ] ) ) ? '&purchase_id='.$_REQUEST[ 'purchase_id' ] : '' );
			header( 'location: '.$dlurl );
		}
	} // End ms_download_file
?>