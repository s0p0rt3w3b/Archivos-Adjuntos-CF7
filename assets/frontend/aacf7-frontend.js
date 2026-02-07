(function ($) {
	'use strict';

	function cssEscapeIdent(s) {
		return String(s).replace(/[^a-zA-Z0-9_-]/g, '\\$&');
	}

	function toCssVal(v) {
		if (v === undefined || v === null) return '';
		var s = String(v).trim();
		return s;
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
		try {
			return JSON.parse(raw);
		} catch (e) {
			return {};
		}
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

		/* ===== Contenedor principal ===== */
		css += selRoot + '{';
		var align = normalizeAlign(container.align);
		if (align) css += 'text-align:' + align + ';';
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
		css += '}';

		/* ===== Título ===== */
		css += selRoot + ' .aacf7-title{';
		if (container.title_color) css += 'color:' + toCssVal(container.title_color) + ';';
		if (container.title_size) css += 'font-size:' + toCssVal(container.title_size) + ';';
		if (container.title_weight) css += 'font-weight:' + toCssVal(container.title_weight) + ';';
		css += '}';

		/* ===== Dropzone ===== */
		css += selRoot + ' .aacf7-dropzone{';
		align = normalizeAlign(dropzone.align);
		if (align) css += 'text-align:' + align + ';';
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
		css += '}';

		css += selRoot + ' .aacf7-dropzone-text{';
		if (dropzone.text_color) css += 'color:' + toCssVal(dropzone.text_color) + ';';
		if (dropzone.text_size) css += 'font-size:' + toCssVal(dropzone.text_size) + ';';
		if (dropzone.text_weight) css += 'font-weight:' + toCssVal(dropzone.text_weight) + ';';
		css += '}';

		css += selRoot + ' .aacf7-dropzone-icon{';
		if (dropzone.icon_color) css += 'color:' + toCssVal(dropzone.icon_color) + ';';
		if (dropzone.icon_size) css += 'font-size:' + toCssVal(dropzone.icon_size) + ';';
		css += '}';

		/* ===== Botón ===== */
		css += selRoot + ' .aacf7-btn{';
		css += 'display:inline-flex;align-items:center;justify-content:center;';
		if (button.padding) css += 'padding:' + toCssVal(button.padding) + ';';
		if (button.margin) css += 'margin:' + toCssVal(button.margin) + ';';
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

		// texto del botón
		if (button.text_color) css += 'color:' + toCssVal(button.text_color) + ';';
		if (button.text_size) css += 'font-size:' + toCssVal(button.text_size) + ';';
		if (button.text_weight) css += 'font-weight:' + toCssVal(button.text_weight) + ';';
		css += '}';

		if (button.hover || button.text_hover) {
			css += selRoot + ' .aacf7-btn:hover{';
			if (button.hover) css += 'background:' + toCssVal(button.hover) + ';';
			if (button.text_hover) css += 'color:' + toCssVal(button.text_hover) + ';';
			css += '}';
		}

		/* ===== Nota ===== */
		css += selRoot + ' .aacf7-note{';
		align = normalizeAlign(note.align);
		if (align) css += 'text-align:' + align + ';';
		if (note.color) css += 'color:' + toCssVal(note.color) + ';';
		if (note.size) css += 'font-size:' + toCssVal(note.size) + ';';
		if (note.weight) css += 'font-weight:' + toCssVal(note.weight) + ';';
		if (note.margin) css += 'margin:' + toCssVal(note.margin) + ';';
		css += '}';

		/* ===== Archivo adjunto (si existe UI) ===== */
		if (file && Object.keys(file).length) {
			css += selRoot + ' .aacf7-file-item{';
			align = normalizeAlign(file.align);
			if (align) css += 'text-align:' + align + ';';
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
		}

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
		// SVG de subida (usa currentColor para poder colorearlo con CSS)
		var svg =
			'<svg class="aacf7-dropzone-icon" width="56" height="56" viewBox="0 0 24 24" aria-hidden="true" focusable="false">' +
			'<path fill="currentColor" d="M19 15a1 1 0 0 1 2 0v3a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3v-3a1 1 0 1 1 2 0v3a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-3ZM12 3a1 1 0 0 1 1 1v9.586l2.293-2.293a1 1 0 1 1 1.414 1.414l-4.007 4.007a1.25 1.25 0 0 1-1.386.27a1.2 1.2 0 0 1-.27-.27L7.05 12.707a1 1 0 1 1 1.414-1.414L11 13.828V4a1 1 0 0 1 1-1Z"/>' +
			'</svg>';

		var $dropzone = $wrap.find('.aacf7-dropzone');
		if ($dropzone.length && $dropzone.find('.aacf7-dropzone-icon').length === 0) {
			$dropzone.prepend(svg);
		}

		// Contenedor de archivos (si no existe)
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

		var item =
			'<div class="aacf7-file-item">' +
			'  <div class="aacf7-file-row">' +
			'    <span class="aacf7-file-icon" aria-hidden="true">📎</span>' +
			'    <div class="aacf7-file-details">' +
			'      <span class="aacf7-file-name">' + $('<div/>').text(name).html() + '</span>' +
			'      <span class="aacf7-file-size">' + $('<div/>').text(size).html() + '</span>' +
			'    </div>' +
			'    <button type="button" class="aacf7-remove" aria-label="Eliminar archivo">×</button>' +
			'  </div>' +
			'  <div class="aacf7-progress" aria-hidden="true">' +
			'    <div class="aacf7-progress-bar" style="width:0%"></div>' +
			'  </div>' +
			'</div>';

		$files.append(item);

		// Simulación de progreso (UI). Para progreso real se necesita subida AJAX.
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

		// Click en botón
		$wrap.on('click', '[data-aacf7-browse="1"]', function (e) {
			e.preventDefault();
			$input.trigger('click');
		});

		// Click en dropzone (abre selector)
		$dropzone.on('click', function (e) {
			// Evitar doble click cuando se pulsa el botón
			if ($(e.target).closest('.aacf7-btn').length) return;
			$input.trigger('click');
		});

		// Selección de archivo
		$input.on('change', function () {
			var file = this.files && this.files[0] ? this.files[0] : null;
			if (!file) {
				clearSelectedFile($wrap);
				return;
			}
			renderSelectedFile($wrap, file);
		});

		// Eliminar
		$wrap.on('click', '.aacf7-remove', function () {
			clearSelectedFile($wrap);
		});

		// Drag & drop (1 archivo)
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

			// asignar al input (solo 1). Nota: en algunos navegadores no se puede setear FileList directo.
			// Aquí mostramos UI igualmente y dejamos el input para selección manual si el browser bloquea.
			var file = dt.files[0];
			renderSelectedFile($wrap, file);

			// Intentar setear el input (funciona en algunos browsers modernos)
			try {
				var data = new DataTransfer();
				data.items.add(file);
				$input[0].files = data.files;
				$input.trigger('change');
			} catch (err) {
				// fallback: no hacemos nada, pero UI ya muestra el archivo
			}
		});
	}

	$(function () {
		$('.aacf7-wrap').each(function () {
			initUploader($(this));
		});
	});
})(jQuery);