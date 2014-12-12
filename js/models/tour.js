var tour_date = '[name=tour_date]';

var Tour = function(parent, args) {
	//private variables
	args = args || {};

	var self = {},
	table_id = args.calendar_table || "",
	month_id = args.calendar_month_id || "",
	date_input = args.date_input || null;
	
	self.calendar_el = args.calendar_el || "";
	self.id = args.id || "";
				
	self.parent = parent;

	var set_variables = function(args) {
		//console.log("set_variables", args);
		self.name = args.tour_name || "";
		self.description = args.description || "";
		self.price = args.from_price || "";
		self.next_date = args.next_bookable_date || null;
		self.image = args.image || "";
		self.url = args.url || "";
		self.rates = args.rates || "";

		if (self.next_date) {
			self.calendar = Calendar({
				date: new Date(self.next_date),
				el: self.el,
				table_id: table_id,
				month_id: month_id,
				date_input: date_input
			});
		}
	}

	set_variables(args);

	self.el = args.el || $('<option>', {text: self.name, value: self.id});
	
	self.load_calendar = function(options) {
		self.calendar.load(self.id, options);
	}
	
	self.load = function() {
		if (self.id) {		
			var q = new $.Deferred();
			$http.post("fetch_tour_data", {
				tour_id: self.id
			}).then(function(data) {
				//console.log("Tour Data Loaded", data);			
				set_variables(data);
				q.resolve(data);
			});		
			return q.promise();
		}
	}
	
	if (args.autoload) {
		self.load().then(function() {
			if (args.onLoad) {
				args.onLoad(self);
			}
		});
	}

	return self;
}

var Tours = function(args) {
	args = args || {};
	var tours = {}, 
	self = {
		el: '[name=tour_id]',
	},
	table_id = args.calendar_table || "",
	month_id = args.calendar_month_id || "",

	q = new $.Deferred(),
	calendar_button = '#calendar-button';

	self.toursLoaded = q.promise();
	
	self.calendar = Calendar({
		table_id: table_id,
		month_id: month_id
	});
	
	self.add = function(tour) {
		tours[tour.id] = Tour(self, tour);
				
		$(self.el).append(tours[tour.id].el);
		
		if ($(tour_date).val() == "") {
			self.select_tour();
		}
	}
	
	self.select_tour = function() {
		var $selected = $('[name=tour_id] option:selected');

		var selected_id = $selected.val(); 
	
		//console.log("Selecting Tour", $selected);
	
		self.selected_tour = self.tours[selected_id];
		var next_date = self.selected_tour.next_date;			

		var rates = self.selected_tour.rates;
		//console.log("Rates", rates);		
		
		$('[name=adult_rate]').val(rates.adults);
		$('[name=child_rate]').val(rates.children);
		
		//self.calendar.set_date(next_date);			
	}
			
	self.show_calendar = function(e) {
		e.preventDefault();
		e.stopPropagation();

		if (self.selected_tour) {
			self.calendar.show(self.selected_tour);
		} else {
			alert("Please Select a Tour");
		}
	}
	
	self.tours = tours;
	self.selected_tour = null;

	self.load_tours = function() {
		return $http.post("fetch_tours_data");
	}

	
	//load tours
	self.load_tours().then(function(tours) {
		//console.log("Loading Tours", tours);
		for (var i in tours) {
			self.add(tours[i]);
		}
		
		q.resolve();
	});
	
	//call events
	$(document).ready(function() {
		//console.log("Tours(el)", $(el));
			
		$(self.el).on('change', self.select_tour);
		$(calendar_button).on('click', self.show_calendar);
		
	});
	
	return self;
}