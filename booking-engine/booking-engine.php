<?php 
class TourcmsBookingEngine {

	private $invalid_tour_error;
	private $invalid_promo_error;
	private $invalid_date_error;
	private $no_availabilities_error;
	private $tourcms_technical_problem;
	private $invalid_title_error;
	private $invalid_email_error;
	
	public $booking;	//XML String	

	public $temp_booking_id;
	public $final_booking_id;
	public $totals_string;
	public $tour_date;
	public $tour_name;
	public $user_id;
	public $total_amount;

	public $sales_tax;
	public $booking_fee;
	
	private $helper;
	private $debug;
	
	private $rates;
	public $total_customers;
	public $client_name;
	public $order_completed;
	
	public function __construct($user_id) {
		$this->user_id = $user_id;
		$this->order_completed = false;
		if (defined('PLATFORM')) {
			$helper = PLATFORM.'_tourcms_helper';
			$this->helper = new $helper();

			$errors = $this->helper->load_error_config();			
			
			foreach($errors as $error=>$message) {
				$this->$error = $message;
			}
		} else {
			$this->invalid_tour_error = 'This is not a real tour.';
			$this->invalid_promo_error = 'The promo code you entered was invalid.';
			$this->invalid_date_error = 'Please enter a valid date.';
			$this->no_availabilities_error = 'Sorry! No more availabilities for parties of this size on the requested date!';
			$this->tourcms_technical_problem = 'There was a TourCMS Technical Problem!';
			$this->invalid_email_error = 'Please enter a valid email address.';
			$this->invalid_title_error = ' Invalid title. Please select from the available options in the dropdown list.';						
			$this->missing_customers_error = 'Please enter a valid number of travelers.';
		}

		$this->debug = $this->get_debug_mode_config();
		$this->checkout_url = $this->get_checkout_url();
		$this->nonce = $this->helper->create_nonce();
	}

	public function get_booking_id() {
		if ($this->booking_id)
			return $this->booking_id;
		else
			return $this->temp_booking_id;
	}

	private function return_message($message, $tourcms_message = '') {
		$this->helper->log_error("booking_engine", "Message: $message | Tourcms Message: $tourcms_message", $this->get_booking_id());
		return json_encode(array('success'=>false, 'error_message'=>$message, 'tourcms_message'=>$tourcms_message));
	}

