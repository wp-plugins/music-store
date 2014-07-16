jQuery( function( $ )
	{
		// Correct the header and items width
		var correct_header = function()
			{
				$( '.music-store-items,.music-store-pagination' ).each(
					function()
					{

						var e = $( this );
						if( e.parents( '.widget' ).length == 0 )
						{
							e.css( 'width', $( '.music-store-header' ).outerWidth( ) );
						}
					}
				);
			};
		
		correct_header();
		
		// Replace the popularity texts with the stars 
		var popularity_top = 0;
		$( '.collection-popularity,.song-popularity' ).each(
			function()
			{
				var e = $( this ),
					p = parseInt( e.find( 'span' ).remove().end().text().replace( /\s/g, '' ) );
					
				e.text( '' ).attr( 'popularity', p );
				popularity_top = Math.max( popularity_top, p );
			}
		);
		
		$( '.collection-popularity,.song-popularity' ).each(
			function()
			{
			
				var e = $( this ),
					p = e.attr( 'popularity' ),
					str = '',
					active = 0;

				if( popularity_top > 0 )
				{
					active = Math.ceil( p / popularity_top * 100 / 20 );
				}
				
				for( var i = 0; i < active; i++ )
				{
					str += '<div class="star-active"></div>';
				}
				
				for( var i = 0, h = 5 - active; i < h; i++ )
				{
					str += '<div class="star-inactive"></div>';
				}
				e.html( str );
			}
		);
		
		// Correct the images heights
		var min_height = Number.MAX_VALUE
			correct_heights = function()
			{
			$( '.music-store-items .song-cover img, .music-store-items .colllection-cover img' ).each(
				function()
				{
					var e = $( this );
					min_height = Math.min( e.height(), min_height );
				}
			);

			if( min_height != Number.MAX_VALUE )
			{
				$( '.music-store-items .song-cover, .music-store-items .collection-cover' ).css( { 'height': min_height+'px', 'overflow': 'hidden' } );
			}	
			
			$( '.song-cover, .collection-cover' ).append( $( '<div class="ms-inner-shadow"></div>' ) );
			
			// Correct the item heights
			var	height_arr = [],
				max_height = 0;
			$( '.music-store-items' ).children( 'div' ).each(
				function()
				{
					var e = $( this );
					if( e.hasClass( 'music-store-item' ) )
					{
						max_height = Math.max( e.height(), max_height );
					}
					else
					{
						height_arr.push( max_height );
						max_height = 0;
					}
				}
			);
			
			if( height_arr.length )
			{
				$( '.music-store-items' ).children( 'div' ).each(
					function()
					{
						var e = $( this );
						if( e.hasClass( 'music-store-item' ) )
						{
							e.height( height_arr[ 0 ] );
						}
						else
						{
							height_arr.splice( 0, 1 );
						}
					}
				);
			}	
		};
		
		$( window ).load( function(){ correct_header(); correct_heights(); } );
		
		// Modify the price box
		$( '.song-price' ).each(
			function()
			{
				var e = $( this );
				e.closest( 'div' ).addClass( 'price-box' ).find( 'span:not(.song-price),span.invalid' ).remove();
			}
		);
		
		$( '.collection-price' ).each(
			function()
			{
				var e = $( this );
				e.closest( 'div' ).addClass( 'price-box' ).find( 'span:not(.collection-price),span.invalid' ).remove();
			}
		);
		
		// Modify the single pages structure
		$( '.music-store-song .left-column' ).append( $('<div></div>').html( $( '.music-store-song .right-column' ).html() ) );
		$( '.music-store-song .right-column' ).html( '' ).append( $( '.music-store-song .bottom-content' ) );
		$( '.music-store-collection .left-column' ).append( $('<div></div>').html( $( '.music-store-collection .right-column' ).html() ) );
		$( '.music-store-collection .right-column' ).html( '' ).append( $( '.music-store-collection .bottom-content' ) );
		
		// Modify the shopping cart design
		$( '.ms-shopping-cart-list,.ms-shopping-cart-resume' ).wrap( '<div class="ms-shopping-cart-wrapper"></div>' );
	} 	
);