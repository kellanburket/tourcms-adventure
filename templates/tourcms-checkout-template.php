<?php
/*
Template Name: Checkout
*/
get_header(); 

if (isset($_REQUEST['_tourcms_sidebar_nonce'])) {
	$nonce = $_REQUEST['_tourcms_sidebar_nonce'];
} elseif (isset($_REQUEST['_tourcms_footer_nonce'])) {
	$nonce = $_REQUEST['_tourcms_footer_nonce'];
}

if (!wp_verify_nonce($nonce, 'tourcms_checkout')) {
	wp_redirect(site_url());
}

$user_id = $_GET['id'];
$helper = get_tourcms_helper();
$engine = $helper->load_engine($user_id);

if ($engine->order_completed == true) {
	wp_redirect(site_url());
}


$referer = $_SERVER["HTTP_REFERER"];
$test_mode = $helper->get_authorize_net_test_mode_config();

if ($test_mode) {
	$post_url = $helper->get_setting('authorize_net_url_test');
	$api_login = $helper->get_setting('authorize_net_api_login_test');
	$transaction_key = $helper->get_setting('authorize_net_transaction_key_test');
	$md5_setting = $helper->get_setting('authorize_net_md5_setting_test');
	$prefill = true;
} else {
	$post_url = $helper->get_setting('authorize_net_url');
	$api_login = $helper->get_setting('authorize_net_api_login');
	$transaction_key = $helper->get_setting('authorize_net_transaction_key');
	$md5_setting = $helper->get_setting('authorize_net_md5_setting');
	$prefill = false;
}

$states = get_us_states();

//$xml = $helper->get_booking_info($user_id);
$totals_string = stripslashes($engine->totals_string); //stripslashes($xml['totals_string']);
$booking_id = $engine->booking_id;
$tour_date = $engine->tour_date; //$xml['tour_date'];
$tour_name = $engine->tour_name; //$xml['tour_name'];


$year = intval(date("Y"));
$utc_timestamp = time();
$amount = $engine->total_amount;


$total_guests = $engine->total_customers;
$sales_tax = $engine->sales_tax;

$booking_fee_string = get_booking_fee_string($engine->booking_fee, $total_guests, $sales_tax);
$sales_tax_string = get_tax_string($amount, $sales_tax);

$relay_response_url = $helper->get_relay_response_url();

$fingerprint = hash_hmac("md5", $api_login . "^" . $booking_id . "^" . $utc_timestamp . "^" . $amount . "^", $transaction_key); 

$sim = new AuthorizeNetSIM_Form(
	array(
	'x_amount'        	=> $amount,
	'x_fp_sequence'   	=> $booking_id,
	'x_fp_hash'       	=> $fingerprint,
	'x_fp_timestamp'  	=> $utc_timestamp,
	'x_relay_response'	=> "TRUE",
	'x_relay_url'     	=> $relay_response_url,
	'x_login'         	=> $api_login
	)
);

$hidden_fields = $sim->getHiddenFieldString();


?>
<h1>Checkout</h1>

