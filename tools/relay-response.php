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

$audit_trail_message = $helper->get_setting('audit_trail_message');
$tourcms_payment_error = $helper->get_setting('tourcms_payment_error');
$authorize_net_failed_payment = $helper->get_setting('authorize_net_failed_payment');
$authorize_net_transaction_error = $helper->get_setting('authorize_net_transaction_error');

if ($test_mode) {
	$api_login = $helper->get_setting('authorize_net_api_login_test');
	$transaction_key = $helper->get_setting('authorize_net_transaction_key_test');
	$md5_setting = $helper->get_setting('authorize_net_md5_setting_test');
} else {
	$api_login = $helper->get_setting('authorize_net_api_login');
	$transaction_key = $helper->get_setting('authorize_net_transaction_key');
	$md5_setting = $helper->get_setting('authorize_net_md5_setting');
}

// Query the TourCMS API, upgrading the booking from temporary to live
$response = new AuthorizeNetSIM($api_login, $md5_setting); 
?>

<html>
	<head>
		<script language="javascript">
		<?php
		//verify this authorize.net by checking the md5 settings
		if ($response->isAuthorizeNet()) {
			//check response approval
			if ($response->approved) {

				$engine = $helper->load_engine($user_id);
				$engine->order_completed = true;

				// Build the XML to post to TourCMS
				$booking = new SimpleXMLElement('<booking />');
				$booking->addChild('booking_id', $booking_id);
				 
				// Query the TourCMS API, upgrading the booking from temporary to live
				$result = $tourcms->commit_new_booking($booking, $channel_id);
				$error = strip_tags($result->error->asXML());
				

				if ($error == 'OK') { 
					$payment = new SimpleXMLElement('<payment />');
					$payment->addChild('booking_id', $booking_id);
					$payment->addChild('payment_value', $response->amount); 
					$payment->addChild('payment_reference', $x_trans_id); 
					$payment->addChild('payment_type', 'Credit Card');
					$result = $tourcms->create_payment($payment, $channel_id);
					?>
					
					window.location.replace("<?php echo $redirect_url.'?user_id='.$user_id.'&booking_id='.$booking_id; ?>");<?php
				} else { 
					
					$payment = new SimpleXMLElement('<payment />');
					$payment->addChild('booking_id', $booking_id);
					$payment->addChild('audit_trail_note', $audit_trail_message);
					$result = $tourcms->log_failed_payment($payment, $channel_id); 
		
					?>
					alert('<?php echo $tourcms_payment_error; ?>'); 
					window.location.replace("<?php echo get_site_url(); ?>");
					<?php
				}
			} else { ?>
				//var_dump($response);
				alert('<?php echo $authorize_net_transaction_error; ?>: Response(<?php echo $response->response_reason_code ; ?>) <?php echo $response->response_reason_text ?>'); 
				window.location.replace("<?php echo $_SERVER['HTTP_REFERER']; ?>");
				
			<?php } ?>
			
		<?php } else { ?>
			<?php //echo 'MD5 Setting: '.$md5_setting; ?>
			<?php //var_dump($response); ?>
			
			alert('Are you sure you want to do this?'); 
			window.location.replace="<?php echo get_site_url(); ?>";
					
		<?php } ?>

		
    	</script>
	</head>
</html>