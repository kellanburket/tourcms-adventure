var modal = (function($){
	var method = {}, $overlay, $modal, $content, $close, $loader;
		
	$overlay = $('<div id="overlay"></div>');
	$modal = $('<div id="modal"></div>');
	$loader = $('');
	$content = $('<div id="modal-content"></div>');
	$close = $('<a id="modal-close" href="#"></a>');
	
	$modal.hide();
	$overlay.hide();
	$modal.append($content, $close);

	$(document).ready(function(){
		$('body').append($overlay, $modal);
		$modal.append($loader);
	});
	
	method.open = function (settings) {
		method.updateContent(settings);
		$overlay.hide().show();
		$overlay.animate({duration: 600, opacity: .8});

		$modal.css({opacity: 1});
		$modal.hide().show();


		$close.off("click");
		$overlay.off("click");

		$close.on("click", function(event) {
			method.close(event, settings.callback);
		});

		$overlay.on("click", function(event) {
			//method.close(event, settings.callback);
		});

	};
	
	method.updateContent = function(settings) {
		$modal.offset().top; 		// for forced redraw
		$content.empty().append(settings.content);
		var height = $content.height();
		$modal.css({height: height});
	};
	
	method.close = function (event, callback) {
		event.stopPropagation();
		event.preventDefault();
		
		callback();
		$modal.css({opacity: 0, display: "none"});
		$overlay.css({opacity: 0, display: "none"});
		$content.empty();
	};	
	
	return method;
}(jQuery));