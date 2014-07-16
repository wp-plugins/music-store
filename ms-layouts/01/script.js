jQuery( 
	function( $ )
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
							e.css( 'min-width', $( '.music-store-header' ).outerWidth() );
						}
					}
				);
			};
			
		correct_header();	
		$( window ).load( correct_header );
		
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
		
		// Correct the item heights
		var height_arr = [],
			max_height = 0,
			correct_heights = function()
			{
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
		
		$( window ).load( function(){ correct_heights(); } );	
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
		
		// Indicate the active tab
		
		$( '.music-store-tabs' ).children( 'li' ).click(
			function()
			{
				var e = $( this ),
					p = e.position(),
					w = e.width()/2;
				
				if( $( '.music-store-corner' ).length == 0 )
				$( '.music-store-tabs-container' ).prepend( $( '<div class="music-store-corner"></div>' ) );
				$( '.music-store-corner' ).css( 'margin-left', ( p.left + w ) + 'px' );
			}
		);
		$( 'li.active-tab' ).click();
	} 	
);