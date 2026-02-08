(function ($) {
	'use strict';

	function insertAtCursor($textarea, text) {
		var el = $textarea.get(0);
		if (!el) return;

		var start = el.selectionStart || 0;
		var end = el.selectionEnd || 0;
		var val = $textarea.val();

		$textarea.val(val.substring(0, start) + text + val.substring(end));
		el.selectionStart = el.selectionEnd = start + text.length;
		$textarea.trigger('change');
	}

	function ensureButtonNearFormTextarea() {
		var $textarea = $('#wpcf7-form');
		if (!$textarea.length) return;

		if ($('#aacf7-btnbar').length) return;

		// Intentar copiar estilo de los botones CF7 (clases WP)
		var $bar = $(
			'<div id="aacf7-btnbar" class="wp-core-ui" style="margin:8px 0;">' +
				'<button type="button" class="button" id="aacf7-cf7-btn">archivos adjuntos</button>' +
			'</div>'
		);

		$textarea.before($bar);
	}

	function isValidFieldName(name) {
		// Reglas: empieza con letra, luego letras/números/_/-
		return /^[a-zA-Z][a-zA-Z0-9_-]*$/.test(name);
	}

	function openOverlay() {
		var $src = $('#aacf7-taggen-panel .tag-generator-panel');
		if (!$src.length) {
			alert('No se encontró el panel AACF7 (HTML).');
			return;
		}

		var $overlay = $('<div class="aacf7-overlay" />');
		var $inner = $('<div class="aacf7-overlay-inner" />');
		var $panel = $src.clone(true, true);

		// Nota de validación
		$panel.find('.control-box fieldset').append(
			'<p style="margin-top:10px;color:#666;">' +
			'El nombre debe empezar con una letra (ej: <code>adjuntos1</code>). No uses solo números.' +
			'</p>'
		);

		$inner.append($panel);
		$overlay.append($inner);
		$('body').append($overlay);

		function updatePreview() {
			var required = $panel.find('input[name="required"]').prop('checked');
			var name = ($panel.find('input[name="name"]').val() || '').trim();
			var type = required ? 'adjuntos_cf7*' : 'adjuntos_cf7';

			var tag = '[' + type + (name ? ' ' + name : '') + ']';
			$panel.find('input.tag').val(tag);
		}

		$panel.on('keyup change', 'input', updatePreview);
		updatePreview();

		$panel.on('click', '.insert-tag', function (e) {
			e.preventDefault();

			var name = ($panel.find('input[name="name"]').val() || '').trim();

			if (!name) {
				alert('Ingresa un nombre (ej: adjuntos1).');
				return;
			}

			if (!isValidFieldName(name)) {
				alert('Nombre inválido. Debe empezar con letra y solo usar a-z, 0-9, _ o - (ej: adjuntos1).');
				return;
			}

			var required = $panel.find('input[name="required"]').prop('checked');
			var type = required ? 'adjuntos_cf7*' : 'adjuntos_cf7';
			var tag = '[' + type + ' ' + name + ']';

			var $textarea = $('#wpcf7-form');
			insertAtCursor($textarea, tag);

			$overlay.remove();
		});

		$overlay.on('click', function (e) {
			if (e.target === this) $overlay.remove();
		});

		$(document).on('keydown.aacf7', function (e) {
			if (e.key === 'Escape') {
				$overlay.remove();
				$(document).off('keydown.aacf7');
			}
		});
	}

	$(function () {
		ensureButtonNearFormTextarea();

		if (window.MutationObserver) {
			var obs = new MutationObserver(function () {
				ensureButtonNearFormTextarea();
			});
			obs.observe(document.body, { childList: true, subtree: true });
		}
	});

	$(document).on('click', '#aacf7-cf7-btn', function (e) {
		e.preventDefault();
		openOverlay();
	});
})(jQuery);