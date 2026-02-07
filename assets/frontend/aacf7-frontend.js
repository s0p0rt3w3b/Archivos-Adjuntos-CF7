(function ($) {
	'use strict';

	function cssEscapeIdent(s) {
		return String(s).replace(/[^a-zA-Z0-9_-]/g, '\\$&');
	}

	function toCssVal(v) {
		if (v === undefined || v === null) return '';
		return String(v).trim();
	}

	function normalizeAlign(a) {
		a = String(a || '').toLowerCase();
		if (a === 'start') return 'left';
		if (a === 'end') return 'right';
		if (['left', 'right', 'center', 'justify'].includes(a)) return a;
		return '';
	}

	function bytesToHuman(bytes) {
		if (!bytes && bytes !== 0) return '';
		var thresh = 1024;
		if (Math.abs(bytes) < thresh) return bytes + ' B';
		var units = ['KB', 'MB', 'GB', 'TB'];
		var u = -1;
		do {
			bytes /= thresh;
			++u;
		} while (Math.abs(bytes) >= thresh && u < units.length - 1);
		return bytes.toFixed(0) + ' ' + units[u];
	}

	function getStyles($wrap) {
		var raw = $wrap.attr('data-styles');
		if (!raw) return {};
		try { return JSON.parse(raw); } catch (e) { return {}; }
	}

	function applyInlineStyles($wrap) {
		var id = $wrap.attr('id');
		if (!id) return;

		var styles = getStyles($wrap);

		var container = styles.container || {};
		var dropzone = styles.dropzone || {};
		var button = styles.button || {};
		var note = styles.note || {};
		var file = styles.file || {};

		var selRoot = '#' + cssEscapeIdent(id);
		var css = '';

		/* ===== Contenedor principal (solo caja) ===== */
		css += selRoot + '{';
		if (container.bg) css += 'background:' + toCssVal(container.bg) + ';';
		if (container.radius) css += 'border-radius:' + toCssVal(container.radius) + ';';

		var bw = toCssVal(container.border_width);
		var bs = toCssVal(container.border_style);
		var bc = toCssVal(container.border_color);
		if (bw || bs || bc) {
			css += 'border-style:' + (bs || 'solid') + ';';
			css += 'border-width:' + (bw || '1px') + ';';
			css += 'border-color:' + (bc || '#dcdcdc') + ';';
		}
		if (container.box_shadow) css += 'box-shadow:' + toCssVal(container.box_shadow) + ';';

		if (container.padding) css += 'padding:' + toCssVal(container.padding) + ';';
		if (container.margin) css += 'margin:' + toCssVal(container.margin) + ';';

		css += '}';

		/* ===== T赤tulo (texto del t赤tulo + alineaci車n) ===== */
		css += selRoot + ' .aacf7-title{';
		var titleAlign = normalizeAlign(container.align);
		if (titleAlign) css += 'text-align:' + titleAlign + ';';
		if (container.title_color) css += 'color:' + toCssVal(container.title_color) + ';';
		if (container.title_size) css += 'font-size:' + toCssVal(container.title_size) + ';';
		if (container.title_weight) css += 'font-weight:' + toCssVal(container.title_weight) + ';';
		if (container.title_padding) css += 'padding:' + toCssVal(container.title_padding) + ';';
		if (container.title_margin) css += 'margin:' + toCssVal(container.title_margin) + ';';
		css += '}';

		/* ===== Dropzone (caja) ===== */
		css += selRoot + ' .aacf7-dropzone{';
		if (dropzone.bg) css += 'background:' + toCssVal(dropzone.bg) + ';';
		if (dropzone.radius) css += 'border-radius:' + toCssVal(dropzone.radius) + ';';

		bw = toCssVal(dropzone.border_width);
		bs = toCssVal(dropzone.border_style);
		bc = toCssVal(dropzone.border_color);
		if (bw || bs || bc) {
			css += 'border-style:' + (bs || 'dotted') + ';';
			css += 'border-width:' + (bw || '1px') + ';';
			css += 'border-color:' + (bc || '#cccccc') + ';';
		}
		if (dropzone.box_shadow) css += 'box-shadow:' + toCssVal(dropzone.box_shadow) + ';';
		if (dropzone.padding) css += 'padding:' + toCssVal(dropzone.padding) + ';';
		if (dropzone.margin) css += 'margin:' + toCssVal(dropzone.margin) + ';';
		css += '}';

		/* ===== Texto del 芍rea Dropzone ===== */
		css += selRoot + ' .aacf7-dropzone-text{';
		var dzAlign = normalizeAlign(dropzone.align);
		if (dzAlign) css += 'text-align:' + dzAlign + ';';
		if (dropzone.text_color) css += 'color:' + toCssVal(dropzone.text_color) + ';';
		if (dropzone.text_size) css += 'font-size:' + toCssVal(dropzone.text_size) + ';';
		if (dropzone.text_weight) css += 'font-weight:' + toCssVal(dropzone.text_weight) + ';';
		if (dropzone.text_padding) css += 'padding:' + toCssVal(dropzone.text_padding) + ';';
		if (dropzone.text_margin) css += 'margin:' + toCssVal(dropzone.text_margin) + ';';
		css += '}';

		/* ===== Icono Dropzone ===== */
		css += selRoot + ' .aacf7-dropzone-icon{';
		if (dropzone.icon_color) css += 'color:' + toCssVal(dropzone.icon_color) + ';';
		if (dropzone.icon_size) css += 'font-size:' + toCssVal(dropzone.icon_size) + ';';
		css += '}';
		if (dzAlign) {
			css += selRoot + ' .aacf7-dropzone-icon{display:block;';
			if (dzAlign === 'left') css += 'margin-left:0;margin-right:auto;';
			if (dzAlign === 'center') css += 'margin-left:auto;margin-right:auto;';
			if (dzAlign === 'right') css += 'margin-left:auto;margin-right:0;';
			css += '}';
		}

		/* ===== Bot車n Adjuntar ===== */
		css += selRoot + ' .aacf7-btn{';
		css += 'display:inline-flex;align-items:center;justify-content:center;';
		if (button.padding) css += 'padding:' + toCssVal(button.padding) + ';';

		var userMargin = toCssVal(button.margin);
		if (userMargin) {
			css += 'margin:' + userMargin + ';';
		}

		if (button.radius) css += 'border-radius:' + toCssVal(button.radius) + ';';

		bw = toCssVal(button.border_width);
		bs = toCssVal(button.border_style);
		bc = toCssVal(button.border_color);
		if (bw || bs || bc) {
			css += 'border-style:' + (bs || 'solid') + ';';
			css += 'border-width:' + (bw || '1px') + ';';
			css += 'border-color:' + (bc || 'transparent') + ';';
		}

		if (button.bg) css += 'background:' + toCssVal(button.bg) + ';';
		if (button.box_shadow) css += 'box-shadow:' + toCssVal(button.box_shadow) + ';';

		// Texto del bot車n
		var btnTextAlign = normalizeAlign(button.text_align);
		if (btnTextAlign) css += 'text-align:' + btnTextAlign + ';';

		if (button.text_color) css += 'color:' + toCssVal(button.text_color) + ';';
		if (button.text_size) css += 'font-size:' + toCssVal(button.text_size) + ';';
		if (button.text_weight) css += 'font-weight:' + toCssVal(button.text_weight) + ';';

		css += 'transition:background-color .25s ease,color .25s ease,border-color .25s ease,box-shadow .25s ease;';
		css += '}';

		if (button.hover || button.text_hover) {
			css += selRoot + ' .aacf7-btn:hover{';
			if (button.hover) css += 'background:' + toCssVal(button.hover) + ';';
			if (button.text_hover) css += 'color:' + toCssVal(button.text_hover) + ';';
			css += '}';
		}

		/* ===== Alineaci車n del bot車n (solo si NO hay margin manual) ===== */
		var btnAlign = normalizeAlign(button.align);
		if (btnAlign && !userMargin) {
			css += selRoot + ' .aacf7-btn{';
			css += 'width:fit-content;';
			if (btnAlign === 'left') css += 'margin-left:0;margin-right:auto;';
			if (btnAlign === 'center') css += 'margin-left:auto;margin-right:auto;';
			if (btnAlign === 'right') css += 'margin-left:auto;margin-right:0;';
			css += '}';
		}

		/* ===== Nota informativa ===== */
		css += selRoot + ' .aacf7-note{';
		var noteAlign = normalizeAlign(note.align);
		if (noteAlign) css += 'text-align:' + noteAlign + ';';
		if (note.color) css += 'color:' + toCssVal(note.color) + ';';
		if (note.size) css += 'font-size:' + toCssVal(note.size) + ';';
		if (note.weight) css += 'font-weight:' + toCssVal(note.weight) + ';';
		if (note.margin) css += 'margin:' + toCssVal(note.margin) + ';';
		if (note.padding) css += 'padding:' + toCssVal(note.padding) + ';';
		css += '}';

		/* ===== DIV archivo adjuntado ===== */
		css += selRoot + ' .aacf7-file-item{';
		var fileAlign = normalizeAlign(file.align);
		if (fileAlign) css += 'text-align:' + fileAlign + ';';
		if (file.bg) css += 'background:' + toCssVal(file.bg) + ';';
		if (file.radius) css += 'border-radius:' + toCssVal(file.radius) + ';';

		bw = toCssVal(file.border_width);
		bs = toCssVal(file.border_style);
		bc = toCssVal(file.border_color);
		if (bw || bs || bc) {
			css += 'border-style:' + (bs || 'solid') + ';';
			css += 'border-width:' + (bw || '1px') + ';';
			css += 'border-color:' + (bc || '#cccccc') + ';';
		}

		if (file.box_shadow) css += 'box-shadow:' + toCssVal(file.box_shadow) + ';';
		if (file.padding) css += 'padding:' + toCssVal(file.padding) + ';';
		if (file.margin) css += 'margin:' + toCssVal(file.margin) + ';';
		css += '}';

		css += selRoot + ' .aacf7-file-row{';
		if (file.row_padding) css += 'padding:' + toCssVal(file.row_padding) + ';';
		if (file.row_margin) css += 'margin:' + toCssVal(file.row_margin) + ';';
		css += '}';

		css += selRoot + ' .aacf7-progress-bar{';
		if (file.progress_color) css += 'background:' + toCssVal(file.progress_color) + ';';
		if (file.progress_height) css += 'height:' + toCssVal(file.progress_height) + ';';
		css += '}';

		css += selRoot + ' .aacf7-file-icon{';
		if (file.icon_color) css += 'color:' + toCssVal(file.icon_color) + ';';
		if (file.icon_size) css += 'font-size:' + toCssVal(file.icon_size) + ';';
		css += '}';

		css += selRoot + ' .aacf7-file-details{';
		if (file.details_color) css += 'color:' + toCssVal(file.details_color) + ';';
		if (file.details_size) css += 'font-size:' + toCssVal(file.details_size) + ';';
		if (file.details_weight) css += 'font-weight:' + toCssVal(file.details_weight) + ';';
		css += '}';

		css += selRoot + ' .aacf7-remove{';
		if (file.remove_color) css += 'color:' + toCssVal(file.remove_color) + ';';
		if (file.remove_size) css += 'font-size:' + toCssVal(file.remove_size) + ';';
		css += '}';

		var styleId = id + '-aacf7-dynamic';
		var styleEl = document.getElementById(styleId);
		if (!styleEl) {
			styleEl = document.createElement('style');
			styleEl.id = styleId;
			document.head.appendChild(styleEl);
		}
		styleEl.textContent = css;
	}

	function ensureFrontendStructure($wrap) {
		var svg =
			'<svg class="aacf7-dropzone-icon" width="56" height="56" viewBox="0 0 24 24" aria-hidden="true" focusable="false">' +
			'<path fill="currentColor" d="M19 15a1 1 0 0 1 2 0v3a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3v-3a1 1 0 1 1 2 0v3a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-3ZM12 3a1 1 0 0 1 1 1v9.586l2.293-2.293a1 1 0 1 1 1.414 1.414l-4.007 4.007a1.25 1.25 0 0 1-1.386.27a1.2 1.2 0 0 1-.27-.27L7.05 12.707a1 1 0 1 1 1.414-1.414L11 13.828V4a1 1 0 0 1 1-1Z"/>' +
			'</svg>';

		var $dropzone = $wrap.find('.aacf7-dropzone');
		if ($dropzone.length && $dropzone.find('.aacf7-dropzone-icon').length === 0) {
			$dropzone.prepend(svg);
		}

		if ($wrap.find('.aacf7-files').length === 0) {
			$wrap.append('<div class="aacf7-files" aria-live="polite"></div>');
		}
	}

	function renderSelectedFile($wrap, file) {
		var $files = $wrap.find('.aacf7-files');
		if (!$files.length) return;

		$files.empty();

		var name = file && file.name ? file.name : 'archivo';
		var size = file && typeof file.size === 'number' ? bytesToHuman(file.size) : '';

		var clipSvg = `
<svg class="aacf7-clip-svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
  <path fill="currentColor" d="M7.5 12.5l7.1-7.1a3 3 0 0 1 4.2 4.2l-8.5 8.5a5 5 0 0 1-7.1-7.1l8.2-8.2a1 1 0 1 1 1.4 1.4l-8.2 8.2a3 3 0 0 0 4.3 4.3l8.5-8.5a1 1 0 0 0-1.4-1.4l-7.1 7.1a1 1 0 0 0 1.4 1.4l6.4-6.4a1 1 0 1 1 1.4 1.4l-6.4 6.4a3 3 0 1 1-4.2-4.2Z"/>
</svg>`.trim();

		var xSvg = `
<svg class="aacf7-x-svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
  <path fill="currentColor" d="M18.3 5.7a1 1 0 0 1 0 1.4L13.4 12l4.9 4.9a1 1 0 1 1-1.4 1.4L12 13.4l-4.9 4.9a1 1 0 1 1-1.4-1.4l4.9-4.9-4.9-4.9A1 1 0 1 1 7.1 5.7l4.9 4.9 4.9-4.9a1 1 0 0 1 1.4 0Z"/>
</svg>`.trim();

		var safeName = $('<div/>').text(name).html();
		var safeSize = $('<div/>').text(size).html();

		var item = `
<div class="aacf7-file-item">
  <div class="aacf7-file-row">
    <span class="aacf7-file-icon" aria-hidden="true">${clipSvg}</span>
    <div class="aacf7-file-details">
      <span class="aacf7-file-name">${safeName}</span>
      <span class="aacf7-file-size">${safeSize}</span>
    </div>
    <button type="button" class="aacf7-remove" aria-label="Eliminar archivo">${xSvg}</button>
  </div>

  <div class="aacf7-progress" aria-hidden="true">
    <div class="aacf7-progress-bar" style="width:0%"></div>
  </div>
</div>`.trim();

		$files.append(item);

		var $bar = $files.find('.aacf7-progress-bar');
		var p = 0;
		var t = setInterval(function () {
			p += 10;
			if (p >= 100) {
				p = 100;
				clearInterval(t);
			}
			$bar.css('width', p + '%');
		}, 80);
	}

	function clearSelectedFile($wrap) {
		var $input = $wrap.find('.aacf7-input');
		$input.val('');
		$wrap.find('.aacf7-files').empty();
	}

	function initUploader($wrap) {
		var $input = $wrap.find('.aacf7-input');
		var $dropzone = $wrap.find('.aacf7-dropzone');

		ensureFrontendStructure($wrap);
		applyInlineStyles($wrap);

		$wrap.on('click', '[data-aacf7-browse="1"]', function (e) {
			e.preventDefault();
			$input.trigger('click');
		});

		$dropzone.on('click', function (e) {
			if ($(e.target).closest('.aacf7-btn').length) return;
			$input.trigger('click');
		});

		$input.on('change', function () {
			var file = this.files && this.files[0] ? this.files[0] : null;
			if (!file) {
				clearSelectedFile($wrap);
				return;
			}
			renderSelectedFile($wrap, file);
		});

		$wrap.on('click', '.aacf7-remove', function () {
			clearSelectedFile($wrap);
		});

		$dropzone.on('dragenter dragover', function (e) {
			e.preventDefault();
			e.stopPropagation();
			$dropzone.addClass('is-dragover');
		});

		$dropzone.on('dragleave dragend drop', function (e) {
			e.preventDefault();
			e.stopPropagation();
			$dropzone.removeClass('is-dragover');
		});

		$dropzone.on('drop', function (e) {
			var dt = e.originalEvent.dataTransfer;
			if (!dt || !dt.files || !dt.files.length) return;

			var file = dt.files[0];
			renderSelectedFile($wrap, file);

			try {
				var data = new DataTransfer();
				data.items.add(file);
				$input[0].files = data.files;
				$input.trigger('change');
			} catch (err) {}
		});
	}

	$(function () {
		$('.aacf7-wrap').each(function () {
			initUploader($(this));
		});
	});
})(jQuery);