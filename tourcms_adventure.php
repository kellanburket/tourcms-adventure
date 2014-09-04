<?php
/*
		Plugin Name: Choose Your Adventure
		Description: Custom Pride of Maui Tour CMS Widget
		Author: TagLine
*/
global $wpdb;
define("PLATFORM", "wordpress");
define("MOBILE_TOUR_PAGE", "tourcms_mobile");
define("TOUR_PAGE", "tourcms");
define("TOUR_CHECKOUT", "tourcms_checkout");
define('TOURCMS_URL', plugins_url().'/tourcms-adventure');
define('TOURCMS_ROOT', WP_PLUGIN_DIR.'/tourcms-adventure');
define('TOURCMS_HELPER', TOURCMS_ROOT.'/helpers/'.PLATFORM.'_tourcms_helper.php');
define('TOURCMS_BOOKING_TABLE', $wpdb->prefix.'tourcms_bookings');

require_once('tools/tourcms-toolbox.php');
require_once("lib/tourcms/config.php");
require_once("admin/tourcms-admin-ui.php");

//load widgets
add_action('widgets_init', function() {
	require_once(dirname(__FILE__)."/widgets/sidebar-widget/sidebar-widget.php");
	require_once(dirname(__FILE__)."/widgets/booking-box-widget/booking-box-widget.php");
	register_widget('TourcmsSidebarWidget');	
	register_widget('TourcmsBookingBoxWidget');	
});

