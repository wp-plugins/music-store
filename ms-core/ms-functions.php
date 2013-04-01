<?php

function music_store_extract_attr_as_str($arr, $attr, $separator){
	$result = '';
	$c = count($arr);
	if($c){
		$t = (array)$arr[0];
		$result .= $t[$attr];
		for($i=1; $i < $c; $i++){
			$t = (array)$arr[$i];
			$result .= $separator.$t[$attr];
		}	
	}
	
	return $result;
} // End music_store_extract_attr_as_str

function music_store_get_img_id($url){
	global $wpdb;
	$attachment = $wpdb->get_col($wpdb->prepare("SELECT ID FROM " . $wpdb->prefix . "posts" . " WHERE guid='%s';", $url )); 
    return $attachment[0];
} // End music_store_get_img_id

?>