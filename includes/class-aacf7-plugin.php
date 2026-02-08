<?php
namespace AACF7;

if (!defined('ABSPATH')) {
	exit;
}

final class Plugin {
	private static $instance = null;

	public static function instance(): Plugin {
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->includes();
		$this->init();
	}

	private function includes(): void {
		require_once AACF7_PLUGIN_DIR . 'includes/class-aacf7-i18n.php';
		require_once AACF7_PLUGIN_DIR . 'includes/class-aacf7-cf7-integration.php';
	}

	private function init(): void {
		// Initialize internationalization
		(new I18n())->init();

		// Show admin notice if Contact Form 7 is not active
		add_action('admin_notices', function () {
			if (!defined('WPCF7_VERSION')) {
				echo '<div class="notice notice-warning"><p>'
					. esc_html__('Archivos Adjuntos CF7 requiere Contact Form 7 instalado y activo.', 'archivos-adjuntos-cf7')
					. '</p></div>';
			}
		});

		/**
		 * Initialize CF7 integration only if Contact Form 7 is active.
		 * 
		 * This conditional initialization ensures that:
		 * - The plugin gracefully handles CF7 not being installed/active
		 * - Form tags (adjuntos_cf7) are registered via wpcf7_init hook
		 * - Admin UI panels and settings are properly configured
		 * - Frontend and admin assets are enqueued when needed
		 */
		if (defined('WPCF7_VERSION')) {
			(new CF7_Integration())->init();
		}
	}
}