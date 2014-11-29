<?php
require_once('tourcms_helper.php');
global $wpdb;
define('TOURCMS_BOOKING_TABLE', $wpdb->prefix.'tourcms_bookings');

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
		$mode = get_option('authorize_net_in_test_mode');
		if (current_user_can('manage_options')) {
			$mode = true;
		}
		return $mode;		
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
	
	function load_engine($user_id, $ip = NULL) {
		global $wpdb;
		$ip = ($ip) ? $ip : $_SERVER['REMOTE_ADDR'];
		require_once(TOURCMS_ROOT.'/booking-engine/booking-engine.php');
		
		//echo 'User ID: '.$user_id.'<br>';
		
		//$wpdb->query($wpdb->prepare('SELECT user_id FROM '.TOURCMS_BOOKING_TABLE));
		//$this->debug_wpdb();		
		//exit;		
		$wpdb->query($wpdb->prepare('SELECT engine FROM '.TOURCMS_BOOKING_TABLE.' WHERE user_id = %s AND client_ip = %s', array($user_id, $ip)));		
				
		if ($wpdb->last_result) {
						
			$engine_serial = $wpdb->last_result;
			
			//print_r($engine_serial);
			//exit;
			
			if ($engine_serial[0]->engine) {
				$engine = unserialize($engine_serial[0]->engine);
				if ($engine->order_completed == false) {
					return $engine;
				} else {
					$this->log_error("helper", "order already processed", $engine->booking_id);
					echo json_encode(array('success'=>false, 'error_message'=>'This order has already been processed.'));	
					exit;				
				}
			} else {
				$this->log_error("helper", "error fetching information", $engine->booking_id);
				echo json_encode(array('success'=>false, 'error_message'=>'There was an error fetching your booking information!'));				exit;
			}
		} else {
			$this->log_error("booking", "No booking information available for this customer!", $engine->booking_id);
			echo json_encode(array('success'=>false, 'error_message'=>'No booking information available for this customer!'));	
			exit;
		}
	}
	
	public function log_error($type, $message, $booking_id = NULL) {
		global $wpdb;
		if (!$booking_id && array_key_exists('user_id', $_POST)) {
			$engine = $this->load_engine($_POST['user_id']);
			if (is_object($engine)) {
				$booking_id = $engine->get_booking_id();
			}
		}
		
		$subject = 'Error Report';
		$report = "\r\nBooking ID: $booking_id";
		$report .= "\r\nError Type: $type";
		$report .= "\r\nRemote Address: {$_SERVER['REMOTE_ADDR']}";
		$report .= "\r\nUser Browser: {$_SERVER['HTTP_USER_AGENT']}";
		$report .= "\r\nMessage: $message";

		$wasSent = mail('web2@prideofmaui.com', $subject, $report);
		mail('phillip.rollins@mmsc-maui.com', $subject, $report);
		mail('tyler.bliss@mmsc-maui.com', $subject, $report);
						
		$wpdb->query($wpdb->prepare("INSERT INTO wp_tourcms_errors (error_type, message, booking_id, ip_address, user_agent, mail_was_delivered) VALUES(%s, %s, %d, %s, %s, %d)", array($type, $message, $booking_id, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], ($wasSent) ? 1 : 0)));

	}
	
	function save_engine($engine, $ip = NULL) {
		global $wpdb;
		$ip = (!is_null($ip)) ? $ip : $_SERVER['REMOTE_ADDR'];

		$engine_serial = serialize($engine);
		$wpdb->query(
			$wpdb->prepare('
				SELECT id FROM '.TOURCMS_BOOKING_TABLE.' 
				WHERE user_id = %s 
				AND client_ip = %s', 
				
				array($engine->user_id, $ip)));
		
		if ($wpdb->last_result) {
			$id = $wpdb->last_result[0]->id;
		}
		
		//echo "\nID: $id";
		//echo "\nUserID: ".$engine->user_id;
		//echo "\nEngine ".$engine_serial;
		
		if ($id) {
			$wpdb->query($wpdb->prepare('
				UPDATE '.TOURCMS_BOOKING_TABLE.' 
				SET
				engine=%s,
				final_total=%f,
				booking_id=%d,
				client_name=%s,
				order_completed=%d
				WHERE id=%d
			', array($engine_serial, floatval($engine->total_amount), intval($engine->final_booking_id), $engine->client_name, intval($engine->order_completed), $id)));
		} else {
			$wpdb->insert(TOURCMS_BOOKING_TABLE, 
				array(
					'time'=>current_time('mysql'), 
					'user_id'=>$engine->user_id, 
					'engine'=>$engine_serial,
					'client_ip'=>$ip,
					'total'=>floatval($engine->total_amount),
					'booking_id'=>intval($engine->temp_booking_id),
					'client_name'=>$engine->client_name,
					'order_completed'=>$engine->order_completed,
					'tour_date'=>$engine->tour_date
					), 
				array('%s', '%s', '%s', '%s', '%f', '%d', '%s', '%d', '%s'));
		}
	}
}
?>