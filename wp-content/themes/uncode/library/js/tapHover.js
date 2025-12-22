(function($) {
	"use strict";

	UNCODE.tapHover = function() {

	var isTouch = (
		'ontouchstart' in window ||
		navigator.maxTouchPoints > 0 ||
		navigator.msMaxTouchPoints > 0
	);

	if (isTouch) {
		var $el = $('.tmb:not(.tmb-no-double-tap)').find('.t-entry-visual-cont > a, .drop-hover-link'), 
			elClass = "hover";

		$(window).on('click', function() {
			$el.removeClass(elClass);
		});

		$el.on('click', function(e) {
			e.stopPropagation();
			var link = $(this);
			if ( ! link.hasClass(elClass)) {
				e.preventDefault();
				link.addClass("hover");
				$el.not(this).removeClass(elClass);
				return false;
			}
		});
	}
};


})(jQuery);
