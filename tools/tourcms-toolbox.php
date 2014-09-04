<?php
//Useful Functions to call via Javascript

function get_tourcms_helper() {
	require_once(TOURCMS_HELPER);
	$helper = 'wordpress_tourcms_helper';
	return new $helper();
}

function get_booking_fee_string($fee, $guests, $sales_tax) {

	if ($fee['type'] == 'PER_BOOKING') {
		$fee = floatval($fee['fee']);		
		//(floatval($sales_tax)/100)) + floatval($fee['fee']);
		return sprintf('<span class="total-cost-label float-left">%s per booking:</span><span class="total-cost-label float-right">$%.2f</span>', $fee['description'], $fee, $fee);	
	} else if ($fee['type'] == 'PER_PERSON') {
		$total_cost = floatval($fee['fee']) * floatval($guests); 
		//((floatval($fee['fee']) * (floatval($sales_tax)/100)) + floatval($fee['fee'])) * floatval($guests); 
		return sprintf('<span class="total-cost-label float-left">%s for %d guests at $%.2f per guest: </span><span class="total-cost-label float-right">$%.2f</span>', $fee['description'], intval($guests), floatval($fee['fee']), $total_cost);	
	} else {
		return false;
	}
}

function get_tax_string($amount, $tax) {
	//echo "Amount: {$amount}<br>Tax: {$tax}<br>";
	$tax_amount = floatval($amount) - (floatval($amount) / (1 + (floatval($tax)/100)));
	return sprintf('<span class="total-cost-label float-left">Sales tax:</span><span><span class="total-cost-label float-right">$%.2f</span>', $tax_amount);
}

function check_promo_code($post) {
	$helper = get_tourcms_helper();
	$legal_codes = $helper->get_promo_codes();
	$promo_code = (string) $post['promo_code'];
	
	if ($promo_code && is_array($legal_codes)) {
		foreach ($legal_codes as $legal_code) {
			if (preg_match('/'.$legal_code['code'].'/i', $promo_code, $matches)) {
				$tourcms = load_tourcms();
				$channel_id = SiteConfig::get("channel_id");	
				
				$code_check = $tourcms->show_promo($legal_code['code'], $channel_id);
				if ($code_check->error == "OK") {
					echo json_encode(array(
						'error'=>false,
						'name' => $legal_code['name'],
						'value'=>strip_tags($code_check->promo->value->asXML()), 
						'value_type'=>strip_tags($code_check->promo->value_type->asXML())
					));
					exit;		
				}
			}
		}
	}
	echo json_encode(array('error'=>true, 'message'=>'Invalid Promo Code'));
	exit;	
}

function update_calendar($post) {
	$tourcms = load_tourcms();
	$channel_id = SiteConfig::get("channel_id");
	require_once(TOURCMS_ROOT.'/widgets/sidebar-widget/calendar/live-tourcms-calendar.php');
	$calendar = new LiveTourCMSCalendar($post);
	echo $calendar->display_calendar($tourcms, $channel_id);
	exit;
}

function fetch_rates_data($post) {
	$id = ($_POST['tour_id']) ? intval($_POST['tour_id']) : 1;

	$cache_file = dirname(__FILE__)."/cache/tour_{$id}.xml";	
	if(get_tourcms_cache($cache_file, 1)) {
		$tourcms = load_tourcms();
		$tours = $tourcms->search_raw_departures($id, SiteConfig::get("channel_id"));
		file_put_contents($cache_file, $tours->asXML());				
	} else {
		$tours = simplexml_load_file($cache_file);				
	}

	$error = (string) $tours->error;	

	if ($error == 'OK') {

		$rates_data = $tours->tour->dates_and_prices->departure->rates->rate;
		$rates = array();
		foreach ($rates_data as $rate) {
			$values = array(
				'kind' => strtolower(strip_tags($rate->rate_name->asXML())),
				'rate' => floatval(strip_tags($rate->customer_price->asXML())) + 10
			);
			$rates[] = $values;
		}
	
		echo json_encode($rates);
		exit;
	}
}

function start_booking_engine($post) {
	require_once(TOURCMS_ROOT.'/booking-engine/booking-engine.php');
	$engine = new TourcmsBookingEngine($post['user_id']);
	
	$tourcms = load_tourcms();
	$channel_id = SiteConfig::get("channel_id");
	
	echo $engine->start($post, $tourcms, $channel_id);	
	exit;
}

function authorize_tourcms_booking($post) {
	$tourcms = load_tourcms();
	$channel_id = SiteConfig::get("channel_id");
	$helper = get_tourcms_helper();
	$engine = $helper->load_engine($post['user_id']); 
	echo $engine->authorize($post, $tourcms, $channel_id);	
	exit;
}

function confirm_tour_booking($post) {
	$tourcms = load_tourcms();
	$channel_id = SiteConfig::get("channel_id");
	$helper = get_tourcms_helper();
	$engine = $helper->load_engine($post['user_id']); 
	echo $engine->confirm_booking($post, $tourcms, $channel_id);
	exit;	
}

