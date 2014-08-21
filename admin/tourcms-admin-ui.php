<?php
//Fetch variables $form_data and $menu_fields
require_once('settings-page-builder.php');
require_once('custom-post-type-builder.php');
require_once('meta-boxes.php');
require_once(TOURCMS_ROOT.'/lib/xml-parser.php');

add_action('init', function() { 
	//require_once('config-options/tourcms-config-menu.php');
	$path = TOURCMS_ROOT;
	$url = TOURCMS_URL.'/admin';
	
	$menu = parse($url.'/config-options/config-menu.xml');
	$settings_page_builder = new SettingsPageBuilder($menu);
	
	$info_box = parse($url.'/meta-boxes/special-information-meta-box.xml');
	$id_box = parse($url.'/meta-boxes/tour-id-meta-box.xml');
	$subtitle_box = parse($url.'/meta-boxes/tour-subtitle-meta-box.xml');
	$slug_box = parse($url.'/meta-boxes/tour-slug-meta-box.xml');
	
	$single_template = $path.'/templates/tourcms-tour-template.php';
	$archive_template = $path.'/templates/tourcms-tour-archive-template.php';
	$xml = parse($url.'/config-options/tourcms-page.xml');
	$handle = TOUR_PAGE;
	$post_type_builder = new CustomPostTypeBuilder($handle, $xml, $single_template, $archive_template);
	$post_type_builder->add_meta_boxes(array($info_box, $id_box, $subtitle_box, $slug_box));
	
	$single_template = $path.'/templates/tourcms-mobile-tour-template.php';
	$archive_template = $path.'/templates/tourcms-mobile-tour-archive-template.php';
	$xml = parse($url.'/config-options/tourcms-mobile-page.xml');
	$handle = MOBILE_TOUR_PAGE;
	$post_type_builder = new CustomPostTypeBuilder($handle, $xml, $single_template, $archive_template);
	$post_type_builder->add_meta_boxes(array($id_box, $subtitle_box, $slug_box));
	
	$template = $path.'/templates/tourcms-checkout-template.php';
	$xml = parse($url.'/config-options/checkout-page.xml');
	$post_type_builder = new CustomPostTypeBuilder(TOUR_CHECKOUT, $xml, $template);
}); 


add_action('admin_enqueue_scripts', function() {
	wp_enqueue_script("tourcms_admin_js", TOURCMS_URL."/js/tourcms-admin.js", array('jquery'), false, false);	
	wp_enqueue_style("tourcms_admin_css", TOURCMS_URL."/css/tourcms-admin.css");
});

function parse($path) {
	return KBC_XML_Parser::parse_file($path);
}

add_filter('title_save_pre', function($post_title) { 
	global $post;
	if (is_object($post)) {
		if (($post->post_type == TOUR_PAGE || $post->post_type == MOBILE_TOUR_PAGE)) {
			$tour_id = isset($_POST['tour_id']) ? $_POST['tour_id'] : '';
			if ($tour_id) {
				$tourcms = new TourCMS();
				$channel_id = SiteConfig::get('channel_id');
				$tour_id = sanitize_text_field($tour_id);
				$tour = $tourcms->show_tour($tour_id, $channel_id);	
				
				if ($tour) {
					update_post_meta($post->ID, 'tour_id', strip_tags($tour->tour->tour_id->asXML()));
					if (!$post_title) {
						return $tour->tour->tour_name_long;
					}
				}
			}
		} 
	}
	return $post_title;	
});
