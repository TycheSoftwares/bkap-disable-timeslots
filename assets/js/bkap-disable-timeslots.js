jQuery( document ).ready( function ( $ ) {

	jQuery( ".bkap_disable_timeslots_table" ).on( "click", "[id^=bkap_close_disable_timeslots_row]",  function() {

		let tr 			= $(this).closest('tr').attr('class');
      	var split_data 	= tr.split( '_' );
      	var row_number 	= split_data[ 4 ];
      	let trthis 		= '.bkap_disable_timeslots_row_'  + row_number;
        var selector 	= jQuery( trthis ).find( 'select[id^="bkap_disable_timeslots_day_date"]' ).val();

        selectordata = '';
        if ( selector == "day" ){
        	var selectordata = jQuery( trthis ).find( 'select[id^="bkap_disable_timeslots_weekday_"]' ).val();
        	if ( selectordata != '' && selectordata != null ) {
        		selectordata = selectordata.join(",");
        	}
        } else {
        	var selectordata = jQuery( trthis ).find( 'textarea[id^="bkap_disable_timeslots_date_field_"]' ).val();
        }

        if ( selectordata != '' ) {
        	var bkap_from_time  = jQuery( trthis ).find( 'input[id^="bkap_disable_timeslots_from_time"]' ).val();
	        var bkap_to_time  	= jQuery( trthis ).find( 'input[id^="bkap_disable_timeslots_to_time"]' ).val();

	        if ( selector == "" || selector == null || bkap_from_time == "" || bkap_from_time == null ) {
	          datetimecheck = false;
	        }

	      	var data = {
	        	product_id: bkap_disable_timeslots_params.bkap_product_id,
	        	day_date: selector,
	        	which_day_date: selectordata,
	        	bkap_from_time,
	        	bkap_to_time,
	      		action: 'bkap_delete_disabled_timeslot'
	      	};
	      
	      	jQuery.post( bkap_disable_timeslots_params.ajax_url, data, function(response) {  
	      		console.log(response);
	            jQuery( trthis ).remove();
	        });
        } else {
        	jQuery( trthis ).remove();
        }
    });

	/**
	 * Event when add new row is clicked
	 *
	 * @fires event:click
	 * @since 4.6.0
	 */
	jQuery( '.bkap_add_row_disable_timeslot' ).click(function( e ){

		var each_row    = new Array();
	    var i         = 0;
	    var last_class_name = "";
	    
	    // Calculating new ID to assign new tr.
	    jQuery( "tr[class^='bkap_disable_timeslots_row']" ).each( function(){
	      
	      	var class_name_row 	= jQuery(this)[0].className;
	      	last_class_name 		= class_name_row;
	      	var res 				= class_name_row.replace( "bkap_disable_timeslots_row_", "" );
	      
	      	if ( res == class_name_row && each_row.length == 0 ) {
	        	each_row[i] = 1;
	      	} else {
	        	each_row[i] = parseInt( res );
	      	}
	      	i++;
	    });
	    
	    if ( each_row.length == 0 ) {
	      	new_id = 0;
	    } else {
	    	var max = Math.max.apply(Math,each_row);
		    var new_id = max + 1;
	    }

		var newRow 	= $( this ).data( 'row' );
		newRow 		= newRow.replace( 'bkap_disable_timeslots_row', 'bkap_disable_timeslots_row_' + new_id );
		newRow      = newRow.replace( 'bkap_disable_timeslots_date_field', 'bkap_disable_timeslots_date_field_' + new_id );
		newRow      = newRow.replace( 'bkap_disable_timeslots_weekday', 'bkap_disable_timeslots_weekday_' + new_id );

		$(this).closest('table').find('tbody').append( newRow );
		var formats = ["d.m.y", "d-m-yyyy","MM d, yy"];
		jQuery( '#bkap_disable_timeslots_weekday_' + new_id ).select2( { width: '200px', placeholder: 'Select days..' } );		
		jQuery( '#bkap_disable_timeslots_date_field_' + new_id ).datepick({
	        minDate: 		new Date(), 
	        dateFormat: 	formats[1], 
	        multiSelect: 	999, 
	        monthsToShow: 	1, 
	        showTrigger: 	'#calImg'
	    });

		/**
		 * Indicates that the row is added
		 * 
		 * @event bkap_row_added
		 * @since 4.6.0
		 */
		$('body').trigger('bkap_disable_timeslots_row_added');
		return false;
	});

	jQuery( ".bkap_disable_timeslots_day_class" ).select2({ width:'100% !important', placeholder: "Select products.."});
	jQuery( 'textarea[id^="bkap_disable_timeslots_date_field_"]' ).datepick({
	        minDate: 		new Date(), 
	        dateFormat: 	"d-m-yyyy", 
	        multiSelect: 	999, 
	        monthsToShow: 	1, 
	        showTrigger: 	'#calImg'
	    });

	/**
	 * Event for showing the saved resource details
	 *
	 * @fires event:click
	 * @since 4.6.0
	 */
	$( '#bkap_disable_timeslots_rows' ).on( 'change', '#bkap_disable_timeslots_day_date', function() {
		var value = $(this).val();
		var row   = $(this).closest('tr');

		$(row).find( '.bkap_disable_timeslots_date, .bkap_disable_timeslots_day_div' ).hide();

		if ( value == 'day' ) {
			$(row).find('.bkap_disable_timeslots_day_div').show();
		}
		if ( value == 'date' ) {
			$(row).find('.bkap_disable_timeslots_date').show();
		}
	});
	jQuery( '.bkap_preset_link' ).click( function() {

	    


	    return false;
	 });
});