function load_modal($post) {
	require_once(TOURCMS_ROOT.'/booking-engine/booking-engine.php');
	$engine = new TourcmsBookingEngine($post['user_id']);

	$tourcms = load_tourcms();
	$channel_id = SiteConfig::get("channel_id");
	
	$message = $engine->start($post, $tourcms, $channel_id);	
	$object = json_decode($message);
	if ($object->success == false) {
		echo $message;
	} else {
		echo json_encode(array('success'=>true, 'html'=>$engine->display_options($tourcms, $channel_id)));	
	}
	exit;
}

function get_tourcms_cache($cache_file, $hours = 24) {
	$time = filemtime($cache_file);
	$current_time = time();
	$time_passed = $current_time - $time;
	if ($time_passed > (60 * 60 * $hours)) {
		return true;
	} else {
		return false;
	}
	
}

function fetch_tours_data($print = true) {
	$cache_file = dirname(__FILE__)."/cache/all_tours.json";
	if(get_tourcms_cache($cache_file, 1)) {
		$tourcms = load_tourcms(); 
		
		$channel_id = SiteConfig::get("channel_id");
		$tours = $tourcms->list_tours($channel_id)->tour; 
		$data = array();
		
		foreach($tours as $tour) {
			$id = (string) $tour->tour_id;		
			$next = $tourcms->show_tour($id, SiteConfig::get("channel_id"));
			$next = KBC_XML_Parser::parse($next->tour);


			if ($next['next_bookable_date']) {
				list($year, $month, $day) = sscanf($next['next_bookable_date'], '%d-%d-%d');
				$datum = array();						

				$datum['next_bookable_date'] = $month.'/'.$day.'/'.$year;
				$datum['from_price'] = (float) $next['from_price'];			
				$datum['id'] = (int) $id;			
				$datum['tour_name'] = $next['tour_name_long'];

				$data[$id] = $datum;				
			}
		}

		file_put_contents($cache_file, json_encode($data));		
	} else {
		$data = json_decode(file_get_contents($cache_file), true);		
	}

	if ($print) {
		echo json_encode($data);
		exit;
	} else {
		return $data;
	}
}
	
function load_tourcms() {
	if (!class_exists('TourCMS')) {
		if (PLATFORM == 'concrete5') {
			if(class_exists('Loader') && defined('PKG')) {
				Loader::library('tourcms/config', PKG);	
			} else {
				require(TOURCMS_ROOT.'/libraries/tourcms/config.php');
			}
		} elseif (PLATFORM == 'wordpress') {
			require_once(TOURCMS_ROOT.'/lib/tourcms/config');		
		}
	}
	$tourcms = new TourCMS(0, SiteConfig::get("api_private_key"), "simplexml");
	return $tourcms;
}

function get_singular($word) {
	if (preg_match('/adults/i', $word)) {
		return 'adult';
	} else if (preg_match('/children/i', $word)) {
		return 'child';
	} else if (preg_match('/seniors/i', $word)) {
		return 'senior';
	} 
}

function get_us_states() {
	$states = array('N/A' => 'Not Applicable', 'AL'=>"Alabama", 'AK'=>"Alaska", 'AZ'=>"Arizona", 'AR'=>"Arkansas", 'CA'=>"California", 'CO'=>"Colorado", 'CT'=>"Connecticut", 'DE'=>"Delaware",'DC'=>"District Of Columbia", 'FL'=>"Florida", 'GA'=>"Georgia", 'HI'=>"Hawaii", 'ID'=>"Idaho", 'IL'=>"Illinois", 'IN'=>"Indiana", 'IA'=>"Iowa", 'KS'=>"Kansas", 'KY'=>"Kentucky", 'LA'=>"Louisiana", 'ME'=>"Maine", 'MD'=>"Maryland", 'MA'=>"Massachusetts", 'MI'=>"Michigan", 'MN'=>"Minnesota", 'MS'=>"Mississippi",  'MO'=>"Missouri", 'MT'=>"Montana", 'NE'=>"Nebraska", 'NV'=>"Nevada", 'NH'=>"New Hampshire", 'NJ'=>"New Jersey", 'NM'=>"New Mexico",
'NY'=>"New York", 'NC'=>"North Carolina", 'ND'=>"North Dakota", 'OH'=>"Ohio", 'OK'=>"Oklahoma", 'OR'=>"Oregon", 'PA'=>"Pennsylvania",  
'RI'=>"Rhode Island", 'SC'=>"South Carolina", 'SD'=>"South Dakota", 'TN'=>"Tennessee", 'TX'=>"Texas", 'UT'=>"Utah", 'VT'=>"Vermont", 'VA'=>"Virginia", 'WA'=>"Washington", 'WV'=>"West Virginia", 'WI'=>"Wisconsin", 'WY'=>"Wyoming");
	return $states;
}