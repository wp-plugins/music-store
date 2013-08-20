<?php
    error_reporting( E_ERROR || E_PARSE );
    if( !class_exists( 'WP_Http' ) ){
        include_once( ABSPATH . WPINC. '/class-http.php' );
    }    
    
    global $htaccess_accepted;
    $htaccess_accepted = false;
    
	function ms_copy_download_links($file){
		$ext  = pathinfo($file, PATHINFO_EXTENSION);
		$new_file_name = md5($file).'.'.$ext;
		$file_path = MS_DOWNLOAD.'/'.$new_file_name;
		$rand = rand(1000, 1000000);
		if(file_exists($file_path)) return MS_URL.'/ms-downloads/'.$new_file_name.'?param='.$rand;
		$request = new WP_Http;
        $response = $request->request($file);
		if($response['response']['code'] == 200 && file_put_contents($file_path, $response['body'])) return MS_URL.'/ms-downloads/'.$new_file_name.'?param='.$rand;
		return false;
	}
	
	function ms_remove_download_links(){
        global $htaccess_accepted;
        
		$now = time();
		$dif = get_option('ms_old_download_link', MS_OLD_DOWNLOAD_LINK)*86400;
		$d = @dir(MS_DOWNLOAD);
		while (false !== ($entry = $d->read())) {
            // The music-store-icon.gif file allow to know that htaccess file is supported, so it should not be deleted
			if($entry != '.' && $entry != '..' && $entry != 'music-store-icon.gif'){
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
		
        $interval = get_option('ms_old_download_link', MS_OLD_DOWNLOAD_LINK)*86400;
        $purchase = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wpdb->prefix.MSDB_PURCHASE." WHERE purchase_id=%s", $_GET['purchase_id']));	
		$download_links_str = '';
			
        if($purchase){ // Exists the purchase
			if(!current_user_can( 'manage_options' ) && abs(strtotime($purchase->date)-time()) > $interval){
                    $download_links_str = __('The download link has expired, please contact to the vendor', MS_TEXT_DOMAIN);
            }else{    
                
                $id = $purchase->product_id;
                
                $_post = get_post($id);
                if(is_null($_post)) return;
                switch ($_post->post_type){
                    case "ms_song":
                        $obj = new MSSong($id);
                    break;
                    case "ms_collection":
                        $obj = new MSCollection($id);
                    break;
                    default:
                        return;
                    break;
                }
                
                $urls = array();
                
                if($obj->post_type == 'ms_song'){
                    $songObj = new stdClass();
                    if(isset($obj->file)){ 
                        $songObj->title = ms_song_title($obj);
                        $songObj->link  = $obj->file;
                        $urls[] = $songObj;
                    }	
                }else{
                    foreach($obj->song as $song){
                        $songObj = new stdClass();
                        if(isset($song->file)){ 
                            $songObj->title = ms_song_title($song);
                            $songObj->link  = $song->file;
                            $urls[] = $songObj;
                        }	
                    }
                }
                
                foreach($urls as $url){
                    
                    $download_link = ms_copy_download_links($url->link);
                    if($download_link){
                        $download_links_str .= '<div> <a href="'.$download_link.'">'.$url->title.'</a></div>';
                    }
                }
            }
            
			if(empty($download_links_str)){
				$download_links_str = __('The list of purchased products is empty', MS_TEXT_DOMAIN);
			}
			
		} // End purchase checking	
	}
	
	function ms_clear_field(){
		return ' ';
	}
	
	function ms_download_links_content(){
		global $download_links_str;
		return $download_links_str;
	}
	
	function ms_download_links_title($title){
		if( in_the_loop() )
			return __('Download the purchased products', MS_TEXT_DOMAIN);
		else
			return $title;
	}
?>