jQuery(document).ready(function($) {
	var tour_id = $('#actual_tour_id').html();
	console.log("id: " + tour_id);
	$('select[name="tour_id"] option[value="' + tour_id + '"]').attr("selected", "selected");
});