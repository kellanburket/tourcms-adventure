<?php
require_once('tourcms_helper.php');

class wordpress_tourcms_helper extends tourcms_helper {

	public function get_promo_codes() {
		return array(array('name'=>'discovery_discount', 'code'=>'DISCOVERY7', 'type'=>'PERCENT'));
	}
	
	public function load_error_config() {
		return array(
			'invalid_tour_error'=>get_option('invalid_tour'),
			'invalid_promo_error'=>get_option('invalid_promo'),
			'invalid_date_error'=>get_option('invalid_date'),
			'invalid_email_error'=>get_option('invalid_email_error'),
			'invalid_title_error'=>get_option('invalid_title_error'),			
			'no_availabilities_error'=>get_option('no_availabilities'),
			'tourcms_technical_problem'=>get_option('tourcms_technical_problem'),
			'missing_customers_error'=>get_option('missing_customers_error')
		);
	}

	public function create_nonce() {
		return wp_create_nonce('tourcms_checkout');
	}

	public function get_channel_id() {
		return get_option('tourcms_channel_id');
	}
	
	public function get_api_key() {
		return get_option('tourcms_api_key');
	}
	
	public function get_debug_mode_config() {
		return get_option('tourcms_debug_mode');
	}
	
	public function get_authorize_net_test_mode_config() {
		return get_option('authorize_net_in_test_mode');
	}
	
	public function get_checkout_url($user_id) {
		//TODO: Change back to https when were back live
		return '?p='.get_option('tourcms_checkout_page').'&id='.$user_id;
	}

	public function get_setting($name) {
		return get_option($name);
	}
	
	function validate_string($string) {
		return sanitize_text_field($string);
	}
	
	function validate_email($email, $user, $domain) {
		return sanitize_email($email);		
	}
	
	function verify_nonce($nonce, $referring_url) {
		if (!wp_verify_nonce($nonce)) {
			return false;	
		} else {
			return true;
		}
	}	
	
	function debug_wpdb() {
		global $wpdb;
		echo "\nLast Error: ";
		print_r($wpdb->last_error);
		echo "\nLast Query: ";
		print_r($wpdb->last_query);
		echo "\nInsert ID: ";
		print_r($wpdb->insert_id);
		echo "\nNumber of Rows: ";
		print_r($wpdb->num_rows);
		echo "\nLast Result: ";
		print_r($wpdb->last_result);
		echo "\nColumn Info: ";
		print_r($wpdb->col_info);
	}
	
	function load_engine($user_id) {
		global $wpdb;
		require_once(TOURCMS_ROOT.'/booking-engine/booking-engine.php');
		
		//echo 'User ID: '.$user_id.'<br>';
		
		//$wpdb->query($wpdb->prepare('SELECT user_id FROM '.TOURCMS_BOOKING_TABLE));
		//$this->debug_wpdb();		
		//exit;		
		$wpdb->query($wpdb->prepare('SELECT engine FROM '.TOURCMS_BOOKING_TABLE.' WHERE user_id = %s', $user_id));		
				
		if ($wpdb->last_result) {
						
			$engine_serial = $wpdb->last_result;
			
			//print_r($engine_serial);
			//exit;
			
			if ($engine_serial[0]->engine) {
				$engine = unserialize($engine_serial[0]->engine);
				if ($engine->order_completed == false) {
					return $engine;
				} else {
					echo json_encode(array('success'=>false, 'error_message'=>'This order has already been processed.'));	
					exit;				
				}
			} else {
				echo json_encode(array('success'=>false, 'error_message'=>'There was an error fetching your booking information!'));				exit;
			}
		} else {
			echo json_encode(array('success'=>false, 'error_message'=>'No booking information available for this customer!'));	
			exit;
		}
	}
	
	function save_engine($engine) {
		global $wpdb;
		$engine_serial = serialize($engine);
		$wpdb->query($wpdb->prepare('SELECT id FROM '.TOURCMS_BOOKING_TABLE.' WHERE user_id = %s', $engine->user_id));
		$id = end($wpdb->last_result)->id;
		
		//echo "\nID: $id";
		//echo "\nUserID: ".$engine->user_id;
		//echo "\nEngine ".$engine_serial;
		
		if ($id) {
			$wpdb->replace(TOURCMS_BOOKING_TABLE, 
				array(
					'time'=>current_time('mysql'), 
					'user_id'=>$engine->user_id, 
					'engine'=>$engine_serial, 
					'id'=>$id), 
				array('%s', '%s', '%s', '%d'));
		} else {
			$wpdb->insert(TOURCMS_BOOKING_TABLE, 
				array(
					'time'=>current_time('mysql'), 
					'user_id'=>$engine->user_id, 
					'engine'=>$engine_serial), 
				array('%s', '%s', '%s'));
		}
	}
}
?>