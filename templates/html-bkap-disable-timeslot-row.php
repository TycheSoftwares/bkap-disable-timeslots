<?php
/**
 * This file is contains the two information of the availability table.
 *
 * @package BKAP-Disable-Timeslots
 */

$bkapdt_intervals = array();/*

$bkapdt_intervals['daysdate'] = array(
	'day'  => __( 'Day', 'woocommerce-booking' ),
	'date' => __( 'Date', 'woocommerce-booking' ),
);*/

$bkapdt_intervals['days'] = array(
	'all' => __( 'All', 'woocommerce-booking' ),
	'0'   => __( 'Sunday', 'woocommerce-booking' ),
	'1'   => __( 'Monday', 'woocommerce-booking' ),
	'2'   => __( 'Tuesday', 'woocommerce-booking' ),
	'3'   => __( 'Wednesday', 'woocommerce-booking' ),
	'4'   => __( 'Thursday', 'woocommerce-booking' ),
	'5'   => __( 'Friday', 'woocommerce-booking' ),
	'6'   => __( 'Saturday', 'woocommerce-booking' ),
);
?>
<tr class="bkap_disable_timeslots_row">
	<td>
		<div class="bkap_disable_timeslots_day_date_div">
			<select id="bkap_disable_timeslots_day_date" name="bkap_disable_timeslots_day_date">
				<option value="day"><?php esc_html_e( 'Day', 'woocommerce-booking' ); ?></option>
				<option value="date"><?php esc_html_e( 'Date', 'woocommerce-booking' ); ?></option>
			</select>
		</div>
	</td> <!-- Day/Date -->
	<td>
		<div class="bkap_disable_timeslots_day_div">
			<select class="bkap_disable_timeslots_day_class" id="bkap_disable_timeslots_weekday" name="bkap_disable_timeslots_weekday[]" multiple="multiple">
				<?php
				foreach ( $bkapdt_intervals['days'] as $key => $value ) {
					?>
					<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $value ); ?></option>
				<?php } ?>
			</select>
		</div>
		<div class="bkap_disable_timeslots_date fake-input"  style="display: none;">
			<textarea id="bkap_disable_timeslots_date_field" name="bkap_disable_timeslots_date_field" class="date-picker" rows="1" col="30" style="width:100%;height:auto;"></textarea>
			<!-- <input type="text" class="date-picker" id="bkap_bulk_date_field" name="bkap_bulk_date_field"> -->
			<img src="<?php echo esc_attr( plugins_url() ); ?>/woocommerce-booking/assets/images/cal.gif" id="custom_checkin_cal" width="15" height="15" />
		</div>
	</td> <!-- Day/Date field -->
	<td>
		<div class="bkap_disable_timeslots_fromto_div">
			<input type="text" title="Please enter time in 24 hour format e.g 14:00 or 03:00" placeholder="HH:MM" maxlength="5" onkeypress="return bkap_isNumberKey(event)" id="bkap_disable_timeslots_from_time" name="bkap_disable_timeslots_from_time">
			<input type="text" title="Please enter time in 24 hour format e.g 14:00 or 03:00" placeholder="HH:MM" maxlength="5" onkeypress="return bkap_isNumberKey(event)" id="bkap_disable_timeslots_to_time" name="bkap_disable_timeslots_to_time">    
		</div>
	</td> <!-- From & To -->
	<td id="bkap_close_disable_timeslots_row" style="text-align: center;cursor:pointer;"><i class="fa fa-trash" aria-hidden="true"></i></td>
</tr>
