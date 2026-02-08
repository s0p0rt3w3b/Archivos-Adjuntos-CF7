<?php
namespace AACF7;

if (!defined('ABSPATH')) {
	exit;
}

class CF7_Integration {

	public function init(): void {
		// Frontend tag
		add_action('wpcf7_init', [$this, 'register_form_tag']);

		// Admin UI (tab)
		add_filter('wpcf7_editor_panels', [$this, 'register_editor_panel']);
		add_action('wpcf7_after_save', [$this, 'save_form_settings']);

		// Admin assets + panel HTML para overlay (modal)
		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
		add_action('wpcf7_admin_footer', [$this, 'print_tag_generator_panel_html']);

		// Frontend assets
		add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
	}

	/* -----------------------------
	 *  ADMIN: assets (JS + CSS)
	 * ----------------------------- */

	public function enqueue_admin_assets($hook): void {
		if (!is_admin()) {
			return;
		}

		$screen = function_exists('get_current_screen') ? get_current_screen() : null;
		if (
			!$screen
			|| (strpos((string) $screen->id, 'wpcf7') === false && strpos((string) $screen->base, 'wpcf7') === false)
		) {
			return;
		}

		// Botón + modal (ya lo usas)
		wp_enqueue_script(
			'aacf7-cf7-taggen',
			AACF7_PLUGIN_URL . 'assets/admin/aacf7-cf7-taggen.js',
			['jquery'],
			AACF7_VERSION,
			true
		);

		wp_enqueue_style(
			'aacf7-admin',
			AACF7_PLUGIN_URL . 'assets/admin/aacf7-admin.css',
			[],
			AACF7_VERSION
		);

		// UI settings
		wp_enqueue_style(
			'aacf7-settings',
			AACF7_PLUGIN_URL . 'assets/admin/acf7-settings.css',
			[],
			AACF7_VERSION
		);

		// Pickr (color picker moderno)
		wp_enqueue_style(
			'pickr-nano',
			'https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/themes/nano.min.css',
			[],
			AACF7_VERSION
		);

		wp_enqueue_script(
			'pickr',
			'https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/pickr.min.js',
			[],
			AACF7_VERSION,
			true
		);

		wp_enqueue_script(
			'aacf7-settings',
			AACF7_PLUGIN_URL . 'assets/admin/acf7-settings.js',
			['pickr'],
			AACF7_VERSION,
			true
		);
	}

	public function print_tag_generator_panel_html(): void {
		?>
		<div id="aacf7-taggen-panel" style="display:none;">
			<div class="tag-generator-panel">
				<div class="control-box">
					<fieldset>
						<legend><?php echo esc_html__('Inserta el campo de Archivos Adjuntos en el formulario.', 'archivos-adjuntos-cf7'); ?></legend>

						<table class="form-table">
							<tbody>
								<tr>
									<th scope="row"><?php echo esc_html__('Requerido', 'archivos-adjuntos-cf7'); ?></th>
									<td>
										<label><input type="checkbox" name="required" /> <?php echo esc_html__('Sí', 'archivos-adjuntos-cf7'); ?></label>
									</td>
								</tr>

								<tr>
									<th scope="row">
										<label for="aacf7-adjuntos-name"><?php echo esc_html__('Nombre', 'archivos-adjuntos-cf7'); ?></label>
									</th>
									<td>
										<input type="text" id="aacf7-adjuntos-name" name="name" class="tg-name oneline" />
									</td>
								</tr>

							</tbody>
						</table>
					</fieldset>
				</div>

				<div class="insert-box">
					<input type="text" class="tag code" readonly="readonly" onfocus="this.select()" />

					<div class="submitbox">
						<input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr__('Insert Tag', 'contact-form-7'); ?>" />
					</div>

					<br class="clear" />
				</div>
			</div>
		</div>
		<?php
	}

	/* -----------------------------
	 *  FRONTEND: FORM TAG
	 * ----------------------------- */

	public function register_form_tag(): void {
		if (!function_exists('wpcf7_add_form_tag')) {
			return;
		}

		wpcf7_add_form_tag(
			'adjuntos_cf7',
			[$this, 'render_form_tag'],
			['name-attr' => true]
		);

		wpcf7_add_form_tag(
			'adjuntos_cf7*',
			[$this, 'render_form_tag'],
			['name-attr' => true]
		);
	}

	public function enqueue_frontend_assets(): void {
		wp_enqueue_style(
			'aacf7-frontend',
			AACF7_PLUGIN_URL . 'assets/frontend/aacf7-frontend.css',
			[],
			AACF7_VERSION
		);

		wp_enqueue_script(
			'aacf7-frontend',
			AACF7_PLUGIN_URL . 'assets/frontend/aacf7-frontend.js',
			['jquery'],
			AACF7_VERSION,
			true
		);
	}

