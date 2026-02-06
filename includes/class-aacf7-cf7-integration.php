<?php
namespace AACF7;

if (!defined('ABSPATH')) {
	exit;
}

class CF7_Integration {

	public function init(): void {
		add_action('wpcf7_init', [$this, 'register_form_tag']);
		add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
	}

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

		// Config mínima por defecto (hasta que exista el panel React por formulario)
		$max_files   = 1;      // 1..10
		$max_size_mb = 1024;   // default
		$allowed_ext = ['jpg','jpeg','png','webp','bmp','pdf','xlsx','xls','doc','docx'];

		$form_id = $this->get_current_form_id();
		$instance_id = $form_id
			? 'aacf7-uploader-' . $form_id
			: 'aacf7-uploader-' . substr(md5($name . '|' . wp_json_encode($tag)), 0, 10);

		$input_id = $instance_id . '-input';
		$list_id  = $instance_id . '-list';

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
			. ' data-max-size-mb="' . esc_attr((string) $max_size_mb) . '"'
			. ' data-allowed-ext="' . esc_attr(implode(',', $allowed_ext)) . '"'
			. '>';

		$html .= '  <div class="aacf7-title">' . esc_html__('Adjuntar archivos', 'archivos-adjuntos-cf7') . '</div>';

		$html .= '  <div class="aacf7-dropzone' . esc_attr($required_class) . '" role="button" tabindex="0" aria-controls="' . esc_attr($input_id) . '">';
		$html .= '    <div class="aacf7-dropzone-text">' . esc_html__('Arrastra y suelta archivos aquí o haz clic para seleccionar', 'archivos-adjuntos-cf7') . '</div>';
		$html .= '    <button type="button" class="aacf7-btn" data-aacf7-browse="1">' . esc_html__('Adjuntar', 'archivos-adjuntos-cf7') . '</button>';
		$html .= '    <input type="file" class="aacf7-input"'
			. ' id="' . esc_attr($input_id) . '"'
			. ' name="' . esc_attr($name) . '"'
			. $accept_attr
			. $multiple_attr
			. $required_attr
			. ' />';
		$html .= '  </div>';

		$html .= '  <div class="aacf7-note">'
			. esc_html__('Tipos permitidos y límites configurables por el administrador.', 'archivos-adjuntos-cf7')
			. '</div>';

		$html .= '  <div class="aacf7-list" id="' . esc_attr($list_id) . '" aria-live="polite"></div>';

		$html .= '  <template class="aacf7-item-template">';
		$html .= '    <div class="aacf7-item">';
		$html .= '      <div class="aacf7-item-main">';
		$html .= '        <span class="aacf7-item-icon" aria-hidden="true">📎</span>';
		$html .= '        <span class="aacf7-item-name"></span>';
		$html .= '        <span class="aacf7-item-meta"></span>';
		$html .= '      </div>';
		$html .= '      <div class="aacf7-item-actions">';
		$html .= '        <button type="button" class="aacf7-item-remove" data-aacf7-remove="1">' . esc_html__('Eliminar', 'archivos-adjuntos-cf7') . '</button>';
		$html .= '      </div>';
		$html .= '      <div class="aacf7-progress"><div class="aacf7-progress-bar" style="width:0%"></div></div>';
		$html .= '    </div>';
		$html .= '  </template>';

		$html .= '</div>';

		return $html;
	}

	private function ext_to_accept(array $exts): string {
		$accept = [];
		foreach ($exts as $ext) {
			$ext = strtolower(trim((string) $ext));
			if ($ext === '') {
				continue;
			}
			$accept[] = '.' . ltrim($ext, '.');
		}
		return implode(',', array_values(array_unique($accept)));
	}

	private function get_current_form_id(): int {
		$post_id = get_the_ID();
		return is_int($post_id) ? $post_id : 0;
	}
}