add_action('wp_enqueue_scripts', function() {
	global $post;
	$plugin_url = plugins_url().'/tourcms-adventure';

	//wp_enqueue_script("firebug-lite", "https://getfirebug.com/firebug-lite.js");

	wp_register_script("sprintf", $plugin_url.'/js/sprintf.js', array(), false, false);
	wp_enqueue_script("sprintf");
	wp_enqueue_style('fontawesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css');
	wp_register_script("tourcms_adventure_js", $plugin_url.'/js/adventure.js', array('jquery', 'sprintf'), false, false);	
	wp_enqueue_script("tourcms_adventure_js");
	
	wp_register_script("tourcms_modal", $plugin_url.'/js/modal.js', array('jquery', 'tourcms_adventure_js'), false, false);	
	wp_enqueue_script("tourcms_modal");
			
	//register fonts
	wp_register_style('openSans', 'http://fonts.googleapis.com/css?family=Open+Sans:400italic,400,600,800,700');
    wp_enqueue_style( 'openSans');
	wp_register_style('coustard', 'http://fonts.googleapis.com/css?family=Coustard:400,900');
    wp_enqueue_style( 'coustard');
	
	wp_enqueue_style("choose_your_adventure_css", $plugin_url."/css/adventure-style.css", null, false, false);
	wp_register_script('rate_calculation', $plugin_url.'/js/rate-calculation.js', array('jquery', 'sprintf', 'tourcms_adventure_js'), false, false);
	wp_enqueue_script("rate_calculation");

	wp_register_script('tourcms_calendar', $plugin_url.'/js/calendar.js', array('jquery', 'sprintf', 'tourcms_adventure_js', 'rate_calculation'), false, false);
	wp_enqueue_script("tourcms_calendar");

	wp_register_script('tourcms_booking_box', $plugin_url.'/js/booking-box.js', array('jquery', 'tourcms_calendar', 'tourcms_adventure_js'), false, false);
	wp_enqueue_script("tourcms_booking_box");

	wp_register_script('tourcms_switchbox', $plugin_url.'/js/switchbox-tabs.js', array('jquery', 'tourcms_adventure_js'), false, false);
	wp_enqueue_script("tourcms_switchbox");

	
	if(get_post_type() == MOBILE_TOUR_PAGE || get_post_type() == TOUR_PAGE) {
		//TODO--move this back to main page
		
		
	}
	
	if(get_post_type() == MOBILE_TOUR_PAGE) {
		wp_enqueue_style("choose_your_adventure_mobile_css", $plugin_url."/css/tablet-style.css", null, false, false);
		wp_enqueue_style("choose_your_adventure_tablet_css", $plugin_url."/css/mobile-style.css", null, false, false);
	}

	if (get_post_type($post->ID) == TOUR_CHECKOUT) {
		wp_register_script('checkout-js', $plugin_url.'/js/checkout.js', 'jquery');
		wp_enqueue_script("checkout-js");

		wp_enqueue_style("tourcms_checkout_css", $plugin_url."/css/checkout.css", null, false, false);
		require_once(TOURCMS_ROOT.'/booking-engine/booking-engine.php');
		require_once('lib/anet_php_sdk/AuthorizeNet.php');
	}
	
	wp_localize_script("tourcms_adventure_js", "ajax", array( 
		'url' => admin_url( 'admin-ajax.php' ), 
		'action' => 'adventure_ajax_callback', 
		'siteurl' => get_site_url(), 
		'pluginurl' => plugins_url().'/tourcms-adventure/')
	);
	
	wp_localize_script("rate_calculation", "tour_pricing", array( 
		'sales_tax'=> get_option('tourcms_sales_tax'))
	);
	
	wp_localize_script("rate_calculation", "promotions", array(
		array('name' => 'Internet Discount', 'type' => 'REDUCTION', 'value' => 10)
	));

	wp_localize_script("rate_calculation", "legal_promotions", array(
		array('name' => 'Discovery Discount', 'code'=>'DISCOVERY7', 'type' => 'PERCENT', 'value' => 5)
	));
	
	wp_localize_script("tourcms_adventure_js", "colors", array(
		'tablet' => array(
			'tab_inactive'=>array('backgroundColor' => '#ffffff', 'color' => '#777777'),
			'tab_active'=>array('backgroundColor' => '#ffffff', 'color' => '#46B3DD')),
		'mobile' => array(
			'tab_inactive'=>array('backgroundColor' => '#ffffff', 'color' => '#777777'),
			'tab_active'=>array('backgroundColor' => '#ffffff', 'color' => '#46B3DD')),	
		'calendar' => array(
			'standard'=>array('backgroundColor' => '#ffffff', 'color' => '#1c94c4'),
			'selected'=>array('backgroundColor' => '#1D9FE6', 'color' => '#ffffff')
		)
	));	

	wp_localize_script("tourcms_adventure_js", "live_calendar", array(
		'footer' => array(
			'calendar'=>array('id'=>'pop-up-calendar'),
			'table'=>array('id' => 'datepicker-table'),
			'weekday'=>array('class' => 'datepicker-weekday'),
			'day'=>array('class' => 'datepicker-td'),
			'back_one'=>array('id' => 'datepicker-back-one'),
			'forward_one'=>array('id' => 'datepicker-forward-one'),
			'month'=>array('id' => 'datepicker-month'),
			'guests_input'=>array('class' => 'datepicker-guests-input'),
			'submit'=>array('id' => 'datepicker-submit'),
			'date_field'=>array('id' => 'activity-date-field')

		), 'sidebar' => array(
			'calendar'=>array('id'=>'sb-tour-calendar'),
			'table'=>array('id' => 'tourcms-sidebar-table'),
			'weekday'=>array('class' => 'tourcms-sidebar-day'),
			'day'=>array('class' => 'tourcms-sidebar-td'),	
			'back_one'=>array('id' => 'sb-tour-back-one'),
			'forward_one'=>array('id' => 'sb-tour-forward-one'),
			'month'=>array('id' => 'sb-tour-month'),
			'guests_input'=>array('class' => 'sb-tour-guests-input'),
			'submit'=>array('id' => 'sb-submit'),
			'date_field'=>array('id' => 'sb-tour-activity-date-field')
		)
	));	
	
	wp_localize_script("tourcms_adventure_js", "errors", array(
		'access_error' => array('message'=>get_option('tourcms_access_error')),
		'server_error' => array('message'=>get_option('server_error')),
		'date_error' => array('message'=>get_option('invalid_date'))
	));	
	
});

//Add javascript callback
add_action('wp_ajax_nopriv_adventure_ajax_callback', 'adventure_ajax_callback');
add_action('wp_ajax_adventure_ajax_callback', 'adventure_ajax_callback');


register_activation_hook(__FILE__, function() {
	global $wpdb;
	$wpdb->query($wpdb->prepare('SHOW TABLES LIKE %s', TOURCMS_BOOKING_TABLE));  

	if (!$wpdb->last_result) {
		$sql = "CREATE TABLE ".TOURCMS_BOOKING_TABLE." (
		  id mediumint(9) NOT NULL AUTO_INCREMENT,
		  time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		  user_id tinytext NOT NULL,
		  engine mediumblob NOT NULL,
		  UNIQUE KEY id (id)
		);";
		require_once(ABSPATH.'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );	
	}	
});

function adventure_ajax_callback() {
	$tourcms_helper = get_tourcms_helper();
	//echo json_encode($_POST);
	//exit;

	call_user_func($_POST['callback'], $_POST);
}

$date = $booking->start_date;
$name = $booking->customers->customer[0]->customer_name;
$tour = $booking->booking_name;
$total_customers = $booking->customer_count;



?>