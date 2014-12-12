(function($) {
	var scriptRoot = '/wp-content/plugins/tourcms-adventure/js/';
	var scriptClassesRoot = scriptRoot + 'models/';

	$.getScript(scriptClassesRoot + 'base.js', function() {
		$.getScript(scriptClassesRoot + 'calendar.js', function() {
			$.getScript(scriptClassesRoot + 'tour.js', function() {
				var tours = Tours({
					calendar_table: ".datepicker-calendar",
					calendar_month_id: "#datepicker-month",
				});	
			});	
		});
	
		$.getScript(scriptClassesRoot + 'user.js', function() {
			var user = User();	
		}).fail(function() {
			console.log("Could Not Load Users Script", arguments);
		});				
		
	});

	$.getScript(scriptClassesRoot + 'rate.js');	

})(jQuery);