	public function start($post, $tourcms, $channel_id) {
		extract($post);

		$real_tour = false;
		$tours = $tourcms->list_tours($channel_id)->tour;
		
		foreach ($tours as $index=>$tour) {
			if ($tour->tour_id == $tour_id) {
				$real_tour = true;
				$this->tour_name = strip_tags($tour->tour_name->asXML());
				break;
			}
		}
		
		if (!$real_tour) {
			return $this->return_message($this->invalid_tour_error, "Not a real tour"); 
		}

		//Set all numerical
		$this->sales_tax = floatval($sales_tax);
		$this->tour_id = intval($tour_id);
		$this->total_customers = 0;
		
		for($i = 0; $i < count($rates_data); $i++) {
			@ $rates_data[$i]['number'] = intval($rates_data[$i]['number']);
			@ $rates_data[$i]['kind'] = $rates_data[$i]['kind'];
			@ $rates_data[$i]['total'] = floatval($rates_data[$i]['total']);
			@ $rates_data[$i]['rate'] = floatval($rates_data[$i]['rate']);
			@ $this->total_customers += intval($rates_data[$i]['number']);
		}
		
		if ($this->total_customers <= 0) {
			return $this->return_message("Please enter a valid number of travelers.", "Zero travelers selected"); 		
		}
		
		$this->rates = $rates_data;
		
		$this->validate_options($options_data);
		
		//check to make sure promo code is valid;
		$this->promo_code = $this->validate_promo_code($promo_code, $tourcms, $channel_id);

		//check to make sure a valid date has been given;
		$tour_date = explode("/", $tour_date);
		$month = ($tour_date[0] < 10) ? "0".$tour_date[0] : $tour_date[0];
		$day = ($tour_date[1] < 10) ? "0".$tour_date[1] : $tour_date[1];
		$year = $tour_date[2];
		
		if (!checkdate($month, $day, $year)) {
			return $this->return_message($this->invalid_date_error, "Invalid Date"); 
		}

		$this->tour_date = $year.'-'.$month.'-'.$day;


		$availability = $this->check_tour_availability($tourcms, $channel_id);		
	
		//Get Booking Key
		//if all variables clear, start new booking
		
		$booking = new SimpleXMLElement('<booking />');
		$booking->addChild('total_customers', $this->total_customers);
		$url = TOURCMS_URL.'/tools/fetch_tourcms_booking_key.php';
		$url_data = new SimpleXMLElement('<url />'); 
		$url_data->addChild('response_url',	$url);	
		$result = $tourcms->get_booking_redirect_url($url_data, $channel_id);	
		$redirect_url = $result->url->redirect_url;
		
		$this->booking_key = file_get_contents($redirect_url);
		
		if ($this->booking_key == null) {
			return $this->return_message($this->tourcms_technical_problem, "invalid booking key"); 
		}
	
		$booking->addChild('booking_key', $this->booking_key);
		
		if ($this->promo_code) {
			$booking->addChild('promo_code',  $this->promo_code);
		}
		
		$components = $booking->addChild('components');
		$component = $components->addChild('component');
		$component->addChild('component_key', $availability->available_components->component->component_key);

		if ($options_data) {
			$this->process_options_data($component, $availability, $options_data, $this->rates);
		}
		
		unset($this->rates['infants']);
		$customers = $booking->addChild('customers');
		
		foreach($this->rates as $rate) {
			if ($rate == 'infants') continue;
			
			for ($i = 0; $i < $rate['number']; $i++) {
				$customer = $customers->addChild('customer');
				if ($i == 0) {
					$customer->addChild('title', '');
				}
				$customer->addChild('firstname', '');
				$customer->addChild('surname', '');
				
				if ($i == 0) {
					$customer->addChild('email', '');
					$customer->addChild('tel_home', '');		
					$customer->addChild('address', '');
					$customer->addChild('city', '');		
					$customer->addChild('county', '');		
					$customer->addChild('postcode', '');		
					$customer->addChild('country', '');		
				}
				
				$customer->addChild('agecat', strtolower(substr($rate['kind'], 0, 1)));
				
				if ($i == 0) {
					$customer->addChild('tel_mobile', '');
				}			
			}
		}
		
		$this->totals_string = $totals_string;
		
		return $this->new_booking($booking, $tourcms, $channel_id);
	}

	public function new_booking($booking, $tourcms, $channel_id) {
		$result = $tourcms->start_new_booking($booking, $channel_id);
		
		$error = (string) $result->error;
		
		if ($error == "OK") {
			$unavailable = (int) $result->unavailable_component_count;
			
			$this->booking = $booking->asXML();
			$this->temp_booking_id = (string) $result->booking->booking_id;
			
			$this->total_amount = (string) $result->booking->sales_revenue;

			$this->booking_fee = array();			
			$this->booking_fee['description'] = "Fuel Surcharge"; //(string) $result->booking->booking_fee->description;
			$this->booking_fee['type'] = "PER_PERSON"; //(string) $result->booking->booking_fee->fee_type;
			$this->booking_fee['fee'] = 4.00; //(float) $result->booking->booking_fee->fee;

			if ($unavailable == 0) {
				$this->save_order();
				return json_encode(array(
					'success'=> true,
					'debug'=> $this->debug,
					'nonce'=> $this->nonce,
					'user_id'=>$this->user_id,
					'checkout_url'=>$this->checkout_url			
				));
			} elseif ($unavailable > 0) {
				$this->helper->log_error("booking", "No Available Components: $unavailable", $this->get_booking_id());
				return json_encode(array(
					'success'=>false, 
					'unavailable_components'=>$unavailable, 
					'error_message'=>$this->no_availabilities_error, 
					'tourcms_error'=>$error));
			} else {
				$this->helper->log_error("booking", "No Available Components: $unavailable", $this->get_booking_id());
				return json_encode(array(
					'success'=> false,
					'debug'=> true,
					'user_id'=>$this->user_id,
					'checkout_url'=>$this->checkout_url,
					'tourcms_error'=>$error));
			}

		} else {
			$this->helper->log_error("tourcms", $result->asXML(), $this->get_booking_id());
			if ($error == 'PLEASE ADD total_customers') {
				return json_encode(array('success'=>false, 'error_message'=>$this->missing_customers_error));					
			} else {
				return json_encode(array('success'=>false, 'error_message'=>$this->tourcms_technical_problem, 'tourcms_error'=>$error));
			}
			
		}
		
	}
	
