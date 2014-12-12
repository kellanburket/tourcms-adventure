<html>
<head>
	<style>
		p {
			font-family: Helvetica, sans-serif;
		}
		.red { color: red; }
		.green { color: green; }
		.blue { color: blue; }		
	</style>	
</head>
<body>

<?php
/*
define('WP_USE_THEMES', false);
global $wp, $wp_query, $wp_the_query, $wp_rewrite, $wp_did_header, $wpdb;
require(dirname(__FILE__).'/../../../../wp-load.php');
require_once(dirname(__FILE__).'/../tourcms_adventure.php');
require_once(dirname(__FILE__).'/tourcms-toolbox.php');
require_once(dirname(__FILE__).'/../lib/anet_php_sdk/AuthorizeNet.php'); // The SDK
require_once(dirname(__FILE__)."/../lib/tourcms/config.php");
require_once(dirname(__FILE__).'/../booking-engine/booking-engine.php');


$wpdb->query('select * from wp_tourcms_bookings');
$data = array();

foreach ($wpdb->last_result as $result) {
	$engine = unserialize($result->engine);
	$data[] = array(
		"date" => ($result->tour_date) ? $result->tour_date : $engine->tour_date, 
		"amount" => ($result->total) ? $result->total : $engine->total_amount, 
		"tour" => $engine->tour_name,
		"booking_id" => $engine->final_booking_id,
		"temp_booking_id" => $engine->temp_booking_id,
		"order_completed" => ($result->order_completed) ? $result->order_completed : $engine->order_completed,
		"ip"=> ($result->client_ip) ? $result->client_ip : "",
		"name"=> ($result->client_name) ? $result->client_name : ""
	);


	//echo htmlspecialchars($engine->booking);
	//echo "<br><br>";
}
usort($data, function($a, $b) {
	if ($a["booking_id"] < $b["booking_id"]) 
		return 1;
	else if ($a["booking_id"] > $b["booking_id"]) 
		return -1;
	else { 
		return 0;	
	}
		
});

foreach ($data as $d) {
	$clz = "red";
	if ($d['order_completed']) {
		$clz =  "green";
	} else if ($d['temp_booking_id'] && !$d['final_booking_id']) {
		$clz = "blue";
	}
	
	if ($d['booking_id']) {
		echo "<p class='{$clz}'>{$d['booking_id']}/{$d['temp_booking_id']}: ({$d['name']}, {$d['ip']}) {$d['tour']} on {$d['date']} for {$d['amount']}</p>";
	} else {
		echo "<p class='{$clz}>{$d['temp_booking_id']}: ({$d['name']}, {$d['ip']}) {$d['tour']} on {$d['date']} for {$d['amount']}</p>";
	}

}
*/
?>
</body>
</html>