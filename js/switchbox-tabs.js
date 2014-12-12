var last_number = 1;
var width, last_width;
var style;

$(document).ready(function() {
	if ($('.sb-tour-switcher').hasClass('tourcms-mobile')) {
		style = colors.mobile;
	} else {
		style = colors.tablet;
	}
	
	/*	
	$( window ).resize(function() {
  		width = $( window ).width();

		if (width >= 753 && last_width < 753) {
			$('.sb-tour-tab-info-wrap').css({display: "none"});
			$('.sb-tour-tab').css(colors.tablet.tab_inactive);				

			$('#tab-frame-4').css({display: "block"});
			$('#sb-tab-4').css(colors.tablet.tab_active);		
		} else if (width < 753 && last_width >= 753) {
			$('.sb-tour-tab-info-wrap').css({display: "none"});
			$('.sb-tour-tab').css(colors.mobile.tab_inactive);				

			$('#tab-frame-1').css({display: "block"});
			$('#sb-tab-1').css(colors.mobile.tab_active);	
		}
		last_width = width;
	});
	*/
	
	$('.sb-tour-tab').click(function(event) {
		event.preventDefault();
		event.stopPropagation();
		var s = $(this).attr("id").split("-");
		var number = s[2];
		$('.sb-tour-tab-info-wrap').hide();		
		$('.sb-tour-tab').css(style.tab_inactive);
		
		//console.log(style);

		$('#tab-frame-' + number).show();
		$('#sb-tab-' + number).css(style.tab_active);
		
		var factor = Math.abs(last_number - number);
		if (number <=3) {
			
			if (last_number > number && factor == 1) {	
				$('.arrow-left').stop().animate(
					{top: "-=59"},
					200
				);	
			} else if (last_number < number && factor == 1) {
				$('.arrow-left').stop().animate(
					{top: "+=59"},
					200
				);
			} else if (last_number > number && factor == 2) {
				$('.arrow-left').stop().animate(
					{top: "-=118"},
					200
				);
			} else if (last_number < number && factor == 2) {
				$('.arrow-left').stop().animate(
					{top: "+=118"},
					200
				);
			}	
			last_number = number;
		}
	});
});
