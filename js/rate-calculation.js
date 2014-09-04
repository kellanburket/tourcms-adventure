(function($) {
	var total_guests = 0;
	var total_price = 0;
	var tour_id;
	var totals_string;
	var savings_string;
	var nonce;
	var sales_tax = tour_pricing.sales_tax;
	
	var booking_box_tour_id;
	var tour_rates = [];
	var booking_box_tour_rates = [];

	var tour_options = [];
	var modal_options = [];

	
	function Promo(name, type, value) {
		this.name = name;
		this.type = type;
		this.value = parseFloat(value);
	
		this.getDiscountedRate = function(rate) {
			if (this.type == "PERCENT") {
				rate *= ((100 - parseInt(this.value)) / 100);
				return rate;
			} else {
				var new_value = rate - this.value;
				return (new_value > 0) ? new_value : 0;
			}
		}
	
		this.getSavings = function(rates, total) {
			
			if (this.type == "PERCENT") {
				old_total = total;
				total *= ((100 - this.value) / 100);
				return old_total - total;
			} else {
				var total_savings = 0;
				for (var prop in rates) {
					var savings = (rates[prop].rate > this.value) ? this.value * rates[prop].number : 0;
	
					total_savings += savings; 	
				}
				return total_savings;
			}
		};
		
	}
	
	var promos = [];

	if (typeof promotions === 'object') {
		for (var i = 0; i < promotions.length; i++) {
			promos[promotions[i].name] = new Promo(promotions[i].name, promotions[i].type, promotions[i].value, promotions[i].restrictions);
		}
	}
	
	function Rate(kind, rate) {
		
		this.getSingular = function(kind) {
			if (kind.match(/children/i)) {
				return kind.replace(/children/i, "child");
			} else if(kind.match(/.*?ies$/i)) {
				return kind.replace(/(.*?)(ies)$/i, "$1y");
			} else if(kind.match(/.*?[lr]ves$/i)) {
				return kind.replace(/(.*?[lr])ves$/i, "$1f");
			} else if(kind.match(/.*?[^aeiou]{2,}es$/i)) {
				return kind.replace(/(.*?[^aeiou]{2,})(es)$/i, "$1");
			} else if(kind.match(/.*?oes$/i)) {
				return kind.replace(/(.*?o)es$/i, "$1");
			} else if(kind.match(/.*?xes$/i)) {
				return kind.replace(/(.*?x)es$/i, "$1");
			} else {
				return kind.replace(/(.*?)s$/i, "$1");
			}
		}
		//Kind treated as private member variable. Please call getKind()
		this.kind = kind;
		this.single = this.getSingular(kind);
		this.plural = kind;
		
		this.rate = rate;
		this.number = 0;
		this.revised_rate = rate;
		this.promos = new Array();
		
		this.getTotal = function() {
			return this.revised_rate * this.number;
		}
		
		this.getRevisedRate = function(promos) {
			return this.revised_rate; 
		}
		
		this.calculateTax = function(rate, tax) {
			var new_rate = (parseFloat(rate) * parseFloat(tax/100)) + parseFloat(rate);
			return new_rate;
		}
		
		this.setRevisedRate = function(promos) {
			for (var i in promos) {
				var already_set = false;
				for (var ii in this.promos) {
					if (this.promos[ii].name == promos[i].name) {
						already_set = true;
						break;
					}
				}
				
				if (!already_set) {
					this.promos.push(promos[i]);			
					this.revised_rate = promos[i].getDiscountedRate(this.revised_rate);
				}
				
			}
		}
		
		this.setNumber = function(num) {
			this.number = parseInt(num);
			this.total = this.number * parseFloat(this.rate);
		}
		
		this.getKind = function() {
			if (this.number == 1) {
				return this.single;		
			} else {
				return this.plural;
			}
		}
	}
		
	function Option(kind, rate) {
		this.kind = kind;
		this.rate = rate;
		this.number = 0;
		
		this.getTotal = function() {
			return this.getRate() * this.number;
		}
		
		this.getRate = function() {
			//console.log("Option Rate", this.rate, sales_tax);
			return this.rate; // /(1 + tour_pricing.sales_tax/100);
		}
	}
						
	$(document).ready(function() {
		$('.sb-confirm-field').prop('disabled', true);
		tour_id = booking_box_tour_id = $("input[name=tour_id]").val();
	
		$('#datepicker-submit').attr('disabled', 'disabled');
	
		$('.sb-tour-option').each(function() {
			tour_options[$(this).find('[name=option_kind]').val()] = new Option($(this).find('[name=option_kind]').val(), parseFloat($(this).find('[name=option_rate]').val()));
		});
		
		fetchRatesData(tour_id, function(rates) {
			console.log('Done', rates);
			tour_rates = rates;
		});

		fetchRatesData(booking_box_tour_id, function(rates) {
			booking_box_tour_rates = rates;
		});		

		$('#datepicker-select').change(function() {		
			booking_box_tour_id = $('#datepicker-select option:selected').val();		
			console.log('id', booking_box_tour_id);

			fetchRatesData(booking_box_tour_id, function(rates) {
				booking_box_tour_rates = rates;
			});
		});

	
		$('#promo-code-input').keypress(function(event) {
	
			var promo_code = $(this).val() + String.fromCharCode(event.which);
			var legal_codes = legal_promotions; 
			//console.log(legal_codes);
			for (var i = 0; i < legal_codes.length; i++) {
				var reg = new RegExp('^' + legal_codes[i].code + '$', "i");
				if (promo_code.match(reg)) {
					//console.log('We have a match');
					promos[legal_codes[i].name] = new Promo(legal_codes[i].name, legal_codes[i].type, legal_codes[i].value);
					tour_rates = updateSavings(tour_rates, $('.sb-tour-option'), $('#tourcms-totals'));
					$('#promo-code-input').css({backgroundColor: "#00FF00"});
					$('#promo-code-input').attr('disabled', '');
					$('#promo-code-input').val(legal_codes[i].code);
				}
				
			}
		});
		
		$('.sb-confirm-field').click(function(event){
			event.preventDefault();
			event.stopPropagation();
	
			if( tour_rates ) {
				tour_rates = updateSavings(tour_rates, $('.sb-tour-option'), $('#tourcms-totals'));
			}		
	
		});
	
		$('#sb-submit').click(function(event){
			event.preventDefault();
			event.stopPropagation();		
			var date_regex = /[0-9]{1,2}\/[0-9]{1,2}\/[0-9]{4}/;
			var date_info = $('#sb-tour-activity-date-field').val();
			var isItARealDate = date_regex.test(date_info);
			
			if (!isItARealDate) {
				modal.displayContent(errors.date_error.message);
				return false;
			}
				
			
			document.body.style.cursor='wait';
			$('#sb-tour-spinning-loader').show();
			var button_text = $('#sb-tour-submit-text').text();
			$('#sb-tour-submit-text').text('')
			$('#sb-submit').prop('disabled', true);
			
			var options_data = getOptionsData(tour_options);
			nonce = $('[name=_tourcms_sidebar_nonce]').val();
			
			var data = {
				action: ajax.action,
				sales_tax: sales_tax,
				callback: "start_booking_engine",
				options_data: options_data,
				rates_data: get_rates_data(tour_rates),
				tour_date: date_info,
				tour_id: $("input[name=tour_id]").val(),
				promo_code: $('#promo-code-input').val(),
				totals_string: totals_string,
				user_id: $("input[name=user_id]").val()
			};
			
			console.log(data);
			
			$.ajax(ajax.url, {
				data: data, 
				dataType: 'json',
				type: 'POST'
				}
			).done(function(data) {
				
				if (data.success == true) {
					var checkout_url = ajax.siteurl + '/' + data.checkout_url + '&_tourcms_sidebar_nonce=' + nonce;
					if (data.debug == true) {
						console.log(checkout_url);			
						alert('Debug Mode');
						window.location = checkout_url;
					} else {
						window.location = checkout_url;
					}
				} else if (data.success == false) {
					modal.displayMessage(data.error_message);
					$('#sb-submit').prop('disabled', false);
					$('#sb-tour-spinning-loader').hide();
					$('#sb-tour-submit-text').text(button_text);
					//console.log("Error Message Trigger", data, data.error_message);
				} else {
					console.log(data);
				}
			}).fail(function(data) {
				//console.log(data);
				//alert(errors.server);
				modal.displayMessage(data.error_message);
				$('#sb-submit').prop('disabled', false);
				$('#sb-tour-spinning-loader').hide();
				$('#sb-tour-submit-text').text(button_text);
			}).always(function(data) {
				//console.log(data);
				document.body.style.cursor='default';
			});
		});
			
		$('#datepicker-submit').click(function(event) {
			event.preventDefault();
			event.stopPropagation();
			
			if ($('#activity-date-field').val().length > 0 
				&& ( $('#no-adults-input').val().length > 0 
					|| $('#no-children-input').val().length > 0 
				)
			) {
	
			var num_adults = $('[name=datepicker_no_adults]').val();
			var num_children = $('[name=datepicker_no_children]').val();
			console.log("Number", num_adults, num_children);
			booking_box_tour_rates[0].setNumber(num_adults);
			
			var booking_box_rates_data = [];
			booking_box_rates_data.push({kind: booking_box_tour_rates[0].kind, number: booking_box_tour_rates[0].number, rate: booking_box_tour_rates[0].getRevisedRate(), total: booking_box_tour_rates[0].getTotal()});
			
			if (typeof booking_box_tour_rates[1] != 'undefined') {
				booking_box_tour_rates[1].setNumber(num_children);	
				booking_box_rates_data.push({kind: booking_box_tour_rates[1].kind, number: booking_box_tour_rates[1].number, rate: booking_box_tour_rates[1].getRevisedRate(), total: booking_box_tour_rates[1].getTotal()});
			}
	
			nonce = $('[name=_tourcms_footer_nonce]').val();
			$(this).prop('disabled', true);
			$submit = $(this);		
			modal.open(
				{
					content: '<p class="modal-wait-message color-white">Please wait while we retrieve booking information...</p>',
					callback: function() { $submit.prop('disabled', false); }	
				}
			);
			$('#pop-up-calendar').hide();
			
	
			$.post(
				ajax.url, 
				{
					action: ajax.action,
					sales_tax: sales_tax,
					callback: 'load_modal',
					tour_id: $('#datepicker-select option:selected').val(),
					tour_date: $("#activity-date-field").val(),
					rates_data: booking_box_rates_data,
					promo_code: $('#datepicker-promo-code-input').val(),
					tour_name: $('#tour_name').val(),
					user_id: $("input[name=user_id]").val(),
					totals_string: totals_string
				}, function(data){
					//console.log(data);
					if (data.success) {
						modal.updateContent(
							{
								content: data.html,
							}
						);
						/*				
						$('.confirm-field').keydown(function() {
							console.log("Confirm Keydown");
							//$(this).hide().show();
							//$(this).offset().top; //force redraw
							//$('#modal').offset().top;
							//forceRedraw(document.getElementById('modal'));
						});
						$('.confirm-field').focus(function() {
							console.log("Confirm Focus");
							//$(this).hide().show();
							//$(this).offset().top; //force redraw
							//$('#modal').offset().top;
							//forceRedraw(document.getElementById('overlay'));
						});
						*/
						
						$('#confirm-booking').click(function(event){
							event.preventDefault();
							event.stopPropagation();
							var $button = $(this);
							$button.prop('disabled', true);
							
							$('.modal-options').each(function() {
								var kind = $(this).find('[name=modal_kind]').val();
								var rate = $(this).find('[name=modal_rate]').val();
								modal_options[kind] = new Option(kind, rate);
								modal_options[kind].number = $(this).find('[name=modal_number]').val(); 
								//modal_options[kind].total = modal_options[kind].number * modal_options[kind].rate; 
							});

							
							document.body.style.cursor='wait';
							booking_box_tour_rates = updateSavings(booking_box_tour_rates, $('.modal-options'), false, true);
							var data = {
								action: ajax.action,
								sales_tax: sales_tax,
								callback: "confirm_tour_booking",
								hotel: $('#hotel-field').val(),
								room: $('#room-field').val(),
								options_data: getOptionsData(modal_options),
								user_id: $("input[name=user_id]").val(),
								totals_string: totals_string 
							};
							//console.log("Data", data);
							
							$.ajax({
									url: ajax.url, 
									data: data,
									success: function(data){
										//console.log(data);
										if (data.success == true) {
											if (data.debug == true) {
												console.log(data);
												alert ('In Debug Mode');
											} 
											
											var url = ajax.siteurl + '/' + data.checkout_url + '&_tourcms_footer_nonce=' + nonce;
											//console.log(url);
											window.location = url;
										} else {
											console.log(data);
										}
									}, 
									dataType: 'json',
									type: 'POST'									
								}).fail(function(xhr, msg, error) {
									console.log('Fail', xhr.responseText, msg, error);
								}).always(function(data) {
									console.log('Always', data);
									document.body.style.cursor='default';			
									$button.prop('disabled', false);
								});
							return false;
						});
					} else {
						modal.displayMessage(data.error_message);
					}
				},
				'json'
				).fail(function(data) {
					console.log(data.responseText);
					$(this).prop('disabled', false);
				}).always(function(data) {
					document.body.style.cursor='default';			
				});
			}
		});
		
	});
	
	function fetchRatesData(id, callback) {
		tour_id = id || 1;
		console.log('Fetch Rates Data', tour_id);

		return $.post(ajax.url, {tour_id: tour_id, action: ajax.action, callback: 'fetch_rates_data'}, function(data) {
			var rates = {};
			console.log("Rates Data", data);
			var childrenOK = false;
			for (var i = 0; i < data.length; i++) {
				if (data[i].kind == 'adults') {
					rates[0] = new Rate(data[i].kind, data[i].rate);	
					//booking_box_tour_rates[0] = new Rate(data[i].kind, data[i].rate);
				} else if(data[i].kind == 'children') {
					childrenOK = true
					rates[1] = new Rate(data[i].kind, data[i].rate);	
					//booking_box_tour_rates[1] = new Rate(data[i].kind, data[i].rate);
				} else {
					rates[3] = new Rate(data[i].kind, data[i].rate);	
				}

			}
			$dp_children = $('#datepicker-children');
			if (childrenOK) {
				if (!$dp_children.is(':visible')) {
					$dp_children.show();
				}
			} else {
				if ($dp_children.is(':visible')) {
					$dp_children.hide();
				}			
			}
			
			
			//console.log("Tour Rates", tour_rates);
			//console.log("Booking Box Tour Rates", booking_box_tour_rates);
			
			$('#datepicker-submit').removeAttr('disabled');
			init_cursor_events($('.sb-confirm-field'));
			$('.sb-confirm-field').prop('disabled', false);
			callback(rates);
		}, "json");	
	}
	
	function get_rates_data() {
		var data = new Array();
		for (rate in tour_rates) {
			data.push({kind: tour_rates[rate].kind, number: tour_rates[rate].number});
		}
		return data;
	}
	
	function updateSavings(tour_rates, $options, $totals, is_modal) {
		//console.log(tour_rates);
		//console.log(tour_options);
		total_guests = 0;
		for (var i = 0; i < tour_rates.length; i++) {
			total_guests += tour_rates[i].number;		
		}
	
		totals_string = updateTotals(tour_rates, $options, is_modal);
		//console.log("Totals String", totals_string);
		if ($totals) $totals.replaceWith(totals_string);
			
		if (promos) {
			var promo_savings = 0;
			for (var property in promos) {
				promo_savings += promos[property].getSavings(tour_rates, total_price);
				//console.log(promos[property]);
			}
			if (promo_savings > 0 && $totals) {
				$('#sb-tour-savings-box').show();
				var savings_string = sprintf('You Saved $%1.2f on Your Booking!', promo_savings);
				$('#sb-tour-you-saved-text').text(savings_string);			
			}
		}
		return tour_rates;
	}
	
	function getOptionsData(objects) {
		var options_data = [];
		console.log("Options Data", objects);

		for(var prop in objects) {
			options_data.push(
				{
				kind: objects[prop].kind, 
				number: objects[prop].number, 
				total: objects[prop].getTotal(), 
				rate: objects[prop].getRate()
				}
			);
		}
		console.log("Options Data", options_data);
		return options_data;
	}
	
	function updateTotals(tour_rates, $option, is_modal) {
		
		total_price = 0;
		var adults_total = 0;
		var children_total = 0;
		$wrapper = $('<div>');
		$totals = $('<div>', {id:'tourcms-totals'});
		$wrapper.append($totals);
		$booking_ul = $('<ul>', {class: 'sb-booking-ul'});
		$totals.append($booking_ul);

		for(var i in tour_rates) {
			tour_rates[i].setRevisedRate(promos);
			
			if (tour_rates[i].number > 0) {			
				$booking_ul.append($('<li>', {class: 'sb-booking-li', text: sprintf('%d %s at $%1.2f = $%1.2f',
					parseInt(tour_rates[i].number),
					tour_rates[i].getKind(),
					tour_rates[i].getRevisedRate(),
					tour_rates[i].getTotal()
				)}));
				total_price += tour_rates[i].getTotal();
			}
		}
		
		$option.each(function() {
			if (is_modal) {
				var kind = $(this).find('[name=modal_kind]').val();
				modal_options[kind].total = parseFloat(modal_options[kind].getTotal());
					
				if (modal_options[kind].total > 0) {
					$booking_ul.append($('<li>', {class: 'sb-booking-li', text: sprintf('%d %s at $%1.2f = $%1.2f',
						parseInt(modal_options[kind].number),
						kind,
						parseFloat(modal_options[kind].getRate()),
						parseFloat(modal_options[kind].getTotal())
						
					)}));
					total_price += modal_options[kind].total;
				}
			} else {
				var option_kind = $(this).find('[name=option_kind]').val();
				tour_options[option_kind].total = parseFloat(tour_options[option_kind].getTotal());
					
				if (tour_options[option_kind].total > 0) {
					$booking_ul.append($('<li>', {class: 'sb-booking-li', text: sprintf('%d %s at $%1.2f = $%1.2f',
						tour_options[option_kind].number,
						option_kind,
						tour_options[option_kind].getRate(),
						tour_options[option_kind].getTotal()
						
					)}));
					total_price += tour_options[option_kind].total;
				}			
			}
			
		});
			
		$totals.append($('<p>', {id: 'sb-total-price', text: sprintf('Subtotal: $%1.2f', total_price)}));
		return $wrapper.html();
	}
	
	function init_cursor_events($confirm_field) {
	
		var change_handle = false;
		var cursor_pos = 0;
		var key_down = 0;
		var last_key = 0;
		var cntrl_down = false;
		
		$confirm_field.change(function(event) {
			event.preventDefault();
			event.stopPropagation();
			change_handle = true;
			var number = $(this).val();
			var kind = new RegExp($(this).data('kind'), "i");
			var category = $(this).data('category');
			handle_change(number, category, kind);
		});
			
		$confirm_field.click(function(event) {
			if (!change_handle) {
				$(this).val('');
				var kind = new RegExp($(this).data('kind'), "i");
				var category = $(this).data('category');	
				handle_change(0, category, kind);
			}
			change_handle = false;
		});
			
		$confirm_field.keyup(function(event) {
			//console.log("Key Up: " + event.which);		
			key_down = 0;
			cntrl_down = false;
		});
		
		$confirm_field.keydown(function(event) {
			//console.log("Key Down");
			var kind = new RegExp($(this).data('kind'), "i");
			var category = $(this).data('category');
			
			var string_from_char = String.fromCharCode(event.which);
			var content = $(this).val();
			
			switch (event.keyCode) {
				case(17):
					cntrl_down = true;
					break;	
				case(8):
					//allow cascade for this element
					if (cursor_pos > 0) {
						--cursor_pos;
						front_content = content.substr(0, cursor_pos);
						back_content = content.substr(cursor_pos + 1, content.toString().length);
						content = front_content + back_content;
						handle_change(content, category, kind);
					}
					break;
				case(37):
					console.log("KeyDown: " + key_down);
					if (cntrl_down) {
						cursor_pos = 0;
					} else if (cursor_pos > 0) {
						--cursor_pos;
					}
					break;
				case(38):
					content++
					cursor_pos = (content > 0) ? content.toString().length : 1;
					break;
				case(39):
					if (cntrl_down) {
						cursor_pos = content.length;	
					} else if (cursor_pos < content.length) {
						++cursor_pos;
					}
					break;
				case(40):
					content = (content > 0) ? content - 1 : 0;	
					cursor_pos = (content > 0) ?content.toString().length : 1;
					break;
				default:
					if (string_from_char.match(/[0-9]/)) {
						var front_content = content.substr(0, cursor_pos);
						var end_content = content.substr(cursor_pos, content.toString().length);
						content = front_content + string_from_char + end_content;		
						handle_change(content, category, kind);
						++cursor_pos;
					} else {
						//console.log(event.which);
					}
			}
	
			//console.log("Cursor Position(" + cursor_pos + "), Content(" + content + ")");
		});
				
		$confirm_field.focus( function (event) {
		  	cursor_pos = $(this).val().toString().length;
			last_key = 0;
			key_down = 0;
			cntrl_down = false;
			$(this).on('mousewheel.disableScroll', function (event) {
				event.preventDefault()
		  	});
		});
		
		$confirm_field.blur( function (event) {
		  	cursor_pos = 0;
			last_key = 0;
			key_down = 0;
			cntrl_down = false;
			$(this).off('mousewheel.disableScroll');
		});
		
		function handle_change(content, category, kind) {
			//console.log(content + ", " + category + ", " + kind);
			
			if (category == 'option') {
				//console.log('option');
				for (var prop in tour_options) {
					//console.log(tour_options[prop]);
					if (tour_options[prop].kind.match(kind)) {
						tour_options[prop].number = parseInt(content);
					}	
				}
			} else if (category == 'rate') {
				for (var prop in tour_rates) {
					if (tour_rates[prop].kind.match(kind)) {
						tour_rates[prop].number = parseInt(content);
					}	
				}		
			}
			
			if(tour_rates) {
				tour_rates = updateSavings(tour_rates, $('.sb-tour-option'), $('#tourcms-totals'));
			}
		}
	}
})(jQuery);