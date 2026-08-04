<?php
/**
 * CTC Vinkora — Front-end: visibilidad, estilos de botón, ventana de saludo / formulario,
 * multi-agente, rastreo (GA4 / GTM / Meta Pixel) y captura de UTMs.
 * Todo el CSS/JS va INLINE (sobrevive a optimizadores y al fallback sin-JS del enlace wa.me real).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CTCV_Frontend' ) ) :

#[\AllowDynamicProperties]
final class CTCV_Frontend {

	public function __construct() {
		add_action( 'wp_footer', array( $this, 'render_frontend' ), 99 );
		add_shortcode( 'whatsapp_button', array( $this, 'shortcode' ) );

		$o = CTCV_Options::get();
		if ( ! empty( $o['gtm_inject'] ) && '' !== $o['gtm_id'] ) {
			add_action( 'wp_head', array( $this, 'gtm_head' ), 1 );
			add_action( 'wp_body_open', array( $this, 'gtm_body' ) );
		}
	}

	/* ------------------------------------------------------------------ *
	 *  Google Tag Manager (inyección opcional del contenedor)
	 * ------------------------------------------------------------------ */
	public function gtm_head() {
		$o  = CTCV_Options::get();
		$id = $o['gtm_id'];
		if ( '' === $id ) {
			return;
		}
		echo "\n<!-- CTC Vinkora: Google Tag Manager -->\n";
		echo "<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','" . esc_js( $id ) . "');</script>\n";
	}

	public function gtm_body() {
		$o  = CTCV_Options::get();
		$id = $o['gtm_id'];
		if ( '' === $id ) {
			return;
		}
		echo '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=' . esc_attr( $id ) . '" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>' . "\n";
	}

	/* ------------------------------------------------------------------ *
	 *  Visibilidad
	 * ------------------------------------------------------------------ */
	public function should_display() {
		$o = CTCV_Options::get();

		// include_ids: si está relleno, SOLO en esos IDs.
		$inc = self::csv_to_array( $o['include_ids'] );
		$exc = self::csv_to_array( $o['exclude_ids'] );
		$id  = ( is_singular() ) ? (string) get_the_ID() : '';

		if ( '' !== $id && in_array( $id, $exc, true ) ) {
			return false;
		}
		if ( ! empty( $inc ) ) {
			return ( '' !== $id && in_array( $id, $inc, true ) );
		}

		// Excluir por categoría.
		$exc_cats = self::csv_to_array( $o['exclude_cats'] );
		if ( ! empty( $exc_cats ) && ( is_singular( 'post' ) || is_category() ) ) {
			if ( self::matches_category( $exc_cats ) ) {
				return false;
			}
		}

		// Precedencia: 404 -> front -> home(blog) -> category -> archive -> page -> singular.
		if ( is_404() ) {
			return ! empty( $o['vis_404'] );
		}
		if ( is_front_page() ) {
			return ! empty( $o['vis_front'] );
		}
		if ( is_home() ) {
			return ! empty( $o['vis_home'] );
		}
		if ( is_search() ) {
			return ! empty( $o['vis_search'] );
		}
		if ( is_category() ) {
			return ! empty( $o['vis_categories'] );
		}
		if ( is_page() ) {
			return ! empty( $o['vis_pages'] );
		}
		if ( is_singular() ) {
			$pt = get_post_type();
			if ( 'post' === $pt ) {
				return ! empty( $o['vis_posts'] );
			}
			// CPT: por defecto se muestra salvo que esté a 0.
			return isset( $o['vis_cpt'][ $pt ] ) ? ! empty( $o['vis_cpt'][ $pt ] ) : true;
		}
		if ( is_archive() ) {
			return ! empty( $o['vis_archives'] );
		}
		return true;
	}

	private static function csv_to_array( $csv ) {
		$out   = array();
		$parts = array_filter( array_map( 'trim', explode( ',', (string) $csv ) ) );
		foreach ( $parts as $p ) {
			if ( '' !== $p ) {
				$out[] = (string) $p;
			}
		}
		return $out;
	}

	private static function matches_category( $cats ) {
		if ( is_category() ) {
			$obj = get_queried_object();
			if ( $obj && isset( $obj->term_id ) ) {
				if ( in_array( (string) $obj->term_id, $cats, true ) || in_array( (string) $obj->slug, $cats, true ) ) {
					return true;
				}
			}
			return false;
		}
		// Single post: comprobar sus categorías.
		$terms = get_the_category( get_the_ID() );
		if ( is_array( $terms ) ) {
			foreach ( $terms as $t ) {
				if ( in_array( (string) $t->term_id, $cats, true ) || in_array( (string) $t->slug, $cats, true ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/* ------------------------------------------------------------------ *
	 *  Helpers de datos
	 * ------------------------------------------------------------------ */
	private function svg_icon( $class = 'ctcv-icon' ) {
		return '<svg class="' . esc_attr( $class ) . '" viewBox="0 0 32 32" width="28" height="28" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg"><path fill="currentColor" d="M16.003 3.2c-7.06 0-12.8 5.74-12.8 12.8 0 2.257.593 4.463 1.72 6.406L3.2 28.8l6.56-1.71a12.74 12.74 0 0 0 6.243 1.593h.005c7.06 0 12.8-5.74 12.8-12.8 0-3.42-1.332-6.635-3.752-9.055A12.7 12.7 0 0 0 16.003 3.2zm0 23.04h-.004a10.63 10.63 0 0 1-5.417-1.483l-.389-.23-4.03 1.05 1.075-3.926-.253-.403a10.6 10.6 0 0 1-1.62-5.648c0-5.87 4.777-10.647 10.652-10.647a10.58 10.58 0 0 1 7.53 3.122 10.58 10.58 0 0 1 3.117 7.533c0 5.87-4.777 10.647-10.648 10.647zm5.84-7.976c-.32-.16-1.894-.934-2.187-1.04-.293-.107-.507-.16-.72.16-.213.32-.826 1.04-1.013 1.253-.187.213-.373.24-.693.08-.32-.16-1.35-.498-2.573-1.588-.951-.848-1.593-1.896-1.78-2.216-.187-.32-.02-.493.14-.652.144-.143.32-.373.48-.56.16-.187.213-.32.32-.533.107-.213.053-.4-.027-.56-.08-.16-.72-1.736-.987-2.376-.26-.624-.524-.54-.72-.55l-.613-.01c-.213 0-.56.08-.853.4-.293.32-1.12 1.093-1.12 2.667 0 1.573 1.147 3.093 1.307 3.307.16.213 2.253 3.44 5.46 4.827.763.33 1.36.527 1.824.674.767.244 1.464.21 2.016.127.615-.092 1.894-.774 2.16-1.52.267-.747.267-1.387.187-1.52-.08-.133-.293-.213-.613-.373z"/></svg>';
	}

	private function apply_vars( $text, $number ) {
		$url  = '';
		if ( isset( $GLOBALS['wp'] ) && isset( $GLOBALS['wp']->request ) ) {
			$url = home_url( $GLOBALS['wp']->request );
		} else {
			$url = home_url( '/' );
		}
		$repl = array(
			'{site}'   => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			'{title}'  => wp_strip_all_tags( wp_get_document_title() ),
			'{url}'    => $url,
			'{number}' => (string) $number,
		);
		return strtr( (string) $text, $repl );
	}

	/** Agentes con número; el 1º sirve de fallback si no hay número general. */
	private function agents_with_number( $o ) {
		$out = array();
		if ( ! empty( $o['agents_enabled'] ) && is_array( $o['agents'] ) ) {
			foreach ( $o['agents'] as $a ) {
				if ( isset( $a['number'] ) && '' !== $a['number'] ) {
					$out[] = $a;
				}
			}
		}
		return $out;
	}

	private function base_url( $number, $greeting ) {
		return 'https://wa.me/' . $number . '?text=' . rawurlencode( $greeting );
	}

	/* ------------------------------------------------------------------ *
	 *  Shortcode (botón en línea)
	 * ------------------------------------------------------------------ */
	public function shortcode( $atts ) {
		$o = CTCV_Options::get();
		$agents = $this->agents_with_number( $o );
		$number = ( '' !== $o['phone'] ) ? $o['phone'] : ( ! empty( $agents ) ? $agents[0]['number'] : '' );
		if ( '' === $number ) {
			return '';
		}
		$atts    = shortcode_atts( array( 'text' => __( 'Escríbenos por WhatsApp', 'ctc-vinkora' ), 'class' => '' ), $atts, 'whatsapp_button' );
		$greet   = $this->apply_vars( $o['greeting'], $number );
		$classes = 'ctcv-whatsapp ctcv-inline-btn';
		if ( ! empty( $atts['class'] ) ) {
			$classes .= ' ' . sanitize_html_class( $atts['class'], '' );
		}
		return sprintf(
			'<a href="%1$s" class="%2$s" rel="noopener nofollow" target="_blank" data-ctcv-inline="1" style="--ctcv-color:%3$s">%4$s %5$s</a>',
			esc_url( $this->base_url( $number, $greet ) ),
			esc_attr( $classes ),
			esc_attr( $o['color'] ),
			$this->svg_icon(),
			esc_html( $atts['text'] )
		);
	}

	/* ------------------------------------------------------------------ *
	 *  Render principal
	 * ------------------------------------------------------------------ */
	public function render_frontend() {
		$o = CTCV_Options::get();

		$agents = $this->agents_with_number( $o );
		$number = ( '' !== $o['phone'] ) ? $o['phone'] : ( ! empty( $agents ) ? $agents[0]['number'] : '' );

		if ( '' === $number ) {
			return; // sin número no hay ni botón flotante ni inline con tracking
		}

		// El botón FLOTANTE depende de "enabled" + visibilidad; los botones inline (shortcode /
		// clase ctcv-whatsapp) reciben CSS+JS igualmente para conservar estilo y UTMs.
		$floating   = ( ! empty( $o['enabled'] ) && $this->should_display() );
		$show_float = ( $floating && ( ! empty( $o['show_desktop'] ) || ! empty( $o['show_mobile'] ) ) );
		$greeting   = $this->apply_vars( $o['greeting'], $number );

		// Modo de popup: agentes tiene prioridad; luego form; luego greeting; si no, directo.
		$popup = 'none';
		if ( ! empty( $agents ) ) {
			$popup = 'agents';
		} elseif ( 'form' === $o['greeting_type'] ) {
			$popup = 'form';
		} elseif ( 'greeting' === $o['greeting_type'] ) {
			$popup = 'greeting';
		}

		// UTMs.
		$utm_keys     = array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_id', 'utm_term', 'utm_content' );
		$extra_keys   = array_filter( array_map( 'trim', explode( ',', $o['extra_params'] ) ) );
		$all_keys     = array_values( array_unique( array_merge( $utm_keys, $extra_keys ) ) );
		$primary_keys = array_values( array_unique( array_merge( array( 'utm_source' ), $extra_keys ) ) );

		$tz_offset = (int) round( (float) get_option( 'gmt_offset' ) * 60 );

		$cfg = array(
			'store'       => 'ctcv_attr',
			'phone'       => $number,
			'greeting'    => $greeting,
			'keys'        => $all_keys,
			'primary'     => $primary_keys,
			'attribution' => $o['attribution'],
			'ttlDays'     => (int) $o['ttl_days'],
			'onlyIfUtm'   => ! empty( $o['only_if_utm'] ),
			'header'      => $o['track_header'],
			'includePage' => ! empty( $o['include_page'] ),
			'includeRef'  => ! empty( $o['include_ref'] ),
			'compact'     => ! empty( $o['compact'] ),
			'showDesktop' => ! empty( $o['show_desktop'] ),
			'showMobile'  => ! empty( $o['show_mobile'] ),
			'showFloat'   => $show_float,
			'delay'       => (int) $o['delay_ms'],
			'popup'       => $popup,
			'auto'        => ( 'auto' === $o['greeting_auto'] ),
			'autoDelay'   => (int) $o['greeting_delay'],
			'tzOffset'    => $tz_offset,
			'ga4on'       => ! empty( $o['ga4_enabled'] ),
			'ga4event'    => $o['ga4_event'],
			'gtmon'       => ! empty( $o['gtm_push'] ),
			'gtmevent'    => $o['gtm_event'],
			'pixelon'     => ! empty( $o['pixel_enabled'] ),
			'pixelevent'  => $o['pixel_event'],
		);

		echo "\n<!-- CTC Vinkora -->\n";
		$this->print_css( $o );
		if ( $show_float ) {
			$this->print_button( $o, $number, $greeting, $popup );
			if ( 'none' !== $popup ) {
				$this->print_popup( $o, $number, $greeting, $popup, $agents );
			}
		}
		$this->print_js( $cfg );
	}

	/* ------------------------------------------------------------------ *
	 *  CSS
	 * ------------------------------------------------------------------ */
	private function print_css( $o ) {
		$c      = sanitize_hex_color( $o['color'] );
		if ( ! $c ) {
			$c = '#25D366';
		}
		$side   = ( 'left' === $o['position'] ) ? 'left' : 'right';
		$z      = (int) $o['zindex'];
		$sz     = (int) $o['size'];
		$szm    = (int) $o['size_mobile'];
		$ob     = (int) $o['offset_bottom'];
		$os     = (int) $o['offset_side'];
		$rad    = (int) $o['radius'];
		$icon   = max( 18, (int) round( $sz * 0.53 ) );
		$iconm  = max( 18, (int) round( $szm * 0.53 ) );
		$style  = $o['style'];
		$disp   = ( (int) $o['delay_ms'] > 0 ) ? 'none' : 'inline-flex';
		$hdr    = sanitize_hex_color( $o['greeting_color_header'] );
		$hdr    = $hdr ? $hdr : '#075E54';
		$body   = sanitize_hex_color( $o['greeting_color_body'] );
		$body   = $body ? $body : '#ffffff';
		$msg    = sanitize_hex_color( $o['greeting_color_msg'] );
		$msg    = $msg ? $msg : '#e8f5e9';
		$tipoff = $sz + 12;

		$css  = ":root{--ctcv-color:{$c};}\n";
		// Contenedor / posición común.
		$css .= "#ctcv-fab{position:fixed;bottom:calc({$ob}px + env(safe-area-inset-bottom,0px));{$side}:calc({$os}px + env(safe-area-inset-{$side},0px));z-index:{$z};display:{$disp};align-items:center;gap:8px;cursor:pointer;text-decoration:none;-webkit-tap-highlight-color:transparent;transition:transform .18s ease,box-shadow .18s ease;box-sizing:border-box;}\n";
		$css .= "#ctcv-fab:hover{transform:scale(1.05);}\n";
		$css .= "#ctcv-fab:focus-visible{outline:3px solid rgba(37,211,102,.5);outline-offset:3px;}\n";
		$css .= "#ctcv-fab.ctcv-show{display:inline-flex;animation:ctcv-in .3s ease both;}\n";

		// Estilos de botón.
		if ( 'icon' === $style || 'square' === $style || 'icon-padding' === $style ) {
			$pad = ( 'icon-padding' === $style ) ? 'box-shadow:0 8px 22px rgba(0,0,0,.30);' : 'box-shadow:0 6px 18px rgba(0,0,0,.28);';
			$css .= "#ctcv-fab{width:{$sz}px;height:{$sz}px;justify-content:center;border-radius:{$rad}%;background:var(--ctcv-color);color:#fff;{$pad}}\n";
			$css .= "#ctcv-fab .ctcv-icon{width:{$icon}px;height:{$icon}px;}\n";
			// Etiqueta CTA lateral.
			$labpos = ( 'left' === $side ) ? "left:{$tipoff}px" : "right:{$tipoff}px";
			$tx     = ( 'left' === $side ) ? '-8px' : '8px';
			$css   .= "#ctcv-fab .ctcv-cta{position:absolute;{$labpos};background:#fff;color:#111;font:600 13px/1.3 -apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;padding:8px 12px;border-radius:10px;box-shadow:0 4px 14px rgba(0,0,0,.18);white-space:nowrap;pointer-events:none;transform:translateX({$tx});transition:opacity .18s ease,transform .18s ease;}\n";
			if ( 'hover' === $o['cta_mode'] ) {
				$css .= "#ctcv-fab .ctcv-cta{opacity:0;}#ctcv-fab:hover .ctcv-cta{opacity:1;transform:translateX(0);}\n";
			}
		} elseif ( 'button' === $style || 'chip' === $style ) {
			$brad = ( 'chip' === $style ) ? '999px' : '12px';
			$h    = max( 44, (int) round( $sz * 0.8 ) );
			$css .= "#ctcv-fab{min-height:{$h}px;padding:0 18px;border-radius:{$brad};background:var(--ctcv-color);color:#fff;font:600 15px/1 -apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;box-shadow:0 6px 18px rgba(0,0,0,.24);}\n";
			$css .= "#ctcv-fab .ctcv-icon{width:22px;height:22px;flex:0 0 auto;}\n";
			$css .= "#ctcv-fab .ctcv-label{color:#fff;white-space:nowrap;}\n";
		} elseif ( 'text' === $style ) {
			$css .= "#ctcv-fab{background:transparent;color:var(--ctcv-color);font:600 15px/1 -apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;}\n";
			$css .= "#ctcv-fab .ctcv-icon{width:22px;height:22px;flex:0 0 auto;}\n";
			$css .= "#ctcv-fab .ctcv-label{white-space:nowrap;text-decoration:underline;}\n";
		} elseif ( 'image' === $style ) {
			$css .= "#ctcv-fab{background:transparent;}\n";
			$css .= "#ctcv-fab .ctcv-own-img{width:{$sz}px;height:auto;display:block;border-radius:{$rad}%;}\n";
		}

		// Pulso (solo formas con fondo).
		if ( ! empty( $o['pulse'] ) && in_array( $style, array( 'icon', 'square', 'icon-padding' ), true ) ) {
			$css .= "#ctcv-fab::before{content:\"\";position:absolute;inset:0;border-radius:{$rad}%;background:var(--ctcv-color);z-index:-1;animation:ctcv-pulse 2.2s ease-out infinite;}\n";
			$css .= "@keyframes ctcv-pulse{0%{transform:scale(1);opacity:.55}70%{transform:scale(1.7);opacity:0}100%{opacity:0}}\n";
		}
		$css .= "@keyframes ctcv-in{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:none}}\n";

		// Badge.
		if ( ! empty( $o['badge_enabled'] ) ) {
			$css .= "#ctcv-fab .ctcv-badge{position:absolute;top:-4px;right:-4px;min-width:18px;height:18px;padding:0 4px;border-radius:9px;background:#ff3b30;color:#fff;font:700 11px/18px -apple-system,Segoe UI,Roboto,Arial,sans-serif;text-align:center;box-shadow:0 1px 4px rgba(0,0,0,.3);}\n";
		}

		// Botón en línea (shortcode / clase).
		$css .= "a.ctcv-whatsapp.ctcv-inline-btn{display:inline-flex;align-items:center;gap:.5em;background:var(--ctcv-color,{$c});color:#fff!important;text-decoration:none;padding:.7em 1.15em;border-radius:9px;font-weight:600;line-height:1;box-shadow:0 3px 10px rgba(0,0,0,.16);}\n";
		$css .= "a.ctcv-whatsapp.ctcv-inline-btn .ctcv-icon{width:22px;height:22px;}\n";

		// Popup.
		$css .= $this->popup_css( $o, $side, $z, $hdr, $body, $msg );

		// Móvil.
		$css .= "@media (max-width:600px){";
		if ( 'icon' === $style || 'square' === $style || 'icon-padding' === $style ) {
			$css .= "#ctcv-fab{width:{$szm}px;height:{$szm}px;}#ctcv-fab .ctcv-icon{width:{$iconm}px;height:{$iconm}px;}#ctcv-fab .ctcv-cta{display:none;}";
		} elseif ( 'image' === $style ) {
			$css .= "#ctcv-fab .ctcv-own-img{width:{$szm}px;}";
		}
		$css .= "}\n";
		$css .= "@media (prefers-reduced-motion:reduce){#ctcv-fab,#ctcv-fab:hover{transition:none;transform:none}#ctcv-fab::before{animation:none;display:none}#ctcv-fab.ctcv-show{animation:none}}\n";

		if ( '' !== trim( (string) $o['custom_css'] ) ) {
			$css .= "\n" . $o['custom_css'] . "\n";
		}

		echo '<style id="ctcv-css">' . $css . '</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
	}

	private function popup_css( $o, $side, $z, $hdr, $body, $msg ) {
		$w    = "320px";
		if ( 'small' === $o['greeting_size'] ) {
			$w = "280px";
		} elseif ( 'large' === $o['greeting_size'] ) {
			$w = "380px";
		}
		$off  = (int) $o['offset_bottom'] + (int) $o['size'] + 16;
		$oside = (int) $o['offset_side'];
		$zz   = $z + 1;
		$css  = "#ctcv-popup{position:fixed;{$side}:{$oside}px;bottom:{$off}px;width:{$w};max-width:calc(100vw - 24px);z-index:{$zz};background:{$body};border-radius:14px;box-shadow:0 12px 40px rgba(0,0,0,.25);overflow:hidden;font:400 14px/1.4 -apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;display:none;}\n";
		$css .= "#ctcv-popup.ctcv-open{display:block;animation:ctcv-in .25s ease both;}\n";
		$css .= "#ctcv-popup.ctcv-modal{position:fixed;left:50%;top:50%;bottom:auto;{$side}:auto;transform:translate(-50%,-50%);}\n";
		$css .= "#ctcv-overlay{position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:{$z};display:none;}\n#ctcv-overlay.ctcv-open{display:block;}\n";
		$css .= "#ctcv-popup .ctcv-pop-head{background:{$hdr};color:#fff;padding:14px 16px;display:flex;align-items:center;justify-content:space-between;gap:10px;}\n";
		$css .= "#ctcv-popup .ctcv-pop-head strong{font-size:15px;font-weight:700;}\n";
		$css .= "#ctcv-popup .ctcv-pop-close{background:none;border:0;color:#fff;font-size:18px;line-height:1;cursor:pointer;opacity:.85;}\n";
		$css .= "#ctcv-popup .ctcv-pop-body{padding:16px;max-height:60vh;overflow:auto;}\n";
		$css .= "#ctcv-popup .ctcv-msg{background:{$msg};border-radius:10px;padding:12px 14px;margin-bottom:12px;color:#111;}\n";
		$css .= "#ctcv-popup .ctcv-form label{display:block;font-weight:600;margin:10px 0 4px;color:#111;font-size:13px;}\n";
		$css .= "#ctcv-popup .ctcv-form input,#ctcv-popup .ctcv-form textarea,#ctcv-popup .ctcv-form select{width:100%;box-sizing:border-box;padding:9px 11px;border:1px solid #d0d5dd;border-radius:8px;font:inherit;}\n";
		$css .= "#ctcv-popup .ctcv-form textarea{min-height:70px;resize:vertical;}\n";
		$css .= "#ctcv-popup .ctcv-cta-btn{margin-top:14px;width:100%;padding:11px;border:0;border-radius:10px;background:var(--ctcv-color);color:#fff;font-weight:700;font-size:15px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:8px;}\n";
		$css .= "#ctcv-popup .ctcv-cta-btn .ctcv-icon{width:20px;height:20px;}\n";
		$css .= "#ctcv-popup .ctcv-agent{width:100%;display:flex;align-items:center;gap:12px;padding:10px;margin:6px 0;border:1px solid #eaeaea;border-radius:10px;background:#fff;cursor:pointer;text-align:left;}\n";
		$css .= "#ctcv-popup .ctcv-agent:hover{border-color:var(--ctcv-color);}\n";
		$css .= "#ctcv-popup .ctcv-agent img{width:42px;height:42px;border-radius:50%;object-fit:cover;flex:0 0 auto;}\n";
		$css .= "#ctcv-popup .ctcv-agent .ctcv-ava{width:42px;height:42px;border-radius:50%;background:var(--ctcv-color);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;flex:0 0 auto;}\n";
		$css .= "#ctcv-popup .ctcv-agent .ctcv-a-name{font-weight:700;color:#111;}\n";
		$css .= "#ctcv-popup .ctcv-agent .ctcv-a-role{font-size:12px;color:#667085;}\n";
		$css .= "#ctcv-popup .ctcv-agent .ctcv-a-dot{margin-left:auto;width:9px;height:9px;border-radius:50%;background:#12b76a;flex:0 0 auto;}\n";
		$css .= "#ctcv-popup .ctcv-agent.ctcv-off .ctcv-a-dot{background:#d0d5dd;}\n";
		$css .= "#ctcv-popup .ctcv-agent.ctcv-off .ctcv-off-txt{margin-left:auto;font-size:11px;color:#98a2b3;}\n";
		$css .= "#ctcv-popup .ctcv-req{color:#b32d2e;}\n";
		return $css;
	}

	/* ------------------------------------------------------------------ *
	 *  Botón
	 * ------------------------------------------------------------------ */
	private function print_button( $o, $number, $greeting, $popup ) {
		$style = $o['style'];
		$cta   = $o['cta_text'];
		$aria  = ( '' !== trim( (string) $o['tooltip'] ) ) ? $o['tooltip'] : 'WhatsApp';
		$href  = $this->base_url( $number, $greeting );
		$disp0 = ( (int) $o['delay_ms'] > 0 ) ? 'none' : 'inline-flex';

		echo '<a id="ctcv-fab" href="' . esc_url( $href ) . '" target="_blank" rel="noopener nofollow" data-ctcv-style="' . esc_attr( $style ) . '" aria-label="' . esc_attr( $aria ) . '">';
		if ( 'image' === $style && '' !== $o['own_image_url'] ) {
			echo '<img class="ctcv-own-img" src="' . esc_url( $o['own_image_url'] ) . '" alt="' . esc_attr( $aria ) . '">';
		} else {
			echo $this->svg_icon(); // phpcs:ignore WordPress.Security.EscapeOutput
		}
		// Etiqueta inline (button/chip/text) o CTA lateral (icon styles).
		if ( in_array( $style, array( 'button', 'chip', 'text' ), true ) && '' !== trim( (string) $cta ) ) {
			echo '<span class="ctcv-label">' . esc_html( $cta ) . '</span>';
		} elseif ( in_array( $style, array( 'icon', 'square', 'icon-padding' ), true ) && 'hide' !== $o['cta_mode'] && '' !== trim( (string) $cta ) ) {
			echo '<span class="ctcv-cta">' . esc_html( $cta ) . '</span>';
		}
		if ( ! empty( $o['badge_enabled'] ) && '' !== trim( (string) $o['badge_text'] ) ) {
			echo '<span class="ctcv-badge">' . esc_html( $o['badge_text'] ) . '</span>';
		}
		echo '</a>';
		if ( 'none' === $disp0 ) {
			echo '<noscript><style>#ctcv-fab{display:inline-flex}</style></noscript>';
		}
		echo "\n";
	}

	/* ------------------------------------------------------------------ *
	 *  Popup (saludo / formulario / agentes)
	 * ------------------------------------------------------------------ */
	private function print_popup( $o, $number, $greeting, $popup, $agents ) {
		$modal = ( 'modal' === $o['greeting_position'] );
		if ( $modal ) {
			echo '<div id="ctcv-overlay"></div>';
		}
		echo '<div id="ctcv-popup" class="' . ( $modal ? 'ctcv-modal' : '' ) . '" role="dialog" aria-modal="true" hidden>';
		echo '<div class="ctcv-pop-head"><strong>' . esc_html( 'agents' === $popup ? $o['agents_title'] : $o['greeting_title'] ) . '</strong><button type="button" class="ctcv-pop-close" aria-label="' . esc_attr__( 'Cerrar', 'ctc-vinkora' ) . '">&#10005;</button></div>';
		echo '<div class="ctcv-pop-body">';

		if ( 'agents' === $popup ) {
			foreach ( $agents as $a ) {
				$a       = wp_parse_args( is_array( $a ) ? $a : array(), array( 'name' => '', 'number' => '', 'role' => '', 'avatar' => '', 'greeting' => '', 'always' => 0, 'days' => array(), 'from' => '', 'to' => '', 'offline' => 'show' ) );
				$ag_greet = ( '' !== $a['greeting'] ) ? $this->apply_vars( $a['greeting'], $a['number'] ) : $greeting;
				$nm       = ( '' !== $a['name'] ) ? $a['name'] : 'A';
				$initial  = strtoupper( function_exists( 'mb_substr' ) ? mb_substr( $nm, 0, 1 ) : substr( $nm, 0, 1 ) );
				$days     = is_array( $a['days'] ) ? implode( ',', $a['days'] ) : '';
				echo '<button type="button" class="ctcv-agent" data-number="' . esc_attr( $a['number'] ) . '" data-greeting="' . esc_attr( $ag_greet ) . '" data-name="' . esc_attr( $a['name'] ) . '" data-always="' . ( ! empty( $a['always'] ) ? '1' : '0' ) . '" data-days="' . esc_attr( $days ) . '" data-from="' . esc_attr( $a['from'] ) . '" data-to="' . esc_attr( $a['to'] ) . '" data-offline="' . esc_attr( $a['offline'] ) . '">';
				if ( '' !== $a['avatar'] ) {
					echo '<img src="' . esc_url( $a['avatar'] ) . '" alt="">';
				} else {
					echo '<span class="ctcv-ava">' . esc_html( $initial ) . '</span>';
				}
				echo '<span><span class="ctcv-a-name">' . esc_html( $a['name'] ) . '</span>';
				if ( '' !== $a['role'] ) {
					echo '<br><span class="ctcv-a-role">' . esc_html( $a['role'] ) . '</span>';
				}
				echo '</span><span class="ctcv-a-dot"></span><span class="ctcv-off-txt" style="display:none">' . esc_html( $o['agents_offline_text'] ) . '</span>';
				echo '</button>';
			}
		} elseif ( 'form' === $popup ) {
			if ( '' !== trim( (string) $o['greeting_body'] ) ) {
				echo '<div class="ctcv-msg">' . esc_html( $o['greeting_body'] ) . '</div>';
			}
			echo '<form class="ctcv-form">';
			$fields = is_array( $o['form_fields'] ) ? $o['form_fields'] : array();
			foreach ( $fields as $i => $f ) {
				echo $this->form_field_html( $i, $f ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			echo '<button type="submit" class="ctcv-cta-btn" data-number="' . esc_attr( $number ) . '" data-greeting="' . esc_attr( $greeting ) . '">' . $this->svg_icon() . '<span>' . esc_html( $o['form_cta'] ) . '</span></button>'; // phpcs:ignore WordPress.Security.EscapeOutput
			echo '</form>';
		} else { // greeting
			echo '<div class="ctcv-msg">' . esc_html( $o['greeting_body'] ) . '</div>';
			echo '<button type="button" class="ctcv-cta-btn ctcv-greet-cta" data-number="' . esc_attr( $number ) . '" data-greeting="' . esc_attr( $greeting ) . '">' . $this->svg_icon() . '<span>' . esc_html( $o['greeting_cta'] ) . '</span></button>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}

		echo '</div></div>' . "\n";
	}

	private function form_field_html( $i, $f ) {
		$type  = isset( $f['type'] ) ? $f['type'] : 'text';
		$label = isset( $f['label'] ) ? $f['label'] : '';
		$ph    = isset( $f['placeholder'] ) ? $f['placeholder'] : '';
		$req   = ! empty( $f['required'] );
		$add   = ! empty( $f['add'] );
		$lid   = 'ctcv-f-' . (int) $i;
		$dl    = ' data-label="' . esc_attr( $label ) . '" data-add="' . ( $add ? '1' : '0' ) . '"' . ( $req ? ' data-req="1"' : '' );

		if ( 'hidden' === $type ) {
			// Campo oculto: valor fijo del label (útil para etiquetas internas). Se añade si "add".
			return '<input type="hidden" id="' . esc_attr( $lid ) . '" value="' . esc_attr( $label ) . '"' . $dl . '>';
		}

		$reqmark = $req ? ' <span class="ctcv-req">*</span>' : '';
		$h  = '<label for="' . esc_attr( $lid ) . '">' . esc_html( $label ) . $reqmark . '</label>';

		if ( 'textarea' === $type ) {
			$h .= '<textarea id="' . esc_attr( $lid ) . '" placeholder="' . esc_attr( $ph ) . '"' . ( $req ? ' required' : '' ) . $dl . '></textarea>';
		} elseif ( 'select' === $type ) {
			$opts = array_filter( array_map( 'trim', explode( ',', (string) ( isset( $f['options'] ) ? $f['options'] : '' ) ) ) );
			$h   .= '<select id="' . esc_attr( $lid ) . '"' . ( $req ? ' required' : '' ) . $dl . '>';
			$h   .= '<option value="">' . esc_html( '' !== $ph ? $ph : '—' ) . '</option>';
			foreach ( $opts as $opt ) {
				$h .= '<option value="' . esc_attr( $opt ) . '">' . esc_html( $opt ) . '</option>';
			}
			$h .= '</select>';
		} elseif ( 'checkbox' === $type ) {
			$h  = '<label for="' . esc_attr( $lid ) . '" style="display:flex;align-items:center;gap:8px;font-weight:400"><input type="checkbox" id="' . esc_attr( $lid ) . '" style="width:auto"' . ( $req ? ' required' : '' ) . $dl . '> ' . esc_html( $label ) . $reqmark . '</label>';
		} else {
			$itype = in_array( $type, array( 'email', 'tel', 'date' ), true ) ? $type : 'text';
			$h    .= '<input type="' . esc_attr( $itype ) . '" id="' . esc_attr( $lid ) . '" placeholder="' . esc_attr( $ph ) . '"' . ( $req ? ' required' : '' ) . $dl . '>';
		}
		return $h;
	}

	/* ------------------------------------------------------------------ *
	 *  JS (UTM reutilizado + popup + form + agentes + tracking)
	 * ------------------------------------------------------------------ */
	private function print_js( $cfg ) {
		?>
<script id="ctcv-js">
(function(){
	"use strict";
	var CFG = <?php echo wp_json_encode( $cfg, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?>;
	if (!CFG || !CFG.phone) { return; }

	var NOW = Date.now();
	var TTL = (CFG.ttlDays || 90) * 86400000;

	/* ---- almacenamiento tolerante (localStorage -> cookie -> memoria) ---- */
	var mem = null;
	function lsGet(k){ try { return window.localStorage.getItem(k); } catch(e){ return null; } }
	function lsSet(k,v){ try { window.localStorage.setItem(k,v); return true; } catch(e){ return false; } }
	function cookieGet(k){
		try {
			var m = document.cookie.match('(?:^|; )'+k.replace(/([.$?*|{}()\[\]\\\/\+^])/g,'\\$1')+'=([^;]*)');
			return m ? decodeURIComponent(m[1]) : null;
		} catch(e){ return null; }
	}
	function cookieSet(k,v){
		try {
			var d = new Date(NOW + TTL).toUTCString();
			var secure = (location.protocol === 'https:') ? '; Secure' : '';
			document.cookie = k+'='+encodeURIComponent(v)+'; path=/; expires='+d+'; SameSite=Lax'+secure;
		} catch(e){}
	}
	function loadStore(){
		var raw = lsGet(CFG.store) || cookieGet(CFG.store) || (mem ? JSON.stringify(mem) : null);
		if (!raw) { return null; }
		try { var obj = JSON.parse(raw); if (obj && obj.ts && (NOW - obj.ts) > TTL) { return null; } return obj; } catch(e){ return null; }
	}
	function saveStore(obj){
		var raw = JSON.stringify(obj); mem = obj;
		lsSet(CFG.store, raw); cookieSet(CFG.store, raw); // doble persistencia (localStorage + cookie)
	}
	function currentParams(){
		var out = {}, sp;
		try { sp = new URLSearchParams(location.search); } catch(e){ sp = null; }
		for (var i=0;i<CFG.keys.length;i++){
			var k = CFG.keys[i], v = null;
			if (sp) { v = sp.get(k); }
			else { var rx = new RegExp('[?&]'+k.replace(/[.*+?^${}()|[\]\\]/g,'\\$&')+'=([^&#]*)'); var mm = location.search.match(rx); v = mm ? decodeURIComponent(mm[1].replace(/\+/g,' ')) : null; }
			if (v !== null && v !== '') { out[k] = v; }
		}
		return out;
	}
	function countKeys(o){ var n=0,k; for(k in o){ if(Object.prototype.hasOwnProperty.call(o,k)){n++;} } return n; }
	function hasPrimary(o){ var pk = CFG.primary || ['utm_source']; for (var i=0;i<pk.length;i++){ if (o[pk[i]]) { return true; } } return false; }
	function cleanLanding(){ try { return location.origin + location.pathname; } catch(e){ return (location.href || '').split('?')[0]; } }
	function resolveAttribution(){
		var cur = currentParams(); var real = hasPrimary(cur); var st = loadStore();
		if (!st) { st = { params: cur, landing: cleanLanding(), referrer: document.referrer || '', ts: NOW, upd: NOW }; saveStore(st); return st; }
		if (countKeys(st.params) === 0 && real) { st.params = cur; st.landing = cleanLanding(); st.referrer = document.referrer || st.referrer; st.ts = NOW; st.upd = NOW; saveStore(st); return st; }
		if (CFG.attribution === 'last' && real) { st.params = cur; st.landing = cleanLanding(); st.referrer = document.referrer || st.referrer; st.ts = NOW; st.upd = NOW; saveStore(st); }
		return st;
	}

	/* ---- construir mensaje: saludo + campos del formulario + bloque UTM ---- */
	function buildMessage(greeting, formLines){
		var st = loadStore() || resolveAttribution();
		var p = (st && st.params) ? st.params : {};
		var utm = [];
		for (var i=0;i<CFG.keys.length;i++){ var k = CFG.keys[i]; if (p[k]) { utm.push([k, p[k]]); } }
		if (CFG.includePage && st && st.landing) { utm.push(['pagina', st.landing]); }
		if (CFG.includeRef && st && st.referrer) { utm.push(['origen', st.referrer]); }

		var msg = greeting || '';
		var parts = [];
		if (formLines && formLines.length) { parts.push(formLines.map(function(kv){ return kv[0]+': '+kv[1]; }).join('\n')); }
		if (utm.length && !(CFG.onlyIfUtm && countKeys(p) === 0)) {
			var block = CFG.compact ? utm.map(function(kv){ return kv[0]+'='+kv[1]; }).join(' | ') : utm.map(function(kv){ return kv[0]+': '+kv[1]; }).join('\n');
			parts.push((CFG.header ? CFG.header + '\n' : '') + block);
		}
		if (parts.length) { msg += '\n\n' + parts.join('\n\n'); }
		return msg;
	}
	function directUrl(){ return 'https://wa.me/' + CFG.phone + '?text=' + encodeURIComponent(buildMessage(CFG.greeting, null)); }

	/* ---- rastreo (GA4 / GTM / Meta Pixel) ---- */
	function track(number, agentName){
		try {
			var p = { page_title: document.title, page_location: location.href, wa_number: number };
			if (agentName) { p.agent = agentName; }
			var dl = (window.dataLayer && typeof window.dataLayer.push === 'function') ? window.dataLayer : null;
			if (CFG.ga4on) {
				if (typeof window.gtag === 'function') {
					var gp = { transport_type: 'beacon' }; for (var a in p){ if(p.hasOwnProperty(a)){ gp[a]=p[a]; } }
					window.gtag('event', CFG.ga4event, gp);
				} else if (dl) {
					var o1 = { event: CFG.ga4event }; for (var b in p){ if(p.hasOwnProperty(b)){ o1[b]=p[b]; } }
					dl.push(o1);
				}
			}
			if (CFG.gtmon && dl) {
				var o2 = { event: CFG.gtmevent }; for (var c in p){ if(p.hasOwnProperty(c)){ o2[c]=p[c]; } }
				dl.push(o2);
			}
			if (CFG.pixelon && typeof window.fbq === 'function') { window.fbq('track', CFG.pixelevent, p); }
		} catch(e){}
	}
	function openWa(number, greeting, formLines, agentName){
		var url = 'https://wa.me/' + number + '?text=' + encodeURIComponent(buildMessage(greeting, formLines));
		track(number, agentName);
		var w = window.open(url, '_blank');
		if (!w) { location.href = url; }
	}

	/* ---- detección de dispositivo / visibilidad ---- */
	function isMobile(){ return /Android|iPhone|iPad|iPod|Opera Mini|IEMobile|BlackBerry/i.test(navigator.userAgent || '') || (window.matchMedia && window.matchMedia('(max-width:768px)').matches); }
	function allowedHere(){ return isMobile() ? CFG.showMobile : CFG.showDesktop; }

	/* ---- agentes: online segun horario (hora del sitio via tzOffset) ---- */
	function siteNow(){ var n = new Date(); return new Date(n.getTime() + n.getTimezoneOffset()*60000 + (CFG.tzOffset||0)*60000); }
	function agentOnline(el){
		if (el.getAttribute('data-always') === '1') { return true; }
		var days = (el.getAttribute('data-days')||'').split(',').filter(Boolean);
		var from = el.getAttribute('data-from')||'', to = el.getAttribute('data-to')||'';
		if (!days.length || !from || !to) { return true; }
		var sn = siteNow();
		var map = ['sun','mon','tue','wed','thu','fri','sat'];
		if (days.indexOf(map[sn.getDay()]) === -1) { return false; }
		var cur = sn.getHours()*60 + sn.getMinutes();
		function mins(t){ var pp = t.split(':'); return parseInt(pp[0],10)*60 + parseInt(pp[1],10); }
		var f = mins(from), t2 = mins(to);
		return (f <= t2) ? (cur >= f && cur <= t2) : (cur >= f || cur <= t2); // t2<f => rango que cruza medianoche
	}

	/* ---- popup ---- */
	function getPopup(){ return document.getElementById('ctcv-popup'); }
	function getOverlay(){ return document.getElementById('ctcv-overlay'); }
	function openPopup(){
		var pop = getPopup(); if (!pop) { return; }
		pop.hidden = false; pop.classList.add('ctcv-open');
		var ov = getOverlay(); if (ov) { ov.classList.add('ctcv-open'); }
	}
	function closePopup(){
		var pop = getPopup(); if (!pop) { return; }
		pop.classList.remove('ctcv-open'); pop.hidden = true;
		var ov = getOverlay(); if (ov) { ov.classList.remove('ctcv-open'); }
	}

	function wireDirect(a){
		if (!a || a.getAttribute('data-ctcv-wired')) { return; }
		a.setAttribute('data-ctcv-wired','1');
		a.setAttribute('target','_blank'); a.setAttribute('rel','noopener nofollow');
		function refresh(){ a.href = directUrl(); }
		refresh();
		a.addEventListener('pointerdown', refresh, {passive:true});
		a.addEventListener('mouseenter', refresh);
		a.addEventListener('click', function(){ a.href = directUrl(); track(CFG.phone, null); }, false);
	}

	function collectForm(form){
		var lines = [], ok = true;
		var els = form.querySelectorAll('[data-label]');
		for (var i=0;i<els.length;i++){
			var el = els[i];
			var val = (el.type === 'checkbox') ? (el.checked ? 'Sí' : '') : (el.value || '');
			val = ('' + val).trim();
			if (el.getAttribute('data-req') === '1' && !val && el.type !== 'checkbox') { ok = false; try{ el.focus(); }catch(e){} return { ok:false }; }
			if (el.getAttribute('data-req') === '1' && el.type === 'checkbox' && !el.checked) { ok = false; return { ok:false }; }
			if (el.getAttribute('data-add') === '1' && val) { lines.push([ el.getAttribute('data-label') || 'campo', val ]); }
		}
		return { ok: ok, lines: lines };
	}

	function init(){
		resolveAttribution();

		// Enlaces/botones con la clase (shortcode).
		var list = document.querySelectorAll('a.ctcv-whatsapp, [data-ctcv-inline], [data-whatsapp-button]');
		for (var i=0;i<list.length;i++){ wireDirect(list[i]); }

		var fab = document.getElementById('ctcv-fab');
		if (fab && CFG.showFloat) {
			if (!allowedHere()) { if (fab.parentNode) { fab.parentNode.removeChild(fab); } fab = null; }
			if (fab && CFG.delay > 0) { setTimeout(function(){ fab.classList.add('ctcv-show'); }, CFG.delay); }

			if (fab && CFG.popup === 'none') {
				wireDirect(fab);
			} else if (fab) {
				fab.addEventListener('click', function(e){ e.preventDefault(); openPopup(); });
				var pop = getPopup();
				if (pop) {
					var closeBtn = pop.querySelector('.ctcv-pop-close');
					if (closeBtn) { closeBtn.addEventListener('click', closePopup); }
					var ov = getOverlay(); if (ov) { ov.addEventListener('click', closePopup); }

					// Greeting CTA.
					var g = pop.querySelector('.ctcv-greet-cta');
					if (g) { g.addEventListener('click', function(){ openWa(g.getAttribute('data-number'), g.getAttribute('data-greeting'), null); closePopup(); }); }

					// Formulario.
					var form = pop.querySelector('.ctcv-form');
					if (form) {
						form.addEventListener('submit', function(e){
							e.preventDefault();
							var r = collectForm(form);
							if (!r.ok) { return; }
							var btn = form.querySelector('.ctcv-cta-btn');
							openWa(btn.getAttribute('data-number'), btn.getAttribute('data-greeting'), r.lines);
							closePopup();
						});
					}

					// Agentes.
					var ags = pop.querySelectorAll('.ctcv-agent');
					for (var a=0;a<ags.length;a++){
						(function(el){
							var online = agentOnline(el);
							if (!online) {
								if (el.getAttribute('data-offline') === 'hide') { el.style.display = 'none'; return; }
								el.classList.add('ctcv-off');
								var dot = el.querySelector('.ctcv-a-dot'); if (dot) { dot.style.display='none'; }
								var ot = el.querySelector('.ctcv-off-txt'); if (ot) { ot.style.display=''; }
							}
							el.addEventListener('click', function(){ openWa(el.getAttribute('data-number'), el.getAttribute('data-greeting'), null, el.getAttribute('data-name')); closePopup(); });
						})(ags[a]);
					}

					// Apertura automática.
					if (CFG.auto) { setTimeout(openPopup, Math.max(0, (CFG.autoDelay||0) * 1000)); }
				}
			}
		}

		// Botones inyectados tarde (builders).
		if (window.MutationObserver) {
			var mo = new MutationObserver(function(muts){
				for (var m=0;m<muts.length;m++){ var nodes = muts[m].addedNodes; for (var n=0;n<nodes.length;n++){ var el = nodes[n]; if (el.nodeType !== 1) { continue; }
					if (el.matches && el.matches('a.ctcv-whatsapp,[data-ctcv-inline],[data-whatsapp-button]')) { wireDirect(el); }
					if (el.querySelectorAll){ var inner = el.querySelectorAll('a.ctcv-whatsapp,[data-ctcv-inline],[data-whatsapp-button]'); for (var q=0;q<inner.length;q++){ wireDirect(inner[q]); } }
				} }
			});
			try { mo.observe(document.body, {childList:true, subtree:true}); } catch(e){}
		}
	}

	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
})();
</script>
		<?php
	}
}

endif;
