<?php 
/**
 * Plugin Name: BKAP Disable Timeslots
 * Description: This plugin is used for disabling the timeslots of Weekdays/Dates.
 * Version: 1.0
 * Author: Tyche Softwares
 * Author URI: http://www.tychesoftwares.com/
 * Requires PHP: 5.6
 * WC requires at least: 3.0.0
 * WC tested up to: 3.3.4
 * Text Domain: bkap-disable-timeslots
 */

if( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'BKAP_Disable_Timeslots' ) ) :

/**
 * Booking & Appointment Plugin Specific Dates Dropdown Class
 * 
 * @class BKAP_Specific_Dates_Dropdown
 */	

class BKAP_Disable_Timeslots {

	/**
     * Default constructor
     *
     * @since 1.0
     */

	public function __construct() {

		$this->bkapdt_define_constants();

		add_action( 'admin_head', 										array( $this, 'bkapdt_my_custom_styles' ) );
		add_action( 'after_bkap_load_product_scripts_js', 				array( $this, 'after_bkap_load_product_scripts_js_callback' ), 10, 1 );
		add_action( 'bkap_before_duration_based_time_section', 			array( $this, 'bkap_after_duration_based_time_section_callback' ), 10, 2 );
		add_filter( 'bkap_additional_data_after_timeslots_calculator', 	array( $this, 'bkap_additional_data_after_timeslots_calculator_callback' ), 10, 3 );
		add_action( 'wp_ajax_bkap_delete_disabled_timeslot', 			array( $this, 'bkap_delete_disabled_timeslot' ) );

		add_filter( 'bkap_edit_display_timeslots', 						array( $this, 'bkapdt_edit_display_timeslots_callback' ), 20, 5 );
		add_filter( 'bkap_add_additional_data', 						array( $this, 'bkap_add_additional_data_callback' ), 10, 3 );
	}

	public static function bkap_add_additional_data_callback( $additional_data, $booking_settings, $product_id ) {

		global $wpdb;

		$bkap_disabled_timeslots 	= get_post_meta( $product_id, '_bkap_disabled_timeslots', true );
		$blockdate 					= '';
		$wapbk_lockout_days 		= $additional_data['wapbk_lockout_days'];

		if ( count( $bkap_disabled_timeslots ) > 0 ) {
			foreach ( $bkap_disabled_timeslots as $date => $date_timeslots ) {
				$dateymd = date( 'Y-m-d', strtotime( $date ) );

				$time_query 	= "SELECT * FROM `".$wpdb->prefix."booking_history`
									WHERE post_id = %d
									AND start_date = %s
									AND status = ''";
				$results_time   = $wpdb->get_results( $wpdb->prepare( $time_query, $product_id, $dateymd ) );

				$timeslots = array();

				if ( count( $results_time ) > 0 ) {

					$flag = true;
					foreach ( $results_time as $key => $value ) {
						$ft = $tt = $value->from_time;
						if ( '' != $value->to_time ){
							$tt = $value->to_time;
						}

						$timeslots[] = $ft . "-" . $tt;
					}

					$count_timeslots = count( $timeslots );
					$i = 0; 
					foreach ( $timeslots as $timeslotskey => $timeslotsvalue ) {

						$flag = false;

						$t_explode = explode( "-" , $timeslotsvalue );

						foreach ( $date_timeslots as $k => $v ) {
			    			$from_time = $v['from_slot_hrs'] .":".$v['from_slot_min'];
			    			$to_time   = $v['to_slot_hrs'] .":".$v['to_slot_min'];

			    			if ( strtotime( $t_explode[0] ) >= strtotime( $from_time ) && strtotime( $t_explode[1] ) <= strtotime( $to_time ) ) {
			    				$flag = true;
			    			}
			    		}

			    		if ( $flag ) {
			    			$i++;
			    			unset( $timeslots[ $timeslotskey ] );
			    		}
					}

					if ( $i == $count_timeslots ) {
						$blockdate .= '"'.$date . '",';
					}
				}
			}
		}

		if ( "" != $blockdate ) {
			 $blockdate 	= substr( $blockdate, 0, strlen( $blockdate ) - 1 );

			if ( "" != $wapbk_lockout_days ) {
				$wapbk_lockout_days .= "," . $blockdate;
			} else {
				$wapbk_lockout_days = $blockdate;
			}
		}

		$additional_data[ 'wapbk_lockout_days' ] = $wapbk_lockout_days;		
		return $additional_data;
	}

