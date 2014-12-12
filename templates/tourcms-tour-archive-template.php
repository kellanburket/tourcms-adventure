<?php /** Template Name: TourCMS Archive Template **/ ?>
<?php 

/*
$tourcms = new TourCMS();
$channel_id = SiteConfig::get("channel_id");

$today = "Y-m-d";
$today = date($today);
list($year, $month, $day) = sscanf($today, "%d-%d-%d");

if ($month != 12) {
	$month++;
} else {
	$month = 1;
	$year++;	
}

$month = ($month < 10) ? '0'.$month : $month;
$day = ($day < 10) ? '0'.$day : $day;

$end_date = $year.'-'.$month.'-'.$day;
$args = 'between_date_start='.$today.'&between_date_end='.$end_date;
$data = $tourcms->search_tours($args, $channel_id);
$tours = $data->tour;
*/
$tours = fetch_tours_data(false, 24);
$order = json_decode(get_option('tourcms_order'), true);
?>

<?php get_header(); ?>
<h1 class="tour-archive-h1">Available Tours</h1>	
	<?php //print_r($tours); ?>        
	<?php foreach($order['visible'] as $tour_id) { ?>
    <div class="tour-archive-wrapper">		
        <h3 class="tour-archive-h3"><a href="<?php echo $tours[$tour_id]['url']; ?>"><?php echo $tours[$tour_id]['tour_name']; ?></a></h3>
        <a href="<?php echo $tours[$tour_id]['url']; ?>">
        	<div class="tour-archive-thumbnail">
            	<img src="<?php echo $tours[$tour_id]['image']; ?>" class="tour-archive-image">
			</div>
        </a>
		<div class="tour-archive-description">
            <p><?php echo $tours[$tour_id]['description']; ?></p>
            <a href="<?php echo $tours[$tour_id]['url']; ?>"><button class="tour-archive-book-now">Book Now</button></a>	
        </div>
	</div>
	<?php } ?>

<?php get_footer(); ?>
