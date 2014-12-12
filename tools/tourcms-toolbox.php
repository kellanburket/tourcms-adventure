<?php
//Useful Functions to call via Javascript

function log_tourcms_error() {
	$helper = get_tourcms_helper();
	$helper->log_error($_POST['type'], $_POST['message']);
}

function get_tourcms_helper() {
	require_once(TOURCMS_HELPER);
	$helper = 'wordpress_tourcms_helper';
	return new $helper();
}

function serve_secure_image($url){
	$url = str_replace("http://", "https://", $url, $count = 1);
	$urlparts = explode('.', $url);
	if (preg_match('/r\d+/', $urlparts[1])) {
	  	$urlparts[1] = 'ssl';
	  	$url = implode('.', $urlparts);
  	}
  	return($url);
}


function get_booking_fee_string($fee, $guests, $sales_tax) {

	if ($fee['type'] == 'PER_BOOKING') {
		$fee = floatval($fee['fee']);		
		//(floatval($sales_tax)/100)) + floatval($fee['fee']);
		return sprintf('<span class="total-cost-label float-left">%s per booking:</span><span class="total-cost-label float-right">$%.2f</span>', $fee['description'], $fee, $fee);	
	} else if ($fee['type'] == 'PER_PERSON') {
		$total_cost = floatval($fee['fee']) * floatval($guests); 
		//((floatval($fee['fee']) * (floatval($sales_tax)/100)) + floatval($fee['fee'])) * floatval($guests); 
		return sprintf('<span class="total-cost-label float-left">%s ($%.2f/guest): </span><span class="total-cost-label float-right">$%.2f</span>', $fee['description'], floatval($fee['fee']), $total_cost);	
	} else {
		return false;
	}
}

function get_tax_string($amount, $tax) {
	//echo "Amount: {$amount}<br>Tax: {$tax}<br>";
	$tax_amount = reverse_calculate_sales_tax($amount, $tax);
	return sprintf('<span class="total-cost-label float-left">Sales tax:</span><span><span class="total-cost-label float-right">$%.2f</span>', $tax_amount);
}

function reverse_calculate_sales_tax($amount, $tax) {
	return floatval($amount) - (floatval($amount) / (1 + (floatval($tax)/100)));
}

function calculate_sales_tax($subtotal, $tax) {
	return round($subtotal * ($tax / 100), 2, PHP_ROUND_HALF_UP);
}

