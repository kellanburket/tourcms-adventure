jQuery(document).ready(function($) {
	var tour_id = $('#actual_tour_id').html();
	console.log("id: " + tour_id);
	$('select[name="tour_id"] option[value="' + tour_id + '"]').attr("selected", "selected");
	
	var all_tours = $('.tour');
	var all_tour_data = {visible: [], invisible: []};

	function reload_tours() {
		all_tours = $('.tour');
		
		all_tour_data = {visible: [], invisible: []};
		var j = 0;
		$.each(all_tours, function() {
			++j;
			if ($(this).find('input[type=checkbox]').prop('checked') == true) {				
				all_tour_data.visible.push($(this).data('tour_id'));
				$(this).children('td').first().text(j + ".");
			} else {
				all_tour_data.invisible.push($(this).data('tour_id'));
				$(this).children('td').first().text("");
			}
			
		});
		
		var input_data = JSON.stringify(all_tour_data);
		$('[name="tourcms_order"]').val(input_data);
		//console.log("Input Data", $('[name="tourcms_order"]').val());
		//console.log("All Tour Data", all_tour_data);
	}

	function reorder_tours(context, val) {
		var index = all_tours.index(context);

		var id = context.data('tour_id');		
		var order = context.data('order');		
		var next = index + val;
		console.log("id/order", id, next);
		if (next >= 0 && next < all_tours.length) {
			var thing = context.detach();
			if (val == 1) {
				all_tours.eq(next).after(thing);	
			} else if (val == -1) {
				all_tours.eq(next).before(thing);				
			}
			
			reload_tours();
		}
	}
	
	
	$('.fa-arrow-up').on('click', function() {
		reorder_tours($(this).parent().parent(), -1)
	});

	$('.fa-arrow-down').on('click', function() {
		reorder_tours($(this).parent().parent(), 1);
	});
	
	$('.show-tour').change(function() {
		var thing = $(this).parent().parent().detach();
		console.log($(this).prop('checked'));
		if ($(this).prop('checked') == true) {
			var last_one;
			$.each(all_tours.not(thing), function() {
				if ($(this).data('order')) {
					last_one = $(this);
				}			
			});
			last_one.after(thing);	
			reload_tours();
		} else {
			all_tours.not(thing).last().after(thing);	
			reload_tours();
		}

	});
});