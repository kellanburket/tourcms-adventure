<?php
	function get_tour_id_meta_box() {
		global $post;
		$tours = fetch_tours_data(false, 24);
		$id = get_post_meta($post->ID, 'tour_id', true);

		echo "<p class='tour_id_label'>Tour ID: <span id='actual_tour_id'>{$id}</span></p>";

		wp_nonce_field('tour_id_meta_box', 'tour_id_meta_box_nonce');

		echo '<select name="tour_id">';

		if (is_array($tours)) {

			foreach ($tours as $id=>$tour) {

				echo "<option value='$id'";

				echo ($tour['tour_name'] == $post->post_title) ? ' selected="selected">' : '>';

				echo "{$tour['tour_name']}</option>";

			}
		}
		echo '</select>';		
	}
?>