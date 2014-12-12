<?php
	
function simple_tidy_url( $name ) {
	return str_replace("%20", "-", rawurlencode(strtolower(str_replace("/", "-and-", $name))));
}

// Get a product url
function get_product_url ($name, $channel_id, $tour_id, $location, $type = "tour") {
	return "tours/".simple_tidy_url($name)."_".$tour_id."/";
}

function asterisk2Ul( $text ) {
	$text = preg_replace("/\*+(.*)?/i","<ul><li>$1</li></ul>",$text);
	$text = preg_replace("/(\<\/ul\>\n(.*)\<ul\>*)+/","",$text);
	return $text;
}

function format_date($selected_year, $selected_month, $selected_day) {

return $selected_year
	.'-'
	.(intval($selected_month) < 10 ? '0'.$selected_month :  $selected_month)
	.'-'
	.(intval($selected_day) < 10 ? '0'.$selected_day : $selected_day);
}