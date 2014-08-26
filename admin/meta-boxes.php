<?php
	function get_tour_id_meta_box() {
		global $post;
    	$tourcms = new TourCMS();
		$channel_id = SiteConfig::get('channel_id');
		$tours = $tourcms->search_tours('', $channel_id);
		
		$id = get_post_meta($post->ID, 'tour_id', true);
		echo '<p class="tour_id_label">Tour ID: <span id="actual_tour_id">'.$id.'</span></p>';
		wp_nonce_field('tour_id_meta_box', 'tour_id_meta_box_nonce');
		echo '<select name="tour_id">';
		foreach ($tours as $tour) {
			if ($tour->tour_id) {
				echo '<option value="'.$tour->tour_id.'"';
				echo ($tour->tour_name_long == $post->post_title) ? ' selected="selected">' : '>';
				echo $tour->tour_name_long.'</option>';
			}
		}
		echo '</select>';		
	}
?>