var modal = (function(){
	var method = {}, $overlay, $modal, $content, $close, $loader;
		
	$overlay = $('<div id="overlay"></div>');
	$modal = $('<div id="modal"></div>');
	$loader = $('');
	$content = $('<div id="modal-content"></div>');
	$close = $('<a id="modal-close" href="#">close</a>');
	
	$modal.hide();
	$overlay.hide();
	$modal.append($content, $close);

	$(document).ready(function(){
		$('body').append($overlay, $modal);
		$modal.append($loader);
	});
	
	method.center = function () {
		var top, left;
		
		top = Math.max($(window).height() - $modal.outerHeight(), 0) / 2;
		
		$modal.css({
			//top: top + $(window).scrollTop(),
			//left: 0,
			//right: 0,
			//width: "50%",
		});
	}
	
	method.handle = function() {
		//console.log('method.handle');
		$modal
			.on("click", $content, function(event) {
				console.log('in the content');
				event.stopPropagation();
			})
			.on("click", function() {
				console.log('anywhere else');
				modal.close();
			});
		$overlay.click( function() {
			modal.close();
		});
	};
	
	method.open = function (settings) {
		$content.empty().append(settings.content);
		$modal.show();
		
		$modal.css({opacity: 1})
		method.center();
	
		$(window).bind('resize.modal', method.center);
	
		$overlay.show();
	};
	
	method.close = function () {
		$modal.hide();
		$modal.removeClass('modalIsOpen');
		$overlay.hide();
		$content.empty();
		$(window).unbind('resize.modal');
	};	
	
	return method;
}());