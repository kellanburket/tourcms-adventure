<?php
abstract class tourcms_helper {

	public function load_error_config() {
		return array(
			'invalid_tour_error'=>'This is not a real tour.',
			'invalid_promo_error'=>'The promo code you entered was invalid',
			'invalid_date_error'=>'Please enter a valid date.',
			'no_availabilities_error'=>'Sorry! No more availabilities for the requested date!',
			'tourcms_technical_problem'=>'There was a TourCMS Technical Problem!'			
		);
	}
	
	public function get_setting($name) {
		return 0;	
	}
	
	public function unlink_reservation($user_id, $booking_id) {
		//unlink(TOURCMS_ROOT.'/orders/'.$user_id.'.xml');
		file_put_contents(TOURCMS_ROOT.'/orders/'.$user_id.'.txt', $booking_id);	
		//delete_user_meta($user_id, 'booking_id');
		//add_user_meta($user_id, 'booking_id', $booking_id, true);
	}
	
	public function get_user_id() {
		return uniqid();
	}
	
	public function get_debug_mode_config() {
		return true;
	}
	
	public function get_relay_response_url() {
		return TOURCMS_URL.'/tools/relay-response.php';
	}
	
	public function get_booking_info($user_id) {
		require_once(TOURCMS_ROOT.'/lib/xml-parser.php');
		return KBC_XML_Parser::parse_file(TOURCMS_ROOT.'/orders/'.$user_id.'.xml'); 
	}
	
	public function get_authorize_net_test_mode_config() {
		return true;
	}
	
	public function get_checkout_url($user_id) {
		return 'https://'.$_SERVER['SERVER_NAME'].'/checkout?p='.$user_id;
	}

	public function load_database() {
		require_once(dirname(__FILE__).'/../database.php');
		return new TourcmsCustomWidgetsDatabaseHelper(); 
	}
		
	public function save_order($engine) {
		return file_put_contents(TOURCMS_ROOT.'/orders/'.$engine->user_id.'.txt', serialize($engine));	
	}
		
	public function save_order_to_xml($args) {
		extract($args); 		
		$xml = new DOMDocument('1.0');	
		
		$info = $xml->createElement('user_info');
		$info = $xml->appendChild($info);
		
		$uid = $xml->createElement('user_id', $user_id); 
		$info->appendChild($uid);
				
		$tname = $xml->createElement('tour_name', $tour_name);
		$info->appendChild($tname);
				
		$tdate = $xml->createElement('tour_date', $tour_date);
		$info->appendChild($tdate);
				
		$cdata = $xml->createCDATASection($totals_string);
		$totals = $xml->createElement('totals_string'); 
		$info->appendChild($totals);
		$totals->appendChild($cdata);

		$bk = $xml->createElement('booking');
		$info->appendChild($bk);
		$frag = $xml->createDocumentFragment();
		$frag->appendXML(substr($booking->asXML(), strpos($booking->asXML(), '?>') + 2));
		$bk->appendChild($frag);

		$res = $xml->createElement('result');
		$info->appendChild($res);
		$frag = $xml->createDocumentFragment();
		$frag->appendXML(substr($result->asXML(), strpos($result->asXML(), '?>') + 2));
		$res->appendChild($frag);
		
		$xml->save(TOURCMS_ROOT.'/orders/'.$user_id.'.xml');
	}
	
	function validate_string($string) {
		//check for invalid unicode
		if (@preg_match( '/^./us', $string) === 0) {
			return false;
		}

		$string = trim( preg_replace('/[\r\n\t ]+/', ' ', $filtered) );

		$found = false;		
		while ( preg_match('/%[a-f0-9]{2}/i', $string, $match) ) {
			$string = str_replace($match[0], '', $string);
			$found = true;
		}

		if ( $found ) {
			// Strip out the whitespace that may now exist after removing the octets.
			$string = trim( preg_replace('/ +/', ' ', $string) );
		}
		return htmlspecialchars(strip_tags($string));
	}
	
	function validate_email($email, $user, $domain) {
		if(!$user || !$domain) {
			return false;
		}
		
		if(!checkdnsrr($domain, "MX")) {
			return false;
		}
		
		return $email;
	}
	
	function validate_date($date) {
	
	}
	
	function verify_nonce($nonce, $referring_url) {
		return true;
	}

}
?>