function bkapdt_settings_data( settings_data ){

	var booking_disable_times       = {}; 
	var j = 0;
      var datetimecheck = true;
      jQuery( '#bkap_disable_timeslots_rows tr[class^="bkap_disable_timeslots_row_"]' ).each( function (i, row) {

        var selector = jQuery( this ).find( 'select[id^="bkap_disable_timeslots_day_date"]' ).val();


        if ( selector == "day" ){
        	var selectordata = jQuery( this ).find( 'select[id^="bkap_disable_timeslots_weekday_"]' ).val();
        } else {
        	var selectordata = jQuery( this ).find( 'textarea[id^="bkap_disable_timeslots_date_field_"]' ).val();
        }
        var bkap_from_time  = jQuery( this ).find( 'input[id^="bkap_disable_timeslots_from_time"]' ).val();
        var bkap_to_time  	= jQuery( this ).find( 'input[id^="bkap_disable_timeslots_to_time"]' ).val();

        if ( selector == "" || selector == null || bkap_from_time == "" || bkap_from_time == null ) {
          datetimecheck = false;
        }

        if ( selector == "day" ){
        	for( sd in selectordata ){
        		booking_disable_times[j] = {};
		        booking_disable_times[j][ 'day_date' ]      = selector;
		        booking_disable_times[j][ 'which_day_date'] = selectordata[sd];
		        booking_disable_times[j][ 'from_time' ]     = bkap_from_time;
		        booking_disable_times[j][ 'to_time' ]       = bkap_to_time;
		        j++;		
        	}
        } else {
        	dates = selectordata.split(',');
        	for ( sd in dates ) {
        		booking_disable_times[j] = {};
		        booking_disable_times[j][ 'day_date' ]      = selector;
		        booking_disable_times[j][ 'which_day_date'] = dates[sd];
		        booking_disable_times[j][ 'from_time' ]     = bkap_from_time;
		        booking_disable_times[j][ 'to_time' ]       = bkap_to_time;
		        j++;	
        	}
        }
      });

      if ( !datetimecheck ) {
        return settings_data = { error : "datetime" };
      }
      /**
       * Count is greater than 4 because when we have empty object its minimum length will be 2. So more than that will make sure that we have a data.
       * @since: 4.5.0
       */
      /*if ( Object.keys( bkap_time_slots ).length > 0  ) {
          booking_times = bkap_time_slots;
      }*/

      settings_data[ 'booking_disable_times' ] = booking_disable_times;

  return settings_data;
}