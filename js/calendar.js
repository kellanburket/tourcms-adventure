//console.log("In the Calendar File");
(function($) {
	//console.log("In the Calendar Function");
	var scriptRoot = '/wp-content/plugins/tourcms-adventure/js/';
	var scriptClassesRoot = scriptRoot + 'models/';
	var tour;			

	$.getScript(scriptClassesRoot + 'base.js', function() {
	
		$.getScript(scriptClassesRoot + 'calendar.js', function() {
			
			$.getScript(scriptClassesRoot + 'tour.js', function() {
	
				tour = new Tour(null, {
					el: '#sb-tour-calendar',
					id: $('[name=tour_id]').val(),

					calendar_table: "#tourcms-sidebar-table",
					calendar_month_id: "#sb-tour-month",
					date_input: "activity_date",
					autoload: true,
					onLoad: function(self) {
						self.load_calendar({
							th_class: "tourcms-sidebar-day",
							td_class: "tourcms-sidebar-td"
						});
					}
				});					

			}).fail(function() {
				console.log("Tour Fail", arguments);					
			});
		}).fail(function() {
			console.log("Calendar Fail", arguments);					
		});
	}).fail(function() {
		console.log("Base Fail", arguments);					
	});	
})(jQuery);

