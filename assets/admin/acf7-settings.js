(function () {
	'use strict';

	var TAB_IDS = [
		'tab-almacenamiento',
		'tab-opciones',
		'tab-textos',
		'tab-validacion',
		'tab-estilos'
	];

	function updateStorageSubboxes(wrapper) {
		var checked = wrapper.querySelector('input[name="aacf7_storage_mode"]:checked');
		var mode = checked ? checked.value : 'default';

		var boxDefault = wrapper.querySelector('[data-aacf7-subbox="default"]');
		var boxUser = wrapper.querySelector('[data-aacf7-subbox="user"]');

		if (boxDefault) boxDefault.style.display = (mode === 'default') ? '' : 'none';
		if (boxUser) boxUser.style.display = (mode === 'user') ? '' : 'none';
	}

	function updateFormatActives(wrapper) {
		wrapper.querySelectorAll('.acf7-format').forEach(function (label) {
			var cb = label.querySelector('input[type="checkbox"]');
			if (!cb) return;
			label.classList.toggle('active', cb.checked);
		});
	}

	function closeAllInlinePickers(exceptCombo) {
		document.querySelectorAll('.color-picker-combo[data-pickr-open="1"]').forEach(function (combo) {
			if (exceptCombo && combo === exceptCombo) return;
			combo.dataset.pickrOpen = '0';
		});
	}

	function initInlinePickr(wrapper) {
		if (!window.Pickr) return;

		wrapper.querySelectorAll('.color-picker-combo').forEach(function (combo) {
			if (combo.__pickrReady) return;
			combo.__pickrReady = true;

			var input = combo.querySelector('input.color-input-full[type="color"]');
			var swatch = combo.querySelector('.color-swatch');
			if (!input || !swatch) return;

			// Valor inicial
			var initial = input.value || combo.dataset.color || '#0073aa';
			input.value = initial;
			combo.dataset.color = initial;
			swatch.style.background = initial;

			// Ocultamos input nativo pero sigue enviándose por POST
			input.style.display = 'none';

			// Contenedor inline (debajo del combo)
			var inlineHost = document.createElement('div');
			inlineHost.className = 'acf7-pickr-inline';
			combo.appendChild(inlineHost);

			// Estado cerrado por defecto
			combo.dataset.pickrOpen = '0';

			var pickr = Pickr.create({
				el: inlineHost,
				theme: 'nano',
				default: initial,
				inline: true,          // <- clave: NO flotante
				autoReposition: false, // no aplica en inline
				lockOpacity: true,
				comparison: false,
				components: {
					preview: true,
					opacity: false,
					hue: true,
					interaction: {
						hex: true,
						rgba: true,
						input: true,
						clear: false,
						save: true
					}
				}
			});

			function setColor(hex) {
				if (!hex) return;
				input.value = hex;
				combo.dataset.color = hex;
				swatch.style.background = hex;
				input.dispatchEvent(new Event('change', { bubbles: true }));
			}

			pickr.on('change', function (color) {
				setColor(color.toHEXA().toString());
			});

			pickr.on('save', function (color) {
				setColor(color.toHEXA().toString());
				// al guardar, cerramos (opcional)
				combo.dataset.pickrOpen = '0';
			});

			// Click en todo el combo abre/cierra (y cierra otros)
			combo.addEventListener('click', function (e) {
				// si el click viene del picker (inputs internos), no togglear
				if (e.target && (e.target.closest('.pcr-app') || e.target.closest('.pcr-interaction'))) {
					return;
				}

				var open = combo.dataset.pickrOpen === '1';
				if (!open) {
					closeAllInlinePickers(combo);
					combo.dataset.pickrOpen = '1';
				} else {
					combo.dataset.pickrOpen = '0';
				}
			});
		});
	}

	function initWrapper(wrapper) {
		var nav = wrapper.querySelector('.acf7-tabs-nav');
		if (!nav) return;

		var buttons = Array.prototype.slice.call(nav.querySelectorAll('button'));
		var panels = TAB_IDS.map(function (id) { return wrapper.querySelector('#' + id); });
		if (!buttons.length || panels.some(function (p) { return !p; })) return;

		function activate(index) {
			buttons.forEach(function (b, i) { b.classList.toggle('active', i === index); });
			panels.forEach(function (p, i) { p.classList.toggle('active', i === index); });
			initInlinePickr(wrapper);
		}

		buttons.forEach(function (btn, idx) {
			btn.addEventListener('click', function () { activate(idx); });
		});

		activate(0);

		wrapper.addEventListener('change', function (e) {
			var t = e.target;
			if (t && t.matches('input[name="aacf7_storage_mode"]')) updateStorageSubboxes(wrapper);
			if (t && t.matches('.acf7-format input[type="checkbox"]')) updateFormatActives(wrapper);
		});

		updateStorageSubboxes(wrapper);
		updateFormatActives(wrapper);
		initInlinePickr(wrapper);
	}

	document.addEventListener('click', function (e) {
		// click fuera de cualquier combo => cerrar todos
		if (!e.target.closest('.color-picker-combo')) {
			closeAllInlinePickers(null);
		}
	});

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.acf7-wrapper[data-aacf7-settings="1"]').forEach(initWrapper);
	});
})();