<form method="post" action="<?php echo $post_url; ?>" id="authorize-payment-form">
	<?php wp_nonce_field(); ?>
	<?php echo $hidden_fields; ?>
    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>" id="user_id">
    <input type="hidden" name="referring_url" value="<?php echo $referer; ?>" id="referring_url">
	<input type="hidden" name="test_mode" value="<?php echo $test_mode; ?>">
    <div id="checkout-wrap">
        <div id="checkout-main">
            <div id="checkout-div-1" class="checkout-div">
                <div class="checkout-h3-wrap">
                    <h3>Customer Details</h3>
                </div>
                 <label class="checkout-label">Title</label>
                <select class="checkout-select" id="title">
                    <option value="mr">Mr.</option>
                    <option value="mrs">Mrs.</option>
                    <option value="ms">Ms.</option>        
                    <option value="miss">Miss</option>
                </select>
                
                <label class="checkout-label">First Name</label>
                <input type="text" name="x_first_name" id="firstname" class="checkout-field" value="<?php echo $prefill ? 'Test' : ''; ?>"> 
            
                <label class="checkout-label">Last Name</label>
                <input type="text" name="x_last_name" id="surname" class="checkout-field" value="<?php echo $prefill ? 'User' : ''; ?>"> 
            
                <label class="checkout-label">E-Mail</label>
                <input type="text" name="x_email" id="email" class="checkout-field" value="<?php echo $prefill ? 'kellan@taglinegroup.com' : ''; ?>"> 
            
                <label class="checkout-label">Home Phone</label>
                <input type="text" name="x_phone" id="tel_home" class="checkout-field" value="<?php echo $prefill ? '425-555-1212' : ''; ?>"> 
            
                <label class="checkout-label">Mobile Phone</label>
                <input type="text" name="x_phone" id="tel_mobile" class="checkout-field" value="<?php echo $prefill ? '425-555-1212' : ''; ?>"> 
            
            </div>
            
            <!-- <div id="checkout-div-2" class="checkout-div">
                <div class="checkout-h3-wrap">
                    <h3>Other Travelers</h3>
                </div>        
                <?php 
				/*
				$i = 0;
            
                for( $customers->next(); $customers->valid(); $customers->next() ) {
                    $i++;
                    foreach($customers->getChildren() as $name => $data) {
                        if ($name == 'agecat') { ?>
                            <h4><?php echo $i.'.'.(($data == 'a') ? 'Adult Traveler' : 'Child Traveler'); ?></h4>
                            <label class="checkout-label">First Name</label>
                            <input type="text" id="firstname-<?php echo $i; ?>" class="checkout-field" value="<?php echo $prefill ? 'Kid' : ''; ?>"> 
                        
                            <label class="checkout-label">Last Name</label>
                            <input type="text" id="surname-<?php echo $i; ?>" class="checkout-field" value="<?php echo $prefill ? 'A' : ''; ?>"> 
            
                        <?php }
                    }
                } */?>
            </div> -->
            
            <div id="checkout-div-3" class="checkout-div">

                <div class="checkout-h3-wrap">
                    <h3>Billing Address</h3>
                </div>
                    
                <label class="checkout-label">Address</label>
                <input type="text" name="x_address" id="address" class="checkout-field" value="<?php echo $prefill ? '1 Main Street' : ''; ?>"> 
            
                <label class="checkout-label">City</label>
                <input type="text" name="x_city" id="city" class="checkout-field" value="<?php echo $prefill ? 'Bellvue' : ''; ?>"> 
            
                <label for "x_state" class="checkout-label">State</label>
                <select name="x_state" id="county" class="checkout-field" value="<?php echo $prefill ? 'WA' : ''; ?>"> 
                	<?php if(is_array($states)) { ?>
                		<?php foreach($states as $s_abbr=>$s_name) { ?>
                			<option value="<?php echo $s_name; ?>"><?php echo $s_abbr; ?></option>	
						<?php } ?>
                	<?php } ?>
                </select>
            
                <label class="checkout-label">Zip Code</label>
                <input type="text" name="x_zip" id="postcode" class="checkout-field" value="<?php echo $prefill ? '98004' : ''; ?>"> 
            
                <label class="checkout-label">Country</label>
                <input type="text" name="x_country" id="country" class="checkout-field" value="<?php echo $prefill ? 'US' : ''; ?>"> 
            </div>
            
            <div id="checkout-div-4" class="checkout-div">
                <div class="checkout-h3-wrap">
                    <h3>Payment Information</h3>
                </div>
                <div id="credit-cards">
                    <img class="cc-images" src="<?php echo TOURCMS_URL.'/img/visa.png' ;?>">
                    <img class="cc-images" src="<?php echo TOURCMS_URL.'/img/discover.png' ;?>">
                    <img class="cc-images" src="<?php echo TOURCMS_URL.'/img/mastercard.png' ;?>">
                    <img class="cc-images" src="<?php echo TOURCMS_URL.'/img/american-express.png' ;?>">
                </div>
            
                <label class="checkout-label">Credit Card Number</label>
                <input type="text" name="x_card_num" id="cc_number" class="checkout-field" value="<?php echo $prefill ? '4111111111111111' : ''; ?>"> 
         
                <div class="checkout-exp-block" id="checkout-exp-head">
                    <label class="checkout-label checkout-label-exp">Month</label>
                    <label class="checkout-label checkout-label-exp">Year</label>
                     <label class="checkout-label checkout-label-exp">Expiration Date</label>
                </div>
                <div class="checkout-exp-block" id="checkout-exp-body">
                    <select id="cc_month" class="checkout-select checkout-select-exp">
                    <?php for ($i = 1; $i <= 12; $i++) { ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php } ?>
                    </select>            
                    <select id="cc_year" class="checkout-select checkout-select-exp" value="<?php echo ($year + 1) ?>">
                    <?php for ($i = $year; $i <= $year + 30; $i++) { ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php } ?>
                    </select>
                    <input type="text" class="checkout-field checkout-field-exp" name="x_exp_date" readonly value="<?php echo ($prefill) ? '04/17' : ''; ?>"></input>
				</div>

                <label class="checkout-label">Security Code</label>
                <input type="text" id="cc_code" class="checkout-field" name="x_card_code" value="<?php echo $prefill ? '782' : ''; ?>"> 
                
            </div>
            
       
        </div>
        
        <div id="checkout-sidebar">
            <div id="checkout-sidebar-div-1" class="checkout-div checkout-sidebar-div">
                <div id="checkout-sidebar-top">
                    <?php echo $tour_name; ?>			
                </div>
                <div id="checkout-sidebar-bottom">
                    <?php echo $totals_string; ?>
                </div>
            </div>
        </div>
    </div>
     <div id="checkout-wrap-2" class="checkout-div">
            <h3>Confirm Order and Pay</h3>
      		<p id="checkout-activity-2"><?php echo $tour_name; ?></p>
            <p id="checkout-date-2"><?php echo $tour_date; ?></p>
            <table id="checkout-table">
                <thead id="checkout-thead">
                    <tr class="checkout-thead-tr checkout-tr">
                        <th class="checkout-th checkout-td checkout-row-1">Activity</th>
                        <th class="checkout-th checkout-td checkout-row-2">Date</th>
                        <th class="checkout-th checkout-td checkout-row-3">Total</th>
                    </tr>
                </thead>
      
                <tfoot></tfoot>
      
                <tbody id="checkout-tbody">
                    <tr class="checkout-tbody-tr checkout-tr">
                        <td class="checkout-tbody-td checkout-td checkout-row-1" id="checkout-activity"><?php echo $tour_name; ?></td>
                        <td class="checkout-tbody-td checkout-td checkout-row-2" id="checkout-date"><?php echo $tour_date; ?></td>
                        <td class="checkout-tbody-td checkout-td checkout-row-3" id="checkout-subtotal"><?php echo $totals_string; ?></td>
                    </tr>
                    
                    <tr class="checkout-tbody-tr checkout-tr">
                        <td class="checkout-tbody-td checkout-td checkout-row-1 blank-td"></td>
                        <td class="checkout-tbody-td checkout-td checkout-row-2 blank-td"></td>
                        <td class="checkout-tbody-td checkout-td checkout-row-3" id="checkout-total">
                        	<ul class="sb-booking-ul">
                            	<li class="sb-booking-li"></li>
                            </ul>
							<ul class="sb-total-ul">
                                <li class="sb-total-li"><?php echo $booking_fee_string; ?></li>
                                <li class="sb-total-li"><?php echo $sales_tax_string; ?></li>
                                <li class="sb-total-li black-border-top">
                                    <span class="total-cost-label float-left">Total after taxes and fees:</span>
                                    <span id="total-cost-total">$<?php echo $amount; ?></span>
                                </li>
							</ul>
                        </td>
                    </tr>
                    <tr class="checkout-tbody-tr checkout-tr">
                    
                        <td class="checkout-tbody-td checkout-td checkout-row-1 blank-td"></td>
                        <td class="checkout-tbody-td checkout-td checkout-row-2 blank-td"></td>
                        <td class="checkout-tbody-td checkout-td checkout-row-3 blank-td" id="checkout-button">
                           	<div class="submit-div"> 
                                <button id="checkout-continue">
                                	<p id="checkout-continue-text">Continue</p>
                                    <div id="sb-tour-spinning-loader"></div>
                                </button>
                      		</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
	</form>