	public function authorize($post, $tourcms, $channel_id) {
		$booking = simplexml_load_string($this->booking);
		$customer = $booking->customers->customer;

		$customer[0]->email = $this->validate_email($post['email']);
		$customer[0]->title = $this->validate_title($post['title']);
		$customer[0]->firstname = $this->validate_string($post['firstname']);
		$customer[0]->surname =  $this->validate_string($post['surname']);
		$customer[0]->tel_home = $this->validate_string($post['tel_home']);
		$customer[0]->address = $this->validate_string($post['address']);
		$customer[0]->city = $this->validate_string($post['city']);
		$customer[0]->county = $this->validate_string($post['county']);
		$customer[0]->postcode = $this->validate_string($post['postcode']);
		$customer[0]->country = $this->validate_string($post['country']);
		$customer[0]->tel_mobile = $this->validate_string($post['tel_mobile']);

		$this->client_name = $post['firstname'] . " " . $post['surname'];


		$final_booking = $tourcms->start_new_booking($booking, $channel_id);
		$error = (string) $final_booking->error;
		if ($error == "OK") {
			$this->final_booking_id = (string) $final_booking->booking->booking_id;
			
			$this->booking_fee = array();			
			$this->booking_fee['display'] = (string) $result->booking->booking_fee->fee_display;
			$this->booking_fee['description'] = (string) $result->booking->booking_fee->description;
			$this->booking_fee['type'] = (string) $result->booking->booking_fee->fee_type;
			$this->booking_fee['fee'] = (float) $result->booking->booking_fee->fee;

			$this->save_order();
			return json_encode(array(
				"success"=>true, 
				"debug"=>$this->debug, 
				"user_id"=>$this->user_id, 
				"booking_id"=>$this->final_booking_id,
				"total"=>$this->total_amount
			));
	
		} else {
			$this->helper->log_error("tourcms", $result->asXML(), $this->get_booking_id());
			return json_encode(array(
				'error_message'=>$this->tourcms_technical_problem,
				'error'=>$error,
			)); 
		}
		
	}
			
	public function confirm_booking($post, $tourcms, $channel_id) {
		$booking = simplexml_load_string($this->booking);	
		extract($post);
		$this->totals_string = $totals_string;
		$availability = $this->check_tour_availability($tourcms, $channel_id);
		$booking->components->component->note = $hotel.', '.$room;
		$this->validate_options($options_data);
		$this->process_options_data($booking->components->component, $availability, $options_data);
	
		return $this->new_booking($booking, $tourcms, $channel_id);
	}
	
