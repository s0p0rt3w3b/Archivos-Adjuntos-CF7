(function ($) {
	'use strict';

	function initUploader($wrap) {
		const $input = $wrap.find('.aacf7-input');

		$wrap.on('click', '[data-aacf7-browse="1"]', function () {
			$input.trigger('click');
		});
	}

	$(function () {
		$('.aacf7-wrap').each(function () {
			initUploader($(this));
		});
	});
})(jQuery);