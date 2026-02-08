<?php
namespace AACF7;

if (!defined('ABSPATH')) {
	exit;
}

class I18n {
	public function init(): void {
		add_action('init', [$this, 'load_textdomain']);
	}

	public function load_textdomain(): void {
		load_plugin_textdomain(
			'archivos-adjuntos-cf7',
			false,
			dirname(plugin_basename(AACF7_PLUGIN_FILE)) . '/languages'
		);
	}
}