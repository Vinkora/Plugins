<?php
/**
 * CTC Vinkora — Esquema de opciones, defaults y saneado con MERGE por pestaña.
 *
 * Una sola opción (`ctcv_options`) para todo el plugin. Como cada pestaña envía solo SUS campos,
 * el sanitize() parte de la opción existente y sobrescribe únicamente las claves de la pestaña
 * activa (mapa tab→claves). Así guardar una pestaña NO borra las demás. Los checkboxes ausentes
 * se ponen a 0 solo si pertenecen a la pestaña activa.
 *
 * PHP 7.2–8.5: sintaxis conservadora, sin funciones deprecadas ni sintaxis 8.0+ exclusiva.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CTCV_Options' ) ) :

#[\AllowDynamicProperties]
final class CTCV_Options {

	/** Clave única de la opción en wp_options. */
	const OPTION = 'ctcv_options';

	/* ------------------------------------------------------------------ *
	 *  Defaults (esquema COMPLETO)
	 * ------------------------------------------------------------------ */
	public static function defaults() {
		return array(
			// -- General --
			'enabled'              => 1,
			'phone'                => '',            // DERIVADO en el servidor = phone_cc + phone_national (lo lee el front).
			'phone_iso'            => 'co',          // país elegido (solo para pintar bandera/código en el admin).
			'phone_cc'             => '57',          // código de país (dígitos), campo REAL enviado.
			'phone_national'       => '',            // número nacional (dígitos), campo REAL enviado.
			'greeting'             => 'Hola, quiero mas informacion.',
			'tooltip'              => 'Hablemos por WhatsApp',
			'cta_text'             => 'Escribenos por WhatsApp',

			// -- Boton / estilo --
			'style'                => 'icon',   // icon|square|icon-padding|button|text|chip|image
			'color'                => '#25D366',
			'size'                 => 60,
			'size_mobile'          => 56,
			'position'             => 'right',  // right|left
			'offset_bottom'        => 20,
			'offset_side'          => 20,
			'radius'               => 50,       // % de border-radius (50 = circulo)
			'pulse'                => 1,
			'cta_mode'             => 'hover',  // hide|show|hover (etiqueta lateral)
			'own_image_url'        => '',
			'show_desktop'         => 1,
			'show_mobile'          => 1,

			// -- Saludo / formulario --
			'greeting_type'        => 'none',   // none|greeting|form
			'greeting_title'       => 'En que te podemos ayudar?',
			'greeting_body'        => 'Escribenos y te respondemos enseguida.',
			'greeting_cta'         => 'Iniciar chat',
			'greeting_position'    => 'side',   // side|modal
			'greeting_size'        => 'medium', // small|medium|large
			'greeting_auto'        => 'interaction', // auto|interaction
			'greeting_delay'       => 0,        // seg (para auto)
			'greeting_color_header'=> '#075E54',
			'greeting_color_body'  => '#ffffff',
			'greeting_color_msg'   => '#e8f5e9',
			'form_cta'             => 'Enviar y abrir WhatsApp',
			'form_fields'          => array(
				array( 'type' => 'text',  'label' => 'Nombre', 'placeholder' => 'Tu nombre',            'required' => 1, 'add' => 1, 'options' => '' ),
				array( 'type' => 'email', 'label' => 'Correo', 'placeholder' => 'tucorreo@ejemplo.com', 'required' => 0, 'add' => 1, 'options' => '' ),
			),

			// -- Agentes (multi-agente) --
			'agents_enabled'       => 0,
			'agents_title'         => 'Elige con quien hablar',
			'agents_offline_text'  => 'Fuera de horario',
			'agents'               => array(),  // {name,number,role,avatar,greeting,always,days[],from,to,offline}

			// -- Visibilidad --
			'vis_front'            => 1,
			'vis_home'             => 1,        // indice del blog
			'vis_pages'            => 1,
			'vis_posts'            => 1,        // entradas singulares
			'vis_archives'         => 1,
			'vis_categories'       => 1,
			'vis_search'           => 1,
			'vis_404'              => 0,
			'vis_cpt'              => array(),  // { slug => 0|1 }
			'include_ids'          => '',
			'exclude_ids'          => '',
			'exclude_cats'         => '',

			// -- Rastreo: UTMs --
			'attribution'          => 'first',  // first|last
			'ttl_days'             => 90,
			'only_if_utm'          => 1,
			'track_header'         => '- Referencia (por favor no borrar) -',
			// Matriz de UTMs a incluir (marcadas por el usuario). Por defecto = las 6 estándar.
			'tracked_params'       => array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_id', 'utm_term', 'utm_content' ),
			'extra_params'         => '',       // otros parámetros personalizados (fuera de la matriz).
			'include_page'         => 1,
			'include_ref'          => 1,
			'compact'              => 0,

			// -- Rastreo: analitica --
			'ga4_enabled'          => 0,
			'ga4_event'            => 'whatsapp_click',
			'gtm_push'             => 0,
			'gtm_event'            => 'whatsapp_click',
			'gtm_inject'           => 0,
			'gtm_id'               => '',
			'pixel_enabled'        => 0,
			'pixel_event'          => 'Contact',

			// -- Avanzado --
			'delay_ms'             => 0,
			'zindex'               => 99990,
			'badge_enabled'        => 0,
			'badge_text'           => '1',
			'custom_css'           => '',
		);
	}

	/* ------------------------------------------------------------------ *
	 *  Mapa pestaña -> claves (para el MERGE del sanitize)
	 * ------------------------------------------------------------------ */
	public static function tab_keys() {
		return array(
			'general'     => array( 'enabled', 'phone_iso', 'phone_cc', 'phone_national', 'greeting', 'tooltip', 'cta_text' ),
			'button'      => array( 'style', 'color', 'size', 'size_mobile', 'position', 'offset_bottom', 'offset_side', 'radius', 'pulse', 'cta_mode', 'own_image_url', 'show_desktop', 'show_mobile' ),
			'greeting'    => array( 'greeting_type', 'greeting_title', 'greeting_body', 'greeting_cta', 'greeting_position', 'greeting_size', 'greeting_auto', 'greeting_delay', 'greeting_color_header', 'greeting_color_body', 'greeting_color_msg', 'form_cta', 'form_fields' ),
			'agents'      => array( 'agents_enabled', 'agents_title', 'agents_offline_text', 'agents' ),
			'visibility'  => array( 'vis_front', 'vis_home', 'vis_pages', 'vis_posts', 'vis_archives', 'vis_categories', 'vis_search', 'vis_404', 'vis_cpt', 'include_ids', 'exclude_ids', 'exclude_cats' ),
			'tracking'    => array( 'attribution', 'ttl_days', 'only_if_utm', 'track_header', 'tracked_params', 'extra_params', 'include_page', 'include_ref', 'compact', 'ga4_enabled', 'ga4_event', 'gtm_push', 'gtm_event', 'gtm_inject', 'gtm_id', 'pixel_enabled', 'pixel_event' ),
			'advanced'    => array( 'delay_ms', 'zindex', 'badge_enabled', 'badge_text', 'custom_css' ),
		);
	}

	/** Claves booleanas (checkbox): ausencia = 0. */
	private static function checkbox_keys() {
		return array(
			'enabled', 'pulse', 'show_desktop', 'show_mobile',
			'agents_enabled',
			'vis_front', 'vis_home', 'vis_pages', 'vis_posts', 'vis_archives', 'vis_categories', 'vis_search', 'vis_404',
			'only_if_utm', 'include_page', 'include_ref',
			'ga4_enabled', 'gtm_push', 'gtm_inject', 'pixel_enabled',
			'badge_enabled',
		);
	}

	/** Enteros con rango: clave => array(min, max). */
	private static function int_keys() {
		return array(
			'size'          => array( 36, 120 ),
			'size_mobile'   => array( 36, 120 ),
			'offset_bottom' => array( 0, 600 ),
			'offset_side'   => array( 0, 600 ),
			'radius'        => array( 0, 50 ),
			'ttl_days'      => array( 1, 3650 ),
			'delay_ms'      => array( 0, 60000 ),
			'greeting_delay'=> array( 0, 300 ),
			'zindex'        => array( 1, 2147483000 ),
		);
	}

	/** Enumeraciones: clave => array de valores válidos (el 1º es el default). */
	private static function enum_keys() {
		return array(
			'style'             => array( 'icon', 'square', 'icon-padding', 'button', 'text', 'chip', 'image' ),
			'position'          => array( 'right', 'left' ),
			'cta_mode'          => array( 'hover', 'show', 'hide' ),
			'greeting_type'     => array( 'none', 'greeting', 'form' ),
			'greeting_position' => array( 'side', 'modal' ),
			'greeting_size'     => array( 'small', 'medium', 'large' ),
			'greeting_auto'     => array( 'interaction', 'auto' ),
			'attribution'       => array( 'first', 'last' ),
		);
	}

	private static function color_keys() {
		return array( 'color', 'greeting_color_header', 'greeting_color_body', 'greeting_color_msg' );
	}
	private static function text_keys() {
		return array( 'tooltip', 'cta_text', 'greeting_title', 'greeting_cta', 'form_cta', 'agents_title', 'agents_offline_text', 'track_header', 'ga4_event', 'gtm_event', 'pixel_event', 'badge_text' );
	}
	private static function textarea_keys() {
		return array( 'greeting', 'greeting_body' );
	}
	private static function idlist_keys() {
		return array( 'include_ids', 'exclude_ids', 'exclude_cats' );
	}

	/** UTMs/click-ids conocidos para la matriz de selección (clave => etiqueta). */
	public static function known_params() {
		return array(
			'utm_source'   => 'utm_source',
			'utm_medium'   => 'utm_medium',
			'utm_campaign' => 'utm_campaign',
			'utm_id'       => 'utm_id',
			'utm_term'     => 'utm_term',
			'utm_content'  => 'utm_content',
			'gclid'        => 'gclid · Google Ads',
			'fbclid'       => 'fbclid · Meta',
			'wbraid'       => 'wbraid · Google',
			'gbraid'       => 'gbraid · Google',
			'msclkid'      => 'msclkid · Bing',
			'ttclid'       => 'ttclid · TikTok',
			'li_fat_id'    => 'li_fat_id · LinkedIn',
		);
	}

	/* ------------------------------------------------------------------ *
	 *  Lectura de opciones (con migracion y auto-saneo)
	 * ------------------------------------------------------------------ */
	public static function get() {
		$raw = get_option( self::OPTION, array() );
		if ( ! is_array( $raw ) ) {
			$raw = array();
		}
		$opts = wp_parse_args( $raw, self::defaults() );
		unset( $opts['_active_tab'] ); // clave interna del merge; nunca la exponemos a los lectores.

		// Migración: versiones previas incluían las 6 UTMs fijas + los click-ids en `extra_params`.
		// Si la opción guardada no tiene `tracked_params`, lo derivamos (6 UTMs + click-ids conocidos
		// que estuvieran en extra_params) y dejamos `extra_params` solo con los custom.
		if ( ! array_key_exists( 'tracked_params', $raw ) ) {
			$known   = array_keys( self::known_params() );
			$enabled = array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_id', 'utm_term', 'utm_content' );
			$custom  = array();
			$old     = array_filter( array_map( 'trim', explode( ',', isset( $raw['extra_params'] ) ? (string) $raw['extra_params'] : '' ) ) );
			foreach ( $old as $p ) {
				if ( in_array( $p, $known, true ) ) {
					$enabled[] = $p;
				} else {
					$custom[] = $p;
				}
			}
			$opts['tracked_params'] = array_values( array_unique( $enabled ) );
			$opts['extra_params']   = implode( ', ', $custom );
		}

		// Auto-arreglo: saludo con caracter de reemplazo U+FFFD (BD utf8 sin emoji) -> default.
		if ( isset( $opts['greeting'] ) && false !== strpos( (string) $opts['greeting'], "\xEF\xBF\xBD" ) ) {
			$d                = self::defaults();
			$opts['greeting'] = $d['greeting'];
		}
		return $opts;
	}

	/** Deja solo dígitos (código país + número). */
	public static function clean_phone( $raw ) {
		return preg_replace( '/\D+/', '', (string) $raw );
	}

	/* ------------------------------------------------------------------ *
	 *  Sanitize con MERGE por pestaña
	 * ------------------------------------------------------------------ */
	public static function sanitize( $input ) {
		$d        = self::defaults();
		$existing = get_option( self::OPTION, array() );
		if ( ! is_array( $existing ) ) {
			$existing = array();
		}
		$out = wp_parse_args( $existing, $d ); // opción completa como punto de partida

		if ( ! is_array( $input ) ) {
			return $out;
		}

		$tab     = isset( $input['_active_tab'] ) ? sanitize_key( $input['_active_tab'] ) : '';
		$map     = self::tab_keys();
		$keys    = isset( $map[ $tab ] ) ? $map[ $tab ] : array();
		if ( empty( $keys ) ) {
			// Sin pestaña válida: no tocar nada (evita borrados accidentales).
			return $out;
		}

		$checkbox = self::checkbox_keys();
		$ints     = self::int_keys();
		$enums    = self::enum_keys();
		$colors   = self::color_keys();
		$texts    = self::text_keys();
		$areas    = self::textarea_keys();
		$idlists  = self::idlist_keys();

		foreach ( $keys as $key ) {
			// Checkbox: ausencia = 0.
			if ( in_array( $key, $checkbox, true ) ) {
				$out[ $key ] = empty( $input[ $key ] ) ? 0 : 1;
				continue;
			}
			// Especiales.
			if ( 'phone_iso' === $key ) {
				$out['phone_iso'] = isset( $input['phone_iso'] ) ? sanitize_key( $input['phone_iso'] ) : 'co';
				continue;
			}
			if ( 'phone_cc' === $key ) {
				$out['phone_cc'] = self::clean_phone( isset( $input['phone_cc'] ) ? $input['phone_cc'] : '' );
				continue;
			}
			if ( 'phone_national' === $key ) {
				$out['phone_national'] = self::clean_phone( isset( $input['phone_national'] ) ? $input['phone_national'] : '' );
				continue;
			}
			if ( 'compact' === $key ) {
				$out['compact'] = ( isset( $input['compact'] ) && '1' === (string) $input['compact'] ) ? 1 : 0;
				continue;
			}
			if ( 'own_image_url' === $key ) {
				$out['own_image_url'] = isset( $input['own_image_url'] ) ? esc_url_raw( trim( (string) $input['own_image_url'] ) ) : '';
				continue;
			}
			if ( 'gtm_id' === $key ) {
				$out['gtm_id'] = self::clean_gtm_id( isset( $input['gtm_id'] ) ? $input['gtm_id'] : '' );
				continue;
			}
			if ( 'tracked_params' === $key ) {
				// Matriz de casillas: valida lo enviado contra la lista conocida. Nada marcado = vacío.
				$allowed = array_keys( self::known_params() );
				$sel     = ( isset( $input['tracked_params'] ) && is_array( $input['tracked_params'] ) ) ? $input['tracked_params'] : array();
				$clean   = array();
				foreach ( $sel as $p ) {
					$p = sanitize_key( $p );
					if ( in_array( $p, $allowed, true ) && ! in_array( $p, $clean, true ) ) {
						$clean[] = $p;
					}
				}
				$out['tracked_params'] = $clean;
				continue;
			}
			if ( 'extra_params' === $key ) {
				$out['extra_params'] = self::clean_param_list( isset( $input['extra_params'] ) ? $input['extra_params'] : '' );
				continue;
			}
			if ( 'custom_css' === $key ) {
				// CSS: quitar etiquetas (no permitir </style> ni <script>), conservar el resto.
				$css               = isset( $input['custom_css'] ) ? (string) $input['custom_css'] : '';
				$out['custom_css'] = trim( wp_strip_all_tags( $css ) );
				continue;
			}
			if ( 'form_fields' === $key ) {
				$out['form_fields'] = self::sanitize_form_fields( isset( $input['form_fields'] ) ? $input['form_fields'] : array() );
				continue;
			}
			if ( 'agents' === $key ) {
				$out['agents'] = self::sanitize_agents( isset( $input['agents'] ) ? $input['agents'] : array() );
				continue;
			}
			if ( 'vis_cpt' === $key ) {
				$out['vis_cpt'] = self::sanitize_cpt_map( isset( $input['vis_cpt'] ) ? $input['vis_cpt'] : array() );
				continue;
			}
			// Enteros con rango.
			if ( isset( $ints[ $key ] ) ) {
				$v           = isset( $input[ $key ] ) ? absint( $input[ $key ] ) : $d[ $key ];
				$out[ $key ] = max( $ints[ $key ][0], min( $ints[ $key ][1], $v ) );
				continue;
			}
			// Enumeraciones.
			if ( isset( $enums[ $key ] ) ) {
				$v           = isset( $input[ $key ] ) ? (string) $input[ $key ] : '';
				$out[ $key ] = in_array( $v, $enums[ $key ], true ) ? $v : $enums[ $key ][0];
				continue;
			}
			// Colores.
			if ( in_array( $key, $colors, true ) ) {
				$cin         = ( isset( $input[ $key ] ) && is_string( $input[ $key ] ) ) ? $input[ $key ] : '';
				$c           = sanitize_hex_color( $cin );
				$out[ $key ] = $c ? $c : $d[ $key ];
				continue;
			}
			// Listas de IDs / slugs.
			if ( in_array( $key, $idlists, true ) ) {
				$out[ $key ] = self::clean_csv( isset( $input[ $key ] ) ? $input[ $key ] : '' );
				continue;
			}
			// Textareas.
			if ( in_array( $key, $areas, true ) ) {
				$out[ $key ] = isset( $input[ $key ] ) ? sanitize_textarea_field( $input[ $key ] ) : $d[ $key ];
				continue;
			}
			// Texto simple (resto).
			$out[ $key ] = isset( $input[ $key ] ) ? sanitize_text_field( $input[ $key ] ) : $d[ $key ];
		}

		// El número completo es DERIVADO en el servidor = código país + nacional. Se recompone aquí,
		// así se guarda AUNQUE el JS del selector no corra (los campos son reales, no un hidden por JS).
		if ( 'general' === $tab ) {
			$out['phone'] = self::clean_phone( $out['phone_cc'] . $out['phone_national'] );
		}

		// CRÍTICO — conservar `_active_tab` en la salida. WordPress ejecuta el sanitize DOS veces
		// cuando la opción se crea por primera vez (update_option -> add_option); en la 2ª pasada
		// el input = salida de la 1ª (SIN el hidden del formulario). Si no llevamos `_active_tab`,
		// esa 2ª pasada cae en el `return` de "sin pestaña" y devuelve los defaults, borrando lo
		// recién guardado (bug "no guarda el número"). Al conservarlo, la 2ª pasada re-aplica la
		// MISMA pestaña de forma idempotente y el valor sobrevive. get() lo oculta a los lectores.
		$out['_active_tab'] = $tab;

		return $out;
	}

	/* ------------------------------------------------------------------ *
	 *  Helpers de saneado
	 * ------------------------------------------------------------------ */

	/** GTM-XXXXXXX (mayúsculas, A-Z 0-9 y guion; debe empezar por GTM-). */
	public static function clean_gtm_id( $raw ) {
		$v = strtoupper( preg_replace( '/[^A-Za-z0-9\-]/', '', (string) $raw ) );
		if ( '' === $v ) {
			return '';
		}
		return ( 0 === strpos( $v, 'GTM-' ) ) ? $v : '';
	}

	/** Lista de claves de query (utm/click-ids): solo A-Z 0-9 _ -, separadas por coma. */
	public static function clean_param_list( $raw ) {
		$parts = array_filter( array_map( 'trim', explode( ',', (string) $raw ) ) );
		$clean = array();
		foreach ( $parts as $p ) {
			$p = preg_replace( '/[^A-Za-z0-9_\-]/', '', $p );
			if ( '' !== $p ) {
				$clean[] = $p;
			}
		}
		return implode( ', ', array_unique( $clean ) );
	}

	/** CSV genérico (IDs numéricos o slugs). Deja tokens limpios separados por coma. */
	public static function clean_csv( $raw ) {
		$parts = array_filter( array_map( 'trim', explode( ',', (string) $raw ) ) );
		$clean = array();
		foreach ( $parts as $p ) {
			$p = sanitize_text_field( $p );
			if ( '' !== $p ) {
				$clean[] = $p;
			}
		}
		return implode( ', ', $clean );
	}

	private static function sanitize_form_fields( $rows ) {
		$out   = array();
		$types = array( 'text', 'email', 'textarea', 'select', 'checkbox', 'tel', 'date', 'hidden' );
		if ( ! is_array( $rows ) ) {
			return $out;
		}
		$i = 0;
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) || $i >= 30 ) {
				continue;
			}
			$i++;
			$type  = ( isset( $row['type'] ) && in_array( $row['type'], $types, true ) ) ? $row['type'] : 'text';
			$label = isset( $row['label'] ) ? sanitize_text_field( $row['label'] ) : '';
			if ( '' === $label && 'hidden' !== $type ) {
				continue; // sin label no sirve (salvo hidden)
			}
			$out[] = array(
				'type'        => $type,
				'label'       => $label,
				'placeholder' => isset( $row['placeholder'] ) ? sanitize_text_field( $row['placeholder'] ) : '',
				'required'    => empty( $row['required'] ) ? 0 : 1,
				'add'         => empty( $row['add'] ) ? 0 : 1,
				'options'     => isset( $row['options'] ) ? sanitize_textarea_field( $row['options'] ) : '',
			);
		}
		return $out;
	}

	private static function sanitize_agents( $rows ) {
		$out = array();
		if ( ! is_array( $rows ) ) {
			return $out;
		}
		$days_valid = array( 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' );
		$i = 0;
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) || $i >= 30 ) {
				continue;
			}
			$i++;
			$number = self::clean_phone( isset( $row['number'] ) ? $row['number'] : '' );
			$name   = isset( $row['name'] ) ? sanitize_text_field( $row['name'] ) : '';
			if ( '' === $number && '' === $name ) {
				continue; // fila vacía
			}
			$days = array();
			if ( isset( $row['days'] ) && is_array( $row['days'] ) ) {
				foreach ( $row['days'] as $dk => $dv ) {
					if ( in_array( $dk, $days_valid, true ) && ! empty( $dv ) ) {
						$days[] = $dk;
					}
				}
			}
			$out[] = array(
				'name'     => $name,
				'number'   => $number,
				'role'     => isset( $row['role'] ) ? sanitize_text_field( $row['role'] ) : '',
				'avatar'   => isset( $row['avatar'] ) ? esc_url_raw( trim( (string) $row['avatar'] ) ) : '',
				'greeting' => isset( $row['greeting'] ) ? sanitize_textarea_field( $row['greeting'] ) : '',
				'always'   => empty( $row['always'] ) ? 0 : 1,
				'days'     => $days,
				'from'     => self::clean_time( isset( $row['from'] ) ? $row['from'] : '' ),
				'to'       => self::clean_time( isset( $row['to'] ) ? $row['to'] : '' ),
				'offline'  => ( isset( $row['offline'] ) && 'hide' === $row['offline'] ) ? 'hide' : 'show',
			);
		}
		return $out;
	}

	/** "HH:MM" válido o ''. */
	public static function clean_time( $raw ) {
		$raw = trim( (string) $raw );
		if ( preg_match( '/^([01]?\d|2[0-3]):([0-5]\d)$/', $raw ) ) {
			return $raw;
		}
		return '';
	}

	private static function sanitize_cpt_map( $map ) {
		$out = array();
		if ( ! is_array( $map ) ) {
			return $out;
		}
		foreach ( $map as $slug => $val ) {
			$slug = sanitize_key( $slug );
			if ( '' !== $slug ) {
				$out[ $slug ] = empty( $val ) ? 0 : 1;
			}
		}
		return $out;
	}
}

endif;
