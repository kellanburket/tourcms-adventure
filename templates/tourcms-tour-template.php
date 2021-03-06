<?php
/* 
Template Name: Content
*/
	$template_uri = get_template_directory_uri();
	$upload_dir = wp_upload_dir(); 
	$pagecustoms = getOptions();
	
	//Sidebar
	if (isset($pagecustoms["zeitgeist_activate_sidebar"])){$sideo = $pagecustoms['zeitgeist_sidebar_orientation'];}else{$sideo = "";}
	if (isset($pagecustoms["zeitgeist_activate_sidebar"])){$sidebar = $pagecustoms["zeitgeist_sidebar"];}else{$sidebar = "Page Sidebar";}
	
	//Pagetitle
	if(isset($pagecustoms['zeitgeist_activate_page_title'])){ $headline = "off";} else {$headline = "on";}
	if(isset($pagecustoms['zeitgeist_header_title']))$htitle = $pagecustoms['zeitgeist_header_title']; else $htitle=get_the_title();

	//Page Slider
	if(isset($pagecustoms["zeitgeist_activate_slider"])&&$pagecustoms["zeitgeist_activate_slider"]=="on") {
		$zeitgeist_slider = $pagecustoms["zeitgeist_header_slider"];
	}else{
		$zeitgeist_slider ="";
	}
	
	//Sidebar
	if(isset($pagecustoms["zeitgeist_activate_sidebar"])){
		if (isset($pagecustoms["zeitgeist_sidebar"])){$sidebar = $pagecustoms["zeitgeist_sidebar"];}else{$sidebar = "Blog Sidebar";}
		$sidebar_orientation = $pagecustoms["zeitgeist_sidebar_orientation"];
		$zeitgeist_activate_sidebar="on";
	} else { $zeitgeist_activate_sidebar="off"; }

	if (!function_exists('str_lreplace')) {
		function str_lreplace($search, $replace, $subject) {
			$pos = strrpos($subject, $search);

			if($pos !== false)
			{
				$subject = substr_replace($subject, $replace, $pos, strlen($search));
			}

			return $subject;
		}	
	}
	get_header(); 
?>
<style type="text/css">
	.vision_two_thirds {  }
	.one_third_lastcolumn { width: 33%; float: right; margin-right: 1.5%; }
	#sb-tour-widget-wrap { width: 100%; }
	.embed-container.prepended { display: none; }
	@media only screen and (max-width: 979px) {
		.dummyMargin { margin-right: 0 !important; }
		.vision_two_thirds,
		.one_third_lastcolumn { float: none; margin: 0; }
		.vision_two_thirds { width: 100%; }
		.one_third_lastcolumn { width: 70%; margin: 50px auto 0 auto; }
		
.single-tourcms #sb-tour-widget-wrap { margin-bottom: 30px; }
.single-tourcms .container .row .span12 .vision_two_thirds .embed-container.first { display: none; }
.embed-container.prepended { display: block; }	
	}
	@media only screen and (max-width: 640px) {
		.vision_two_thirds,
		.one_third_lastcolumn { float: none; margin: 0; }
		.vision_two_thirds { width: 100%; }
		.one_third_lastcolumn { width: 100%; margin: 30px auto 0 auto; }
	}
</style>
<script>
	jQuery(function ($)
	{
		var $vid = $(".single-tourcms .container .row .span12 .vision_two_thirds .embed-container:first").addClass("first").clone().addClass("prepended");
		$(".single-tourcms .container .row .span12:first > .subtitle").after($vid);
		// var $dummyMargin = $(".single-tourcms .container .row .span12 .vision_two_thirds .dummyMargin");
		// var $fChild = $dummyMargin.find(">:first-child");
		// if ($fChild.hasClass("jwplayer"))
		// {
			// $(window).load(function ()
			// {
				// var $vid = $fChild.addClass("custVideo first").clone().addClass("prepended");
				// $(".single-tourcms .container .row .span12:first > .subtitle").after($vid);	
			// });
		// }
		// else if ($fChild.hasClass("embed-container"))
		// {
			// var $vid = $fChild.addClass("custVideo first").clone().addClass("prepended");
			// $(".single-tourcms .container .row .span12:first > .subtitle").after($vid);	
		// }		
	});
</script>

		<!--
		########################################
			-	HOME SLIDER  - 
		########################################
		-->
		<div class="homesliderwrapper">
			<div class="homeslider">
				<?php // echo do_shortcode('[rev_slider '.$zeitgeist_slider.']'); ?>
				<?php include(get_template_directory().'/call-to-action-slider-button.php'); ?>
			</div>
		</div>
<?php global $post; ?>

<?php if ($headline!="off"){ ?>
	<!-- Page Title -->  
    <div class="row pagetitle">
    	<h1><?php echo $htitle;?></h1>
    </div>
<?php } ?>

<!-- Main Container -->
<div class="container">
	<input type="hidden" name="tour_id" value="<?php echo get_post_meta($post->ID, 'tour_id', true); ?>">

    <!-- Body -->
    <div class="row">
        
    <?php if(have_posts()) : 
        while(have_posts()) : the_post(); 
			$subtitle = get_post_meta(get_the_id(), 'subtitle', true); ?>	
        <div class="span12">
        
            <div style="text-align: center; margin-bottom: 50px;" align="center" class="subtitle">
            	<span style="font-size: x-large;" data-mce-mark="1"><b><?php echo $subtitle; ?></b></span>
            </div>
            <div class="one_third_lastcolumn">
				<?php
					global $post;
					ob_start();
					dynamic_sidebar('tour-sidebar');
					$sidebar = ob_get_clean();
					$tour_id = get_post_meta($post->ID, 'tour_id', true);
					if ($tour_id == 1 || $post->post_name == 'halloween-cruise') $sidebar = str_lreplace('</div>', '', $sidebar);
					_e($sidebar);
				?>
            </div>
            <div class="vision_two_thirds">
                <?php the_content(); ?>	
            </div>

     	</div>

    <?php endwhile;  //have_posts ?> 
    
    <?php else : ?>
        <div>
            <p><?php _e('Oops, we could not find what you were looking for...', 'zeitgeist'); ?></p>
        </div>
    <?php endif; ?>
    <!-- Content End -->
        
    	<div class="clear">
        </div>
    	
        <div align="center" id="footercta" style="margin-top:30px; margin-bottom: 0px;">
        	<a href="#booknow"><img src="http://www.prideofmaui.com/wp-content/uploads/2013/05/diveincta.png" alt="Dive In. Book Your Adventure." /></a>
        </div>
    <!-- /Body -->
    
    <!-- Bottom Spacing -->
    	<div class="row top0">
        </div>
	</div>
</div><!-- /container -->
</div>
<?php dynamic_sidebar('tours'); ?>
<?php get_footer(); ?>