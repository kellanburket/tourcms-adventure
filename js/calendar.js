$last = null;

var date = new Date();
var month, year
var tour;

function getCalendar(id) {
	//console.log(id);
	for (var i = 0; i < calendars.length; i++) {
		if (calendars[i].id == id) {
			return calendars[i];
		}
	}
}

function Calendar(classes, month, year, tour) {
	this.selected_month = parseInt(month);
	this.selected_year = parseInt(year);
	this.tour = tour;
	this.date_field = classes.date_field.id;
	
	this.back_id = classes.back_one.id;
	this.forward_id = classes.forward_one.id;
	
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
	
	this.setSelectedDate = function(day) {
		this.selected_date = this.selected_month + "/" + day + "/" + this.selected_year;
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

var calendars = new Array();

$(document).ready(function() {

	month = $('[name=current_month]').val();
	year = $('[name=current_year]').val();
	tour = $("input[name=tour_id]").val();
	var i = 0;
	
	
	for (var calendar in live_calendar) {
		calendars[i] = new Calendar(live_calendar[calendar], month, year, tour)
		loadCalendar(calendars[i++]);
	}

	$('.forward-one').click(function(event) {		
		event.preventDefault();
		event.stopPropagation();
		$(this).siblings(".back-one").removeAttr("disabled");
		$cal = getCalendar($(this).parent().siblings('.tourcms-live-calendar').attr('id'));
		$cal.incrementMonth();
		getNewCalendar($cal);
	});

	$('.day-td').not(".unavailable").click($.proxy(date_handler.fire));

	$('.back-one').click(function(event) {	
		event.preventDefault();
		event.stopPropagation();
		$cal = getCalendar($(this).parent().siblings('.tourcms-live-calendar').attr('id'));
		$cal.decrementMonth();
		getNewCalendar(getCalendar($cal));
	});
	
	$('#calendar-button').click(function(event){
		event.preventDefault();
		event.stopPropagation();
		$cal = getCalendar($(this).siblings('#pop-up-calendar').children('.tourcms-live-calendar').attr('id'));
		if (!$('#pop-up-calendar').is(":visible")) {		
			getNewCalendar($cal);  
			$('#pop-up-calendar').show();
		}
	});
});

var date_handler = {
	fire: function(event){
		event.preventDefault();
		event.stopPropagation();
		
		$cal = getCalendar($(this).parent().parent().parent().attr('id'));
		//console.log($cal);

		$cal.setSelectedDate(event.target.textContent);
	
			
		$('.' + $cal.guests_clz).each(function() {
			if ($(this).val() > 0) {
				$('#' + $cal.submit_id).prop('disabled', false);			
			}
		});
		
		$('#tourcms-totals').replaceWith(updateTotals());
		$('#pop-up-calendar').fadeOut();

		$cal.updateDateField();

		if ($last != null) {
			$last.css(colors.calendar.standard);	
		}
		$last = $(event.target);
		
		//console.log($last);
		$(event.target).css(colors.calendar.selected);
	}
}

function loadCalendar(calendar) {
	$.ajax({
		url: ajax.url,
		type: "POST",
		data: {
			tour_id: tour,
			action: ajax.action,
			callback: 'update_calendar',
			weekday_class: calendar.weekday_clz,
			day_class: calendar.day_clz,
			table_id: calendar.id,
		}
	}).done(function(html) {
		$('#' + calendar.id).empty();
		$('#' + calendar.id).replaceWith(html);
		//$('#sb-tour-month').text(months[month-1] + " " + year);
		$('.' + calendar.day_clz).not(".unavailable").click($.proxy(date_handler.fire));
		$(window).resize(function(event) {
			var width = $('.' + calendar.day_clz).css('width');
			$('.' + calendar.day_clz).css('height', width + 5);
			//console.log(width);			
		});
		
	}).fail(function(data) {
		alert(errors.server_error);
	});
}

function getNewCalendar(calendar) {
	document.body.style.cursor = 'wait';
		
	if (calendar.selected_month == month) {
		$('#' + calendar.back_id).attr("disabled", "disabled");
	}
	
	$.ajax({
		url: ajax.url,
		type: "POST",
		data: {
			action: ajax.action,
			callback: 'update_calendar', 
			selected_month: calendar.selected_month,
			selected_year: calendar.selected_year,
			selected_day: calendar.selected_day,
			tour_id: calendar.tour,
			table_id: calendar.id,
			weekday_class: calendar.weekday_clz,
			day_class: calendar.day_clz
		}
	}).done(function(html) {

		//console.log(calendar);
		$('#' + calendar.id).empty();
		$('#' + calendar.id).replaceWith(html);

		$('#' + calendar.month_id).text(calendar.getFullMonth() + " " + calendar.selected_year);
		$('.' + calendar.day_clz).not(".unavailable").click($.proxy(date_handler.fire));
	}).fail(function(data) {
		alert(errors.access_error.message);
	}).always(function(data) {
		document.body.style.cursor = 'default';
	});
}

