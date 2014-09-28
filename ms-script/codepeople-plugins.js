jQuery(function($){
	$('.ms-player.single audio').mediaelementplayer({
		features: ['playpause','current','progress','duration','volume'],
		videoVolume: 'horizontal',
		iPadUseNativeControls: false,
		iPhoneUseNativeControls: false
	});
	
	$('.ms-player.multiple audio').mediaelementplayer({
		features: ['playpause'],
		iPadUseNativeControls: false,
		iPhoneUseNativeControls: false
	});
    
    timeout_counter = 10;
    window['music_store_counting'] = function()
    {
        var loc = document.location.href;
        document.getElementById( "music_store_error_mssg" ).innerHTML = timeout_text+' '+timeout_counter;
        if( timeout_counter == 0 )
        {
            document.location = loc+( ( loc.indexOf( '?' ) == -1 ) ? '?' : '&' )+'timeout=1';    
        }
        else
        {
            timeout_counter--;
            setTimeout( music_store_counting, 1000 );
        }    
    };

    if( $( '[id="music_store_error_mssg"]' ).length ) 
    {
        music_store_counting();
    }    
});