var Calendar = function(args) {
	args = args || {};

	var self = {
		table_id: args.table_id || "",
		month_id: args.month_id || ""
	},

	el = args.el || "",
	current_date = args.date || new Date(),
	current_month = current_date.getMonth(),
	current_year = current_date.getFullYear();

	console.log("Current Date", current_date);
	
	var months = [
		"January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"
	]; 
	
	//last calendar day element clicked
	self.$last = null;

	var input = args.date_input || 'tour_date';
	self.input = '[name=' + input + ']';
	
	self.el = args.el || '#pop-up-calendar';

	var handle = function(args) {
		args = args || {};
		
		//Event Handlers
		self.onClickDate = args.onClickDate || function() {},
		self.onMonthChange = args.onMonthChange || function() {}

		//Reset Controls
		self.reset_controls();

		//Turn Controls On
		$('.available').on('click', function(e) {			
			e.preventDefault();
			e.stopPropagation();
			
			var d = $(this).data('date');
			var ad = d.split('-');
			
			self.year = parseInt(ad[0]);
			self.month = parseInt(ad[1]);
			self.date = parseInt(ad[2]);
									
			$(self.input).val(self.month + "/" + self.date + "/" + self.year);

			//Fire Date Click Handler				
			self.onClickDate(e);

		});

		$('.forward-one').on('click', function(e) {
			e.preventDefault();
			e.stopPropagation();

			$('.back-one').prop('disabled', false);
			
			self.day = 0;

			if (self.month < 12) {
				self.month = parseInt(self.month) + 1;
			} else {
				self.month = 1;
				self.year = parseInt(self.year) + 1;
			}
			//console.log("Forward One", tour);
			self.set_datepicker_month();				
			self.onMonthChange(e);
		});

		if ( self.year <= current_year && (self.month - 1) <= current_month ) {
			$('.back-one').prop('disabled', true);
		} else {
			$('.back-one').on('click', function(e) {
				e.preventDefault();
				e.stopPropagation();

				self.date = 0;

				if (self.month > 1) {
					self.month = parseInt(self.month) - 1;
				} else {
					self.month = 12;
					self.year = parseInt(self.year) - 1;
				}	
				
				//console.log("Back One", tour);
				self.set_datepicker_month();				
				self.onMonthChange(e);
			});
		}
	}

	self.reset_controls = function() {
		$('.available').off('click');
		$('.forward-one').off('click');
		$('.back-one').off('click');	
	}
	
	self.set_datepicker_month = function() {
		$(self.month_id).text(months[self.month - 1] + " " + self.year);		
	}
		
	self.current_date = new Date();
	self.year = self.current_date.getFullYear();
	self.month = self.current_date.getMonth() + 1;
	self.date = self.current_date.getUTCDate();
	
	self.set_date = function(next_date) {
		//console.log("Next Date", self.$input, next_date);
		$(self.input).val(next_date);
		var d = next_date.split("/");

		self.month = d[0];
		self.date = d[1];
		self.year = d[2];
		
		//console.log("Current Date", self.month, self.date, self.year);
	}
	
	self.load = function(id, useroptions) {
		var q = new $.Deferred();
		var options = $.extend({				
			day: self.date,
			month: self.month,
			year: self.year,
			id: id
		}, useroptions || {});
	
		$http.getTemplate("build_calendar", options).then(function(data) {
		
			//console.log("Loading Calendar", data);
			var html = $.parseHTML(data);				

			$(self.table_id).html(html);

			$(self.el).css({opacity: 0, visibility: 'visible'});
			$(self.el).animate({opacity: 1}, {duration: 800});
			
			$('.calendar-spinner').fadeOut();	
			$('#sb-tour-month').text(months[self.month - 1] + " " + self.year);
	
			$(window).resize(function(event) {
				var width = $('.day-td').css('width');
				$('.day-td').css('height', width + 5);
			});

			setTimeout(function() {
				handle({
					onClickDate: function(event) {
						if (self.$last != null) {
							self.$last.css(colors.calendar.standard);	
						}
			
						self.$last = $(event.target);
						$(event.target).css(colors.calendar.selected);

					},
					onMonthChange: function(event) {

						$(self.el).css({visibility: 'hidden', opacity: 0});
						$('.calendar-spinner').show();

						self.load(id, useroptions);
					} 
				})
				q.resolve();
			}, 0);
		});
		return q.promise();
	}
			
	self.show = function(tour) {
		$('.datepicker-calendar').html("");

		var q = new $.Deferred();

		//console.log("Show Calendar", self.parent.calendar);
		$http.getTemplate("build_calendar", {
			day: self.date,
			month: self.month,
			year: self.year,
			id: tour.id
		}, {
			//dataType: "json",
			dataType: "text"
		}).then(function(data) {
			var html = $.parseHTML(data);
			//console.log("DATA", data);
			$('.datepicker-calendar').html(html);
			$('#pop-up-calendar').show();		

			setTimeout(function() {
				handle({
					onClickDate: function() {
						$(self.el).hide();
					},
					onMonthChange: function() {
						self.show(tour);
					} 
				})
				
				q.resolve();
			}, 0);
		});	
		
		return q.promise();
	}
		
		
						
					
		return self;		
	}