	public static function bkap_delete_disabled_timeslot() {

		if ( isset( $_POST['which_day_date'] ) && $_POST['which_day_date'] != '' ){

			$which_day_date = explode(",", $_POST['which_day_date'] );
			$product_id 	= $_POST['product_id'];
			$day_date 		= $_POST['day_date'];
			$bkap_from_time = $_POST['bkap_from_time'];
			$bkap_to_time 	= $_POST['bkap_to_time'];

			if ( $day_date == "day" ){
				if ( in_array( "all", $which_day_date ) ){
					$which_day_date = array();
					for( $i=0; $i<7; $i++ ){
						$which_day_date[] = 'booking_weekday_' . $i;
					}
				} else {
					foreach ($which_day_date as $key => $value) {
						$which_day_date[$key] = 'booking_weekday_' . $value;
					}
				}
			}

			foreach ( $which_day_date as $key => $value ) {
				self::bkapdt_delete_individual_disabled_time_settings( $product_id, $value, $bkap_from_time, $bkap_to_time ); 
			}
		}
		die();
	}

	function bkapdt_delete_individual_disabled_time_settings( $product_id, $day_value, $from_time, $to_time ) {
    
        $existing_settings 		= get_post_meta( $product_id, '_bkap_disabled_timeslots', true );        
        $updated_time_settings 	= self::bkapdt_unset_sdisabled_time_array( $existing_settings, $day_value, $from_time, $to_time );
        update_post_meta( $product_id, '_bkap_disabled_timeslots', $updated_time_settings );
    
    }

	function bkapdt_unset_sdisabled_time_array( $existing_settings, $day_value, $from_time, $to_time ) {
    
        //split the time into hrs and mins
        $from_time_array 	= explode( ':', $from_time );
        $from_hrs 			= $from_time_array[ 0 ];
        $from_mins 			= $from_time_array[ 1 ];
    
        $to_hrs 			= '00';
        $to_mins 			= '00';
        if ( isset( $to_time ) && '' != $to_time ) {
            $to_time_array 	= explode( ':', $to_time );
            $to_hrs 		= $to_time_array[ 0 ];
            $to_mins 		= $to_time_array[ 1 ];
        }
    
        if ( is_array( $existing_settings ) && count( $existing_settings ) > 0 ) {
    
            foreach( $existing_settings as $day => $day_settings ) {
    
                if ( $day == $day_value ) { // matching day/date
    
                    foreach( $day_settings as $time_key => $time_settings ) {
    
                        // Match the time
                        if ( trim( $from_hrs ) == $time_settings[ 'from_slot_hrs' ] && trim( $from_mins ) == $time_settings[ 'from_slot_min' ] && trim( $to_hrs ) == $time_settings[ 'to_slot_hrs' ] && trim( $to_mins ) == $time_settings[ 'to_slot_min' ] ) {
                            $unset_key = $time_key;
                            break;
                        }
                    }
                    // unset the array
                    if ( isset( $unset_key ) && is_numeric( $unset_key ) ) {
                        unset( $existing_settings[ $day ][ $unset_key ] );
                        break;
                    }
                }
            }
        }
    
        return $existing_settings;
    }

