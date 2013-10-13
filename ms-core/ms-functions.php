<?php
// Check if the PHP memory is sufficient
function music_store_check_memory( $files = array() ){
    $required = 0;
    
    $m = ini_get( 'memory_limit' );
    $m = trim($m);
    $l = strtolower($m[strlen($m)-1]); // last
    switch($l) {
        // The 'G' modifier is available since PHP 5.1.0
        case 'g':
            $m *= 1024;
        case 'm':
            $m *= 1024;
        case 'k':
            $m *= 1024;
    }

    foreach ( $files as $file ){
        $memory_available = $m - memory_get_usage(true);
        $response = wp_remote_head( $file );
        if( !is_wp_error( $response ) && $response['response']['code'] == 200 ){
            $required += $response['headers']['content-length'];
            if( $required >= $memory_available - 100 ) return false;
        }else return false;
    }
    return true;
} // music_store_check_memory

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