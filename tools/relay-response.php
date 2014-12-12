<html>
<head>
<?php
define('WP_USE_THEMES', false);
global $wp, $wp_query, $wp_the_query, $wp_rewrite, $wp_did_header;
require(dirname(__FILE__).'/../../../../wp-load.php');
require_once(dirname(__FILE__).'/../tourcms_adventure.php');
require_once(dirname(__FILE__).'/tourcms-toolbox.php');
require_once(dirname(__FILE__).'/../lib/anet_php_sdk/AuthorizeNet.php'); // The SDK
require_once(dirname(__FILE__)."/../lib/tourcms/config.php");

extract($_POST);
$tourcms = load_tourcms();
$helper = get_tourcms_helper();
$channel_id = SiteConfig::get("channel_id");	

$redirect_url = TOURCMS_URL.'/templates/order-receipt.php';
$test_mode = $helper->get_authorize_net_test_mode_config();

/*
if ($test_mode) {
	echo "<h1>SERVER</h1>";
	foreach($_SERVER as $k=>$s) {
		echo "<p>[ $k ] $s</p>";
	}

	echo "<h1>POST</h1>";
	foreach($_POST as $k=>$p) {
		echo "<p>[ $k ] $p</p>";
	}
	exit;
}
*/

$audit_trail_message = $helper->get_setting('audit_trail_message');
$tourcms_payment_error = $helper->get_setting('tourcms_payment_error');
$authorize_net_failed_payment = $helper->get_setting('authorize_net_failed_payment');
$authorize_net_transaction_error = $helper->get_setting('authorize_net_transaction_error');
?>
		<script language="javascript">
		<?php
		if ($x_response_code == 1 && $x_response_reason_code == 1) {
			$engine = $helper->load_engine($user_id, $ip);
			$engine->order_completed = 1;
			$engine->client_name = $x_first_name . " " . $x_last_name; 
			$helper->save_engine($engine, $ip);
			// Build the XML to post to TourCMS
			$booking = new SimpleXMLElement('<booking />');
			$booking->addChild('booking_id', $booking_id);
			 
			// Query the TourCMS API, upgrading the booking from temporary to live
			$result = $tourcms->commit_new_booking($booking, $channel_id);
			$error = (string) $result->error;
			
			if ($error == 'OK') { 
				$payment = new SimpleXMLElement('<payment />');
				$payment->addChild('booking_id', $booking_id);
				$payment->addChild('payment_value', $x_amount); 
				$payment->addChild('payment_reference', $x_trans_id); 
				$payment->addChild('payment_type', 'Credit Card');
				$result = $tourcms->create_payment($payment, $channel_id);
				?>
				
				window.location.replace("<?php echo $redirect_url.'?user_id='.$user_id.'&booking_id='.$booking_id; ?>");<?php
			} else { 
				$helper->log_error("tourcms", $error, $booking_id);
				
				$payment = new SimpleXMLElement('<payment />');
				$payment->addChild('booking_id', $booking_id);
				$payment->addChild('audit_trail_note', $audit_trail_message);
				$result = $tourcms->log_failed_payment($payment, $channel_id); 
	
				?>
				alert('<?php echo $tourcms_payment_error; ?>'); 
				window.location.replace("<?php echo get_site_url(); ?>");
				<?php
			} 
		} else { 
			$helper->log_error("authorize.net", $x_response_reason_code + ": " + $x_response_reason_text, $booking_id);
			
			switch ($x_response_reason_code) {
				case(27):
					$msg = 'Please enter the billing address found on your credit card statement to continue.';
					$route = $_SERVER['HTTP_REFERER'];
					break;	
				default:
					$msg = 'Are you sure you want to do this?';
					$route = get_site_url();
			}
			
			
			?>
			
			alert("<?php echo $msg; ?>"); 
			window.location.replace("<?php echo $route; ?>");
					
	<?php } ?>
    </script>
</head>
</html>