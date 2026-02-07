(function () {
	'use strict';

	var TAB_IDS = [
		'tab-almacenamiento',
		'tab-opciones',
		'tab-textos',
		'tab-validacion',
		'tab-estilos'
	];

	function getFormIdFromScreen() {
		var el = document.querySelector('input[name="post_ID"]');
		return el && el.value ? String(el.value) : '';
	}

	function storageKey(wrapper) {
		var formId = getFormIdFromScreen();
		if (!formId) formId = wrapper.getAttribute('data-form-id') || '';
		return 'aacf7_active_tab_' + (formId || 'global');
	}

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
		return Math.min(Math.max(n, min), max);
	}

	function closeAllPickers(exceptCombo) {
		document.querySelectorAll('.color-picker-combo[data-pickr-open="1"]').forEach(function (combo) {
			if (exceptCombo && combo === exceptCombo) return;
			combo.dataset.pickrOpen = '0';
			if (combo.__pickrInstance) {
				try { combo.__pickrInstance.hide(); } catch (e) {}
			}
		});
	}

	function positionPickrToCombo(combo) {
		if (!combo || !combo.__pickrInstance) return;

		var root = combo.__pickrInstance.getRoot && combo.__pickrInstance.getRoot();
		var app = root && root.app ? root.app : null;
		if (!app) return;

		if (app.parentNode !== document.body) document.body.appendChild(app);

		var rect = combo.getBoundingClientRect();

		var prevVis = app.style.visibility;
		var prevDisp = app.style.display;
		app.style.visibility = 'hidden';
		app.style.display = 'block';
		var appRect = app.getBoundingClientRect();
		app.style.visibility = prevVis || '';
		app.style.display = prevDisp || '';

		var viewportW = window.innerWidth;
		var viewportH = window.innerHeight;
		var gap = 8;

		var spaceBelow = viewportH - rect.bottom;
		var spaceAbove = rect.top;
		var placeBelow = (spaceBelow >= appRect.height + gap) || (spaceBelow >= spaceAbove);

		var top = placeBelow ? (rect.bottom + gap) : (rect.top - appRect.height - gap);
		var left = rect.left;

		left = clamp(left, 8, viewportW - appRect.width - 8);
		top = clamp(top, 8, viewportH - appRect.height - 8);

		app.style.position = 'fixed';
		app.style.left = left + 'px';
		app.style.top = top + 'px';
		app.style.margin = '0';
		app.style.zIndex = '999999';
	}

	function initFloatingPickr(wrapper) {
		if (!window.Pickr) return;

		wrapper.querySelectorAll('.color-picker-combo').forEach(function (combo) {
			if (combo.__pickrReady) return;
			combo.__pickrReady = true;

			var input = combo.querySelector('input.color-input-full[type="color"]');
			var swatch = combo.querySelector('.color-swatch');
			if (!input || !swatch) return;

			var initial = input.value || combo.dataset.color || '#0073aa';
			input.value = initial;
			combo.dataset.color = initial;
			swatch.style.background = initial;

			input.setAttribute('aria-hidden', 'true');
			input.tabIndex = -1;

			combo.dataset.pickrOpen = '0';

			var pickr = Pickr.create({
				el: combo,
				theme: 'nano',
				default: initial,
				inline: false,
				useAsButton: true,
				autoReposition: false,
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

			combo.__pickrInstance = pickr;

			function setColor(hex) {
				if (!hex) return;
				input.value = hex;
				combo.dataset.color = hex;
				swatch.style.background = hex;
				input.dispatchEvent(new Event('change', { bubbles: true }));
			}

			pickr.on('init', function (instance) {
				var app = instance.getRoot().app;
				if (app) {
					app.classList.add('aacf7-pickr-floating');
					if (app.parentNode !== document.body) document.body.appendChild(app);
				}
			});

			pickr.on('show', function () {
				combo.dataset.pickrOpen = '1';
				combo.__pickrOpenedAt = Date.now();
				setTimeout(function () { positionPickrToCombo(combo); }, 0);
			});

			pickr.on('hide', function () {
				combo.dataset.pickrOpen = '0';
			});

			pickr.on('change', function (color) {
				setColor(color.toHEXA().toString());
			});

			pickr.on('save', function (color) {
				setColor(color.toHEXA().toString());
				pickr.hide();
			});

			var onMove = function () {
				if (combo.dataset.pickrOpen === '1') positionPickrToCombo(combo);
			};
			window.addEventListener('resize', onMove);
			window.addEventListener('scroll', onMove, true);

			// ✅ SOLO abrir/cerrar con click en el combo, sin bloquear el modal
			combo.addEventListener('pointerdown', function (e) {
				if (e.target && e.target.closest && e.target.closest('.pcr-app')) return;

				e.preventDefault();
				e.stopPropagation();

				var open = combo.dataset.pickrOpen === '1';
				if (!open) {
					closeAllPickers(combo);
					pickr.show();
				}
			}, true);

			combo.addEventListener('click', function (e) {
				e.stopPropagation();
			}, true);
		});
	}

	function initWrapper(wrapper) {
		var nav = wrapper.querySelector('.acf7-tabs-nav');
		if (!nav) return;

		var buttons = Array.prototype.slice.call(nav.querySelectorAll('button'));
		var panels = TAB_IDS.map(function (id) { return wrapper.querySelector('#' + id); });
		if (!buttons.length || panels.some(function (p) { return !p; })) return;

		var key = storageKey(wrapper);

		function activate(index, persist) {
			buttons.forEach(function (b, i) { b.classList.toggle('active', i === index); });
			panels.forEach(function (p, i) { p.classList.toggle('active', i === index); });

			if (persist !== false) {
				try { localStorage.setItem(key, String(index)); } catch (e) {}
			}

			initFloatingPickr(wrapper);
		}

		// Restaurar tab
		var savedIndex = 0;
		try {
			var raw = localStorage.getItem(key);
			if (raw !== null) {
				var n = parseInt(raw, 10);
				if (!isNaN(n) && n >= 0 && n < buttons.length) savedIndex = n;
			}
		} catch (e) {}

		buttons.forEach(function (btn, idx) {
			btn.addEventListener('click', function () { activate(idx, true); });
		});

		activate(savedIndex, false);

		wrapper.addEventListener('change', function (e) {
			var t = e.target;
			if (t && t.matches('input[name="aacf7_storage_mode"]')) updateStorageSubboxes(wrapper);
			if (t && t.matches('.acf7-format input[type="checkbox"]')) updateFormatActives(wrapper);
		});

		updateStorageSubboxes(wrapper);
		updateFormatActives(wrapper);
		initFloatingPickr(wrapper);
	}

	document.addEventListener('pointerdown', function (e) {
		if (e.target.closest('.color-picker-combo') || e.target.closest('.pcr-app')) return;
		closeAllPickers(null);
	}, true);

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') closeAllPickers(null);
	});

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.acf7-wrapper[data-aacf7-settings="1"]').forEach(initWrapper);
	});
})();