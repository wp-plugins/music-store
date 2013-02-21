<?php
	/* Short and sweet */
	require('../../../../wp-blog-header.php');	
	
	function ms_copy_download_links($file){
		$ext  = pathinfo($file, PATHINFO_EXTENSION);
		$new_file_name = md5($file).'.'.$ext;
		$file_path = MS_DOWNLOAD.'/'.$new_file_name;
		$rand = rand(1000, 1000000);
		if(file_exists($file_path))
			return MS_URL.'/ms-downloads/'.$new_file_name.'?param='.$rand;
		
		if(file_put_contents($file_path, file_get_contents($file))){
			return MS_URL.'/ms-downloads/'.$new_file_name.'?param='.$rand;
		}
		return false;
	}
	
	function ms_remove_download_links(){
		$now = time();
		$dif = get_option('ms_old_download_link', MS_OLD_DOWNLOAD_LINK)*86400;
		$d = dir(MS_DOWNLOAD);
		while (false !== ($entry = $d->read())) {
			if($entry != '.' && $entry != '..' && $entry != '.htaccess'){
				$file_name = MS_DOWNLOAD.'/'.$entry;
				$date = filemtime($file_name);
				if($now-$date >= $dif){ // Delete file
					unlink($file_name);
				}
			}
		}
		$d->close();
	}
	
	function ms_song_title($song_obj){
		if(isset($song_obj->post_title)) return $song_obj->post_title;
		return pathinfo($song_obj->file, PATHINFO_FILENAME);
	}
	
	function ms_generate_downloads(){
		global $wpdb, $download_links_str;
		
		ms_remove_download_links();
		
		$purchase = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wpdb->prefix.MSDB_PURCHASE." WHERE purchase_id=%s", $_GET['purchase_id']));	
		
		if($purchase){ // Exists the purchase
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
			
			$download_links_str = '';
			foreach($urls as $url){
				$download_link = ms_copy_download_links($url->link);
				if($download_link){
					$download_links_str .= '<div> <a href="'.$download_link.'">'.$url->title.'</a></div>';
				}
			}
			
			if(empty($download_links_str)){
				$download_links_str = __('The list of purchased products is empty', MS_TEXT_DOMAIN);
			}
			
			load_template(dirname(__FILE__).'/../ms-templates/ms-donwload-page-template.php');
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
	
	if(isset($_GET['purchase_id'])) ms_generate_downloads();
?>