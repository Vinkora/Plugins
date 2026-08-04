<?php
/**
 * CTC Vinkora — Auto-actualización desde GitHub Releases (sin librerías de terceros).
 *
 * Consulta la API pública de GitHub `/releases/latest`, cachea el resultado 12 h (evita el límite
 * de 60 req/hora por IP de la API sin token) y le dice a WordPress que hay una versión nueva para
 * que aparezca el botón "Actualizar" como en cualquier plugin. Usa el ZIP adjunto al release
 * (asset .zip) como paquete; si no hay asset, cae al zipball y renombra la carpeta a `ctc-vinkora`.
 *
 * Requiere un repo PÚBLICO (para repos privados haría falta incrustar un token, no recomendado).
 * PHP 7.2–8.5: sintaxis conservadora.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CTCV_Updater' ) ) :

#[\AllowDynamicProperties]
final class CTCV_Updater {

	private $owner;
	private $repo;
	private $prefix;    // prefijo de etiqueta en mono-repo (ej. 'ctc-vinkora-'); '' = repo dedicado
	private $slug;      // 'ctc-vinkora'
	private $basename;  // 'ctc-vinkora/ctc-vinkora.php'
	private $version;
	private $cache_key = 'ctcv_gh_release';
	private $cache_ttl = 43200;  // 12 h
	private $fail_ttl  = 1800;   // 30 min (cachea el fallo para no martillar la API)

	public function __construct( $owner, $repo, $prefix = '' ) {
		$this->owner    = (string) $owner;
		$this->repo     = (string) $repo;
		$this->prefix   = (string) $prefix;
		$this->slug     = dirname( plugin_basename( CTCV_FILE ) );
		$this->basename = plugin_basename( CTCV_FILE );
		$this->version  = defined( 'CTCV_VERSION' ) ? CTCV_VERSION : '0';

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );
		add_filter( 'upgrader_source_selection', array( $this, 'fix_dir_name' ), 10, 4 );
		add_action( 'upgrader_process_complete', array( $this, 'clear_cache' ) );
	}

	/** Lee el último release (cacheado). Devuelve array vacío si no hay o si falla. */
	private function get_release() {
		// "Buscar de nuevo" en Escritorio -> Actualizaciones (update-core.php?force-check=1) fuerza
		// un refresco inmediato, sin esperar la caché de 12 h. Solo limpia un transient (inocuo).
		if ( isset( $_GET['force-check'] ) && '' !== (string) $_GET['force-check'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			delete_transient( $this->cache_key );
		}
		$cached = get_transient( $this->cache_key );
		if ( false !== $cached ) {
			return is_array( $cached ) ? $cached : array();
		}
		if ( '' === $this->owner || '' === $this->repo ) {
			return array();
		}
		$base = 'https://api.github.com/repos/' . rawurlencode( $this->owner ) . '/' . rawurlencode( $this->repo ) . '/releases';
		// Mono-repo (con prefijo): listar releases y filtrar por etiqueta. Repo dedicado: /latest.
		$url = ( '' !== $this->prefix ) ? ( $base . '?per_page=30' ) : ( $base . '/latest' );
		$res = wp_remote_get(
			$url,
			array(
				'timeout' => 12,
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'CTC-Vinkora-Updater',
				),
			)
		);
		if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
			set_transient( $this->cache_key, array(), $this->fail_ttl );
			return array();
		}
		$body    = json_decode( wp_remote_retrieve_body( $res ), true );
		$release = null;
		$ver     = '';

		if ( '' !== $this->prefix ) {
			// Elegir el release más nuevo cuya etiqueta empiece por el prefijo del plugin.
			if ( is_array( $body ) ) {
				foreach ( $body as $r ) {
					if ( ! is_array( $r ) || empty( $r['tag_name'] ) || ! empty( $r['draft'] ) || ! empty( $r['prerelease'] ) ) {
						continue;
					}
					$tag = (string) $r['tag_name'];
					if ( 0 !== strpos( $tag, $this->prefix ) ) {
						continue;
					}
					$v = ltrim( substr( $tag, strlen( $this->prefix ) ), 'vV' );
					if ( '' !== $v && ( null === $release || version_compare( $v, $ver, '>' ) ) ) {
						$release = $r;
						$ver     = $v;
					}
				}
			}
		} elseif ( is_array( $body ) && ! empty( $body['tag_name'] ) ) {
			$release = $body;
			$ver     = ltrim( (string) $body['tag_name'], 'vV' );
		}

		if ( null === $release || '' === $ver ) {
			set_transient( $this->cache_key, array(), $this->fail_ttl );
			return array();
		}

		$zip = '';
		if ( ! empty( $release['assets'] ) && is_array( $release['assets'] ) ) {
			foreach ( $release['assets'] as $a ) {
				if ( isset( $a['name'], $a['browser_download_url'] ) && '.zip' === substr( strtolower( (string) $a['name'] ), -4 ) ) {
					$zip = (string) $a['browser_download_url'];
					break;
				}
			}
		}
		// El zipball trae TODO el repo (en mono-repo no sirve): solo se usa como respaldo en repo dedicado.
		if ( '' === $zip && '' === $this->prefix && ! empty( $release['zipball_url'] ) ) {
			$zip = (string) $release['zipball_url'];
		}
		$out = array(
			'version'   => $ver,
			'zip'       => $zip,
			'changelog' => isset( $release['body'] ) ? (string) $release['body'] : '',
			'url'       => isset( $release['html_url'] ) ? (string) $release['html_url'] : '',
			'date'      => isset( $release['published_at'] ) ? (string) $release['published_at'] : '',
		);
		set_transient( $this->cache_key, $out, $this->cache_ttl );
		return $out;
	}

	/** Inyecta la actualización en el transient de WordPress si el release es más nuevo. */
	public function check_update( $transient ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}
		$rel = $this->get_release();
		if ( empty( $rel['version'] ) || empty( $rel['zip'] ) ) {
			return $transient;
		}
		if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
			$transient->response = array();
		}
		if ( version_compare( $rel['version'], $this->version, '>' ) ) {
			$transient->response[ $this->basename ] = (object) array(
				'slug'        => $this->slug,
				'plugin'      => $this->basename,
				'new_version' => $rel['version'],
				'url'         => $rel['url'],
				'package'     => $rel['zip'],
			);
		} elseif ( isset( $transient->no_update ) && is_array( $transient->no_update ) ) {
			$transient->no_update[ $this->basename ] = (object) array(
				'slug'        => $this->slug,
				'plugin'      => $this->basename,
				'new_version' => $this->version,
				'url'         => isset( $rel['url'] ) ? $rel['url'] : '',
				'package'     => '',
			);
		}
		return $transient;
	}

	/** Popup "Ver detalles de la versión". */
	public function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || ! isset( $args->slug ) || $args->slug !== $this->slug ) {
			return $result;
		}
		$rel = $this->get_release();
		if ( empty( $rel['version'] ) ) {
			return $result;
		}
		return (object) array(
			'name'          => 'CTC Vinkora',
			'slug'          => $this->slug,
			'version'       => $rel['version'],
			'author'        => '<a href="https://vinkora.net">Vinkora</a>',
			'homepage'      => 'https://vinkora.net',
			'download_link' => $rel['zip'],
			'trunk'         => $rel['zip'],
			'last_updated'  => $rel['date'],
			'sections'      => array(
				'changelog' => $this->changelog_html( $rel['changelog'] ),
			),
		);
	}

	private function changelog_html( $md ) {
		$md = wp_strip_all_tags( (string) $md );
		if ( '' === trim( $md ) ) {
			return '<p>' . esc_html__( 'Consulta la última versión en GitHub.', 'ctc-vinkora' ) . '</p>';
		}
		return '<pre style="white-space:pre-wrap">' . esc_html( $md ) . '</pre>';
	}

	/**
	 * Garantiza que la carpeta extraída se llame `ctc-vinkora` (el zipball de GitHub la nombra
	 * owner-repo-hash). Solo actúa sobre NUESTRO plugin para no afectar otras actualizaciones.
	 */
	public function fix_dir_name( $source, $remote_source, $upgrader, $hook_extra = array() ) {
		if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->basename ) {
			return $source;
		}
		if ( empty( $source ) || empty( $remote_source ) ) {
			return $source;
		}
		$src = untrailingslashit( $source );
		if ( basename( $src ) === $this->slug ) {
			return $source; // ya correcto (asset .zip bien estructurado)
		}
		$desired = trailingslashit( $remote_source ) . $this->slug;
		global $wp_filesystem;
		if ( $wp_filesystem && $wp_filesystem->move( $src, $desired ) ) {
			return trailingslashit( $desired );
		}
		return $source;
	}

	public function clear_cache() {
		delete_transient( $this->cache_key );
	}
}

endif;