function get_price_before_tax($price, $tax) {
	$tax = reverse_calculate_sales_tax($price, $tax);
	return $price - $tax;
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

function get_tour_options($id, $date) {
	$tourcms = load_tourcms();
	$channel_id = SiteConfig::get("channel_id");
	$params = "date=".$date.'&show_options=1';
	$options = $tourcms->show_tour($id, $channel_id, $params)->tour->options->option;
	return $options;
}

function get_tourcms_cache($cache_file, $hours = 24) {
	if (file_exists($cache_file)) {
		$time = filemtime($cache_file);
		$current_time = time();
		$time_passed = $current_time - $time;
		if ($time_passed > (60 * 60 * $hours)) {
			return true;
		} else {
			return false;
		}
	} else {
		return true;
	}	
}

function get_available_dates($id, $y, $m, $d) {	
	$tourcms = load_tourcms();
	$channel_id = SiteConfig::get("channel_id");
	
	$first_day_of_month = intval(date('w', strtotime("1-$m-$y")));	
	$days_in_month = cal_days_in_month(CAL_GREGORIAN, intval($m), intval($y));

	//echo 'Search Date Begin: ';
	$search_begin_date = $y.'-'.str_pad($m, 2, '0', STR_PAD_LEFT).'-01';
	//echo '<br>Search Date End: ';
	$search_end_date = $y.'-'.str_pad($m, 2, '0', STR_PAD_LEFT).'-'.$days_in_month;
	//echo '<br>';
	$params_string = 'between_date_start='.$search_begin_date.'&between_date_end='.$search_end_date;
	
	$availability = $tourcms->show_tour_datesanddeals($id, $channel_id, $params_string);

	//echo json_encode(array("availability"=> $availability, "params" => $params_string, "id" => $id, "channel_id" => $channel_id));
	//exit;

	if (!is_object($availability->dates_and_prices)) {
		return false;
	} else {
		return $availability->dates_and_prices->date;
	}
}

function build_calendar() {
	extract($_POST);

	$td_class = (isset($td_class)) ? $td_class : "";
	$th_class = (isset($th_class)) ? $th_class : "";
	
	$available_dates = get_available_dates($id, $year, $month, $day);	
	$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
	$first_day_of_month = intval(date('w', strtotime("1-{$month}-{$year}")));	


	$days_of_week = array("Su", "Mo", "Tu", "We", "Th", "Fr", "Sa");
	$days_gone_by = 0;

	$return = '<tr>';
    
	foreach($days_of_week as $value) {
		$return .= "<th class='$th_class'>$value</th>";
	}

    $return .= '</tr><tr>';

	for ($i = 0; $i < $first_day_of_month; $i++) {
    	$return .= '<td class="date_empty date_td"></td>';
        $days_gone_by++; 
	}

	$ii = 0;		        
	$date_prefix = sprintf("%d-%02d-", $year, $month);

	for ($i = 1; $i <= $days_in_month; $i++) {
		$class = "date_any $td_class";
		
		if ($available_dates[$ii]) {

			$next_available_date = (string) $available_dates[$ii]->start_date;			
			$current_date = sprintf("%s%02d", $date_prefix, $i);  
				
			if ($next_available_date == $current_date) {
				$class .= " available";

				do {
					if (count($available_dates) == ++$ii)
						break;
				} while ((string) $available_dates[$ii]->start_date == $next_available_date);
			} else {
				$class .= " unavailable";
			}
		} else {
			$class .= " unavailable";
		}
		
		$return .= sprintf("<td data-date='%d-%02d-%02d' class='day-td %s'>%s</td>", $year, $month, $i, $class, $i);
		
		$days_gone_by++;
		
		if ($days_gone_by % 7 == 0) {
			$return .= '</tr><tr>';
		}
	}
	$return .= '</tr>';

	echo $return;
	exit;
}

function fetch_tour_data($id = null, $print = true, $hours = 1) {
	if (array_key_exists("tour_id", $_POST)) {
		$id = $_POST['tour_id'];
	} elseif (!$id && !array_key_exists("tour_id", $_POST)) {
		echo json_encode(array("success" => "false", "message" => "No Tour ID."));
		exit;
	}

	$cache_file = dirname(__FILE__)."/cache/tour_$id.json";
	
	if(get_tourcms_cache($cache_file, $hours)) {
		$tourcms = load_tourcms(); 			
		$data = parse_tour_data($id, $tourcms);		
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

function fetch_rates_data($id, $second_attempt = false, $print = true, $hours = 24) {
	$id = (array_key_exists("id", $_POST)) ? intval($_POST['tour_id']) : $id;

	$cache_file = dirname(__FILE__)."/cache/tour_rates_{$id}.json";	
	
	if(get_tourcms_cache($cache_file, $hours)) {
		$tourcms = load_tourcms();
		$tours = $tourcms->search_raw_departures($id, SiteConfig::get("channel_id"));
		
		$error = (string) $tours->error;
	
		if ($error == 'OK') {
	
			$rates_data = $tours->tour->dates_and_prices->departure->rates->rate;
			$rates = array();
	
			foreach ($rates_data as $rate) {
				$rates[strtolower((string) $rate->rate_name)] = (float) $rate->customer_price;
			}

			file_put_contents($cache_file, json_encode($rates));
		
		} else {
			if (!$second_attempt) {
				fetch_tours_data(false, 1);
				fetch_rates_data($id, true, $print, $hours);
			}
		}

	} else {
		$file = file_get_contents($cache_file);
		$rates = json_decode($file);			
	}

	if ($print) {
		echo json_encode($rates);
		exit;
	} else {
		return $rates;
	}
}

function parse_tour_data($id, $tourcms) {
	$next = $tourcms->show_tour($id, SiteConfig::get("channel_id"));
	$next = KBC_XML_Parser::parse($next->tour);

	if ($next['next_bookable_date']) {
		list($year, $month, $day) = sscanf($next['next_bookable_date'], '%d-%d-%d');
		$datum = array();						

		$datum['next_bookable_date'] = $month.'/'.$day.'/'.$year;
		$datum['from_price'] = (float) $next['from_price'];			
		$datum['rates'] = fetch_rates_data($id, false, false);
		$datum['id'] = (int) $id;			
		$datum['tour_name'] = $next['tour_name_long'];
		$datum['image'] = serve_secure_image($next['images']['image']['url']);
		$datum['description'] = $next['shortdesc'];
		$datum['url'] = $next['tour_url'];				
		return $datum;

	} else {
		return null;
	}
	
}

function fetch_tours_data($print = true, $hours = 1) {
	$cache_file = dirname(__FILE__)."/cache/all_tours.json";
	if(get_tourcms_cache($cache_file, $hours)) {
		$tourcms = load_tourcms(); 
		
		$channel_id = SiteConfig::get("channel_id");
		$tours = $tourcms->list_tours($channel_id)->tour; 
		$data = array();
		
		foreach($tours as $tour) {
			$id = (string) $tour->tour_id;		
			if (!is_null($next = parse_tour_data($id, $tourcms))) {
				$data[$id] = $next;
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

function pluralize($term) {
	$blacklist = array(
		"child" => "children"
	);

	$term = strtolower($term);
	if ( array_key_exists($term, $blacklist) ) {
		return $blacklist[$term];
	}
	
	return $term + "s";
}

function singularize($term) {
	$blacklist = array(
		"children" => "child"
	);

	$term = strtolower($term);
	if ( array_key_exists($term, $blacklist) ) {
		return $blacklist[$term];
	}
	
	return substr($term, 0, strlen($term) - 1) . "s";
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

function parse_display_price($price) {
	return round(floatval($price) / (floatval(floatval(get_option('tourcms_sales_tax')) / 100) + 1), 2); 
}