	public function render_form_tag($tag): string {
		if (!is_object($tag)) {
			return '';
		}

		$name = isset($tag->name) ? (string) $tag->name : '';
		if ($name === '') {
			return '';
		}

		$is_required = method_exists($tag, 'is_required') ? (bool) $tag->is_required() : false;

		// ✅ obtener el ID REAL del formulario CF7 en frontend
		$form_id = 0;
		if (class_exists('\\WPCF7_ContactForm') && method_exists('\\WPCF7_ContactForm', 'get_current')) {
			$cf7 = \WPCF7_ContactForm::get_current();
			if ($cf7 && method_exists($cf7, 'id')) {
				$form_id = (int) $cf7->id();
			}
		}
		if (!$form_id) {
			$form_id = $this->get_current_form_id(); // fallback
		}

		$settings = $this->get_form_settings($form_id);

		// ✅ estilos para frontend (los lee assets/frontend/aacf7-frontend.js)
		$styles = $settings['styles'] ?? [];
		if (!is_array($styles)) {
			$styles = [];
		}
		$styles_json = wp_json_encode($styles);

		$max_files   = (int) ($settings['max_files'] ?? 1);
		$max_size_kb = (int) ($settings['max_size_kb'] ?? 1024);

		$allowed_ext = $settings['allowed_ext'] ?? ['jpg','jpeg','png','webp','bmp','pdf','xlsx','xls','doc','docx'];
		if (!is_array($allowed_ext)) {
			$allowed_ext = ['jpg','jpeg','png','webp','bmp','pdf','xlsx','xls','doc','docx'];
		}

		$delivery_mode = (string) ($settings['delivery_mode'] ?? 'url'); // attach|url
		
		// ✅ TEXTOS (admin -> frontend)
		$text_title = trim((string) ($settings['text_title'] ?? ''));
		if ($text_title === '') {
			$text_title = esc_html__('Adjuntar archivos', 'archivos-adjuntos-cf7');
		}

		$text_drop = trim((string) ($settings['text_drop'] ?? ''));
		if ($text_drop === '') {
			$text_drop = esc_html__('Arrastra y suelta archivos aquí o haz clic para seleccionar', 'archivos-adjuntos-cf7');
		}

		$text_button = trim((string) ($settings['text_button'] ?? ''));
		if ($text_button === '') {
			$text_button = esc_html__('Adjuntar', 'archivos-adjuntos-cf7');
		}

		// Notas por modo + fallback legacy
		$text_note_legacy = trim((string) ($settings['text_note'] ?? ''));
		$text_note_url = trim((string) ($settings['text_note_url'] ?? ''));
		$text_note_attach = trim((string) ($settings['text_note_attach'] ?? ''));

		$note_text = ($delivery_mode === 'url')
			? (($text_note_url !== '') ? $text_note_url : $text_note_legacy)
			: (($text_note_attach !== '') ? $text_note_attach : $text_note_legacy);

        // ✅ MENSAJES (admin -> frontend) para validación JS (sin modal)
        $msg_required = trim((string) ($settings['msg_required'] ?? ''));
        if ($msg_required === '') {
        	$msg_required = esc_html__('Este campo es obligatorio.', 'archivos-adjuntos-cf7');
        }
        
        $msg_size = trim((string) ($settings['msg_size'] ?? ''));
        if ($msg_size === '') {
        	$msg_size = esc_html__('El archivo excede el tamaño máximo permitido.', 'archivos-adjuntos-cf7');
        }
        
        $msg_count = trim((string) ($settings['msg_count'] ?? ''));
        if ($msg_count === '') {
        	$msg_count = esc_html__('Has superado el número máximo de archivos.', 'archivos-adjuntos-cf7');
        }
        
        $msg_type = trim((string) ($settings['msg_type'] ?? ''));
        if ($msg_type === '') {
        	$msg_type = esc_html__('Tipo de archivo no permitido.', 'archivos-adjuntos-cf7');
        }

		$instance_id = $form_id
			? 'aacf7-uploader-' . $form_id
			: 'aacf7-uploader-' . substr(md5($name . '|' . wp_json_encode($tag)), 0, 10);

		$input_id = $instance_id . '-input';

		$required_attr  = $is_required ? ' aria-required="true" required' : '';
		$required_class = $is_required ? ' aacf7-required' : '';

		$multiple_attr = ($max_files > 1) ? ' multiple' : '';
		$accept_attr = ' accept="' . esc_attr($this->ext_to_accept($allowed_ext)) . '"';

		$html  = '';
		$html .= '<div class="aacf7-wrap"'
			. ' id="' . esc_attr($instance_id) . '"'
			. ' data-form-id="' . esc_attr((string) $form_id) . '"'
			. ' data-field-name="' . esc_attr($name) . '"'
			. ' data-max-files="' . esc_attr((string) $max_files) . '"'
			. ' data-max-size-kb="' . esc_attr((string) $max_size_kb) . '"'
			. ' data-allowed-ext="' . esc_attr(implode(',', $allowed_ext)) . '"'
            . ' data-delivery-mode="' . esc_attr($delivery_mode) . '"'
            . ' data-styles="' . esc_attr($styles_json) . '"'
            . ' data-msg-required="' . esc_attr($msg_required) . '"'
            . ' data-msg-size="' . esc_attr($msg_size) . '"'
            . ' data-msg-count="' . esc_attr($msg_count) . '"'
            . ' data-msg-type="' . esc_attr($msg_type) . '"'
            . '>';

		$html .= '  <div class="aacf7-title">' . esc_html($text_title) . '</div>';
		$html .= '  <div class="aacf7-dropzone' . esc_attr($required_class) . '" role="button" tabindex="0" aria-controls="' . esc_attr($input_id) . '">';
		$html .= '    <div class="aacf7-dropzone-text">' . esc_html($text_drop) . '</div>';
		$html .= '    <button type="button" class="aacf7-btn" data-aacf7-browse="1">' . esc_html($text_button) . '</button>';
		$html .= '    <input type="file" class="aacf7-input"'
			. ' id="' . esc_attr($input_id) . '"'
			. ' name="' . esc_attr($name) . '"'
			. $accept_attr
			. $multiple_attr
			. $required_attr
			. ' />';
		$html .= '  </div>';

		if ($note_text !== '') {
			$html .= '  <div class="aacf7-note">' . esc_html($note_text) . '</div>';
		}

		$html .= '</div>';

		return $html;
	}

	private function ext_to_accept(array $exts): string {
		$accept = [];
		foreach ($exts as $ext) {
			$ext = strtolower(trim((string) $ext));
			if ($ext === '') continue;
			$accept[] = '.' . ltrim($ext, '.');
		}
		return implode(',', array_values(array_unique($accept)));
	}

	private function get_current_form_id(): int {
		$post_id = get_the_ID();
		return is_int($post_id) ? $post_id : 0;
	}

	/* -----------------------------
	 *  ADMIN: EDITOR PANEL (TAB)
	 * ----------------------------- */

	public function register_editor_panel($panels) {
		$panels['aacf7'] = [
			'title'    => __('Archivos Adjuntos CF7', 'archivos-adjuntos-cf7'),
			'callback' => [$this, 'render_editor_panel'],
		];
		return $panels;
	}

