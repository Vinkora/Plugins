<?php
/**
 * Plugin Name:       CTC Vinkora
 * Plugin URI:        https://vinkora.net
 * Description:       Click To Chat de Vinkora: botón de WhatsApp con estilos, ventana de saludo, formulario, multi-agente, reglas de visibilidad y rastreo (UTMs + GA4 + Google Tag Manager + Meta Pixel). Universal, autónomo, sin dependencias externas.
 * Version:           2.0.4
 * Author:            Vinkora
 * Author URI:        https://vinkora.net
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ctc-vinkora
 * Requires at least: 5.2
 * Tested up to:      6.8
 * Requires PHP:      7.2
 *
 * NOTA DE DISEÑO (por qué "no se rompe"):
 * - Solo APIs de núcleo estables: Options API, add_menu_page, add_shortcode, wp_footer, wp_head,
 *   wp_body_open. Sin REST, sin admin-ajax, sin base de datos propia, sin jQuery ni librerías.
 * - Front-end (CSS/JS) INLINE en un IIFE con fallbacks (localStorage -> cookie -> memoria) y con
 *   enlace wa.me real como respaldo si un optimizador borra el JS.
 * - PHP 7.2–8.5: sintaxis conservadora (sin str_contains/str_starts_with/array_key_first/nullsafe/
 *   arrow fns/typed props/propiedades dinámicas/funciones deprecadas).
 * - Una sola opción `ctcv_options`; pestañas con MERGE selectivo (guardar una no borra las otras).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CTCV_VERSION', '2.0.4' );
define( 'CTCV_FILE', __FILE__ );
define( 'CTCV_DIR', plugin_dir_path( __FILE__ ) );
define( 'CTCV_URL', plugin_dir_url( __FILE__ ) );

// Auto-actualización desde GitHub Releases. Mono-repo Vinkora/Plugins; los releases de ESTE
// plugin usan etiquetas con el prefijo `ctc-vinkora-` (ej. ctc-vinkora-2.0.1).
define( 'CTCV_GH_OWNER', 'Vinkora' );
define( 'CTCV_GH_REPO', 'Plugins' );
define( 'CTCV_GH_PREFIX', 'ctc-vinkora-' );

require_once CTCV_DIR . 'includes/class-ctcv-options.php';
require_once CTCV_DIR . 'includes/class-ctcv-admin.php';
require_once CTCV_DIR . 'includes/class-ctcv-frontend.php';
require_once CTCV_DIR . 'includes/class-ctcv-updater.php';

if ( ! function_exists( 'ctcv_boot' ) ) {
	function ctcv_boot() {
		if ( class_exists( 'CTCV_Admin' ) ) {
			new CTCV_Admin();
		}
		if ( class_exists( 'CTCV_Frontend' ) ) {
			new CTCV_Frontend();
		}
		if ( class_exists( 'CTCV_Updater' ) && '' !== CTCV_GH_OWNER ) {
			new CTCV_Updater( CTCV_GH_OWNER, CTCV_GH_REPO, CTCV_GH_PREFIX );
		}
	}
}
ctcv_boot();

// Activación: crea la opción con defaults si no existe (preserva v1; evita doble sanitize inicial).
if ( ! function_exists( 'ctcv_activate' ) ) {
	function ctcv_activate() {
		if ( false === get_option( CTCV_Options::OPTION, false ) ) {
			add_option( CTCV_Options::OPTION, CTCV_Options::defaults() );
		}
	}
}
register_activation_hook( __FILE__, 'ctcv_activate' );

// Limpieza al desinstalar.
if ( ! function_exists( 'ctcv_uninstall' ) ) {
	function ctcv_uninstall() {
		delete_option( CTCV_Options::OPTION );
	}
}
register_uninstall_hook( __FILE__, 'ctcv_uninstall' );
