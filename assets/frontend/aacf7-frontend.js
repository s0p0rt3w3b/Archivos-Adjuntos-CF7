(function ($) {
	'use strict';

	function bytesToHuman(bytes) {
		if (!bytes && bytes !== 0) return '';
		const units = ['B', 'KB', 'MB', 'GB'];
		let i = 0;
		let b = bytes;
		while (b >= 1024 && i < units.length - 1) {
			b /= 1024;
			i++;
		}
		return (Math.round(b * 10) / 10) + ' ' + units[i];
	}

	function initUploader($wrap) {
		const $dropzone = $wrap.find('.aacf7-dropzone');
		const $input = $wrap.find('.aacf7-input');
		const $list = $wrap.find('.aacf7-list');
		const $template = $wrap.find('.aacf7-item-template')[0];

		function addFileItem(file) {
			const frag = document.importNode($template.content, true);
			const $item = $(frag).find('.aacf7-item');

			$item.find('.aacf7-item-name').text(file.name);
			$item.find('.aacf7-item-meta').text(bytesToHuman(file.size));

			$item.find('[data-aacf7-remove="1"]').on('click', function () {
				$item.remove();
			});

			$list.append($item);
		}

		function handleFiles(fileList) {
			const files = Array.from(fileList || []);
			files.forEach(addFileItem);
		}

		$wrap.on('click', '[data-aacf7-browse="1"]', function () {
			$input.trigger('click');
		});

		$input.on('change', function (e) {
			handleFiles(e.target.files);
		});

		$dropzone.on('dragover', function (e) {
			e.preventDefault();
			e.stopPropagation();
			$dropzone.addClass('is-dragover');
		});

		$dropzone.on('dragleave', function (e) {
			e.preventDefault();
			e.stopPropagation();
			$dropzone.removeClass('is-dragover');
		});

		$dropzone.on('drop', function (e) {
			e.preventDefault();
			e.stopPropagation();
			$dropzone.removeClass('is-dragover');
			const dt = e.originalEvent.dataTransfer;
			if (dt && dt.files) {
				handleFiles(dt.files);
			}
		});

		$dropzone.on('keydown', function (e) {
			if (e.key === 'Enter' || e.key === ' ') {
				e.preventDefault();
				$input.trigger('click');
			}
		});
	}

	$(function () {
		$('.aacf7-wrap').each(function () {
			initUploader($(this));
		});
	});
})(jQuery);