	public function display_options($tourcms, $channel_id) {
	
		$params = "date=".$this->tour_date;
		
		for ($i = 1; $i <= count($this->rates); $i++) {
			$params .= '&r'.$i.'='.$this->rates[$i - 1]['number'];
		}
		
		$params = 'id='.$this->tour_id.'&show_options=1';
		$tour_options = $tourcms->show_tour($this->tour_id, $channel_id, $params)->tour->options->option;
		
		$total = 0;
			
		$return = '<div id="booking-box">';
		$return .= '<table id="review-booking-table" class="review-booking-block">';
		$return .= sprintf('<thead><tr><th class="table-head">This reservation is available for %s on %s</th></tr></thead>', 
			$this->tour_name, 
			$this->tour_date
		);
		$return .= '<tbody>';
			
		foreach($this->rates as $rate) {
			if ($rate['number'] > 0) {
				$return .= '<tr class="booking-tr">';
				$return .= sprintf('<td class=" color-white booking-td">%d %s at $%1.2f</td><td class="booking-td color-white td-mid"></td><td class="booking-td color-white">$%1.2f</td>',
					$rate['number'],
					($rate['number'] > 1) ? $rate['kind'] : get_singular($rate['kind']),
					$rate['rate'],
					$rate['total']
				);
				
				$return .= '</tr>';			
				$total += floatval($rate['total']);
			}
		}
		
		$return .= '</tbody>
				</table>
			<div id="extra-information">
				<h5 class="confirm-booking-h5">Accommodation Information</h5> 
				
				<table class="boarding-table">
					<tbody>
						<tr>
							<td class="booking-td confirm-label" id="hotel-label">Hotel</td>	
							<td class="booking-td td-mid"></td>
							<td class="booking-td hotel-room-confirm-fields"><input type="text" name="hotel" id="hotel-field" class="confirm-field"></td>
						</tr>
						<tr>
							<td class="booking-td confirm-label" id="room-label">Room (if known)</td>
							<td class="booking-td td-mid"></td>
							<td class="booking-td"><input type="text" name="room" id="room-field" class="confirm-field"></td>
						</tr>
					</tbody>
				</table>';
									
			
		if (count($tour_options) > 0) {
			
		
			$return .= '<h5 class="confirm-booking-h5">Available Upgrades</h5>';
			$return .= '<table id="available-upgrades">';
		
			for ($i = 0; $i < count($tour_options); $i++) {
				$display_price = round(floatval($tour_options[$i]->from_price) / (floatval(floatval(get_option('tourcms_sales_tax')) / 100) + 1), 2);
				$return .= '<tr>';
				$return .= '<td class="booking-td confirm-label">'.$tour_options[$i]->option_name.': </td>';
				$return .= '<td class="booking-td confirm-label td-mid">$'.sprintf('%.2f', $display_price).'</td>';	
				$return .= '<td class="booking-td">';
				$return .= '<fieldset name="modal_options['.$i.']" class="modal-options">';
				$return .= '<input type="number" name="modal_number" id="modal-option-number-field-'.$i.'" class="confirm-field" min="0">';
				$return .= '<input type="hidden" name="modal_kind" value="'.$tour_options[$i]->option_name.'" id="modal-option-kind-field-'.$i.'">';
				$return .= '<input type="hidden" name="modal_rate" value="'.$display_price.'" id="modal-option-rate-field-'.$i.'" >';
				$return .= '</fieldset>';
				$return .= '</td>';					
				$return .= '</tr>';		
			}
			$return .= '</table>';
		}
		$return .= '<div id="submit-div">
						<button id="confirm-booking">
						Confirm Booking
						</button>
					</div>
				</div>    
			</form>
		</div>';
			
		return $return;
	}
	
