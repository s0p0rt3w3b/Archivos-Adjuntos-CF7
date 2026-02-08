<?php
/**
 * Plugin Name:       Archivos Adjuntos CF7
 * Description:       Campo avanzado de adjuntos para Contact Form 7 con subida temporal, progreso y envío por URL o adjunto.
 * Version:           0.1.0
 * Author:            InforCom Soluciones Web
 * Text Domain:       archivos-adjuntos-cf7
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 */

if (!defined('ABSPATH')) {
	exit;
}

define('AACF7_VERSION', '0.1.0');
define('AACF7_PLUGIN_FILE', __FILE__);
define('AACF7_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AACF7_PLUGIN_URL', plugin_dir_url(__FILE__));

$plugin_class_file = AACF7_PLUGIN_DIR . 'includes/class-aacf7-plugin.php';

if (!file_exists($plugin_class_file)) {
	add_action('admin_notices', function () use ($plugin_class_file) {
		echo '<div class="notice notice-error"><p>'
			. esc_html__('Archivos Adjuntos CF7: falta el archivo requerido:', 'archivos-adjuntos-cf7')
			. ' <code>' . esc_html($plugin_class_file) . '</code>'
			. '</p></div>';
	});
	return;
}

require_once $plugin_class_file;

function aacf7_boot() {
	if (class_exists('AACF7\\Plugin')) {
		return \AACF7\Plugin::instance();
	}

	add_action('admin_notices', function () {
		echo '<div class="notice notice-error"><p>'
			. esc_html__('Archivos Adjuntos CF7: no se pudo cargar la clase principal AACF7\\Plugin.', 'archivos-adjuntos-cf7')
			. '</p></div>';
	});

	return null;
}
add_action('plugins_loaded', 'aacf7_boot');