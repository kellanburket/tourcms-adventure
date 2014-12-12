var User = function() {
	var self = {
		id: null
	};

	self.checkout = function(data) {				

		if (data.success) {
			var nonce = $('[name=_tourcms_homepage_nonce]').val();
			var checkout_url = ajax.siteurl + '/' + data.checkout_url + '&_tourcms_homepage_nonce=' + nonce;
			console.log("Checkout URL", checkout_url);
			window.location = checkout_url;

		} else if (data.success == false || !data.success) {
			console.log("Fail", data);

			alert(data.error_message);

			$('#search-now').prop('disabled', 'false')
			$('#search-now').find('.button-text').text("Search Now");
			$('.spinner').hide();
		} 
	}


	self.uniqid = function(num) {
		
		var c = "";
		for (i = 0; i < num; i++) {
			c += String.fromCharCode(parseInt(Math.random() * (91 - 65) + 65));
		}
				
	    var d = new Date(),
	        m = d.getMilliseconds(),
	        u = ++d + m;
			id = c + u.toString(16);	        
			

		$("input[name=user_id]").val(id);	
		//console.log("uniqid", id, $("input[name=user_id]").val());

	    return id;
	}

	self.book_tour = function(e) {
		e.preventDefault();
		e.stopPropagation();
		
		var tour_id = parseInt($('[name=tour_id]').val());
		var tour_date = $('[name=tour_date]').val();
		var no_adults = parseInt($('#no-adults-input').val());
		var no_children = parseInt($('#no-children-input').val());
		var adult_rate = parseFloat($('[name=adult_rate]').val());
		var child_rate = parseFloat($('[name=child_rate]').val());
		
		
		if (no_adults + no_children <= 0) {
			alert("Please enter a valid number of travelers!");				
			return;
		}			

		if (!tour_id) {
			alert("Please select a valid tour");				
			return;			
		}

		if (!tour_date) {
			alert("Please select a valid date");				
			return;			
		}

		if (!self.id) {
			alert("Something went wrong. Please try reloading the page.");				
			return;			
		}
		
		var data = {
			tour_id: tour_id,
			user_id: self.id,
			option_data: {},
			rates_data: [
				{
					kind: "adults",
					number: no_adults,
					rate: adult_rate,
					total: no_adults * adult_rate
				},
				{
					kind: "children",
					number: no_children,
					rate: child_rate,
					total: no_children * child_rate
				}
			],
			tour_date: tour_date								
		}

		//console.log("Book Now", data);

		$('#search-now').prop('disabled, true');
		$('#search-now').find('.button-text').text('');
		$('.spinner').show();
		
		$http.post("start_booking_engine", data).then(self.checkout);

	}

	$(document).ready(function() {	
		self.id = self.uniqid(4);
		//console.log("Self ID", self.id);
		$('[name=user_id]').val(self.id);
		$('#search-now').click(self.book_tour);
	});	
	
	return self;
}