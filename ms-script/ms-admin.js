﻿jQuery(function(){
	(function($){
		var reports = []; //Array of reports used to hide or display items from reports
		
		// Sales Reports
		window[ 'ms_reload_report' ] = function( e ){
			var e  			  = $(e),
				report_id 	  = e.attr( 'report' ),
				report  	  = reports[ report_id ],
				datasets 	  = [],
				container_id  = '#'+e.attr( 'container' ),
				type 		  = e.attr( 'chart_type' ),
				checked_items = $( 'input[report="'+report_id+'"]:CHECKED' ),
				dataObj;
			
			checked_items.each( function(){ 
				var i = $(this).attr( 'item' );
				if( type == 'Pie' ) datasets.push( report[ i ] );
				else datasets.push( report.datasets[ i ] );
			} );
			
			if ( type == 'Pie' ) dataObj = datasets;
			else dataObj = { 'labels' : report.labels, 'datasets' : datasets };
			
			new Chart( $( container_id ).find( 'canvas' ).get(0).getContext( '2d' ) )[ type ]( dataObj, { scaleStartValue: 0 } );
		};
		
		window[ 'ms_load_report' ] = function( el, id, title, data, type, label, value ){
			function get_random_color() {
				var letters = '0123456789ABCDEF'.split('');
				var color = '#';
				for (var i = 0; i < 6; i++ ) {
					color += letters[Math.round(Math.random() * 15)];
				}
				return color;
			};
			
			if(el.checked){
				var container = $( '#'+id );
				
				if( container.html().length){
					container.show();
				}else{
					if( typeof ms_global != 'undefined' ){
						var from  = $( '[name="from_year"]' ).val()+'-'+$( '[name="from_month"]' ).val()+'-'+$( '[name="from_day"]' ).val(),
							to    = $( '[name="to_year"]' ).val()+'-'+$( '[name="to_month"]' ).val()+'-'+$( '[name="to_day"]' ).val();
						
						jQuery.getJSON( ms_global.aurl, { 'ms-action' : 'paypal-data', 'data' : data, 'from' : from, 'to' : to }, (function( id, title, type, label, value ){
								return function( data ){
											var datasets = [],
												dataObj,
												legend = '',
												color,
												tmp,
												index = reports.length;
											
											
											for( var i in data ){
												var v = Math.round( data[ i ][ value ] );
												if( typeof tmp == 'undefined' || tmp == null || data[ i ][ label ] != tmp ){
													color 	= get_random_color();
													tmp 	= data[ i ][ label ];
													legend 	+= '<div style="float:left;padding-right:5px;"><input type="checkbox" CHECKED chart_type="'+type+'" container="'+id+'" report="'+index+'" item="'+i+'" onclick="ms_reload_report( this );" /></div><div class="ms-legend-color" style="background:'+color+'"></div><div class="ms-legend-text">'+tmp+'</div><br />';
													if( type == 'Pie' ) datasets.push( { 'value' : v, 'color' : color } );
													else datasets.push( { 'fillColor' : color, 'strokeColor' : color, data:[ v ] } );
													
												}else{
													datasets[ datasets.length - 1][ 'data' ].push( v );
												}
											}
											
											var e = $( '#'+id );
											e.html('<div class="ms-chart-title">'+title+'</div><div class="ms-chart-legend"></div><div style="float:left;"><canvas width="400" height="400" ></canvas></div><div style="clear:both;"></div>');
											
											// Create legend
											e.find( '.ms-chart-legend').html( legend );
											
											if( type == 'Pie' ) dataObj = datasets;
											else dataObj = { 'labels' : [ 'Currencies' ], 'datasets' : datasets };
											
											reports[index] = dataObj;
											var chartObj = new Chart( e.find( 'canvas' ).get(0).getContext( '2d' ) )[ type ]( dataObj );
											e.show();
										} 
							})( id, title, type, label, value )
						);
					}
				}	
			}else{
				$( '#'+id ).hide();
			}	
		};
	
		// Methods definition
        window[ 'ms_display_more_info' ] = function( e ){
            e = $( e );
            e.parent().hide().next( '.ms_more_info' ).show();
        };
        
        window[ 'ms_hide_more_info' ] = function( e ){
            e = $( e );
            e.parent().hide().prev( '.ms_more_info_hndl' ).show();
        };
        
		window['ms_remove'] = function(e){
			$(e).parents('.ms-property-container').remove();
		};
		
		window['ms_select_element'] = function(e, add_to, new_element_name){
			var v = e.options[e.selectedIndex].value,
				t = e.options[e.selectedIndex].text;
			if(v != 'none'){
				$('#'+add_to).append(
					'<div class="ms-property-container"><input type="hidden" name="'+new_element_name+'[]" value="'+v+'" /><input type="button" onclick="ms_remove(this);" class="button" value="'+t+' [x]"></div>'
				);
			}	
		};
		
		window['ms_add_element'] = function(input_id, add_to, new_element_name){
			var n = $('#'+input_id),
				v = n.val();
			n.val('');	
			if( !/^\s*$/.test(v)){
				$('#'+add_to).append(
					'<div class="ms-property-container"><input type="hidden" name="'+new_element_name+'[]" value="'+v+'" /><input type="button" onclick="ms_remove(this);" class="button" value="'+v+' [x]"></div>'
				);
			}	
		};
		
		window["send_to_download_url"] = function(html) {

			file_url = jQuery(html).attr('href');
			if (file_url) {
				jQuery(file_path_field).val(file_url);
			}
			tb_remove();
			window.send_to_editor = window.send_to_editor_default;

		}
		
		window ['open_insertion_music_store_window'] = function(){
			var tags = music_store.tags,
				cont = $(tags.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"'));
			
			cont.dialog({
				dialogClass: 'wp-dialog',
				modal: true,
				closeOnEscape: true,
                close: function(){
                    $(this).remove();
                },
				buttons: [
					{text: 'OK', click: function() {
						var a   = $('#artist'),
							b   = $('#album'),
							c 	= $('#columns'),
							g   = $('#genre'),
							l   = $('#load'),
							sc  = '[music_store';

						var v = c.val();
						if(/\d+/.test(v) && v > 1) sc += ' columns='+v; 
						if(l[0].selectedIndex) sc += ' load="'+l[0].options[l[0].selectedIndex].value+'"';
						if(g[0].selectedIndex) sc += ' genre='+g[0].options[g[0].selectedIndex].value;
						if(a[0].selectedIndex) sc += ' artist='+a[0].options[a[0].selectedIndex].value;
						if(b[0].selectedIndex) sc += ' album='+b[0].options[b[0].selectedIndex].value;
						sc += ']';
						if(send_to_editor) send_to_editor(sc);
						$(this).dialog("close"); 
					}}
				]
			});
		};
		
		window['delete_purchase'] = function(id){
			if(confirm('Are you sure to delete the purchase record?')){
				var f = $('#purchase_form');
				f.append('<input type="hidden" name="purchase_id" value="'+id+'" />');
				f[0].submit();
			}	
		};
		
		// Main application
		var file_path_field;
		window["send_to_editor_default"] = window.send_to_editor;
		
        jQuery('.product-data').bind('click', function(evt){
            if($(evt.target).hasClass('button_for_upload')){
                file_path_field = $(evt.target).parent().find('.file_path');

                formfield = jQuery(file_path_field).attr('name');

                window.send_to_editor = window.send_to_download_url;

                //tb_show('', 'media-upload.php?post_id=' + music_store.post_id + '&amp;TB_iframe=true');
                tb_show('', 'media-upload.php?post_id=0&amp;TB_iframe=true');
                return false;
            }    
        });
	})(jQuery)
})