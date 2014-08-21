<?php
define('WP_USE_THEMES', false);

global $wp, $wp_query, $wp_the_query, $wp_rewrite, $wp_did_header;
require(dirname(__FILE__).'/../../../../wp-load.php');
require_once(dirname(__FILE__).'/../tourcms_adventure.php');
require_once(dirname(__FILE__).'/../tools/tourcms-toolbox.php');
require_once(dirname(__FILE__).'/../lib/anet_php_sdk/AuthorizeNet.php'); // The SDK
require_once(dirname(__FILE__)."/../lib/tourcms/config.php");

extract($_POST);

$tourcms = load_tourcms();
$channel_id = SiteConfig::get("channel_id");	

$user_id = $_GET['user_id'];
$booking_id = $_GET['booking_id'];

$booking = $tourcms->show_booking($booking_id, $channel_id);
$booking = $booking->booking;
$options = array('tourcms_receipt_page_head', 'tourcms_receipt_page_text', 'tourcms_receipt_page_note');

foreach ($options as $option) {
	add_filter('option_'.$option, function($content) use ($booking) {
		
		$date = $booking->start_date;
		$name = $booking->customers->customer[0]->customer_name;
		$tour = $booking->booking_name;
		$total_customers = $booking->customer_count;
		
		$shortcode = array('/\[start_date\]/i', '/\[tour_name\]/i', '/\[customer_count\]/i', '/\[customer_name\]/i');
		$replacement_text = array($date, $tour, $total_customers, $name);
		
		$new_content = preg_replace($shortcode, $replacement_text, $content); 	
		
		return $new_content;
		
	});
}


get_header();
?>
<style>

</style>
<div id="booking-confirmed">
	<h1><?php echo get_option('tourcms_receipt_page_head'); ?></h1>
	<h4><?php echo get_option('tourcms_receipt_page_text'); ?></h4>
    <p><?php echo get_option('tourcms_receipt_page_note'); ?></p>
</div>

<br><br>Please DO NOT hit your browsers back button or your card may be charged again.<br><br>
 