	public static function bkap_additional_data_after_timeslots_calculator_callback( $settings_data, $product_id, $clean_settings_data ){
			// date & time settings
            $booking_time_settings  = array();
            $existing_time_settings = get_post_meta( $product_id, '_bkap_disabled_timeslots', true );

            if ( isset( $clean_settings_data->booking_disable_times ) && count( get_object_vars( $clean_settings_data->booking_disable_times ) ) > 0 ) {

                foreach ( $clean_settings_data->booking_disable_times as $booking_times ) {
                    
                    $record_present = false; // assume no record is present for this date/day and time slot
                    $days 			= array();

                    switch ( $booking_times->day_date ) {
                    	case 'day':

                    		if ( $booking_times->which_day_date == "all" ){
                    			for ( $i = 0; $i < 7; $i++ ) { 
                    				$days[] = "booking_weekday_" . $i; 
                    			}
                    		} else {
                    			$days[] = "booking_weekday_". $booking_times->which_day_date;
                    		}
                    		break;
                    	case 'date':
                    		$days = explode( ",", $booking_times->which_day_date );
                    		break;
                    }
                    
                    // for all the days
                    foreach ( $days as $day_check ) {
            
                        $from_slot_array    = explode( ':', $booking_times->from_time );
            
                        $from_slot_hrs      = trim( $from_slot_array[ 0 ] );
                        $from_slot_min      = trim( $from_slot_array[ 1 ] );

                        $from_slot_hrs      = ( $from_slot_hrs != "" ) ? $from_slot_hrs : '00';
                        $from_slot_min      = ( $from_slot_min != "" ) ? $from_slot_min : '00';
            
                        $to_slot_hrs        = '00';
                        $to_slot_min        = '00';
            
                        if ( isset( $booking_times->to_time ) && '' != $booking_times->to_time ) {
                            $to_slot_array = explode( ':', $booking_times->to_time );
            
                            $to_slot_hrs = trim( $to_slot_array[ 0 ] );
                            $to_slot_min = trim( $to_slot_array[ 1 ] );
                        }
            
                        // check if a record exists already
                        if ( is_array( $existing_time_settings ) && count( $existing_time_settings ) > 0 ) {
                            
                            // check if there's a record present for that day/date
                            if ( isset( $existing_time_settings[ $day_check ] ) ) {
            
                                foreach( $existing_time_settings[ $day_check ] as $key => $existing_record ) {
            
                                    if ( $from_slot_hrs == $existing_record['from_slot_hrs']
                                        && $from_slot_min == $existing_record['from_slot_min']
                                        && $to_slot_hrs == $existing_record['to_slot_hrs']
                                        && $to_slot_min == $existing_record['to_slot_min'] ) {
                                            
                                        $new_key        =  $key;
                                        $record_present = true;
                                        break;
                                    }
                                }
                            }
                        }

                        if ( ! $record_present ) {
                            // check if there's a record present for that day/date
                            if ( isset( $booking_time_settings[ $day_check ] ) &&  ! empty( $booking_time_settings[ $day_check ] )  ) {
                                $new_key = max( array_keys( $booking_time_settings[$day_check] ) ) + 1;
                            } else {
                                $new_key = 0;
                            }
                        }
            
                        $booking_time_settings[ $day_check ][ $new_key ][ 'from_slot_hrs' ]     = $from_slot_hrs;
                        $booking_time_settings[ $day_check ][ $new_key ][ 'from_slot_min' ]     =  $from_slot_min;
                        $booking_time_settings[ $day_check ][ $new_key ][ 'to_slot_hrs' ]       = $to_slot_hrs;
                        $booking_time_settings[ $day_check ][ $new_key ][ 'to_slot_min' ]       =  $to_slot_min;
                    }
                }
            
                if ( is_array( $booking_time_settings ) ) {
                    $settings_data[ '_bkap_disabled_timeslots' ] = $booking_time_settings;
                }
            }

            return $settings_data;
	}

	/**
	 * Add CSS here and later we will add it in saparate file.
	 */