	public function render_editor_panel($form) {
		$form_id = method_exists($form, 'id') ? (int) $form->id() : 0;
		$s = $this->get_form_settings($form_id);

		// Almacenamiento
		$storage_mode = (string) ($s['storage_mode'] ?? 'default'); // default|user
		$storage_subdir_default = (string) ($s['storage_subdir_default'] ?? 'cf7-uploads');
		$storage_subdir_user = (string) ($s['storage_subdir_user'] ?? '');

		$attach_to_mail = (bool) ($s['attach_to_mail'] ?? true);
		$delete_after_days = (int) ($s['delete_after_days'] ?? 30);

		// Opciones
		$max_size_kb = (int) ($s['max_size_kb'] ?? 1024);
		$max_files = (int) ($s['max_files'] ?? 1);

		$allowed_ext = $s['allowed_ext'] ?? ['png'];
		if (!is_array($allowed_ext)) {
			$allowed_ext = ['png'];
		}
		$allowed_ext = array_map('strtolower', $allowed_ext);

		// Textos
		$text_title = (string) ($s['text_title'] ?? 'Adjunta tus archivos');
		$text_drop  = (string) ($s['text_drop'] ?? 'Arrastra tus archivos aquí');
		$text_btn   = (string) ($s['text_button'] ?? 'Seleccionar archivo');

		$text_note  = (string) ($s['text_note'] ?? 'Máximo 5MB por archivo'); // legacy
		$text_note_url = (string) ($s['text_note_url'] ?? $text_note);
		$text_note_attach = (string) ($s['text_note_attach'] ?? $text_note);

		// Validación
		$msg_required = (string) ($s['msg_required'] ?? 'Este campo es obligatorio.');
		$msg_size     = (string) ($s['msg_size'] ?? 'El archivo excede el tamaño máximo permitido.');
		$msg_count    = (string) ($s['msg_count'] ?? 'Has superado el número máximo de archivos.');
		$msg_type     = (string) ($s['msg_type'] ?? 'Tipo de archivo no permitido.');

		// Estilos (array)
		$styles = $s['styles'] ?? [];
		if (!is_array($styles)) {
			$styles = [];
		}

		$format_labels = [
			'jpg'  => 'JPG',
			'jpeg' => 'JPEG',
			'png'  => 'PNG',
			'pdf'  => 'PDF',
			'doc'  => 'DOC',
			'docx' => 'DOCX',
			'xls'  => 'XLS',
			'xlsx' => 'XLSX',
		];

		// Helpers
		$sv = function (array $arr, string $k, string $default = ''): string {
			return isset($arr[$k]) ? (string) $arr[$k] : $default;
		};
		$g = function (string $section, string $key, string $default = '') use ($styles, $sv): string {
			$sec = isset($styles[$section]) && is_array($styles[$section]) ? $styles[$section] : [];
			return $sv($sec, $key, $default);
		};

		?>
		<div class="acf7-wrapper" data-aacf7-settings="1">
			<div class="acf7-tabs-nav">
				<button type="button" class="active">Almacenamiento</button>
				<button type="button">Opciones</button>
				<button type="button">Textos</button>
				<button type="button">Validación</button>
				<button type="button">Estilos</button>
			</div>

			<!-- ==================== PESTAÑA ALMACENAMIENTO ==================== -->
			<div class="acf7-tab-content active" id="tab-almacenamiento">
				<div class="acf7-section">
					<h2 class="acf7-section-title"><span class="icono_titulo">▶</span> UBICACIÓN DE ARCHIVOS</h2>

					<table class="form-table acf7-table">
						<tbody>
							<tr>
								<th scope="row"><label>Almacenamiento</label></th>
								<td>
									<label class="acf7-radio-option">
										<input type="radio" name="aacf7_storage_mode" value="default" <?php checked($storage_mode, 'default'); ?>>
										Directorio interno por defecto (<span class="acf7-muted">wp-content/uploads</span>)
									</label>

									<div class="acf7-subbox" data-aacf7-subbox="default">
										<input type="text" class="acf7-input" name="aacf7_storage_subdir_default" value="<?php echo esc_attr($storage_subdir_default); ?>">
										<p class="acf7-hint">Carpeta dentro de wp-content/uploads/</p>
									</div>

									<label class="acf7-radio-option">
										<input type="radio" name="aacf7_storage_mode" value="user" <?php checked($storage_mode, 'user'); ?>>
										Directorio interno por usuario (<span class="acf7-muted">wp-content/uploads/usuario</span>)
									</label>

									<div class="acf7-subbox" data-aacf7-subbox="user">
										<input type="text" class="acf7-input" name="aacf7_storage_subdir_user" value="<?php echo esc_attr($storage_subdir_user); ?>">
										<p class="acf7-hint">Carpeta dentro de wp-content/uploads/ (ej: usuario/enero)</p>
									</div>
								</td>
							</tr>

							<tr>
								<th scope="row"><label>Configuración</label></th>
								<td class="configuracion">
									<label class="acf7-checkbox">
										<input type="checkbox" name="aacf7_attach_to_mail" value="1" <?php checked($attach_to_mail, true); ?>>
										Adjuntar archivos al correo (archivo completo)
									</label>

									<div class="acf7-inline">
										<label>Eliminar archivos después de:</label>
										<input type="number" class="acf7-input-number" name="aacf7_delete_after_days" value="<?php echo esc_attr((string) $delete_after_days); ?>">
										<span>días</span>
									</div>
									<p class="acf7-hint">Usa 0 para deshabilitar limpieza automática</p>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>

			<!-- ==================== PESTAÑA OPCIONES ==================== -->
			<div class="acf7-tab-content" id="tab-opciones">
				<div class="acf7-section">
					<div class="margin">
						<h2 class="acf7-section-title"><span class="icono_titulo">▶</span> LÍMITES DE ARCHIVOS</h2>
						<table class="form-table acf7-table">
							<tbody>
								<tr>
									<th scope="row"><label>Tamaño máximo</label></th>
									<td>
										<input type="number" class="acf7-input-number" name="aacf7_max_size_kb" value="<?php echo esc_attr((string) $max_size_kb); ?>">
										<span>KB</span>
									</td>
								</tr>
								<tr>
									<th scope="row"><label>Cantidad máxima</label></th>
									<td>
										<input type="number" class="acf7-input-number" name="aacf7_max_files" value="<?php echo esc_attr((string) $max_files); ?>">
									</td>
								</tr>
							</tbody>
						</table>
					</div>

					<h2 class="acf7-section-title"><span class="icono_titulo">▶</span> TIPOS DE ARCHIVO PERMITIDOS</h2>
					<table class="form-table acf7-table">
						<tbody>
							<tr>
								<th scope="row"><label>Formatos</label></th>
								<td>
									<div class="acf7-formatos">
										<?php foreach ($format_labels as $ext => $label) :
											$checked = in_array($ext, $allowed_ext, true);
											?>
											<label class="acf7-format<?php echo $checked ? ' active' : ''; ?>">
												<input type="checkbox" name="aacf7_allowed_ext[]" value="<?php echo esc_attr($ext); ?>" <?php checked($checked, true); ?>>
												<?php echo esc_html($label); ?>
											</label>
										<?php endforeach; ?>
									</div>
								</td>
							</tr>
						</tbody>
					</table>

				</div>
			</div>

			<!-- ==================== PESTAÑA TEXTOS ==================== -->
			<div class="acf7-tab-content" id="tab-textos">
				<div class="acf7-section">
					<h2 class="acf7-section-title"><span class="icono_titulo">▶</span> TEXTOS DE CAMPOS</h2>

					<table class="form-table acf7-table">
						<tbody>
							<tr><th scope="row"><label>Título del campo</label></th><td><input type="text" class="acf7-input" name="aacf7_text_title" value="<?php echo esc_attr($text_title); ?>"></td></tr>
							<tr><th scope="row"><label>Texto del área de arrastre</label></th><td><input type="text" class="acf7-input" name="aacf7_text_drop" value="<?php echo esc_attr($text_drop); ?>"></td></tr>
							<tr><th scope="row"><label>Texto del botón</label></th><td><input type="text" class="acf7-input" name="aacf7_text_button" value="<?php echo esc_attr($text_btn); ?>"></td></tr>
							<tr><th scope="row"><label>Nota informativa</label></th><td><input type="text" class="acf7-input" name="aacf7_text_note" value="<?php echo esc_attr($text_note); ?>"></td></tr>
						</tbody>
					</table>
				</div>
			</div>

			<!-- ==================== PESTAÑA VALIDACIÓN ==================== -->
			<div class="acf7-tab-content" id="tab-validacion">
				<div class="acf7-section">
					<h2 class="acf7-section-title"><span class="icono_titulo">▶</span> MENSAJES DE VALIDACIÓN</h2>

					<table class="form-table acf7-table">
						<tbody>
							<tr><th scope="row"><label>Campo obligatorio</label></th><td><input type="text" class="acf7-input" name="aacf7_msg_required" value="<?php echo esc_attr($msg_required); ?>"></td></tr>
							<tr><th scope="row"><label>Tamaño excedido</label></th><td><input type="text" class="acf7-input" name="aacf7_msg_size" value="<?php echo esc_attr($msg_size); ?>"></td></tr>
							<tr><th scope="row"><label>Cantidad excedida</label></th><td><input type="text" class="acf7-input" name="aacf7_msg_count" value="<?php echo esc_attr($msg_count); ?>"></td></tr>
							<tr><th scope="row"><label>Tipo no permitido</label></th><td><input type="text" class="acf7-input" name="aacf7_msg_type" value="<?php echo esc_attr($msg_type); ?>"></td></tr>
						</tbody>
					</table>
				</div>
			</div>

			<!-- ==================== PESTAÑA ESTILOS ==================== -->
			<div class="acf7-tab-content" id="tab-estilos">
				<div class="acf7-section">
					<h2 class="acf7-section-title"><span class="icono_titulo">▶</span> CONFIGURACIÓN DE ESTILOS</h2>

					<!-- 1. CONTENEDOR PRINCIPAL -->
					<div class="acf7-style-panel" id="panel-1">
						<h3 class="acf7-style-title">1. CONTENEDOR PRINCIPAL</h3>

						<div class="acf7-grid">
							<div class="acf7-field">
								<label>ALINEACIÓN (TÍTULO)</label>
								<select class="acf7-select" name="aacf7_styles[container][align]">
									<?php foreach (['left','right','center','justify','start','end'] as $o) : ?>
										<option value="<?php echo esc_attr($o); ?>" <?php selected($g('container','align','center'), $o); ?>><?php echo esc_html($o); ?></option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="acf7-field">
								<label>COLOR DE FONDO</label>
								<?php $v = $g('container','bg','#9b51e0'); ?>
								<div class="color-picker-combo" data-color="<?php echo esc_attr($v); ?>">
									<div class="color-visual">
										<div class="color-swatch" style="background:<?php echo esc_attr($v); ?>;"></div>
										<div class="color-label">Seleccionar un color</div>
									</div>
									<input type="color" class="color-input-full" name="aacf7_styles[container][bg]" value="<?php echo esc_attr($v); ?>">
								</div>
							</div>

							<!-- ✅ NUEVO: padding/margin del contenedor -->
							<div class="acf7-field"><label>PADDING</label><input type="text" name="aacf7_styles[container][padding]" placeholder="10px" value="<?php echo esc_attr($g('container','padding','')); ?>"></div>
							<div class="acf7-field"><label>MARGIN</label><input type="text" name="aacf7_styles[container][margin]" placeholder="0" value="<?php echo esc_attr($g('container','margin','')); ?>"></div>

							<div class="acf7-field"><label>BORDER RADIUS</label><input type="text" name="aacf7_styles[container][radius]" placeholder="6px" value="<?php echo esc_attr($g('container','radius','6px')); ?>"></div>
							<div class="acf7-field"><label>BORDE GROSOR</label><input type="text" name="aacf7_styles[container][border_width]" placeholder="1px" value="<?php echo esc_attr($g('container','border_width','1px')); ?>"></div>

							<div class="acf7-field">
								<label>BORDE ESTILO</label>
								<select class="acf7-select" name="aacf7_styles[container][border_style]">
									<?php foreach (['solid','dashed','dotted','double','groove','ridge','inset','outset','none','hidden'] as $o) : ?>
										<option value="<?php echo esc_attr($o); ?>" <?php selected($g('container','border_style','dotted'), $o); ?>><?php echo esc_html($o); ?></option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="acf7-field">
								<label>BORDE COLOR</label>
								<?php $v = $g('container','border_color','#b47ae0'); ?>
								<div class="color-picker-combo" data-color="<?php echo esc_attr($v); ?>">
									<div class="color-visual"><div class="color-swatch" style="background:<?php echo esc_attr($v); ?>;"></div><div class="color-label">Seleccionar un color</div></div>
									<input type="color" class="color-input-full" name="aacf7_styles[container][border_color]" value="<?php echo esc_attr($v); ?>">
								</div>
							</div>

							<div class="acf7-field full"><label>BOX SHADOW</label><input type="text" name="aacf7_styles[container][box_shadow]" placeholder="0 1px 4px rgba(0,0,0,0.1)" value="<?php echo esc_attr($g('container','box_shadow','')); ?>"></div>
						</div>

						<div class="acf7-subpanel">
							<h4 class="acf7-subpanel-title">TEXTO DEL TÍTULO</h4>
							<div class="acf7-grid">
								<div class="acf7-field">
									<label>COLOR</label>
									<?php $v = $g('container','title_color','#333333'); ?>
									<div class="color-picker-combo" data-color="<?php echo esc_attr($v); ?>">
										<div class="color-visual"><div class="color-swatch" style="background:<?php echo esc_attr($v); ?>;"></div><div class="color-label">Seleccionar un color</div></div>
										<input type="color" class="color-input-full" name="aacf7_styles[container][title_color]" value="<?php echo esc_attr($v); ?>">
									</div>
								</div>
								<div class="acf7-field"><label>TAMAÑO</label><input type="text" name="aacf7_styles[container][title_size]" placeholder="18px" value="<?php echo esc_attr($g('container','title_size','15px')); ?>"></div>
								<div class="acf7-field">
									<label>GROSOR</label>
									<select class="acf7-select" name="aacf7_styles[container][title_weight]">
										<?php foreach (['normal','bold','lighter','bolder','100','200','300','400','500','600','700','800','900'] as $o) : ?>
											<option value="<?php echo esc_attr($o); ?>" <?php selected($g('container','title_weight','500'), $o); ?>><?php echo esc_html($o); ?></option>
										<?php endforeach; ?>
									</select>
								</div>

								<!-- ✅ NUEVO: padding/margin del texto título -->
								<div class="acf7-field"><label>PADDING</label><input type="text" name="aacf7_styles[container][title_padding]" placeholder="0" value="<?php echo esc_attr($g('container','title_padding','')); ?>"></div>
								<div class="acf7-field"><label>MARGIN</label><input type="text" name="aacf7_styles[container][title_margin]" placeholder="0" value="<?php echo esc_attr($g('container','title_margin','')); ?>"></div>
							</div>
						</div>
					</div>

					<!-- 2. CONTENEDOR DROPZONE -->
					<div class="acf7-style-panel" id="panel-2">
						<h3 class="acf7-style-title">2. CONTENEDOR DROPZONE</h3>

						<div class="acf7-grid">
							<div class="acf7-field">
								<label>ALINEACIÓN (TEXTO ÁREA)</label>
								<select class="acf7-select" name="aacf7_styles[dropzone][align]">
									<?php foreach (['left','right','center','justify','start','end'] as $o) : ?>
										<option value="<?php echo esc_attr($o); ?>" <?php selected($g('dropzone','align','center'), $o); ?>><?php echo esc_html($o); ?></option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="acf7-field">
								<label>COLOR DE FONDO</label>
								<?php $v = $g('dropzone','bg','#f7f7f7'); ?>
								<div class="color-picker-combo" data-color="<?php echo esc_attr($v); ?>">
									<div class="color-visual"><div class="color-swatch" style="background:<?php echo esc_attr($v); ?>;"></div><div class="color-label">Seleccionar un color</div></div>
									<input type="color" class="color-input-full" name="aacf7_styles[dropzone][bg]" value="<?php echo esc_attr($v); ?>">
								</div>
							</div>

							<!-- ✅ NUEVO: padding/margin de dropzone -->
							<div class="acf7-field"><label>PADDING</label><input type="text" name="aacf7_styles[dropzone][padding]" placeholder="12px" value="<?php echo esc_attr($g('dropzone','padding','')); ?>"></div>
							<div class="acf7-field"><label>MARGIN</label><input type="text" name="aacf7_styles[dropzone][margin]" placeholder="0" value="<?php echo esc_attr($g('dropzone','margin','')); ?>"></div>

							<div class="acf7-field"><label>BORDER RADIUS</label><input type="text" name="aacf7_styles[dropzone][radius]" placeholder="4px" value="<?php echo esc_attr($g('dropzone','radius','4px')); ?>"></div>
							<div class="acf7-field"><label>BORDE GROSOR</label><input type="text" name="aacf7_styles[dropzone][border_width]" placeholder="1px" value="<?php echo esc_attr($g('dropzone','border_width','1px')); ?>"></div>

							<div class="acf7-field">
								<label>BORDE ESTILO</label>
								<select class="acf7-select" name="aacf7_styles[dropzone][border_style]">
									<?php foreach (['solid','dashed','dotted','double','groove','ridge','inset','outset','none','hidden'] as $o) : ?>
										<option value="<?php echo esc_attr($o); ?>" <?php selected($g('dropzone','border_style','dotted'), $o); ?>><?php echo esc_html($o); ?></option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="acf7-field">
								<label>BORDE COLOR</label>
								<?php $v = $g('dropzone','border_color','#cccccc'); ?>
								<div class="color-picker-combo" data-color="<?php echo esc_attr($v); ?>">
									<div class="color-visual"><div class="color-swatch" style="background:<?php echo esc_attr($v); ?>;"></div><div class="color-label">Seleccionar un color</div></div>
									<input type="color" class="color-input-full" name="aacf7_styles[dropzone][border_color]" value="<?php echo esc_attr($v); ?>">
								</div>
							</div>

							<div class="acf7-field full"><label>BOX SHADOW</label><input type="text" name="aacf7_styles[dropzone][box_shadow]" placeholder="none" value="<?php echo esc_attr($g('dropzone','box_shadow','none')); ?>"></div>
						</div>

						<div class="acf7-subpanel">
							<h4 class="acf7-subpanel-title">ICONO SVG</h4>
							<div class="acf7-grid">
								<div class="acf7-field">
									<label>COLOR</label>
									<?php $v = $g('dropzone','icon_color','#0073aa'); ?>
									<div class="color-picker-combo" data-color="<?php echo esc_attr($v); ?>">
										<div class="color-visual"><div class="color-swatch" style="background:<?php echo esc_attr($v); ?>;"></div><div class="color-label">Seleccionar un color</div></div>
										<input type="color" class="color-input-full" name="aacf7_styles[dropzone][icon_color]" value="<?php echo esc_attr($v); ?>">
									</div>
								</div>
								<div class="acf7-field"><label>TAMAÑO</label><input type="text" name="aacf7_styles[dropzone][icon_size]" placeholder="50px" value="<?php echo esc_attr($g('dropzone','icon_size','50px')); ?>"></div>
							</div>
						</div>

						<div class="acf7-subpanel">
							<h4 class="acf7-subpanel-title">TEXTO DEL ÁREA</h4>
							<div class="acf7-grid">
								<div class="acf7-field">
									<label>COLOR</label>
									<?php $v = $g('dropzone','text_color','#333333'); ?>
									<div class="color-picker-combo" data-color="<?php echo esc_attr($v); ?>">
										<div class="color-visual"><div class="color-swatch" style="background:<?php echo esc_attr($v); ?>;"></div><div class="color-label">Seleccionar un color</div></div>
										<input type="color" class="color-input-full" name="aacf7_styles[dropzone][text_color]" value="<?php echo esc_attr($v); ?>">
									</div>
								</div>
								<div class="acf7-field"><label>TAMAÑO</label><input type="text" name="aacf7_styles[dropzone][text_size]" placeholder="15px" value="<?php echo esc_attr($g('dropzone','text_size','15px')); ?>"></div>
								<div class="acf7-field">
									<label>GROSOR</label>
									<select class="acf7-select" name="aacf7_styles[dropzone][text_weight]">
										<?php foreach (['normal','bold','lighter','bolder','100','200','300','400','500','600','700','800','900'] as $o) : ?>
											<option value="<?php echo esc_attr($o); ?>" <?php selected($g('dropzone','text_weight','500'), $o); ?>><?php echo esc_html($o); ?></option>
										<?php endforeach; ?>
									</select>
								</div>

								<!-- ✅ NUEVO: padding/margin del texto del área -->
								<div class="acf7-field"><label>PADDING</label><input type="text" name="aacf7_styles[dropzone][text_padding]" placeholder="0" value="<?php echo esc_attr($g('dropzone','text_padding','')); ?>"></div>
								<div class="acf7-field"><label>MARGIN</label><input type="text" name="aacf7_styles[dropzone][text_margin]" placeholder="0" value="<?php echo esc_attr($g('dropzone','text_margin','')); ?>"></div>
							</div>
						</div>
					</div>

					<!-- 3. BOTÓN ADJUNTAR -->
					<div class="acf7-style-panel" id="panel-3">
						<h3 class="acf7-style-title">3. BOTÓN ADJUNTAR</h3>

						<div class="acf7-grid">
							<div class="acf7-field">
								<label>ALINEACIÓN (BOTÓN)</label>
								<select class="acf7-select" name="aacf7_styles[button][align]">
									<?php foreach (['left','right','center','justify','start','end'] as $o) : ?>
										<option value="<?php echo esc_attr($o); ?>" <?php selected($g('button','align','center'), $o); ?>><?php echo esc_html($o); ?></option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="acf7-field">
								<label>COLOR DE FONDO</label>
								<?php $v = $g('button','bg','#0073aa'); ?>
								<div class="color-picker-combo" data-color="<?php echo esc_attr($v); ?>">
									<div class="color-visual"><div class="color-swatch" style="background:<?php echo esc_attr($v); ?>;"></div><div class="color-label">Seleccionar un color</div></div>
									<input type="color" class="color-input-full" name="aacf7_styles[button][bg]" value="<?php echo esc_attr($v); ?>">
								</div>
							</div>

							<div class="acf7-field">
								<label>COLOR HOVER</label>
								<?php $v = $g('button','hover','#005f8d'); ?>
								<div class="color-picker-combo" data-color="<?php echo esc_attr($v); ?>">
									<div class="color-visual"><div class="color-swatch" style="background:<?php echo esc_attr($v); ?>;"></div><div class="color-label">Seleccionar un color</div></div>
									<input type="color" class="color-input-full" name="aacf7_styles[button][hover]" value="<?php echo esc_attr($v); ?>">
								</div>
							</div>

							<div class="acf7-field"><label>PADDING</label><input type="text" name="aacf7_styles[button][padding]" placeholder="10px 20px" value="<?php echo esc_attr($g('button','padding','10px 20px')); ?>"></div>
							<div class="acf7-field"><label>MARGIN</label><input type="text" name="aacf7_styles[button][margin]" placeholder="0px" value="<?php echo esc_attr($g('button','margin','0px')); ?>"></div>
							<div class="acf7-field"><label>BORDER RADIUS</label><input type="text" name="aacf7_styles[button][radius]" placeholder="4px" value="<?php echo esc_attr($g('button','radius','4px')); ?>"></div>
							<div class="acf7-field"><label>BORDE GROSOR</label><input type="text" name="aacf7_styles[button][border_width]" placeholder="1px" value="<?php echo esc_attr($g('button','border_width','1px')); ?>"></div>

							<div class="acf7-field">
								<label>BORDE ESTILO</label>
								<select class="acf7-select" name="aacf7_styles[button][border_style]">
									<?php foreach (['solid','dashed','dotted','double','groove','ridge','inset','outset','none','hidden'] as $o) : ?>
										<option value="<?php echo esc_attr($o); ?>" <?php selected($g('button','border_style','solid'), $o); ?>><?php echo esc_html($o); ?></option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="acf7-field">
								<label>BORDE COLOR</label>
								<?php $v = $g('button','border_color','#cccccc'); ?>
								<div class="color-picker-combo" data-color="<?php echo esc_attr($v); ?>">
									<div class="color-visual"><div class="color-swatch" style="background:<?php echo esc_attr($v); ?>;"></div><div class="color-label">Seleccionar un color</div></div>
									<input type="color" class="color-input-full" name="aacf7_styles[button][border_color]" value="<?php echo esc_attr($v); ?>">
								</div>
							</div>

							<div class="acf7-field full"><label>BOX SHADOW</label><input type="text" name="aacf7_styles[button][box_shadow]" placeholder="none" value="<?php echo esc_attr($g('button','box_shadow','none')); ?>"></div>
						</div>

						<div class="acf7-subpanel">
							<h4 class="acf7-subpanel-title">TEXTO DEL BOTÓN</h4>
							<div class="acf7-grid">
								<div class="acf7-field">
									<label>ALINEACIÓN</label>
									<select class="acf7-select" name="aacf7_styles[button][text_align]">
										<?php foreach (['left','center','right'] as $o) : ?>
											<option value="<?php echo esc_attr($o); ?>" <?php selected($g('button','text_align','center'), $o); ?>><?php echo esc_html($o); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
								<div class="acf7-field"><label>TAMAÑO</label><input type="text" name="aacf7_styles[button][text_size]" placeholder="14px" value="<?php echo esc_attr($g('button','text_size','14px')); ?>"></div>
								<div class="acf7-field">
									<label>GROSOR</label>
									<select class="acf7-select" name="aacf7_styles[button][text_weight]">
										<?php foreach (['normal','bold','lighter','bolder','100','200','300','400','500','600','700','800','900'] as $o) : ?>
											<option value="<?php echo esc_attr($o); ?>" <?php selected($g('button','text_weight','500'), $o); ?>><?php echo esc_html($o); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
								<div class="acf7-field">
									<label>COLOR</label>
									<?php $v = $g('button','text_color','#ffffff'); ?>
									<div class="color-picker-combo" data-color="<?php echo esc_attr($v); ?>">
										<div class="color-visual"><div class="color-swatch" style="background:<?php echo esc_attr($v); ?>;"></div><div class="color-label">Seleccionar un color</div></div>
										<input type="color" class="color-input-full" name="aacf7_styles[button][text_color]" value="<?php echo esc_attr($v); ?>">
									</div>
								</div>
								<div class="acf7-field">
									<label>COLOR HOVER</label>
									<?php $v = $g('button','text_hover','#eeeeee'); ?>
									<div class="color-picker-combo" data-color="<?php echo esc_attr($v); ?>">
										<div class="color-visual"><div class="color-swatch" style="background:<?php echo esc_attr($v); ?>;"></div><div class="color-label">Seleccionar un color</div></div>
										<input type="color" class="color-input-full" name="aacf7_styles[button][text_hover]" value="<?php echo esc_attr($v); ?>">
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- 4. NOTA INFORMATIVA -->
					<div class="acf7-style-panel" id="panel-4">
						<h3 class="acf7-style-title">4. NOTA INFORMATIVA</h3>
						<div class="acf7-grid">
							<div class="acf7-field">
								<label>ALINEACIÓN</label>
								<select class="acf7-select" name="aacf7_styles[note][align]">
									<?php foreach (['left','right','center','justify','start','end'] as $o) : ?>
										<option value="<?php echo esc_attr($o); ?>" <?php selected($g('note','align','center'), $o); ?>><?php echo esc_html($o); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="acf7-field">
								<label>COLOR</label>
								<?php $v = $g('note','color','#666666'); ?>
								<div class="color-picker-combo" data-color="<?php echo esc_attr($v); ?>">
									<div class="color-visual"><div class="color-swatch" style="background:<?php echo esc_attr($v); ?>;"></div><div class="color-label">Seleccionar un color</div></div>
									<input type="color" class="color-input-full" name="aacf7_styles[note][color]" value="<?php echo esc_attr($v); ?>">
								</div>
							</div>
							<div class="acf7-field"><label>TAMAÑO</label><input type="text" name="aacf7_styles[note][size]" placeholder="13px" value="<?php echo esc_attr($g('note','size','13px')); ?>"></div>
							<div class="acf7-field">
								<label>GROSOR</label>
								<select class="acf7-select" name="aacf7_styles[note][weight]">
									<?php foreach (['normal','bold','lighter','bolder','100','200','300','400','500','600','700','800','900'] as $o) : ?>
										<option value="<?php echo esc_attr($o); ?>" <?php selected($g('note','weight','500'), $o); ?>><?php echo esc_html($o); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="acf7-field"><label>MARGIN</label><input type="text" name="aacf7_styles[note][margin]" placeholder="10px 0" value="<?php echo esc_attr($g('note','margin','10px 0')); ?>"></div>
							<div class="acf7-field"><label>PADDING</label><input type="text" name="aacf7_styles[note][padding]" placeholder="0" value="<?php echo esc_attr($g('note','padding','')); ?>"></div>
						</div>
					</div>

					<!-- 5. DIV ARCHIVO ADJUNTADO -->
					<div class="acf7-style-panel" id="panel-5">
						<h3 class="acf7-style-title">5. DIV ARCHIVO ADJUNTADO</h3>

						<div class="acf7-grid">
							<div class="acf7-field">
								<label>ALINEACIÓN</label>
								<select class="acf7-select" name="aacf7_styles[file][align]">
									<?php foreach (['left','right','center','justify','start','end'] as $o) : ?>
										<option value="<?php echo esc_attr($o); ?>" <?php selected($g('file','align','center'), $o); ?>><?php echo esc_html($o); ?></option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="acf7-field">
								<label>COLOR DE FONDO</label>
								<?php $v = $g('file','bg','#ffffff'); ?>
								<div class="color-picker-combo" data-color="<?php echo esc_attr($v); ?>">
									<div class="color-visual"><div class="color-swatch" style="background:<?php echo esc_attr($v); ?>;"></div><div class="color-label">Seleccionar un color</div></div>
									<input type="color" class="color-input-full" name="aacf7_styles[file][bg]" value="<?php echo esc_attr($v); ?>">
								</div>
							</div>

							<div class="acf7-field"><label>BORDER RADIUS</label><input type="text" name="aacf7_styles[file][radius]" placeholder="4px" value="<?php echo esc_attr($g('file','radius','4px')); ?>"></div>
							<div class="acf7-field"><label>BORDE GROSOR</label><input type="text" name="aacf7_styles[file][border_width]" placeholder="1px" value="<?php echo esc_attr($g('file','border_width','1px')); ?>"></div>

							<div class="acf7-field">
								<label>BORDE ESTILO</label>
								<select class="acf7-select" name="aacf7_styles[file][border_style]">
									<?php foreach (['solid','dashed','dotted','double','groove','ridge','inset','outset','none','hidden'] as $o) : ?>
										<option value="<?php echo esc_attr($o); ?>" <?php selected($g('file','border_style','solid'), $o); ?>><?php echo esc_html($o); ?></option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="acf7-field">
								<label>BORDE COLOR</label>
								<?php $v = $g('file','border_color','#cccccc'); ?>
								<div class="color-picker-combo" data-color="<?php echo esc_attr($v); ?>">
									<div class="color-visual"><div class="color-swatch" style="background:<?php echo esc_attr($v); ?>;"></div><div class="color-label">Seleccionar un color</div></div>
									<input type="color" class="color-input-full" name="aacf7_styles[file][border_color]" value="<?php echo esc_attr($v); ?>">
								</div>
							</div>

							<div class="acf7-field"><label>BOX SHADOW</label><input type="text" name="aacf7_styles[file][box_shadow]" placeholder="none" value="<?php echo esc_attr($g('file','box_shadow','none')); ?>"></div>
							<div class="acf7-field"><label>PADDING</label><input type="text" name="aacf7_styles[file][padding]" placeholder="10px" value="<?php echo esc_attr($g('file','padding','10px')); ?>"></div>
							<div class="acf7-field"><label>MARGIN</label><input type="text" name="aacf7_styles[file][margin]" placeholder="8px 0" value="<?php echo esc_attr($g('file','margin','8px 0')); ?>"></div>
						</div>

						<div class="acf7-subpanel">
							<h4 class="acf7-subpanel-title">BARRA DE PROGRESO</h4>
							<div class="acf7-grid">
								<div class="acf7-field">
									<label>COLOR</label>
									<?php $v = $g('file','progress_color','#2271b1'); ?>
									<div class="color-picker-combo" data-color="<?php echo esc_attr($v); ?>">
										<div class="color-visual"><div class="color-swatch" style="background:<?php echo esc_attr($v); ?>;"></div><div class="color-label">Seleccionar un color</div></div>
										<input type="color" class="color-input-full" name="aacf7_styles[file][progress_color]" value="<?php echo esc_attr($v); ?>">
									</div>
								</div>
								<div class="acf7-field"><label>ALTO</label><input type="text" name="aacf7_styles[file][progress_height]" placeholder="4px" value="<?php echo esc_attr($g('file','progress_height','4px')); ?>"></div>
							</div>
						</div>

						<div class="acf7-subpanel">
							<h4 class="acf7-subpanel-title">ICONO DEL ARCHIVO</h4>
							<div class="acf7-grid">
								<div class="acf7-field">
									<label>COLOR</label>
									<?php $v = $g('file','icon_color','#333333'); ?>
									<div class="color-picker-combo" data-color="<?php echo esc_attr($v); ?>">
										<div class="color-visual"><div class="color-swatch" style="background:<?php echo esc_attr($v); ?>;"></div><div class="color-label">Seleccionar un color</div></div>
										<input type="color" class="color-input-full" name="aacf7_styles[file][icon_color]" value="<?php echo esc_attr($v); ?>">
									</div>
								</div>
								<div class="acf7-field"><label>TAMAÑO</label><input type="text" name="aacf7_styles[file][icon_size]" placeholder="15px" value="<?php echo esc_attr($g('file','icon_size','15px')); ?>"></div>
							</div>
						</div>

						<div class="acf7-subpanel">
							<h4 class="acf7-subpanel-title">TEXTO DETALLES</h4>
							<div class="acf7-grid">
								<div class="acf7-field">
									<label>COLOR</label>
									<?php $v = $g('file','details_color','#000000'); ?>
									<div class="color-picker-combo" data-color="<?php echo esc_attr($v); ?>">
										<div class="color-visual"><div class="color-swatch" style="background:<?php echo esc_attr($v); ?>;"></div><div class="color-label">Seleccionar un color</div></div>
										<input type="color" class="color-input-full" name="aacf7_styles[file][details_color]" value="<?php echo esc_attr($v); ?>">
									</div>
								</div>
								<div class="acf7-field"><label>TAMAÑO</label><input type="text" name="aacf7_styles[file][details_size]" placeholder="14px" value="<?php echo esc_attr($g('file','details_size','14px')); ?>"></div>
								<div class="acf7-field">
									<label>GROSOR</label>
									<select class="acf7-select" name="aacf7_styles[file][details_weight]">
										<?php foreach (['normal','bold','lighter','bolder','100','200','300','400','500','600','700','800','900'] as $o) : ?>
											<option value="<?php echo esc_attr($o); ?>" <?php selected($g('file','details_weight','500'), $o); ?>><?php echo esc_html($o); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
							</div>
						</div>

						<div class="acf7-subpanel">
							<h4 class="acf7-subpanel-title">BOTÓN ELIMINAR</h4>
							<div class="acf7-grid">
								<div class="acf7-field">
									<label>COLOR</label>
									<?php $v = $g('file','remove_color','#cc0000'); ?>
									<div class="color-picker-combo" data-color="<?php echo esc_attr($v); ?>">
										<div class="color-visual"><div class="color-swatch" style="background:<?php echo esc_attr($v); ?>;"></div><div class="color-label">Seleccionar un color</div></div>
										<input type="color" class="color-input-full" name="aacf7_styles[file][remove_color]" value="<?php echo esc_attr($v); ?>">
									</div>
								</div>
								<div class="acf7-field"><label>TAMAÑO</label><input type="text" name="aacf7_styles[file][remove_size]" placeholder="14px" value="<?php echo esc_attr($g('file','remove_size','14px')); ?>"></div>
							</div>
						</div>

					</div><!-- /panel-5 -->

				</div>
			</div><!-- /tab-estilos -->

		</div>
		<?php
	}

