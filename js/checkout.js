(function($) {
	var referer;
		
	var credit_card_date = function() {
		this.set_year = function(year) {
			this.full_year = parseInt(year);
			this.year = year.toString().slice(-2);
		}
		
		this.set_month = function(month) {
			this.month = parseInt(month);
		}
		
		this.output_date = function() {
			var month = (this.month < 10) ? '0' + this.month : this.month;
			return month + "/" + this.year; 
		}
	
		var date = new Date();
		this.month = parseInt(date.getMonth()) + 1;
		this.full_year = parseInt(date.getYear());
		this.year = date.getYear().toString().slice(-2);
	}
	
	var cc_date = new credit_card_date();
	
	$(document).ready(function() {
		referer = $('#referring_url').val()
	
		if($('[name=test_mode]' == 'false')) {
			$('form').find('input').each(function() {
				//$(this).val('');
			});
		}
	
		$('[name=x_exp_date]').val(cc_date.output_date());
		$('#cc_month').val(cc_date.month);
			
		$('#cc_month').change(function() {
			cc_date.set_month($(this).val());
			$('[name=x_exp_date]').val(cc_date.output_date());
		});
		
	
		$('#cc_year').change(function() {
			cc_date.set_year($(this).val());
			$('[name=x_exp_date]').val(cc_date.output_date());	
		});
		
		$('#checkout-continue').click(function(event){
			event.preventDefault();
			event.stopPropagation();
			
			$('[name="x_exp_date"]').val($('#cc_month').val()  + "/" + $('#cc_year').val().substr(2,4));
			var x_exp = $('[name="x_exp_date"]').val();			

			if (!x_exp.match(/\d{1,2}?\/\d\d/)) {
				var msg = "Please fill in a valid credit card expiration date!";
				alert(msg);
				log_tourcms_error("checkout.js", msg);
				return;
			}

			if (!$('#firstname').val()) {
				var msg = "Please enter a first name!";
				alert(msg);
				log_tourcms_error("checkout.js", msg);
				return;
			}

			if (!$('#email').val()) {
				var msg = "Please enter an email address!";
				alert(msg);
				log_tourcms_error("checkout.js", msg);
				return;
			}

			if (!$('#surname').val()) {
				var msg = "Please enter a last name!";
				alert(msg);
				log_tourcms_error("checkout.js", msg);
				return;
			}

			if (!$('#tel_home').val() && !$('#tel_mobile').val()) {
				var msg = "Please enter a telephone number!";
				alert(msg);
				log_tourcms_error("checkout.js", msg);
				return;
			}

			if (!$('#address').val()) {
				var msg = "Please enter an address!";
				alert(msg);
				log_tourcms_error("checkout.js", msg);
				return;
			}

			if (!$('#city').val()) {
				var msg = "Please enter a city!";
				alert(msg);
				log_tourcms_error("checkout.js", msg);
				return;
			}

			if (!$('#postcode').val()) {
				var msg = "Please enter a zip code!";
				alert(msg);
				log_tourcms_error("checkout.js", msg);
				return;
			}

			if (!$('#country').val()) {
				var msg = "Please enter a country!";
				alert(msg);
				log_tourcms_error("checkout.js", msg);
				return;
			}

			
			$(this).prop('disabled', true);
			$form = $('#authorize-payment-form');
			var form_url = $form.attr( 'action' );
			
			$('#sb-tour-spinning-loader').show();
			var button_text = $('#checkout-continue-text').text();
			$('#checkout-continue-text').text('')
			
			document.body.style.cursor='wait';	
			var data_variables = {
				action: ajax.action, 
				callback: 'authorize_tourcms_booking',
				title: $('#title').val(),
				firstname: $('#firstname').val(),
				surname: $('#surname').val(),
				email: $('#email').val(),
				tel_home: $('#tel_home').val(),
				tel_mobile: $('#tel_mobile').val(),
				address: $('#address').val(),
				city: $('#city').val(),
				county: $('#county').val(),
				postcode: $('#postcode').val(),
				country: $('#country').val(),
				//cc_number: $('#cc_number').val(),
				//cc_month: $('#cc_month').val(),
				//cc_year: $('#cc_year').val(),
				user_id: $('#user_id').val(),
				referring_url: $('#referring_url').val(),
			};


			$.post(ajax.url, data_variables, function(data){
			
				
			
				if (data.success == true) {

					var data_total = parseFloat(data.total);
					var x_amount_total = parseFloat($("[name='x_amount']").val());

					if (data_total == x_amount_total) {
						var booking_id = $("<input>").attr("type", "hidden").attr("name", "booking_id").attr("id", "new_data").val(data.booking_id);
					   	$form.append($(booking_id));
		
						if (data.debug == false) {
							
							try {
								$form.submit();
							} catch(err) {
								log_tourcms_error("checkout.js:form.submit", err.message);
								alert("Something went wrong while processing your request. Please try again later.");
								$(this).prop('disabled', false);
								$('#sb-tour-spinning-loader').hide();			
								$('#checkout-continue-text').text(button_text);
							}
						} else {
							alert('Debug Mode');
							$form.submit();
						}			
					} else {
						alert("Something went wrong while calculating charges. Please try again later.");
						log_tourcms_error("checkout.js:transaction_success", data_total + " not equal to " + x_amount_total);
					}
				} else if (data.success == false) {
					$("#checkout-continue").prop('disabled', false);
					$('#sb-tour-spinning-loader').hide();
					$('#checkout-continue-text').text(button_text);

					if (data.error_type == "tourcms_error") {
						var msg = 'Your Session has Expired';
						alert(msg);
						log_tourcms_error("checkout.js", msg);
					} else if (referer != null) {
						if (data.debug == false) {
							var random = parseInt(Math.random() * 10000000);
							window.location.replace(referer + '?nocache=' + random);
						} else {
							var msg = "There was a problem booking your reservation";
							alert(msg);
							log_tourcms_error("checkout.js:error", JSON.stringify(data));
						}						
					} else {
						//modal.open(data.error_message);
						//modal.handle();
						alert(data.error_message);
						log_tourcms_error("checkout.js:error", JSON.stringify(data));
					}
				} else {
					alert("Sorry, there was a problem completing your booking. Please try again later.");
					alert(msg);
					log_tourcms_error("checkout.js:fail-no success msg set", JSON.stringify(data));
				}
			}, 
			'json'
			).fail(function(data) {
				alert("Sorry, there was a problem completing your booking. Please try again later.");
								
				data_variables["xhr_response_text"] = data.responseText;
				data_variables["xhr_status_text"] = data.statusText;				
								
				log_tourcms_error("checkout.js:server error", JSON.stringify(data_variables));
				$("#checkout-continue").prop('disabled', false);
				$('#sb-tour-spinning-loader').hide();
				$('#checkout-continue-text').text(button_text);
			}).always(function(data) {
				document.body.style.cursor='default';	
			});
		});
		$('[name=x_exp_date]').val('');
	});
})(jQuery);