function log_tourcms_error(type, message) {
	$.post(ajax.url, {
		action: ajax.action, 
		callback: 'log_tourcms_error',
		user_id: $('#user_id').val(),
		type: type,
		message: message
	});
}