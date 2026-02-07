(function ($) {
	'use strict';

	function updateAdjuntosTag($panel) {
		var required = $panel.find('input[name="required"]').prop('checked');
		var name = ($panel.find('input[name="name"]').val() || '').trim();
		var type = required ? 'adjuntos_cf7*' : 'adjuntos_cf7';

		var tag = '[' + type;
		if (name) tag += ' ' + name;
		tag += ']';

		$panel.find('input.tag').val(tag);
	}

	$(document).on('click', '.tag-generator-panel .control-box input, .tag-generator-panel .control-box', function () {
		// noop: permite que CF7 monte el panel antes de leer valores
	});

	// Cada vez que cambie algo en el panel del generador, actualizar preview
	$(document).on('keyup change', '.tag-generator-panel', function () {
		var $panel = $(this);

		// Solo nuestro generador: se detecta por el input name=tg-name? aquí vamos por presencia de nuestro campo
		if ($panel.find('#aacf7-adjuntos-name').length) {
			updateAdjuntosTag($panel);
		}
	});

	// Primer render
	$(function () {
		$('.tag-generator-panel').each(function () {
			var $panel = $(this);
			if ($panel.find('#aacf7-adjuntos-name').length) {
				updateAdjuntosTag($panel);
			}
		});
	});
})(jQuery);