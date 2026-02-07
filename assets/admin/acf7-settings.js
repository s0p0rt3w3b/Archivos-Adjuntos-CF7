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

	function clamp(n, min, max) {
		return Math.min(max, Math.max(min, n));
	}

	function getPickrAppForButton(btn) {
		// Pickr crea un .pcr-app global, pero lo “asocia” al botón que lo abrió.
		// Buscamos el app visible más cercano en el DOM (fallback: el único visible).
		var apps = Array.prototype.slice.call(document.querySelectorAll('.pcr-app'));
		if (!apps.length) return null;

		// 1) si hay uno visible (display != none), usar ese
		var visible = apps.find(function (a) {
			return a.offsetParent !== null && getComputedStyle(a).display !== 'none';
		});
		return visible || apps[apps.length - 1];
	}

	function positionAppUnderCombo(app, combo) {
		if (!app || !combo) return;

		var r = combo.getBoundingClientRect();
		var gap = 8;

		// Asegurar medidas del app (si aún no tiene, forzar layout)
		var appW = app.offsetWidth || 260;
		var appH = app.offsetHeight || 320;

		var vw = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);
		var vh = Math.max(document.documentElement.clientHeight, window.innerHeight || 0);

		var left = clamp(r.left, 8, vw - appW - 8);

		// Si no hay espacio abajo, poner arriba
		var preferTop = r.bottom + gap;
		var top;
		if (preferTop + appH <= vh - 8) {
			top = preferTop;
		} else {
			top = clamp(r.top - gap - appH, 8, vh - appH - 8);
		}

		app.style.position = 'fixed';
		app.style.left = left + 'px';
		app.style.top = top + 'px';
		app.style.zIndex = '999999';
	}

	function initPickr(wrapper) {
		if (!window.Pickr) return;

		wrapper.querySelectorAll('.color-picker-combo').forEach(function (combo) {
			if (combo.__pickrReady) return;
			combo.__pickrReady = true;

			var input = combo.querySelector('input.color-input-full[type="color"]');
			var swatch = combo.querySelector('.color-swatch');
			if (!input || !swatch) return;

			var initial = input.value || combo.dataset.color || '#0073aa';
			input.value = initial;
			swatch.style.background = initial;

			// botón overlay clickeable
			var btn = document.createElement('button');
			btn.type = 'button';
			btn.className = 'acf7-pickr-btn';
			btn.setAttribute('aria-label', 'Seleccionar un color');
			btn.style.all = 'unset';
			btn.style.position = 'absolute';
			btn.style.inset = '0';
			btn.style.cursor = 'pointer';
			btn.style.zIndex = '2';
			combo.appendChild(btn);

			// ocultamos el input nativo, pero queda para POST
			input.style.display = 'none';

			var pickr = Pickr.create({
				el: btn,
				theme: 'nano',
				default: initial,
				lockOpacity: true,
				comparison: false,
				useAsButton: true,
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

			function reposition() {
				var app = getPickrAppForButton(btn);
				positionAppUnderCombo(app, combo);
			}

			pickr.on('show', function () {
				// doble rAF para asegurar que el DOM del picker ya esté medible
				window.requestAnimationFrame(function () {
					window.requestAnimationFrame(reposition);
				});
			});

			pickr.on('change', function (color) {
				setColor(color.toHEXA().toString());
				// mientras arrastra, mantener bien posicionado
				reposition();
			});

			pickr.on('save', function (color) {
				setColor(color.toHEXA().toString());
				pickr.hide();
			});

			window.addEventListener('scroll', reposition, true);
			window.addEventListener('resize', reposition);
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
			initPickr(wrapper);
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
		initPickr(wrapper);
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.acf7-wrapper[data-aacf7-settings="1"]').forEach(initWrapper);
	});
})();