	public function save_form_settings($form): void {
		$form_id = method_exists($form, 'id') ? (int) $form->id() : 0;
		if (!$form_id) {
			return;
		}

		$prev = $this->get_form_settings($form_id);

		$storage_mode = isset($_POST['aacf7_storage_mode']) ? (string) $_POST['aacf7_storage_mode'] : 'default';
		$storage_mode = in_array($storage_mode, ['default', 'user'], true) ? $storage_mode : 'default';

		$storage_subdir_default = isset($_POST['aacf7_storage_subdir_default']) ? (string) $_POST['aacf7_storage_subdir_default'] : 'cf7-uploads';
		$storage_subdir_default = trim($storage_subdir_default);

		$storage_subdir_user = isset($_POST['aacf7_storage_subdir_user']) ? (string) $_POST['aacf7_storage_subdir_user'] : '';
		$storage_subdir_user = trim($storage_subdir_user);

		$attach_to_mail = isset($_POST['aacf7_attach_to_mail']) && (string) $_POST['aacf7_attach_to_mail'] === '1';

		$delete_after_days = isset($_POST['aacf7_delete_after_days']) ? (int) $_POST['aacf7_delete_after_days'] : 30;
		if ($delete_after_days < 0) $delete_after_days = 0;

		$max_size_kb = isset($_POST['aacf7_max_size_kb']) ? (int) $_POST['aacf7_max_size_kb'] : 1024;
		if ($max_size_kb < 1) $max_size_kb = 1;

		$max_files = isset($_POST['aacf7_max_files']) ? (int) $_POST['aacf7_max_files'] : 1;
		if ($max_files < 1) $max_files = 1;
		if ($max_files > 10) $max_files = 10;

		$allowed_ext = isset($_POST['aacf7_allowed_ext']) && is_array($_POST['aacf7_allowed_ext'])
			? (array) $_POST['aacf7_allowed_ext']
			: ['png'];

		$allowed_ext = array_values(array_unique(array_filter(array_map(function ($p) {
			$p = strtolower(trim((string) $p));
			$p = ltrim($p, '.');
			return preg_replace('/[^a-z0-9]+/', '', $p);
		}, $allowed_ext))));

		if (empty($allowed_ext)) {
			$allowed_ext = ['png'];
		}

		$text_title = isset($_POST['aacf7_text_title']) ? sanitize_text_field((string) $_POST['aacf7_text_title']) : '';
		$text_drop  = isset($_POST['aacf7_text_drop']) ? sanitize_text_field((string) $_POST['aacf7_text_drop']) : '';
		$text_btn   = isset($_POST['aacf7_text_button']) ? sanitize_text_field((string) $_POST['aacf7_text_button']) : '';
		$text_note  = isset($_POST['aacf7_text_note']) ? sanitize_text_field((string) $_POST['aacf7_text_note']) : '';
		$text_note_url = isset($_POST['aacf7_text_note_url']) ? sanitize_text_field((string) $_POST['aacf7_text_note_url']) : '';
		$text_note_attach = isset($_POST['aacf7_text_note_attach']) ? sanitize_text_field((string) $_POST['aacf7_text_note_attach']) : '';

		$msg_required = isset($_POST['aacf7_msg_required']) ? sanitize_text_field((string) $_POST['aacf7_msg_required']) : '';
		$msg_size     = isset($_POST['aacf7_msg_size']) ? sanitize_text_field((string) $_POST['aacf7_msg_size']) : '';
		$msg_count    = isset($_POST['aacf7_msg_count']) ? sanitize_text_field((string) $_POST['aacf7_msg_count']) : '';
		$msg_type     = isset($_POST['aacf7_msg_type']) ? sanitize_text_field((string) $_POST['aacf7_msg_type']) : '';

		$styles = isset($_POST['aacf7_styles']) && is_array($_POST['aacf7_styles']) ? $_POST['aacf7_styles'] : [];
		$styles = $this->sanitize_deep($styles);

		$settings = array_merge($prev, [
			'storage_mode' => $storage_mode,
			'storage_subdir_default' => $storage_subdir_default,
			'storage_subdir_user' => $storage_subdir_user,

			'attach_to_mail' => $attach_to_mail,
			'delete_after_days' => $delete_after_days,

			'max_size_kb' => $max_size_kb,
			'max_files' => $max_files,
			'allowed_ext' => $allowed_ext,

			'text_title' => $text_title,
			'text_drop' => $text_drop,
			'text_button' => $text_btn,
			'text_note' => $text_note,
			'text_note_url' => $text_note_url,
			'text_note_attach' => $text_note_attach,

			'msg_required' => $msg_required,
			'msg_size' => $msg_size,
			'msg_count' => $msg_count,
			'msg_type' => $msg_type,

			'styles' => $styles,
		]);

		update_post_meta($form_id, '_aacf7_settings', $settings);
	}

	private function sanitize_deep($value) {
		if (is_array($value)) {
			$out = [];
			foreach ($value as $k => $v) {
				$k = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) $k);
				$out[$k] = $this->sanitize_deep($v);
			}
			return $out;
		}
		return sanitize_text_field((string) $value);
	}

	private function get_form_settings(int $form_id): array {
		if (!$form_id) {
			return [];
		}

		$settings = get_post_meta($form_id, '_aacf7_settings', true);
		return is_array($settings) ? $settings : [];
	}
}