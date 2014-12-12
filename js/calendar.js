//console.log("In the Calendar File");
(function($) {
	//console.log("In the Calendar Function");

	$last = null;
	var calendars = {};
	var tours = {};
	var tour;
	var currentDate = new Date();
	var month = currentDate.getMonth() + 1;
	var year = currentDate.getFullYear();
	
	function getCalendar(context) {
		var calendar = $(context).closest('.tourcms-live-calendar');
		if (calendar.length == 0) {
			calendar = $(context).siblings('.tourcms-live-calendar');
		}
		var id = calendar.children('.table-wrapper').children('.tourcms-live-calendar-table').attr('id')
		//var table = calendar
		//var table_id = table.attr('id');
		//console.log("Calendar", calendar);
		//console.log("Calendars", calendars);
		//console.log('Calendar ID', id);
		return calendars[id];
	}

	var Tour = function(id, name, next_date, from_price) {
		this.id = id;
		this.name = name;
		this.from_price = from_price;
		this.next_date = next_date;				
	}

	var Calendar = function(classes, month, year, tour) {
		this.selected_month = parseInt(month);
		this.selected_year = parseInt(year);
		this.tour = tour;
		this.date_field = classes.date_field.id;
		
		this.back_id = classes.back_one.id;
		this.forward_id = classes.forward_one.id;
		this.calendar_id = classes.calendar.id;
		this.selected_date;
	
		this.guests_clz = classes.guests_input.class;
		this.submit_clz = classes.submit.id;
		this.id = classes.table.id;
		this.weekday_clz = classes.weekday.class;
		this.day_clz = classes.day.class;	
		this.month_id = classes.month.id;
	
		this.getFullMonth = function() {
			return months[this.selected_month - 1];
		}
		this.updateDateField = function() {
			$('#' + this.date_field).val(this.selected_date);
		};
		
		this.parseDate = function() {
			var date = $('#' + this.date_field).val();
			if (date.match(/\d{1,2}\/\d{1,2}\/\d{4,4}/)) {
				this.selected_date = date;

				var split_date = date.split('/');
				this.selected_month = split_date[0];
				this.selected_day = split_date[1];
				this.selected_year = split_date[2];							
			}			
		};
		
		this.setSelectedDate = function(day) {
			this.selected_date = this.selected_month + "/" + day + "/" + this.selected_year;
		};
		
		this.setSelectedMonth = function(month) {
			this.selected_month = month;
		};

		this.setSelectedYear = function(year) {
			this.selected_year = year;
		};
		
		this.incrementMonth = function() {
			if (this.selected_month == 12) {
				this.selected_month = 1;
				++this.selected_year;
			} else {
				++this.selected_month;
			}	
		};
		
		this.decrementMonth = function() {
			if (this.selected_month == 1) {
				this.selected_month = 12;
				--this.selected_year;
			} else {
				--this.selected_month;
			}	
		};
	
		var months = new Array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
	}

	function fetchToursData(callback) {
		//console.log("Fetch Tours Data ", ajax.url);
		$.ajax({url: ajax.url, data: {action: ajax.action, callback: 'fetch_tours_data'}, success: function(data) {
			//console.log("Return Data from Server", data);
			for (var i in data) {
				tours[i] = new Tour(i, data[i].tour_name, data[i].next_bookable_date, data[i].from_price);			
			}
			//console.log("Tours", tours);					
			$('#calendar-button').click(function(event){
				event.preventDefault();
				event.stopPropagation();
				$cal = getCalendar('#calendar-button');
				if (!$('#pop-up-calendar').is(":visible")) {		
					$cal.parseDate();				
					$cal.tour = $('#datepicker-select option:selected').val(); 	
					getNewCalendar($cal);  
					$('#pop-up-calendar').show();
				}
			});
						
			callback(tours);
	
		}, error: function(xhr, status, error) {
			//console.log(xhr);
			//console.log(status);
			//console.log(error);						
		}, dataType: "json", type: "POST"});	
	
	}
	
	function loadCalendar(calendar) {
		$.ajax({
			url: ajax.url,
			type: "POST",
			data: {
				tour_id: calendar.tour,
				action: ajax.action,
				callback: 'update_calendar',
				selected_month: calendar.selected_month,
				selected_year: calendar.selected_year,
				weekday_class: calendar.weekday_clz,
				day_class: calendar.day_clz,
				table_id: calendar.id,
			}
		}).done(function(html) {
			//console.log("Calendar", calendar);
			$parent = $('#' + calendar.calendar_id).children('.table-wrapper');
			$parent.empty();
			$parent.append(html);

			$('#sb-tour-calendar').css({opacity: 0, visibility: 'visible'});
			$('#sb-tour-calendar').animate({opacity: 1}, {duration: 800, complete: function() {

			}});
			$('.calendar-spinner').fadeOut();

			$('#datepicker-submit').prop("disabled", false);
			$('#' + calendar.month_id).text(calendar.getFullMonth() + " " + calendar.selected_year);
			$('.' + calendar.day_clz).not(".unavailable").click($.proxy(date_handler.fire));
	
			$(window).resize(function(event) {
				var width = $('.' + calendar.day_clz).css('width');
				$('.' + calendar.day_clz).css('height', width + 5);
			});
			
			//console.log(calendar);
			
		}).fail(function(xhr) {
			//log_tourcms_error("calendar_error", xhr.responseText);
			//modal.displayMessage("Calendar could not be displayed");
		});
	}
	
	function getNewCalendar(calendar) {
		document.body.style.cursor = 'wait';
		
		$('#sb-tour-calendar').css({visibility: 'hidden'});
		$('.calendar-spinner').show();
		
		if (calendar.selected_month == month) {
			$('#' + calendar.back_id).attr("disabled", "disabled");
		}
		
		var data = {
			action: ajax.action,
			callback: 'update_calendar', 
			selected_month: calendar.selected_month,
			selected_year: calendar.selected_year,
			selected_day: calendar.selected_day,
			tour_id: calendar.tour,
			table_id: calendar.id,
			weekday_class: calendar.weekday_clz,
			day_class: calendar.day_clz
		};

		//console.log("New Calendar", data);		

		$.ajax({
			url: ajax.url,
			type: "POST",
			data: data
		}).done(function(html) {
			//console.log(calendar);
			$('#' + calendar.calendar_id).children('.table-wrapper').empty();
			$('#' + calendar.calendar_id).children('.table-wrapper').append(html);
	
			$('#' + calendar.month_id).text(calendar.getFullMonth() + " " + calendar.selected_year);
			$('.' + calendar.day_clz).not(".unavailable").click($.proxy(date_handler.fire));
		}).fail(function(data) {
			modal.displayMessage(errors.access_error.message);
		}).always(function(data) {
			/*
			$('.calendar-overlay').animate({opacity: 0}, {duration: 1200, complete: function() {
				$('.calendar-overlay').hide();
			}});
			$('#sb-tour-calendar').css({visibility: 'hidden'});
			*/
			$('#sb-tour-calendar').css({opacity: 0, visibility: 'visible'});
			$('#sb-tour-calendar').animate({opacity: 1}, {duration: 800, complete: function() {
			}});
			$('.calendar-spinner').fadeOut();

			document.body.style.cursor = 'default';
		});
	}
	
	var date_handler = {
		fire: function(event){
			event.preventDefault();
			event.stopPropagation();
			
			$cal = getCalendar(this);
			//console.log($cal);
	
			$cal.setSelectedDate(event.target.textContent);
		
				
			$('.' + $cal.guests_clz).each(function() {
				if ($(this).val() > 0) {
					$('#' + $cal.submit_id).prop('disabled', false);			
				}
			});
			
			$('#pop-up-calendar').fadeOut();
			$cal.updateDateField();
			if ($last != null) {
				$last.css(colors.calendar.standard);	
			}
			$last = $(event.target);
			$(event.target).css(colors.calendar.selected);
		}
	};
	
	$(document).ready(function() {
		//console.log("Document Ready");
		tour = $("input[name=tour_id]").val() || 1;
	
		fetchToursData(function(data) {
			//console.log("Data Fetched", data);
			
			var current_tour = data[tour]; 
			for (var calendar in live_calendar) {
				console.log("Current Tour", data, tour);
				var id = live_calendar[calendar].table.id;
				var date = current_tour.next_date.split("/");
				console.log(date[0], date[2], tour);
	
				calendars[id] = new Calendar(live_calendar[calendar], date[0], date[2], tour)
				loadCalendar(calendars[id]);
			}
		});
		
		
		$("#datepicker-select option").each(function() {
			//console.log($(this).val(), tour);
			if ($(this).val() == tour) {
				$(this).prop('selected', true);
			}
		});
		
		$('#datepicker-select').change(function() {
			var id = $('#datepicker-select option:selected').val();
			var newTour = tours[id];
			var newDate = newTour.next_date;
			
			$('#activity-date-field').val(newDate);
		});
		
		$('.forward-one').click(function(event) {		
			event.preventDefault();
			event.stopPropagation();
			$(this).siblings(".back-one").removeAttr("disabled");
			$cal = getCalendar(this);
			$cal.incrementMonth();
			getNewCalendar($cal);
		});
	
		$('.day-td').not(".unavailable").click($.proxy(date_handler.fire));
	
		$('.back-one').click(function(event) {	
			event.preventDefault();
			event.stopPropagation();
			$cal = getCalendar(this);
			$cal.decrementMonth();
			getNewCalendar($cal);
		});
	});
	
})(jQuery);

