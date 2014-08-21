jQuery(document).ready(function($) {

		
	$('#datepicker-select').change(function(event){
		event.preventDefault();
		event.stopPropagation();
		$("select option:selected").each(function() {
			var this_tour = $(this).text();
			$('#the_tour').val(this_tour);
		});
	});
	});