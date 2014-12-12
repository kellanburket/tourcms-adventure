<?php

define('WP_USE_THEMES', false);
global $wp, $wp_query, $wp_the_query, $wp_rewrite, $wp_did_header, $wpdb;	
require(dirname(__FILE__).'/../../../../wp-load.php');
require_once(dirname(__FILE__).'/../tourcms_adventure.php');

require_once('tourcms-toolbox.php');

$plugin_url = plugins_url().'/tourcms-adventure';

$errors = $wpdb->get_results("Select ane.message as authorize_net_message, wte.message as error_message, user_agent, ip_address, booking_id, wte.created_at as date FROM wp_tourcms_errors wte left join authorize_net_errors ane on wte.message = ane.error_id order by error_message");

echo "
<style>
	td {
		width: 20%;
	}
		
	tr:nth-child(even) {
		background: white;
	}
	
	tr:nth-child(odd) {
		background: #999999;
	}
</style>	
<table><tbody>";


$blacklist = array(
	"Customer Did Not Enter a Valid Date",
	"Customer Did Not Enter a Valid Number of Travelers.",
	"Please enter a first name!",
	"Please enter a telephone number!",
	"Please enter a zip code!",
	"Please enter a country!",
	"Please enter a city!",
	"Please enter an address!",
	"Please enter an email address!",
	"Please enter a last name!"
);

foreach($errors as $error) {
	$message = stripslashes($error->error_message);
	echo "<tr>";
	
	if ($error->authorize_net_message) {
		$message = $error->authorize_net_message;
	} elseif (json_decode($message)) {
		$message = json_decode($message, true);
	}
	
	$like = $os = $locale = $engine = $device = $moz = $platform = $wk = $khtml = $browser = "";

	//not working: 		"/(?<moz>.*?)\((compatible; (?<browser>.*?); (?<platform>.*?); (?<os>.*?); (?<engine>.*?)|(?<platform>.*?); (U; |)(?<os>.*?); ((?<locale>.*?); |)(?<device>.*?)|(?<platform>.*?); (?<os>.*?)|(?<platform>.*?))\)($| like (?<like>.*)$| (?<wk>.*?)(?<khtml>\(.*?\)) (?<browser>.*?) .*$)/"
	
	preg_match(
		"/(?<moz>.*?)\(((AOL.*?; .*?;|)(?<nt>Windows .*?); ((?<wow>.*?); |)(?<trident>.*?); (?<revision>.*?)|compatible; (?<ie>.*?); (?<windows>.*?); ((?<pcos>.*?); |)(?<engine>.*?)|(?<linux>.*?); (U; |)(?<linuxos>.*?); .*?|(?<mac>.*?); (?<macos>.*?)|(?<platform>.*?))\)($| like (?<like>.*)$| (?<wk>.*?)(?<khtml>\(.*?\)) (?<browser>.*)| .*? (?<firefox>.*))/", 
		$error->user_agent, 
		$matches
	);
			
	$pcbrowser = $firefox = $macos = $pcos = $linuxos = "";
	
	extract($matches);
		
	if ($ie) $browser = $ie;
	elseif ($firefox) $browser = $firefox;
	elseif ($trident) $browser = "IE : $trident";
			
	if ($macos) $platform = "$mac";
	elseif ($windows) $platform = "$windows";
	elseif ($linuxos) $platform = "$linux : $linuxos";
	elseif ($nt) $platform = $nt;
	
	/*
	list($moz, $platform, $wk, $khtml, $broswer, $version, $based_on) = 
		sscanf($error->user_agent, "%s (%s) %s (%s) %s/%s %s");
	*/




	if (is_array($message)) {
		
		$text = "<ul>";
		foreach($message as $k=>$m) {
			$text .= "<li><strong>$k</strong> : $m</li>";
		}	
		$text .= "</ul>";
		$message = $text;	
	}
	
	if (!in_array($message, $blacklist)) {
		echo "<td>{$message}</td>";	
		echo "<td>{$error->ip_address}</td>";
		echo "<td>{$error->date}</td>";
		echo "<td>$platform</td>";
		echo "<td>$browser</td>";	
		//echo "<td>{$error->user_agent}</td>";
		echo "</tr>";
	}
}

echo "</table></tbody>";


