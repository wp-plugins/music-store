jQuery(function($){
	var min_screen_width = 640;
	
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
	
	// Screen width and adjust columns
	function getWidth()
	{
		var myWidth = 0;
		if( typeof( window.innerWidth ) == 'number' ) {
			//Non-IE
			myWidth = window.innerWidth;
		} else if( document.documentElement && document.documentElement.clientWidth ) {
			//IE 6+ in 'standards compliant mode'
			myWidth = document.documentElement.clientWidth;
		} else if( document.body && document.body.clientWidth ) {
			//IE 4 compatible
			myWidth = document.body.clientWidth;
		}
		
		 if( typeof window.devicePixelRatio != 'undefined' && window.devicePixelRatio ) myWidth = myWidth/window.devicePixelRatio;    
		return ( typeof screen != 'undefined' ) ? Math.min( screen.width, myWidth ) : myWidth;
	};
	
	$( window ).bind( 'orientationchange resize', function(){
		setTimeout( 
			(function( minWidth, getWidth )
			{
				return function()
				{ 
					$( '.music-store-item' ).each( function(){
						var e = $( this ),
							c = e.find( '.collection-cover,.song-cover' );
						
						if( getWidth < minWidth )
						{	
							if( c.length ) c.css( { 'height': 'auto' } );
							e.css( {'width': '100%', 'height': 'auto'} );
						}	
						else
						{	
							if( c.length ) c.css( { 'height': '' } );
							e.css( {'width': e.attr( 'data-width' ), 'height': '' } );
						}	
					} );
					if( getWidth >= minWidth && typeof ms_correct_heights != 'undefined' ) 
						ms_correct_heights();
				}
			})( min_screen_width, getWidth() ), 
			100 
		);
	} );
	$( window ).load( function(){ $( window ).trigger( 'resize' ); } );	
});