	function process_options_data(&$component, $availability, $options_data, $rates_data = array()) {
		$options_added = false;

		if (is_array($rates_data)) {		
			foreach($rates_data as $rate) {
				if ($rate['kind'] == 'infants') {
					$options_data = array_merge($options_data, $rate);
					break;
				}
			}
		}
		
		$tour_options = $availability->available_components->component->options->option;
		//echo "Tour Options: ".count($tour_options)."\n";
		for($i = 0; $i < count($tour_options); $i++) {
			if (is_array($options_data)) {
				foreach($options_data as $option) {
					//echo "Tourcms Option Name: ".$tour_options[$i]->option_name."\n";
					//echo "Saved Option Data: ".$options_data[$j]['name']."\n";
					if ($tour_options[$i]->option_name == $option['kind'] && $option['number'] > 0) {		
						if ($options_added == false) {
							$booking_options = $component->addChild('options');
							$options_added = true;
						}
						$booking_option = $booking_options->addChild('option');
						$booking_option->addChild(
							'component_key', 
							$tour_options[$i]->quantities_and_prices->selection[$option['number'] - 1]->component_key
						);
						break 1;
					}
				}
			}
		}
	}
	
	function get_booking() {
		return simplexml_load_string($this->booking);
	}
	
	function validate_string(&$string) {
		return $this->helper->validate_string($string);
	}
	
	function validate_email($email) {
		list($user, $domain) = split("@", $email);
		$email = $this->helper->validate_email($email, $user, $domain);
		if (!$email) {
			echo $this->return_message($this->invalid_email_error); 
			exit;
		}
		return $email;
	}
	
	function validate_date() {
	
	}
	
	function validate_title($title) {
		$possible_titles = array("mr", "mrs", "ms", "miss");
		if(!in_array($title, $possible_titles)) {
			echo $this->return_message($this->invalid_title_error); 
			exit;				
		}
		return $title;
	}
	
	function verify_nonce($nonce, $referring_url) {
		if (!$this->helper->verify_nonce($nonce, $referring_url)) {
			echo json_encode(array(
				"debug"=>$this->debug,
				"success"=>false,
				"message"=>"Invalid Action"
			));
			exit;		
		}
	}
	
	private function validate_promo_code($code, $tourcms, $channel_id) {
		if ($code) {
			$legal_codes = $this->helper->get_promo_codes();
		
			if (is_string($code)) {
				for ($i = 0; $i < count($legal_codes); $i++) {
					if (preg_match('/'.$legal_codes[$i]['code'].'/i', $code, $matches)) {	
						$code_check = $tourcms->show_promo($legal_codes[$i]['code'], $channel_id);
						$error = strip_tags($code_check->error->asXML());
						if ($error == "OK") {
							return $legal_codes[$i]['code'];
						}
					}
				}
			}
			echo $this->return_message($this->invalid_promo_error); 
			exit;	
		}
	}
	
	private function validate_options(&$options_data) {
		if ($options_data) {
			for($i = 0; $i < count($options_data); $i++) {
				$options_data[$i]['number'] = (isset($options_data[$i]['number'])) ? intval($options_data[$i]['number']) : 0;
				$options_data[$i]['rate'] = (isset($options_data[$i]['rate'])) ? floatval($options_data[$i]['rate']) : 0;
				$options_data[$i]['total'] = (isset($options_data[$i]['total'])) ? floatval($options_data[$i]['total']) : 0;
			}
		}
	}
	
	private function check_tour_availability($tourcms, $channel_id) {
		$params = "date=".$this->tour_date;
		
		for ($i = 1; $i <= count($this->rates); $i++) {
			$params .= '&r'.$i.'='.$this->rates[$i - 1]['number'];
		}
		
		$availability = $tourcms->check_tour_availability($params, $this->tour_id, $channel_id);
		$error = strip_tags($availability->error->asXML());
		if ($error == 'OK') {
			return $availability;
		} else {
			echo $this->return_message($this->no_availabilities_error, $error); 
			exit;
		}		
	}
	
	private function save_order() {
		$this->helper->save_engine($this);
	}

	private function get_user_id() {
		return $this->helper->get_user_id();
	}
	
	private function get_checkout_url() {
		return $this->helper->get_checkout_url($this->user_id);
	}
	
	private function get_debug_mode_config() {
		return $this->helper->get_debug_mode_config();
	}	

}
