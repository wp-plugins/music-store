<?php
if( !defined( 'MS_H_URL' ) ) { echo 'Direct access not allowed.';  exit; }	
// Errors management
$ms_errors = array();
function music_store_setError($error_text){
    global $ms_errors;
    $ms_errors[] = __($error_text, MS_TEXT_DOMAIN);
}	

// Check if URL is for a local file, and return the relative URL or false
function music_store_is_local( $file ){
	$url_parts = parse_url( MS_H_URL );
    if( strpos( $file, MS_H_URL ) !== false )
	{
		$site_url = MS_H_URL;
	}
	elseif( 
		!empty( $url_parts ) && 
		strpos( $file, $url_parts[ 'scheme' ].'://'.$url_parts[ 'host' ] ) !== false 
	)
	{
		$site_url = $url_parts[ 'scheme' ].'://'.$url_parts[ 'host' ].'/';
	}
	else
	{
		return false;
	}	

	$parts = explode( '/', str_replace('\\', '/', str_replace( $site_url, '', MS_URL.'/ms-core' ) ) );
	$file = str_replace( $site_url, '', $file );
	$path = '';
	for( $i = 0; $i < count( $parts ); $i++ ){
		$path .= '../';
	}
	$file = urldecode( dirname( __FILE__ ).'/'.$path.$file );		

	return file_exists( $file ) ? $file : false;
}

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
		if( ( $relative_path = music_store_is_local( $file ) ) !== false ){
			$required += filesize( $relative_path );
			if( $required >= $memory_available - 100 ) return false;
		}else{
			$response = wp_remote_head( $file );
			if( !is_wp_error( $response ) && $response['response']['code'] == 200 ){
				$required += $response['headers']['content-length'];
				if( $required >= $memory_available - 100 ) return false;
			}else return false;
		}	
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

function music_store_get_type( $file )
{
	$type = 'mpeg';
	$ext  = strtolower(pathinfo($file, PATHINFO_EXTENSION));
	switch( $ext ){
		case 'ogg':
		case 'oga':
			$type = 'ogg';
		break;
		case 'wav':
			$type = 'wav';
		break;	
		case 'wma':
			$type = 'wma';
		break;
		case 'aac':
			$type = 'mp4';
	}
	return $type;
} // music_store_get_type

function music_store_get_img_id($url){
	global $wpdb;
	$attachment = $wpdb->get_col($wpdb->prepare("SELECT ID FROM " . $wpdb->prefix . "posts" . " WHERE guid='%s';", $url )); 
    return $attachment[0];
} // End music_store_get_img_id

// From PayPal Data RAW
	/*
	  $fieldsArr, array( 'fields name' => 'alias', ... )
	  $selectAdd, used if is required complete the results like: COUNT(*) as count
	  $groupBy, array( 'alias', ... ) the alias used in the $fieldsArr parameter
	  $orderBy, array( 'alias' => 'direction', ... ) the alias used in the $fieldsArr parameter, direction = ASC or DESC
	*/
	function music_store_getFromPayPalData( $fieldsArr, $selectAdd = '', $from = '', $where = '', $groupBy = array(), $orderBy = array(), $returnAs = 'json' ){
		global $wpdb;
		
		$_select = 'SELECT ';
		$_from = 'FROM '.$wpdb->prefix.MSDB_PURCHASE.( ( !empty( $from ) ) ? ','.$from : '' );
		$_where = 'WHERE '.( ( !empty( $where ) ) ? $where : 1 );
		$_groupBy = ( !empty( $groupBy ) ) ? 'GROUP BY ' : '';
		$_orderBy = ( !empty( $orderBy ) ) ? 'ORDER BY ' : '';
		
		$separator = '';
		foreach( $fieldsArr as $key => $value ){
			$length = strlen( $key )+1;
			$_select .= $separator.' 
							SUBSTRING(paypal_data, 
							LOCATE("'.$key.'", paypal_data)+'.$length.', 
							LOCATE("\r\n", paypal_data, LOCATE("'.$key.'", paypal_data))-(LOCATE("'.$key.'", paypal_data)+'.$length.')) AS '.$value; 
			$separator = ',';
		}
		
		if( !empty( $selectAdd ) ){
			$_select .= $separator.$selectAdd; 
		}
		
		$separator = '';
		foreach( $groupBy as $value ){
			$_groupBy .= $separator.$value;
			$separator = ',';
		}
		
		$separator = '';
		foreach( $orderBy as $key => $value ){
			$_orderBy .= $separator.$key.' '.$value;
			$separator = ',';
		}
		
		$query = $_select.' '.$_from.' '.$_where.' '.$_groupBy.' '.$_orderBy;

		$result = $wpdb->get_results( $query );
		
		if( !empty( $result ) ){
			switch( $returnAs ){
				case 'json':
					return json_encode( $result );
				break;
				default:
					return $result;
				break;
			}
		}
	} // End music_store_getFromPayPalData
?>