<style>
  .FullRowCell a:link { color:#FFFFFF; }
  .FullRowCell a:visited { color:#FFFFFF; }
</style>
 
<!--begin order script from TT system-->
<script type="text/javascript" src="https://www.prideofmaui.com/mbox/mbox.js"></script>
<div id="tt_reservationid" style="visibility:hidden">[reservationid]</div>
<div id="tt_pax" style="visibility:hidden">[pax]</div>
<div id="tt_activityid" style="visibility:hidden">[activityid]</div>
<div id="tt_salesprice" style="visibility:hidden">[salesprice]</div>
  <div class="mboxDefault">
  </div>
 
<script type="text/javascript">
//These values are required to be collected from the rendered page
//Please do not edit
var orderIdNumber = document.getElementById("tt_reservationid").innerHTML;
var paxID = document.getElementById("tt_pax").innerHTML;
var activityID = document.getElementById("tt_activityid").innerHTML;
var salesPrice = document.getElementById("tt_salesprice").innerHTML;
 
//Use below if timestamp is needed
//var timeStamp = Math.round((new Date()).getTime() / 1000);
 
mboxCreate('orderConfirmPage',
'productPurchasedId='+paxID+'',
'orderId='+orderIdNumber+'',
'orderTotal='+salesPrice+'');
</script>
<!--end order script from TT system-->
 
<!-- Google Code for sale confirmation Conversion Page -->
<script type="text/javascript">
<!--
var google_conversion_id = 1064772368;
var google_conversion_language = "en_US";
var google_conversion_format = "3";
var google_conversion_color = "ffffff";
var google_conversion_label = "HcAGCPCmRRCQxtz7Aw";
if (1.0) {
  var google_conversion_value = 1.0;
}
//-->
</script>
<script type="text/javascript" src="https://www.googleadservices.com/pagead/conversion.js">
</script>
<noscript>
<div style="display:inline;">
<img height="1" width="1" style="border-style:none;" alt="" src="https://www.googleadservices.com/pagead/conversion/1064772368/?value=1.0&label=HcAGCPCmRRCQxtz7Aw&guid=ON&script=0"/>
</div>
</noscript>
 
<SCRIPT>
microsoft_adcenterconversion_domainid = 311866;
microsoft_adcenterconversion_cp = 5050;
microsoft_adcenterconversionparams = new Array();
microsoft_adcenterconversionparams[0] = "dedup=1";
</SCRIPT>
<SCRIPT SRC="https://0.r.msn.com/scripts/microsoft_adcenterconversion.js"></SCRIPT>
<NOSCRIPT><IMG width=1 height=1 SRC="https://311866.r.msn.com/?type=1&cp=1&dedup=1"/></NOSCRIPT>
 
<!-- Paste this code just above the closing </body> of your conversion page. The tag will record a conversion every time this page is loaded. Optional 'sku' and 'value' fields are described in the Help Center. -->
<script src="//ah8.facebook.com/js/conversions/tracking.js"></script><script type="text/javascript">
try {
  FB.Insights.impression({
     'id' : 6002538201802,
    'h' : '29ceac3c5c',
     'value' : 1 // you can change this dynamically
  });
} catch (e) {}
</script>
 
<!-- Google Code for Sale Conversion Page -->
<script type="text/javascript">
/* <![CDATA[ */
var google_conversion_id = 1072433990;
var google_conversion_language = "en";
var google_conversion_format = "3";
var google_conversion_color = "ffffff";
var google_conversion_label = "87CeCPK0jQMQxpaw_wM";
var google_conversion_value = 1;
/* ]]> */
</script>
<script type="text/javascript" src="http://www.googleadservices.com/pagead/conversion.js">
</script>
<noscript>
<div style="display:inline;">
<img height="1" width="1" style="border-style:none;" alt="" src="http://www.googleadservices.com/pagead/conversion/1072433990/?value=1&label=87CeCPK0jQMQxpaw_wM&guid=ON&script=0"/>
</div>
</noscript>
 
<script type="text/javascript">
<!--//
  syndConversionType = '101';
  syndAccountID = '2448398';
  syndDomain = document.location.protocol + '//r.bid4keywords.com';
  syndFunnelValue = '100';
  syndDollarValue = '1';
  syndConversionTag = 'conversion';
  document.writeln(unescape("%3Cscript src='" + syndDomain + "/scripts/convTracking.js'%3E%3C/script%3E"));
//-->
</script>
 
<script type="text/javascript">
var fb_param = {};
fb_param.pixel_id = '6006664762402';
fb_param.value = '0.00';
(function(){
  var fpw = document.createElement('script');
  fpw.async = true;
  fpw.src = (location.protocol=='http:'?'http':'https')+'://connect.facebook.net/en_US/fp.js';
  var ref = document.getElementsByTagName('script')[0];
  ref.parentNode.insertBefore(fpw, ref);
})();
</script>
<noscript><img height="1" width="1" alt="" style="display:none" src="https://www.facebook.com/offsite_event.php?id=6006664762402&amp;value=0" /></noscript>
 
<!-- Begin Adobe Marketing Cloud Tag Management code -->
<script type="text/javascript">
//<![CDATA[
var amc=amc||{};if(!amc.on){amc.on=amc.call=function(){}};
document.write("<scr"+"ipt type=\"text/javascript\" src=\"//www.adobetag.com/d1/v2/ZDEtcHJpZGVvZm1hdWktMTM4NzctMjY2Ni0=/amc.js\"></sc"+"ript>");
//]]>
</script>
<script type="text/javascript">
//<![CDATA[
if(s){
          s.pageName = "dev | A3H | Booking Confirmation"
          s.server = ""
          s.channel = "dev | A3H | Reservation System"
        s.events="purchase,event11"
        s.products="Activities; "+activityID+"; 1; "+salesPrice+""
        s.purchaseID=""+orderIdNumber+""
          s.t()
}
//]]>
</script>
<!-- End Adobe Marketing Cloud Tag Management code -->


<?php get_footer(); ?>