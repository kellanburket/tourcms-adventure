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
		$(this).prop('disabled', true);
		$form = $('#authorize-payment-form');
		var form_url = $form.attr( 'action' );
		
		$('#sb-tour-spinning-loader').show();
		var button_text = $('#checkout-continue-text').text();
		$('#checkout-continue-text').text('')
		
		document.body.style.cursor='wait';	
		$.post(ajax.url, {
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
			cc_number: $('#cc_number').val(),
			cc_month: $('#cc_month').val(),
			cc_year: $('#cc_year').val(),
			user_id: $('#user_id').val(),
			referring_url: $('#referring_url').val(),
		}, function(data){
			if (data.success == true) {

				var booking_id = $("<input>").attr("type", "hidden").attr("name", "booking_id").attr("id", "new_data").val(data.booking_id);
			   	$form.append($(booking_id));

				if (data.debug == false) {
					$form.submit();
				
				} else {
					alert('Debug Mode');
					$form.submit();
				}			
			} else if (data.success == false) {
				if (data.error_type == "tourcms_error") {
					alert('Your Session has Expired');
				} else if (referer != null) {
					if (data.debug == false) {
						var random = parseInt(Math.random() * 10000000);
						window.location.replace(referer + '?nocache=' + random);
					} else {
						console.log(data);
					}						
				} else {
					modal.open(data.error_message);
					modal.handle();
					console.log(data);
				}
			} else {
				console.log(data);
			}
		}, 
		'json'
		).fail(function(data) {
			alert(data.error_message);
			$(this).prop('disabled', false);
			$('#sb-tour-spinning-loader').hide();
			$('#checkout-continue-text').text(button_text);
		}).always(function(data) {
			document.body.style.cursor='default';

		});
	});
	$('[name=x_exp_date]').val('');
});