	public static function bkapdt_my_custom_styles(){

		?>
		<style type="text/css">

			.bkap_disable_timeslots_table{
				width: 104%;
			    table-layout: fixed;
			    margin: 2% -2%;
			    border: 1px solid #eee;
			    border-collapse: collapse;
			}
			table.bkap_disable_timeslots_table tr td, table.bkap_disable_timeslots_table tr th {
			    padding: 5px;
			    border: 1px solid #eee;
			}
			#bkap_disable_timeslots_day_date{
				width:100%;
			}
			.bkap_disable_timeslots_fromto_div{
				text-align: center;
			}

		</style>
		<?php
	}

	public static function after_bkap_load_product_scripts_js_callback( $product_id ){

		$ajax_url = get_admin_url() . 'admin-ajax.php';

		wp_register_script( 
				'bkap-disable-timeslots', 
				plugins_url().'/bkap-disable-timeslots/assets/js/bkap-disable-timeslots.js', 
				'', 
				'1.0', 
				true );

		wp_localize_script( 'bkap-disable-timeslots', 'bkap_disable_timeslots_params', array(
				'ajax_url'                 => $ajax_url,
				'bkap_product_id'          => $product_id
			) );

		wp_enqueue_script( 'bkap-disable-timeslots' );
	}


	public static function bkapdt_edit_display_timeslots_callback( $drop_down, $post_id, $booking_settings, $global_settings, $extra_information ) {

		$booking_disabled_times = get_post_meta( $post_id, '_bkap_disabled_timeslots', true );

		if ( is_array( $booking_disabled_times ) && count( $booking_disabled_times ) > 0 ) {

		  	$drop_down_array          	= explode( "|", $drop_down );

		  	if ( count( $drop_down_array ) > 0 ) {    
			    $ymd_date       		= $extra_information['ymddate'];
			    $new_drop_down  		= array();
			    $start_weekday 		    = date( 'w', strtotime( $ymd_date ) );
        		$start_booking_weekday  = 'booking_weekday_' . $start_weekday;
        		$datejny 				= date( 'j-n-Y', strtotime( $ymd_date ) );
        		$weekday_timeslots 		= $date_timeslots = array();

        		if ( isset( $booking_disabled_times[ $start_booking_weekday ] ) && !empty( $booking_disabled_times[ $start_booking_weekday ] )  ) {
        			$weekday_timeslots = $booking_disabled_times[ $start_booking_weekday ];
		    	}

		    	if ( isset( $booking_disabled_times[ $datejny ] ) && !empty( $booking_disabled_times[ $datejny ] )  ) {
        			$date_timeslots = $booking_disabled_times[ $datejny ];
		    	}

		    	$all_disabled_timeslots = array_merge( $weekday_timeslots, $date_timeslots );

		    	foreach ( $drop_down_array as $key => $value ) {

		    		if ( "" != $value ) {

		    			$match = true;
		    			$timeslot = explode( " - ", $value );

		    			foreach ( $all_disabled_timeslots as $k => $v ) {
			    			$from_time = $v['from_slot_hrs'] .":".$v['from_slot_min'];
			    			$to_time   = $v['to_slot_hrs'] .":".$v['to_slot_min'];

			    			$fromtime_compare = $timeslot[0];
			    			if ( isset( $timeslot[1] ) ) {
 								$totime_compare = $timeslot[1];
			    			} else {
			    				$totime_compare = $fromtime_compare;
			    			}

			    			if ( strtotime( $fromtime_compare ) >= strtotime( $from_time ) && strtotime( $totime_compare ) <= strtotime( $to_time ) ){
			    				$match = false;
			    				break;
			    			}
			    		}

				      	if ( $match )	{
				      		$new_drop_down[] = $value;	
				      	}
		    		}		    		
		    	}

		    	if ( count( $new_drop_down ) ) {
		    		$drop_down = implode( "|", $new_drop_down );	
		    	} else {
		    		$drop_down = 'ERROR | ' . __( "No timeslots are available. Please choose some other date.", 'woocommerce-booking' );
		    	}
		  	}
		}

	  	return $drop_down;
	}

	/**
	 * Defining Constants
	 */

	public static function bkapdt_define_constants(){
		if ( !defined( 'BKAPDT_PLUGIN_PATH' ) ) {
            define( 'BKAPDT_PLUGIN_PATH' , untrailingslashit( plugin_dir_path( __FILE__ ) ) );
        }

        if ( !defined( 'BKAPDT_PLUGIN_URL' ) ) {
            define('BKAPDT_PLUGIN_URL' , untrailingslashit( plugins_url ( '/', __FILE__ ) ) );
        }

        define( 'BKAPDT_TEMPLATE_PATH', BKAPDT_PLUGIN_PATH . '/templates/' );
	}

	/**
	 * Defining Constants
	 */

	public static function bkap_after_duration_based_time_section_callback( $product_id, $booking_settings ){
		?>
		<div>
			<div>
                <h4><?php _e( 'Disable Weekdays/Dates Timeslots', 'woocommerce-booking' ); ?></h4>
            </div>
			<table class="bkap_disable_timeslots_table">
				<thead>
					<tr>
						<th width="20%"><b><?php esc_html_e( 'Day/Date', 'woocommerce-booking' ); ?></b>
							<?php echo wc_help_tip( __( 'Select day or date option to for which you want to manage the availability.', 'woocommerce-booking' ) ); ?>
						</th>
						<th width="40%"><b><?php esc_html_e( 'Which Days/Dates?', 'woocommerce-booking' ); ?></b>
							<?php echo wc_help_tip( __( 'For which day or date you want to manage the availability.', 'woocommerce-booking' ) ); ?>
						</th>
						<th width="35%"><b><?php esc_html_e( 'From & To time', 'woocommerce-booking' ); ?></b>
							<?php echo wc_help_tip( __( 'Select from and to time slot value for which you want to manage the availability. This field will be applicable only if you want to manage the availability of the product which is set up with Fixed Time booking type.', 'woocommerce-booking' ) ); ?>
						</th>
						<th class="remove_bulk" width="10%">&nbsp;</th>
					</tr>
				</thead>					
				<tfoot>
					<tr>
						<th colspan="2" style="text-align: left;font-size: 11px;font-style: italic;">
							<?php esc_html_e( 'You can add, update and delete the day/date availability from here.', 'woocommerce-booking' ); ?>
						</th>
						<th colspan="2" style="text-align: right;">
							<a href="#" class="button button-primary bkap_add_row_disable_timeslot" style="text-align: right;" data-row="
							<?php
								ob_start();
								include( BKAPDT_TEMPLATE_PATH . 'html-bkap-disable-timeslot-row.php' );
								$html = ob_get_clean();
								echo esc_attr( $html );
							?>
							"><?php esc_html_e( 'Add Row', 'woocommerce-booking' ); ?></a>
						</th>
					</tr>
				</tfoot>                    
				<tbody id="bkap_disable_timeslots_rows">
					<?php self::bkapdt_displaying_disabled_timeslots_rows( $product_id, $booking_settings ); ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public static function bkapdt_displaying_disabled_timeslots_rows( $product_id, $booking_settings ){

		$disabled_timeslots = get_post_meta( $product_id, '_bkap_disabled_timeslots', true );

		$bkapdt_intervals['days'] = array(
			'all' => __( 'All', 		'woocommerce-booking' ),
			'0'   => __( 'Sunday', 		'woocommerce-booking' ),
			'1'   => __( 'Monday', 		'woocommerce-booking' ),
			'2'   => __( 'Tuesday', 	'woocommerce-booking' ),
			'3'   => __( 'Wednesday', 	'woocommerce-booking' ),
			'4'   => __( 'Thursday', 	'woocommerce-booking' ),
			'5'   => __( 'Friday', 		'woocommerce-booking' ),
			'6'   => __( 'Saturday', 	'woocommerce-booking' ),
		);

		if ( is_array( $disabled_timeslots ) && count( $disabled_timeslots ) > 0 ) {
			$i = 0;
			foreach ( $disabled_timeslots as $key => $value ) {

				if ( count( $value ) > 0 ){

					foreach ( $value as $k => $v ) {

						$day = $date 	= "";
						$day_display 	= "display:none;";
						$date_display 	= "display:none;";
						if ( strpos( $key, 'booking_weekday' ) !== false ) {
							// day
							$day = 'selected="selected"';
							$day_display = "";
							$d = substr( $key, -1 );
						} else {
							// date
							$date = 'selected="selected"';
							$date_display = "";
						}

						$from_time 	= $v['from_slot_hrs'] . ':' . $v['from_slot_min'];
						$to_time 	= $v['to_slot_hrs'] . ':' . $v['to_slot_min'];

						?>

						<tr class="bkap_disable_timeslots_row_<?php echo $i;?>">
							<td>
								<div class="bkap_disable_timeslots_day_date_div">
									<select id="bkap_disable_timeslots_day_date" name="bkap_disable_timeslots_day_date">
										<option value="day" <?php echo $day;?>><?php esc_html_e( 'Day', 'woocommerce-booking' ); ?></option>
										<option value="date" <?php echo $date;?>><?php esc_html_e( 'Date', 'woocommerce-booking' ); ?></option>
									</select>
								</div>
							</td> <!-- Day/Date -->
							<td>
								<div class="bkap_disable_timeslots_day_div" style="<?php echo $day_display;?>">
									<select class="bkap_disable_timeslots_day_class" id="bkap_disable_timeslots_weekday_<?php echo $i; ?>" name="bkap_disable_timeslots_weekday[]" multiple="multiple">
										<?php
										foreach ( $bkapdt_intervals['days'] as $kk => $vv ) {
											$daydateselected = '';
											if ( $kk == $d ) {
												$daydateselected = 'selected';
											}

											?>
											<option <?php echo $daydateselected; ?> value="<?php echo esc_attr( $kk ); ?>"><?php echo esc_html( $vv ); ?></option>
										<?php } ?>
									</select>
								</div>
								<div class="bkap_disable_timeslots_date fake-input"  style="<?php echo $date_display;?>">
									<textarea id="bkap_disable_timeslots_date_field_<?php echo $i; ?>" name="bkap_disable_timeslots_date_field" class="date-picker" rows="1" col="30" style="width:100%;height:auto;"><?php echo $key;?></textarea>
									<!-- <input type="text" class="date-picker" id="bkap_bulk_date_field" name="bkap_bulk_date_field"> -->
									<img src="<?php echo esc_attr( plugins_url() ); ?>/woocommerce-booking/assets/images/cal.gif" id="custom_checkin_cal" width="15" height="15" />
								</div>
							</td> <!-- Day/Date field -->
							<td>
								<div class="bkap_disable_timeslots_fromto_div">
									<input type="text" title="Please enter time in 24 hour format e.g 14:00 or 03:00" placeholder="HH:MM" maxlength="5" value="<?php echo $from_time;?>" onkeypress="return bkap_isNumberKey(event)" id="bkap_disable_timeslots_from_time" name="bkap_disable_timeslots_from_time">
									<input type="text" title="Please enter time in 24 hour format e.g 14:00 or 03:00" placeholder="HH:MM" maxlength="5" value="<?php echo $to_time;?>" onkeypress="return bkap_isNumberKey(event)" id="bkap_disable_timeslots_to_time" name="bkap_disable_timeslots_to_time">    
								</div>
							</td> <!-- From & To -->
							<td id="bkap_close_disable_timeslots_row" style="text-align: center;cursor:pointer;"><i class="fa fa-trash" aria-hidden="true"></i></td>
						</tr>

						<?php
						$i++;
					}
				}
			}	
		}
	}
}
$bkap_disable_timeslots = new BKAP_Disable_Timeslots();
endif;