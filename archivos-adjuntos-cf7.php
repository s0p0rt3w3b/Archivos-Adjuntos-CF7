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

require_once AACF7_PLUGIN_DIR . 'includes/class-aacf7-plugin.php';

function aacf7_boot() {
	return AACF7\Plugin::instance();
}
add_action('plugins_loaded', 'aacf7_boot');
