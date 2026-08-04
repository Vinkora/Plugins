<?php
/**
 * CTC Vinkora — Admin: menú, pestañas (nav-tab) y render de cada pestaña.
 * Una sola opción `ctcv_options`, un solo grupo `ctcv_group`. Cada pestaña es su propio <form>
 * a options.php con un hidden `_active_tab` para el merge selectivo del sanitize.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CTCV_Admin' ) ) :

#[\AllowDynamicProperties]
final class CTCV_Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( CTCV_FILE ), array( $this, 'action_link' ) );
	}

	public function action_link( $links ) {
		$url = admin_url( 'admin.php?page=ctc-vinkora' );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Ajustes', 'ctc-vinkora' ) . '</a>' );
		return $links;
	}

	public function add_menu() {
		$icon = 'data:image/svg+xml;base64,' . base64_encode(
			'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#a7aaad" d="M2 3 L6 3 L10 11 L14 3 L18 3 L11 17 L9 17 Z"/></svg>'
		);
		add_menu_page(
			__( 'CTC Vinkora', 'ctc-vinkora' ),
			__( 'CTC Vinkora', 'ctc-vinkora' ),
			'manage_options',
			'ctc-vinkora',
			array( $this, 'render_page' ),
			$icon,
			58
		);
	}

	public function register() {
		// Asegura la fila de la opción en BD. OJO: crear la opción con defaults() NO evita la doble
		// ejecución del sanitize en el primer guardado (queda en estado "valor == defaults()", que
		// WordPress igual enruta por update_option->add_option -> 2 pasadas). Lo que REALMENTE impide
		// perder el dato es conservar `_active_tab` en la salida del sanitize (ver CTCV_Options), y que
		// el número nacional/código sean campos reales combinados en el servidor.
		if ( false === get_option( CTCV_Options::OPTION, false ) ) {
			add_option( CTCV_Options::OPTION, CTCV_Options::defaults() );
		}
		register_setting(
			'ctcv_group',
			CTCV_Options::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'CTCV_Options', 'sanitize' ),
				'default'           => CTCV_Options::defaults(),
			)
		);
	}

	/* ------------------------------------------------------------------ *
	 *  Helpers de campos
	 * ------------------------------------------------------------------ */
	private function name( $key ) {
		return CTCV_Options::OPTION . '[' . $key . ']';
	}
	private function id( $key ) {
		return 'ctcv_' . $key;
	}
	private function row( $th, $td ) {
		echo '<tr><th scope="row">' . $th . '</th><td>' . $td . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	private function f_check( $o, $key, $label ) {
		return '<label><input type="checkbox" name="' . esc_attr( $this->name( $key ) ) . '" value="1" ' . checked( 1, $o[ $key ], false ) . '> ' . esc_html( $label ) . '</label>';
	}
	private function f_text( $o, $key, $ph = '', $class = 'regular-text' ) {
		return '<input type="text" id="' . esc_attr( $this->id( $key ) ) . '" class="' . esc_attr( $class ) . '" name="' . esc_attr( $this->name( $key ) ) . '" value="' . esc_attr( $o[ $key ] ) . '" placeholder="' . esc_attr( $ph ) . '">';
	}
	private function f_number( $o, $key, $min, $max, $step = 1 ) {
		return '<input type="number" id="' . esc_attr( $this->id( $key ) ) . '" name="' . esc_attr( $this->name( $key ) ) . '" value="' . esc_attr( $o[ $key ] ) . '" min="' . esc_attr( $min ) . '" max="' . esc_attr( $max ) . '" step="' . esc_attr( $step ) . '" style="width:90px">';
	}
	private function f_textarea( $o, $key, $rows = 3 ) {
		return '<textarea id="' . esc_attr( $this->id( $key ) ) . '" class="large-text" rows="' . (int) $rows . '" name="' . esc_attr( $this->name( $key ) ) . '">' . esc_textarea( $o[ $key ] ) . '</textarea>';
	}
	private function f_color( $o, $key ) {
		$id = esc_attr( $this->id( $key ) );
		return '<input type="text" id="' . $id . '" name="' . esc_attr( $this->name( $key ) ) . '" value="' . esc_attr( $o[ $key ] ) . '" style="width:100px"> <input type="color" value="' . esc_attr( $o[ $key ] ) . '" oninput="document.getElementById(\'' . $id . '\').value=this.value">';
	}
	private function f_select( $o, $key, $opts ) {
		$h = '<select id="' . esc_attr( $this->id( $key ) ) . '" name="' . esc_attr( $this->name( $key ) ) . '">';
		foreach ( $opts as $v => $l ) {
			$h .= '<option value="' . esc_attr( $v ) . '" ' . selected( $o[ $key ], $v, false ) . '>' . esc_html( $l ) . '</option>';
		}
		return $h . '</select>';
	}
	private function f_radio( $o, $key, $opts ) {
		$h = '';
		foreach ( $opts as $v => $l ) {
			$h .= '<label style="margin-right:14px"><input type="radio" name="' . esc_attr( $this->name( $key ) ) . '" value="' . esc_attr( $v ) . '" ' . checked( $o[ $key ], $v, false ) . '> ' . esc_html( $l ) . '</label>';
		}
		return $h;
	}
	private function form_open( $tab ) {
		echo '<form method="post" action="options.php">';
		settings_fields( 'ctcv_group' );
		echo '<input type="hidden" name="' . esc_attr( $this->name( '_active_tab' ) ) . '" value="' . esc_attr( $tab ) . '">';
		echo '<table class="form-table" role="presentation">';
	}
	private function form_close() {
		echo '</table>';
		submit_button();
		echo '</form>';
	}

	/** Matriz de casillas de UTMs/click-ids a incluir en el mensaje. */
	private function params_matrix( $o ) {
		$known   = CTCV_Options::known_params();
		$enabled = is_array( $o['tracked_params'] ) ? $o['tracked_params'] : array();
		$field   = esc_attr( CTCV_Options::OPTION . '[tracked_params][]' );
		$h  = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:6px 14px;max-width:840px;background:#fff;border:1px solid #dcdcde;border-radius:6px;padding:12px">';
		foreach ( $known as $key => $label ) {
			$checked = in_array( $key, $enabled, true );
			$h .= '<label style="display:flex;align-items:center;gap:6px;font-family:monospace"><input type="checkbox" name="' . $field . '" value="' . esc_attr( $key ) . '" ' . checked( true, $checked, false ) . '> <span>' . esc_html( $label ) . '</span></label>';
		}
		$h .= '</div>';
		return $h;
	}

	/* ------------------------------------------------------------------ *
	 *  Campo de número con selector de país (buscable, sin dependencias)
	 * ------------------------------------------------------------------ */
	private function phone_field( $o ) {
		// Valores de arranque. El número nacional y el código de país son CAMPOS REALES (se envían
		// aunque el JS no corra); el servidor recompone `phone`. Migración: si venimos de una versión
		// que solo guardaba `phone` completo, derivamos el nacional quitándole el código de país.
		$iso  = ( '' !== $o['phone_iso'] ) ? $o['phone_iso'] : 'co';
		$cc   = $o['phone_cc'];
		$natl = $o['phone_national'];
		if ( '' === $natl && '' !== $o['phone'] ) {
			$full = $o['phone'];
			$natl = ( '' !== $cc && 0 === strpos( $full, $cc ) ) ? substr( $full, strlen( $cc ) ) : $full;
		}
		$h  = '<div class="ctcv-phone">';
		$h .= '<div class="ctcv-cc-box">';
		$h .= '<button type="button" class="ctcv-cc-toggle" id="ctcv-cc-toggle" aria-haspopup="listbox" aria-expanded="false">';
		$h .= '<span class="ctcv-cc-flag" id="ctcv-cc-flag"></span><span class="ctcv-cc-dial" id="ctcv-cc-dial">+' . esc_html( $cc ) . '</span><span class="ctcv-cc-caret"></span></button>';
		$h .= '<div class="ctcv-cc-pop" id="ctcv-cc-pop">';
		$h .= '<input type="text" id="ctcv-cc-search" placeholder="' . esc_attr__( 'Buscar país o código...', 'ctc-vinkora' ) . '" autocomplete="off">';
		$h .= '<ul class="ctcv-cc-list" id="ctcv-cc-list" role="listbox"></ul></div></div>';
		$h .= '<input type="tel" class="regular-text ctcv-natl" id="ctcv-natl" name="' . esc_attr( $this->name( 'phone_national' ) ) . '" value="' . esc_attr( $natl ) . '" placeholder="300 123 4567" autocomplete="off" inputmode="numeric">';
		$h .= '<input type="hidden" name="' . esc_attr( $this->name( 'phone_iso' ) ) . '" id="ctcv-phone-iso" value="' . esc_attr( $iso ) . '">';
		$h .= '<input type="hidden" name="' . esc_attr( $this->name( 'phone_cc' ) ) . '" id="ctcv-phone-cc" value="' . esc_attr( $cc ) . '">';
		$h .= '</div>';
		$h .= '<p class="description">' . esc_html__( 'Elige el país (busca por nombre o código) y escribe el número. Se guarda como código + número, solo dígitos.', 'ctc-vinkora' ) . '</p>';
		return $h;
	}

	/** Lista de países: [ISO2, nombre, código]. La bandera se genera en el navegador desde el ISO. */
	private static function country_data() {
		return array(
			array( 'af', 'Afganistán', '93' ), array( 'al', 'Albania', '355' ), array( 'de', 'Alemania', '49' ),
			array( 'ad', 'Andorra', '376' ), array( 'ao', 'Angola', '244' ), array( 'ai', 'Anguila', '1264' ),
			array( 'ag', 'Antigua y Barbuda', '1268' ), array( 'sa', 'Arabia Saudita', '966' ), array( 'dz', 'Argelia', '213' ),
			array( 'ar', 'Argentina', '54' ), array( 'am', 'Armenia', '374' ), array( 'aw', 'Aruba', '297' ),
			array( 'au', 'Australia', '61' ), array( 'at', 'Austria', '43' ), array( 'az', 'Azerbaiyán', '994' ),
			array( 'bs', 'Bahamas', '1242' ), array( 'bd', 'Bangladés', '880' ), array( 'bb', 'Barbados', '1246' ),
			array( 'bh', 'Baréin', '973' ), array( 'be', 'Bélgica', '32' ), array( 'bz', 'Belice', '501' ),
			array( 'bj', 'Benín', '229' ), array( 'by', 'Bielorrusia', '375' ), array( 'bo', 'Bolivia', '591' ),
			array( 'ba', 'Bosnia y Herzegovina', '387' ), array( 'bw', 'Botsuana', '267' ), array( 'br', 'Brasil', '55' ),
			array( 'bn', 'Brunéi', '673' ), array( 'bg', 'Bulgaria', '359' ), array( 'bf', 'Burkina Faso', '226' ),
			array( 'bi', 'Burundi', '257' ), array( 'bt', 'Bután', '975' ), array( 'cv', 'Cabo Verde', '238' ),
			array( 'kh', 'Camboya', '855' ), array( 'cm', 'Camerún', '237' ), array( 'ca', 'Canadá', '1' ),
			array( 'qa', 'Catar', '974' ), array( 'td', 'Chad', '235' ), array( 'cl', 'Chile', '56' ),
			array( 'cn', 'China', '86' ), array( 'cy', 'Chipre', '357' ), array( 'co', 'Colombia', '57' ),
			array( 'km', 'Comoras', '269' ), array( 'cg', 'Congo', '242' ), array( 'cd', 'Congo (RD)', '243' ),
			array( 'kp', 'Corea del Norte', '850' ), array( 'kr', 'Corea del Sur', '82' ), array( 'ci', 'Costa de Marfil', '225' ),
			array( 'cr', 'Costa Rica', '506' ), array( 'hr', 'Croacia', '385' ), array( 'cu', 'Cuba', '53' ),
			array( 'cw', 'Curazao', '599' ), array( 'dk', 'Dinamarca', '45' ), array( 'dm', 'Dominica', '1767' ),
			array( 'ec', 'Ecuador', '593' ), array( 'eg', 'Egipto', '20' ), array( 'sv', 'El Salvador', '503' ),
			array( 'ae', 'Emiratos Árabes Unidos', '971' ), array( 'er', 'Eritrea', '291' ), array( 'sk', 'Eslovaquia', '421' ),
			array( 'si', 'Eslovenia', '386' ), array( 'es', 'España', '34' ), array( 'us', 'Estados Unidos', '1' ),
			array( 'ee', 'Estonia', '372' ), array( 'sz', 'Esuatini', '268' ), array( 'et', 'Etiopía', '251' ),
			array( 'ph', 'Filipinas', '63' ), array( 'fi', 'Finlandia', '358' ), array( 'fj', 'Fiyi', '679' ),
			array( 'fr', 'Francia', '33' ), array( 'ga', 'Gabón', '241' ), array( 'gm', 'Gambia', '220' ),
			array( 'ge', 'Georgia', '995' ), array( 'gh', 'Ghana', '233' ), array( 'gi', 'Gibraltar', '350' ),
			array( 'gd', 'Granada', '1473' ), array( 'gr', 'Grecia', '30' ), array( 'gl', 'Groenlandia', '299' ),
			array( 'gp', 'Guadalupe', '590' ), array( 'gu', 'Guam', '1671' ), array( 'gt', 'Guatemala', '502' ),
			array( 'gf', 'Guayana Francesa', '594' ), array( 'gy', 'Guyana', '592' ), array( 'gn', 'Guinea', '224' ),
			array( 'gq', 'Guinea Ecuatorial', '240' ), array( 'gw', 'Guinea-Bisáu', '245' ), array( 'ht', 'Haití', '509' ),
			array( 'hn', 'Honduras', '504' ), array( 'hk', 'Hong Kong', '852' ), array( 'hu', 'Hungría', '36' ),
			array( 'in', 'India', '91' ), array( 'id', 'Indonesia', '62' ), array( 'iq', 'Irak', '964' ),
			array( 'ir', 'Irán', '98' ), array( 'ie', 'Irlanda', '353' ), array( 'is', 'Islandia', '354' ),
			array( 'ky', 'Islas Caimán', '1345' ), array( 'ck', 'Islas Cook', '682' ), array( 'fo', 'Islas Feroe', '298' ),
			array( 'sb', 'Islas Salomón', '677' ), array( 'vg', 'Islas Vírgenes (RU)', '1284' ), array( 'vi', 'Islas Vírgenes (EE.UU.)', '1340' ),
			array( 'il', 'Israel', '972' ), array( 'it', 'Italia', '39' ), array( 'jm', 'Jamaica', '1876' ),
			array( 'jp', 'Japón', '81' ), array( 'jo', 'Jordania', '962' ), array( 'kz', 'Kazajistán', '7' ),
			array( 'ke', 'Kenia', '254' ), array( 'kg', 'Kirguistán', '996' ), array( 'ki', 'Kiribati', '686' ),
			array( 'kw', 'Kuwait', '965' ), array( 'la', 'Laos', '856' ), array( 'ls', 'Lesoto', '266' ),
			array( 'lv', 'Letonia', '371' ), array( 'lb', 'Líbano', '961' ), array( 'lr', 'Liberia', '231' ),
			array( 'ly', 'Libia', '218' ), array( 'li', 'Liechtenstein', '423' ), array( 'lt', 'Lituania', '370' ),
			array( 'lu', 'Luxemburgo', '352' ), array( 'mo', 'Macao', '853' ), array( 'mk', 'Macedonia del Norte', '389' ),
			array( 'mg', 'Madagascar', '261' ), array( 'my', 'Malasia', '60' ), array( 'mw', 'Malaui', '265' ),
			array( 'mv', 'Maldivas', '960' ), array( 'ml', 'Malí', '223' ), array( 'mt', 'Malta', '356' ),
			array( 'ma', 'Marruecos', '212' ), array( 'mq', 'Martinica', '596' ), array( 'mu', 'Mauricio', '230' ),
			array( 'mr', 'Mauritania', '222' ), array( 'yt', 'Mayotte', '262' ), array( 'mx', 'México', '52' ),
			array( 'fm', 'Micronesia', '691' ), array( 'md', 'Moldavia', '373' ), array( 'mc', 'Mónaco', '377' ),
			array( 'mn', 'Mongolia', '976' ), array( 'me', 'Montenegro', '382' ), array( 'ms', 'Montserrat', '1664' ),
			array( 'mz', 'Mozambique', '258' ), array( 'mm', 'Myanmar', '95' ), array( 'na', 'Namibia', '264' ),
			array( 'nr', 'Nauru', '674' ), array( 'np', 'Nepal', '977' ), array( 'ni', 'Nicaragua', '505' ),
			array( 'ne', 'Níger', '227' ), array( 'ng', 'Nigeria', '234' ), array( 'no', 'Noruega', '47' ),
			array( 'nc', 'Nueva Caledonia', '687' ), array( 'nz', 'Nueva Zelanda', '64' ), array( 'om', 'Omán', '968' ),
			array( 'nl', 'Países Bajos', '31' ), array( 'pk', 'Pakistán', '92' ), array( 'pw', 'Palaos', '680' ),
			array( 'ps', 'Palestina', '970' ), array( 'pa', 'Panamá', '507' ), array( 'pg', 'Papúa Nueva Guinea', '675' ),
			array( 'py', 'Paraguay', '595' ), array( 'pe', 'Perú', '51' ), array( 'pf', 'Polinesia Francesa', '689' ),
			array( 'pl', 'Polonia', '48' ), array( 'pt', 'Portugal', '351' ), array( 'pr', 'Puerto Rico', '1787' ),
			array( 'gb', 'Reino Unido', '44' ), array( 'cf', 'República Centroafricana', '236' ), array( 'cz', 'República Checa', '420' ),
			array( 'do', 'República Dominicana', '1809' ), array( 're', 'Reunión', '262' ), array( 'rw', 'Ruanda', '250' ),
			array( 'ro', 'Rumania', '40' ), array( 'ru', 'Rusia', '7' ), array( 'ws', 'Samoa', '685' ),
			array( 'as', 'Samoa Americana', '1684' ), array( 'kn', 'San Cristóbal y Nieves', '1869' ), array( 'sm', 'San Marino', '378' ),
			array( 'vc', 'San Vicente y las Granadinas', '1784' ), array( 'lc', 'Santa Lucía', '1758' ), array( 'st', 'Santo Tomé y Príncipe', '239' ),
			array( 'sn', 'Senegal', '221' ), array( 'rs', 'Serbia', '381' ), array( 'sc', 'Seychelles', '248' ),
			array( 'sl', 'Sierra Leona', '232' ), array( 'sg', 'Singapur', '65' ), array( 'sy', 'Siria', '963' ),
			array( 'so', 'Somalia', '252' ), array( 'lk', 'Sri Lanka', '94' ), array( 'za', 'Sudáfrica', '27' ),
			array( 'sd', 'Sudán', '249' ), array( 'ss', 'Sudán del Sur', '211' ), array( 'se', 'Suecia', '46' ),
			array( 'ch', 'Suiza', '41' ), array( 'sr', 'Surinam', '597' ), array( 'th', 'Tailandia', '66' ),
			array( 'tw', 'Taiwán', '886' ), array( 'tz', 'Tanzania', '255' ), array( 'tj', 'Tayikistán', '992' ),
			array( 'tl', 'Timor Oriental', '670' ), array( 'tg', 'Togo', '228' ), array( 'to', 'Tonga', '676' ),
			array( 'tt', 'Trinidad y Tobago', '1868' ), array( 'tn', 'Túnez', '216' ), array( 'tm', 'Turkmenistán', '993' ),
			array( 'tr', 'Turquía', '90' ), array( 'tv', 'Tuvalu', '688' ), array( 'ua', 'Ucrania', '380' ),
			array( 'ug', 'Uganda', '256' ), array( 'uy', 'Uruguay', '598' ), array( 'uz', 'Uzbekistán', '998' ),
			array( 'vu', 'Vanuatu', '678' ), array( 've', 'Venezuela', '58' ), array( 'vn', 'Vietnam', '84' ),
			array( 'ye', 'Yemen', '967' ), array( 'dj', 'Yibuti', '253' ), array( 'zm', 'Zambia', '260' ),
			array( 'zw', 'Zimbabue', '263' ),
		);
	}

	/** CSS + JS del selector de país (solo en la pestaña General). Autónomo, sin dependencias. */
	private function phone_picker_assets() {
		?>
<style>
.ctcv-phone{display:flex;align-items:stretch;gap:8px;max-width:520px}
.ctcv-cc-box{position:relative}
.ctcv-cc-toggle{display:flex;align-items:center;gap:6px;height:34px;padding:0 10px;background:#fff;border:1px solid #8c8f94;border-radius:4px;cursor:pointer;font-size:13px;line-height:1;white-space:nowrap}
.ctcv-cc-toggle:hover{border-color:#2271b1}
.ctcv-cc-flag{font-size:18px}
.ctcv-cc-dial{font-weight:600;color:#1d2327}
.ctcv-cc-caret{width:0;height:0;border-left:4px solid transparent;border-right:4px solid transparent;border-top:5px solid #50575e;margin-left:2px}
.ctcv-cc-pop{position:absolute;top:38px;left:0;z-index:100;width:300px;max-width:80vw;background:#fff;border:1px solid #c3c4c7;border-radius:6px;box-shadow:0 6px 18px rgba(0,0,0,.18);display:none}
.ctcv-cc-pop.open{display:block}
.ctcv-cc-pop input{width:100%;box-sizing:border-box;border:0;border-bottom:1px solid #dcdcde;padding:9px 12px;font-size:13px;outline:none}
.ctcv-cc-list{list-style:none;margin:0;padding:4px 0;max-height:260px;overflow-y:auto}
.ctcv-cc-list li{display:flex;align-items:center;gap:8px;padding:7px 12px;cursor:pointer;font-size:13px}
.ctcv-cc-list li:hover{background:#f0f6fc}
.ctcv-cc-list .ctcv-cc-n{flex:1}
.ctcv-cc-list .ctcv-cc-d{color:#646970}
.ctcv-cc-list .ctcv-cc-empty{color:#646970;cursor:default}
.ctcv-cc-list .ctcv-cc-empty:hover{background:none}
.ctcv-natl{flex:1;min-width:160px}
</style>
<script>
(function(){
	var DATA = <?php echo wp_json_encode( self::country_data() ); ?>;
	var toggle = document.getElementById('ctcv-cc-toggle'),
	    pop    = document.getElementById('ctcv-cc-pop'),
	    search = document.getElementById('ctcv-cc-search'),
	    listEl = document.getElementById('ctcv-cc-list'),
	    flagEl = document.getElementById('ctcv-cc-flag'),
	    dialEl = document.getElementById('ctcv-cc-dial'),
	    natl   = document.getElementById('ctcv-natl'),
	    isoH   = document.getElementById('ctcv-phone-iso'),
	    ccH    = document.getElementById('ctcv-phone-cc');
	if (!toggle || !ccH || !isoH) { return; }
	function flag(iso){
		try { return iso.toUpperCase().replace(/[A-Z]/g, function(c){ return String.fromCodePoint(0x1F1E6 - 65 + c.charCodeAt(0)); }); }
		catch(e){ return iso.toUpperCase(); }
	}
	DATA.sort(function(a,b){ return a[1].localeCompare(b[1]); });
	// El número nacional y el código son campos REALES (se envían solos). El JS solo actualiza la
	// vista y los hidden del país; ya NO depende de sincronizar el número para que se guarde.
	function setCountry(iso, dial){ isoH.value = iso; ccH.value = dial; flagEl.textContent = flag(iso); dialEl.textContent = '+' + dial; }
	function renderList(f){
		f = (f || '').toLowerCase().replace(/^\+/, '');
		var html = '';
		for (var i=0;i<DATA.length;i++){
			var iso=DATA[i][0], name=DATA[i][1], dial=DATA[i][2];
			if (f && name.toLowerCase().indexOf(f) === -1 && dial.indexOf(f) === -1) { continue; }
			html += '<li role="option" data-iso="'+iso+'" data-dial="'+dial+'"><span class="ctcv-cc-flag">'+flag(iso)+'</span> <span class="ctcv-cc-n">'+name+'</span> <span class="ctcv-cc-d">+'+dial+'</span></li>';
		}
		listEl.innerHTML = html || '<li class="ctcv-cc-empty">Sin resultados</li>';
	}
	function open(){ pop.classList.add('open'); toggle.setAttribute('aria-expanded','true'); search.value=''; renderList(''); search.focus(); }
	function close(){ pop.classList.remove('open'); toggle.setAttribute('aria-expanded','false'); }
	toggle.addEventListener('click', function(e){ e.preventDefault(); pop.classList.contains('open') ? close() : open(); });
	search.addEventListener('input', function(){ renderList(search.value); });
	listEl.addEventListener('click', function(e){
		var li = e.target; while (li && li.tagName !== 'LI') { li = li.parentNode; }
		if (!li || !li.getAttribute('data-iso')) { return; }
		setCountry(li.getAttribute('data-iso'), li.getAttribute('data-dial'));
		close(); natl.focus();
	});
	document.addEventListener('click', function(e){ if (!pop.contains(e.target) && !toggle.contains(e.target)) { close(); } });
	// Init: pinta la bandera/código del país guardado (por ISO). Los campos ya vienen del servidor.
	var iso = (isoH.value || 'co').toLowerCase(), found = null;
	for (var j=0;j<DATA.length;j++){ if (DATA[j][0] === iso) { found = DATA[j]; break; } }
	if (found) { flagEl.textContent = flag(found[0]); dialEl.textContent = '+' + found[2]; ccH.value = found[2]; }
	else { flagEl.textContent = flag(iso); dialEl.textContent = '+' + (ccH.value || ''); }
})();
</script>
		<?php
	}

	/* ------------------------------------------------------------------ *
	 *  Página con pestañas
	 * ------------------------------------------------------------------ */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$o          = CTCV_Options::get();
		$has_number = ( '' !== $o['phone'] );
		if ( ! $has_number && ! empty( $o['agents_enabled'] ) && is_array( $o['agents'] ) ) {
			foreach ( $o['agents'] as $a ) {
				if ( ! empty( $a['number'] ) ) {
					$has_number = true;
					break;
				}
			}
		}
		$tabs = array(
			'general'    => __( 'General', 'ctc-vinkora' ),
			'button'     => __( 'Botón', 'ctc-vinkora' ),
			'greeting'   => __( 'Saludo', 'ctc-vinkora' ),
			'agents'     => __( 'Agentes', 'ctc-vinkora' ),
			'visibility' => __( 'Visibilidad', 'ctc-vinkora' ),
			'tracking'   => __( 'Rastreo', 'ctc-vinkora' ),
			'advanced'   => __( 'Avanzado', 'ctc-vinkora' ),
		);
		$active = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! isset( $tabs[ $active ] ) ) {
			$active = 'general';
		}
		?>
		<div class="wrap">
			<div style="margin:16px 0 4px">
				<a href="https://vinkora.net" target="_blank" rel="noopener">
					<img src="<?php echo esc_url( CTCV_URL . 'assets/vinkora-logo.webp' ); ?>" alt="Vinkora" style="height:38px;width:auto;display:block">
				</a>
			</div>
			<h1 style="display:flex;align-items:center;gap:10px;margin-top:.2em">
				<img src="<?php echo esc_url( CTCV_URL . 'assets/vinkora-icon.png' ); ?>" alt="" width="28" height="28" style="border-radius:7px">
				<?php esc_html_e( 'CTC Vinkora', 'ctc-vinkora' ); ?>
			</h1>
			<?php if ( ! $has_number ) : ?>
				<div class="notice notice-warning inline"><p><strong><?php esc_html_e( 'Falta el número de WhatsApp.', 'ctc-vinkora' ); ?></strong> <?php esc_html_e( 'Mientras no haya número (o un agente), el botón no se mostrará.', 'ctc-vinkora' ); ?></p></div>
			<?php endif; ?>

			<nav class="nav-tab-wrapper wp-clearfix" style="margin-top:12px">
				<?php foreach ( $tabs as $slug => $label ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=ctc-vinkora&tab=' . $slug ) ); ?>" class="nav-tab <?php echo ( $active === $slug ) ? 'nav-tab-active' : ''; ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>

			<div style="margin-top:16px">
				<?php
				$method = 'tab_' . $active;
				$this->$method( $o );
				if ( 'general' === $active ) {
					$this->phone_picker_assets();
				}
				?>
			</div>

			<hr>
			<p style="color:#6b7280">
				<strong>CTC Vinkora</strong> — <?php esc_html_e( 'un producto de', 'ctc-vinkora' ); ?>
				<a href="https://vinkora.net" target="_blank" rel="noopener"><strong>Vinkora</strong> · vinkora.net</a>
			</p>
		</div>
		<?php
		$this->repeater_js();
	}

	/* ------------------------------------------------------------------ *
	 *  Pestaña: General
	 * ------------------------------------------------------------------ */
	private function tab_general( $o ) {
		$this->form_open( 'general' );
		$this->row( esc_html__( 'Activar botón', 'ctc-vinkora' ), $this->f_check( $o, 'enabled', __( 'Mostrar el botón de WhatsApp en el sitio', 'ctc-vinkora' ) ) );
		$this->row(
			esc_html__( 'Número de WhatsApp', 'ctc-vinkora' ),
			$this->phone_field( $o )
		);
		$this->row(
			'<label for="' . esc_attr( $this->id( 'greeting' ) ) . '">' . esc_html__( 'Mensaje inicial', 'ctc-vinkora' ) . '</label>',
			$this->f_textarea( $o, 'greeting', 2 ) . '<p class="description">' . esc_html__( 'Texto prellenado del chat. Variables: {site} {title} {url}. Debajo se añaden las UTMs.', 'ctc-vinkora' ) . '</p>'
		);
		$this->row( '<label for="' . esc_attr( $this->id( 'tooltip' ) ) . '">' . esc_html__( 'Texto flotante (tooltip)', 'ctc-vinkora' ) . '</label>', $this->f_text( $o, 'tooltip' ) );
		$this->row( '<label for="' . esc_attr( $this->id( 'cta_text' ) ) . '">' . esc_html__( 'Texto/CTA del botón', 'ctc-vinkora' ) . '</label>', $this->f_text( $o, 'cta_text' ) . '<p class="description">' . esc_html__( 'Se muestra en los estilos con etiqueta (botón, chip, texto, ícono+CTA).', 'ctc-vinkora' ) . '</p>' );
		$this->form_close();
		$this->shortcode_help();
	}

	/* ------------------------------------------------------------------ *
	 *  Pestaña: Botón
	 * ------------------------------------------------------------------ */
	private function tab_button( $o ) {
		$this->form_open( 'button' );
		$styles = array(
			'icon'         => __( 'Ícono redondo', 'ctc-vinkora' ),
			'square'       => __( 'Ícono cuadrado', 'ctc-vinkora' ),
			'icon-padding' => __( 'Ícono con padding', 'ctc-vinkora' ),
			'button'       => __( 'Botón con texto', 'ctc-vinkora' ),
			'chip'         => __( 'Chip (píldora)', 'ctc-vinkora' ),
			'text'         => __( 'Texto plano', 'ctc-vinkora' ),
			'image'        => __( 'Imagen propia', 'ctc-vinkora' ),
		);
		$this->row( esc_html__( 'Estilo', 'ctc-vinkora' ), $this->f_select( $o, 'style', $styles ) );
		$this->row(
			esc_html__( 'Etiqueta / CTA', 'ctc-vinkora' ),
			$this->f_radio( $o, 'cta_mode', array( 'hover' => __( 'Al pasar el ratón', 'ctc-vinkora' ), 'show' => __( 'Siempre visible', 'ctc-vinkora' ), 'hide' => __( 'Oculta', 'ctc-vinkora' ) ) )
		);
		$this->row( '<label for="' . esc_attr( $this->id( 'color' ) ) . '">' . esc_html__( 'Color', 'ctc-vinkora' ) . '</label>', $this->f_color( $o, 'color' ) );
		$this->row( esc_html__( 'Posición', 'ctc-vinkora' ), $this->f_radio( $o, 'position', array( 'right' => __( 'Abajo a la derecha', 'ctc-vinkora' ), 'left' => __( 'Abajo a la izquierda', 'ctc-vinkora' ) ) ) );
		$this->row( '<label for="' . esc_attr( $this->id( 'offset_bottom' ) ) . '">' . esc_html__( 'Separación desde abajo (px)', 'ctc-vinkora' ) . '</label>', $this->f_number( $o, 'offset_bottom', 0, 600 ) );
		$this->row( '<label for="' . esc_attr( $this->id( 'offset_side' ) ) . '">' . esc_html__( 'Separación desde el lado (px)', 'ctc-vinkora' ) . '</label>', $this->f_number( $o, 'offset_side', 0, 600 ) );
		$this->row(
			esc_html__( 'Tamaño (px)', 'ctc-vinkora' ),
			esc_html__( 'Escritorio', 'ctc-vinkora' ) . ' ' . $this->f_number( $o, 'size', 36, 120 ) . ' &nbsp; ' . esc_html__( 'Móvil', 'ctc-vinkora' ) . ' ' . $this->f_number( $o, 'size_mobile', 36, 120 )
		);
		$this->row( '<label for="' . esc_attr( $this->id( 'radius' ) ) . '">' . esc_html__( 'Redondez (%)', 'ctc-vinkora' ) . '</label>', $this->f_number( $o, 'radius', 0, 50 ) . ' <span class="description">' . esc_html__( '50 = círculo, 0 = cuadrado.', 'ctc-vinkora' ) . '</span>' );
		$this->row( esc_html__( 'Animación de pulso', 'ctc-vinkora' ), $this->f_check( $o, 'pulse', __( 'Halo pulsante', 'ctc-vinkora' ) ) );
		$this->row(
			'<label for="' . esc_attr( $this->id( 'own_image_url' ) ) . '">' . esc_html__( 'Imagen propia (URL)', 'ctc-vinkora' ) . '</label>',
			$this->f_text( $o, 'own_image_url', 'https://tusitio.com/wp-content/uploads/...', 'large-text' ) . '<p class="description">' . esc_html__( 'Solo para el estilo "Imagen propia". Pega la URL de una imagen de tu biblioteca de Medios.', 'ctc-vinkora' ) . '</p>'
		);
		$this->row( esc_html__( 'Dónde mostrar', 'ctc-vinkora' ), $this->f_check( $o, 'show_desktop', __( 'Escritorio', 'ctc-vinkora' ) ) . ' &nbsp; ' . $this->f_check( $o, 'show_mobile', __( 'Móvil', 'ctc-vinkora' ) ) );
		$this->form_close();
	}

	/* ------------------------------------------------------------------ *
	 *  Pestaña: Saludo / Formulario
	 * ------------------------------------------------------------------ */
	private function tab_greeting( $o ) {
		$this->form_open( 'greeting' );
		$this->row(
			esc_html__( 'Tipo de ventana', 'ctc-vinkora' ),
			$this->f_radio( $o, 'greeting_type', array(
				'none'     => __( 'Ninguna (botón directo)', 'ctc-vinkora' ),
				'greeting' => __( 'Ventana de saludo', 'ctc-vinkora' ),
				'form'     => __( 'Formulario', 'ctc-vinkora' ),
			) )
		);
		$this->row( '<label for="' . esc_attr( $this->id( 'greeting_title' ) ) . '">' . esc_html__( 'Título de la ventana', 'ctc-vinkora' ) . '</label>', $this->f_text( $o, 'greeting_title', '', 'large-text' ) );
		$this->row( '<label for="' . esc_attr( $this->id( 'greeting_body' ) ) . '">' . esc_html__( 'Texto de la ventana', 'ctc-vinkora' ) . '</label>', $this->f_textarea( $o, 'greeting_body', 2 ) );
		$this->row( '<label for="' . esc_attr( $this->id( 'greeting_cta' ) ) . '">' . esc_html__( 'Texto del botón de la ventana', 'ctc-vinkora' ) . '</label>', $this->f_text( $o, 'greeting_cta' ) );
		$this->row( esc_html__( 'Posición de la ventana', 'ctc-vinkora' ), $this->f_radio( $o, 'greeting_position', array( 'side' => __( 'Junto al botón', 'ctc-vinkora' ), 'modal' => __( 'Modal (centro)', 'ctc-vinkora' ) ) ) );
		$this->row( esc_html__( 'Tamaño', 'ctc-vinkora' ), $this->f_radio( $o, 'greeting_size', array( 'small' => __( 'Pequeña', 'ctc-vinkora' ), 'medium' => __( 'Mediana', 'ctc-vinkora' ), 'large' => __( 'Grande', 'ctc-vinkora' ) ) ) );
		$this->row(
			esc_html__( 'Apertura', 'ctc-vinkora' ),
			$this->f_radio( $o, 'greeting_auto', array( 'interaction' => __( 'Al hacer clic', 'ctc-vinkora' ), 'auto' => __( 'Automática', 'ctc-vinkora' ) ) ) . ' &nbsp; ' . esc_html__( 'Retraso (s):', 'ctc-vinkora' ) . ' ' . $this->f_number( $o, 'greeting_delay', 0, 300 )
		);
		$this->row( esc_html__( 'Color cabecera', 'ctc-vinkora' ), $this->f_color( $o, 'greeting_color_header' ) );
		$this->row( esc_html__( 'Color cuerpo', 'ctc-vinkora' ), $this->f_color( $o, 'greeting_color_body' ) );
		$this->row( esc_html__( 'Color burbuja mensaje', 'ctc-vinkora' ), $this->f_color( $o, 'greeting_color_msg' ) );

		echo '<tr><th colspan="2"><hr><h2 style="margin:.3em 0">' . esc_html__( 'Formulario (solo rellena el mensaje)', 'ctc-vinkora' ) . '</h2><p class="description">' . esc_html__( 'Los campos marcados se añaden al texto de WhatsApp. No se guardan ni envían a ningún servidor.', 'ctc-vinkora' ) . '</p></th></tr>';
		$this->row( '<label for="' . esc_attr( $this->id( 'form_cta' ) ) . '">' . esc_html__( 'Texto del botón de envío', 'ctc-vinkora' ) . '</label>', $this->f_text( $o, 'form_cta', '', 'large-text' ) );
		echo '<tr><td colspan="2">';
		$this->render_form_fields( $o );
		echo '</td></tr>';
		$this->form_close();
	}

	private function render_form_fields( $o ) {
		$types = array(
			'text'     => __( 'Texto', 'ctc-vinkora' ),
			'email'    => __( 'Correo', 'ctc-vinkora' ),
			'tel'      => __( 'Teléfono', 'ctc-vinkora' ),
			'textarea' => __( 'Área de texto', 'ctc-vinkora' ),
			'select'   => __( 'Desplegable', 'ctc-vinkora' ),
			'checkbox' => __( 'Casilla', 'ctc-vinkora' ),
			'date'     => __( 'Fecha', 'ctc-vinkora' ),
			'hidden'   => __( 'Oculto', 'ctc-vinkora' ),
		);
		echo '<div id="ctcv-form-fields">';
		$rows = is_array( $o['form_fields'] ) ? $o['form_fields'] : array();
		foreach ( $rows as $i => $row ) {
			echo $this->form_field_row( $i, $row, $types ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '</div>';
		echo '<p><button type="button" class="button" id="ctcv-add-field">+ ' . esc_html__( 'Añadir campo', 'ctc-vinkora' ) . '</button></p>';
		// Prototipo (índice __i__).
		echo '<script type="text/html" id="ctcv-field-proto">' . $this->form_field_row( '__i__', array(), $types ) . '</script>';
	}

	private function form_field_row( $i, $row, $types ) {
		$n    = CTCV_Options::OPTION . '[form_fields][' . $i . ']';
		$type = isset( $row['type'] ) ? $row['type'] : 'text';
		$sel  = '<select name="' . esc_attr( $n . '[type]' ) . '">';
		foreach ( $types as $v => $l ) {
			$sel .= '<option value="' . esc_attr( $v ) . '" ' . selected( $type, $v, false ) . '>' . esc_html( $l ) . '</option>';
		}
		$sel .= '</select>';
		$label = isset( $row['label'] ) ? $row['label'] : '';
		$ph    = isset( $row['placeholder'] ) ? $row['placeholder'] : '';
		$opts  = isset( $row['options'] ) ? $row['options'] : '';
		$req   = ! empty( $row['required'] );
		$add   = isset( $row['add'] ) ? ! empty( $row['add'] ) : true;

		$h  = '<div class="ctcv-repeat-row" style="border:1px solid #dcdcde;border-radius:6px;padding:10px;margin:0 0 8px;background:#fff">';
		$h .= '<div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">';
		$h .= '<span>' . esc_html__( 'Tipo:', 'ctc-vinkora' ) . '</span> ' . $sel;
		$h .= ' <input type="text" name="' . esc_attr( $n . '[label]' ) . '" value="' . esc_attr( $label ) . '" placeholder="' . esc_attr__( 'Etiqueta', 'ctc-vinkora' ) . '">';
		$h .= ' <input type="text" name="' . esc_attr( $n . '[placeholder]' ) . '" value="' . esc_attr( $ph ) . '" placeholder="' . esc_attr__( 'Placeholder', 'ctc-vinkora' ) . '">';
		$h .= ' <label><input type="checkbox" name="' . esc_attr( $n . '[required]' ) . '" value="1" ' . checked( true, $req, false ) . '> ' . esc_html__( 'Obligatorio', 'ctc-vinkora' ) . '</label>';
		$h .= ' <label><input type="checkbox" name="' . esc_attr( $n . '[add]' ) . '" value="1" ' . checked( true, $add, false ) . '> ' . esc_html__( 'Añadir al mensaje', 'ctc-vinkora' ) . '</label>';
		$h .= ' <button type="button" class="button-link ctcv-remove-row" style="color:#b32d2e">✕</button>';
		$h .= '</div>';
		$h .= '<div style="margin-top:6px"><input type="text" class="large-text" name="' . esc_attr( $n . '[options]' ) . '" value="' . esc_attr( $opts ) . '" placeholder="' . esc_attr__( 'Opciones del desplegable separadas por coma (solo tipo Desplegable)', 'ctc-vinkora' ) . '"></div>';
		$h .= '</div>';
		return $h;
	}

	/* ------------------------------------------------------------------ *
	 *  Pestaña: Agentes
	 * ------------------------------------------------------------------ */
	private function tab_agents( $o ) {
		$this->form_open( 'agents' );
		$this->row( esc_html__( 'Activar multi-agente', 'ctc-vinkora' ), $this->f_check( $o, 'agents_enabled', __( 'Mostrar lista de agentes al abrir el chat', 'ctc-vinkora' ) ) );
		$this->row( '<label for="' . esc_attr( $this->id( 'agents_title' ) ) . '">' . esc_html__( 'Título de la lista', 'ctc-vinkora' ) . '</label>', $this->f_text( $o, 'agents_title', '', 'large-text' ) );
		$this->row( '<label for="' . esc_attr( $this->id( 'agents_offline_text' ) ) . '">' . esc_html__( 'Texto fuera de horario', 'ctc-vinkora' ) . '</label>', $this->f_text( $o, 'agents_offline_text' ) );
		echo '<tr><td colspan="2">';
		echo '<div id="ctcv-agents">';
		$rows = is_array( $o['agents'] ) ? $o['agents'] : array();
		foreach ( $rows as $i => $row ) {
			echo $this->agent_row( $i, $row ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '</div>';
		echo '<p><button type="button" class="button" id="ctcv-add-agent">+ ' . esc_html__( 'Añadir agente', 'ctc-vinkora' ) . '</button></p>';
		echo '<script type="text/html" id="ctcv-agent-proto">' . $this->agent_row( '__i__', array() ) . '</script>';
		echo '</td></tr>';
		$this->form_close();
	}

	private function agent_row( $i, $row ) {
		$n     = CTCV_Options::OPTION . '[agents][' . $i . ']';
		$g     = function( $k ) use ( $row ) {
			return isset( $row[ $k ] ) ? $row[ $k ] : '';
		};
		$days  = isset( $row['days'] ) && is_array( $row['days'] ) ? $row['days'] : array();
		$dmap  = array( 'mon' => 'L', 'tue' => 'M', 'wed' => 'X', 'thu' => 'J', 'fri' => 'V', 'sat' => 'S', 'sun' => 'D' );
		$always = ! empty( $row['always'] ) || empty( $row );
		$offline = ( isset( $row['offline'] ) && 'hide' === $row['offline'] ) ? 'hide' : 'show';

		$h  = '<div class="ctcv-repeat-row" style="border:1px solid #dcdcde;border-radius:6px;padding:12px;margin:0 0 10px;background:#fff">';
		$h .= '<div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">';
		$h .= '<input type="text" name="' . esc_attr( $n . '[name]' ) . '" value="' . esc_attr( $g( 'name' ) ) . '" placeholder="' . esc_attr__( 'Nombre', 'ctc-vinkora' ) . '">';
		$h .= '<input type="text" name="' . esc_attr( $n . '[number]' ) . '" value="' . esc_attr( $g( 'number' ) ) . '" placeholder="' . esc_attr__( 'Número (573001234567)', 'ctc-vinkora' ) . '">';
		$h .= '<input type="text" name="' . esc_attr( $n . '[role]' ) . '" value="' . esc_attr( $g( 'role' ) ) . '" placeholder="' . esc_attr__( 'Rol (Ventas)', 'ctc-vinkora' ) . '">';
		$h .= '<button type="button" class="button-link ctcv-remove-row" style="color:#b32d2e;margin-left:auto">✕</button>';
		$h .= '</div>';
		$h .= '<div style="margin-top:6px"><input type="text" class="large-text" name="' . esc_attr( $n . '[avatar]' ) . '" value="' . esc_attr( $g( 'avatar' ) ) . '" placeholder="' . esc_attr__( 'URL del avatar (opcional)', 'ctc-vinkora' ) . '"></div>';
		$h .= '<div style="margin-top:6px"><input type="text" class="large-text" name="' . esc_attr( $n . '[greeting]' ) . '" value="' . esc_attr( $g( 'greeting' ) ) . '" placeholder="' . esc_attr__( 'Saludo propio del agente (opcional)', 'ctc-vinkora' ) . '"></div>';
		$h .= '<div style="margin-top:8px;display:flex;flex-wrap:wrap;gap:10px;align-items:center">';
		$h .= '<label><input type="checkbox" name="' . esc_attr( $n . '[always]' ) . '" value="1" ' . checked( true, $always, false ) . '> ' . esc_html__( 'Siempre disponible', 'ctc-vinkora' ) . '</label>';
		$h .= '<span>' . esc_html__( 'Días:', 'ctc-vinkora' ) . '</span>';
		foreach ( $dmap as $dk => $dl ) {
			$dchecked = in_array( $dk, $days, true );
			$h       .= '<label style="margin-right:4px"><input type="checkbox" name="' . esc_attr( $n . '[days][' . $dk . ']' ) . '" value="1" ' . checked( true, $dchecked, false ) . '> ' . esc_html( $dl ) . '</label>';
		}
		$h .= '<span>' . esc_html__( 'De', 'ctc-vinkora' ) . '</span> <input type="time" name="' . esc_attr( $n . '[from]' ) . '" value="' . esc_attr( $g( 'from' ) ) . '">';
		$h .= '<span>' . esc_html__( 'a', 'ctc-vinkora' ) . '</span> <input type="time" name="' . esc_attr( $n . '[to]' ) . '" value="' . esc_attr( $g( 'to' ) ) . '">';
		$h .= '<label>' . esc_html__( 'Fuera de horario:', 'ctc-vinkora' ) . ' <select name="' . esc_attr( $n . '[offline]' ) . '"><option value="show" ' . selected( 'show', $offline, false ) . '>' . esc_html__( 'Mostrar', 'ctc-vinkora' ) . '</option><option value="hide" ' . selected( 'hide', $offline, false ) . '>' . esc_html__( 'Ocultar', 'ctc-vinkora' ) . '</option></select></label>';
		$h .= '</div>';
		$h .= '</div>';
		return $h;
	}

	/* ------------------------------------------------------------------ *
	 *  Pestaña: Visibilidad
	 * ------------------------------------------------------------------ */
	private function tab_visibility( $o ) {
		$this->form_open( 'visibility' );
		echo '<tr><td colspan="2"><p class="description">' . esc_html__( 'Marca dónde SÍ debe aparecer el botón.', 'ctc-vinkora' ) . '</p></td></tr>';
		$this->row( esc_html__( 'Portada', 'ctc-vinkora' ), $this->f_check( $o, 'vis_front', __( 'Página de inicio', 'ctc-vinkora' ) ) );
		$this->row( esc_html__( 'Índice del blog', 'ctc-vinkora' ), $this->f_check( $o, 'vis_home', __( 'Listado de entradas', 'ctc-vinkora' ) ) );
		$this->row( esc_html__( 'Páginas', 'ctc-vinkora' ), $this->f_check( $o, 'vis_pages', __( 'Páginas', 'ctc-vinkora' ) ) );
		$this->row( esc_html__( 'Entradas', 'ctc-vinkora' ), $this->f_check( $o, 'vis_posts', __( 'Entradas del blog', 'ctc-vinkora' ) ) );
		$this->row( esc_html__( 'Archivos', 'ctc-vinkora' ), $this->f_check( $o, 'vis_archives', __( 'Páginas de archivo', 'ctc-vinkora' ) ) );
		$this->row( esc_html__( 'Categorías', 'ctc-vinkora' ), $this->f_check( $o, 'vis_categories', __( 'Páginas de categoría', 'ctc-vinkora' ) ) );
		$this->row( esc_html__( 'Búsqueda', 'ctc-vinkora' ), $this->f_check( $o, 'vis_search', __( 'Resultados de búsqueda', 'ctc-vinkora' ) ) );
		$this->row( esc_html__( 'Error 404', 'ctc-vinkora' ), $this->f_check( $o, 'vis_404', __( 'Página 404', 'ctc-vinkora' ) ) );

		$cpts = get_post_types( array( 'public' => true, '_builtin' => false ), 'objects' );
		if ( ! empty( $cpts ) ) {
			echo '<tr><th colspan="2"><hr><h2 style="margin:.3em 0">' . esc_html__( 'Tipos de contenido personalizados', 'ctc-vinkora' ) . '</h2></th></tr>';
			foreach ( $cpts as $slug => $obj ) {
				$slug    = sanitize_key( $slug );
				$checked = isset( $o['vis_cpt'][ $slug ] ) ? (int) $o['vis_cpt'][ $slug ] : 1;
				$cn      = esc_attr( CTCV_Options::OPTION . '[vis_cpt][' . $slug . ']' );
				$td      = '<input type="hidden" name="' . $cn . '" value="0"><label><input type="checkbox" name="' . $cn . '" value="1" ' . checked( 1, $checked, false ) . '> ' . esc_html( $slug ) . '</label>';
				$this->row( esc_html( isset( $obj->labels->name ) ? $obj->labels->name : $slug ), $td );
			}
		}

		echo '<tr><th colspan="2"><hr><h2 style="margin:.3em 0">' . esc_html__( 'Incluir / excluir específicos', 'ctc-vinkora' ) . '</h2></th></tr>';
		$this->row( '<label for="' . esc_attr( $this->id( 'exclude_ids' ) ) . '">' . esc_html__( 'Ocultar en estos IDs', 'ctc-vinkora' ) . '</label>', $this->f_text( $o, 'exclude_ids', '12, 45', 'large-text' ) . '<p class="description">' . esc_html__( 'IDs de páginas/entradas separados por coma. Gana sobre lo de arriba.', 'ctc-vinkora' ) . '</p>' );
		$this->row( '<label for="' . esc_attr( $this->id( 'include_ids' ) ) . '">' . esc_html__( 'Mostrar solo en estos IDs', 'ctc-vinkora' ) . '</label>', $this->f_text( $o, 'include_ids', '', 'large-text' ) . '<p class="description">' . esc_html__( 'Si lo rellenas, el botón SOLO aparece en estos IDs (ignora el resto).', 'ctc-vinkora' ) . '</p>' );
		$this->row( '<label for="' . esc_attr( $this->id( 'exclude_cats' ) ) . '">' . esc_html__( 'Ocultar en estas categorías', 'ctc-vinkora' ) . '</label>', $this->f_text( $o, 'exclude_cats', 'noticias, ofertas', 'large-text' ) . '<p class="description">' . esc_html__( 'Slugs o IDs de categoría separados por coma.', 'ctc-vinkora' ) . '</p>' );
		$this->form_close();
	}

	/* ------------------------------------------------------------------ *
	 *  Pestaña: Rastreo
	 * ------------------------------------------------------------------ */
	private function tab_tracking( $o ) {
		$this->form_open( 'tracking' );
		echo '<tr><th colspan="2"><h2 style="margin:.3em 0">' . esc_html__( 'UTMs en el mensaje', 'ctc-vinkora' ) . '</h2></th></tr>';
		$this->row( esc_html__( 'Ventana de atribución', 'ctc-vinkora' ), $this->f_radio( $o, 'attribution', array( 'first' => __( 'Primer clic (first-touch)', 'ctc-vinkora' ), 'last' => __( 'Último clic (last-touch)', 'ctc-vinkora' ) ) ) );
		$this->row( '<label for="' . esc_attr( $this->id( 'ttl_days' ) ) . '">' . esc_html__( 'Duración de la atribución (días)', 'ctc-vinkora' ) . '</label>', $this->f_number( $o, 'ttl_days', 1, 3650 ) );
		$this->row(
			esc_html__( 'UTMs a incluir', 'ctc-vinkora' ),
			$this->params_matrix( $o ) . '<p class="description">' . esc_html__( 'Marca las que quieres que viajen en el mensaje. Por defecto: las 6 UTMs estándar.', 'ctc-vinkora' ) . '</p>'
		);
		$this->row(
			'<label for="' . esc_attr( $this->id( 'extra_params' ) ) . '">' . esc_html__( 'Otros parámetros (personalizados)', 'ctc-vinkora' ) . '</label>',
			$this->f_text( $o, 'extra_params', '', 'large-text' ) . '<p class="description">' . esc_html__( 'Opcional: otros parámetros de tu URL, separados por coma, aparte de los de arriba.', 'ctc-vinkora' ) . '</p>'
		);
		$this->row( '<label for="' . esc_attr( $this->id( 'track_header' ) ) . '">' . esc_html__( 'Encabezado del bloque', 'ctc-vinkora' ) . '</label>', $this->f_text( $o, 'track_header', '', 'large-text' ) );
		$this->row( esc_html__( 'Formato del bloque', 'ctc-vinkora' ), $this->f_radio( $o, 'compact', array( '0' => __( 'Legible (una línea por dato)', 'ctc-vinkora' ), '1' => __( 'Compacto', 'ctc-vinkora' ) ) ) );
		$this->row( esc_html__( 'Datos extra', 'ctc-vinkora' ), $this->f_check( $o, 'include_page', __( 'Página de destino', 'ctc-vinkora' ) ) . ' &nbsp; ' . $this->f_check( $o, 'include_ref', __( 'Referrer', 'ctc-vinkora' ) ) );
		$this->row( esc_html__( 'Solo si hay UTMs', 'ctc-vinkora' ), $this->f_check( $o, 'only_if_utm', __( 'El tráfico directo ve solo el saludo', 'ctc-vinkora' ) ) );

		echo '<tr><th colspan="2"><hr><h2 style="margin:.3em 0">' . esc_html__( 'Google Analytics 4', 'ctc-vinkora' ) . '</h2></th></tr>';
		$this->row( esc_html__( 'Activar evento GA4', 'ctc-vinkora' ), $this->f_check( $o, 'ga4_enabled', __( 'Disparar un evento al hacer clic (detecta gtag o dataLayer)', 'ctc-vinkora' ) ) );
		$this->row( '<label for="' . esc_attr( $this->id( 'ga4_event' ) ) . '">' . esc_html__( 'Nombre del evento', 'ctc-vinkora' ) . '</label>', $this->f_text( $o, 'ga4_event' ) . '<p class="description">' . esc_html__( 'Recomendado GA4: generate_lead. Por defecto: whatsapp_click.', 'ctc-vinkora' ) . '</p>' );

		echo '<tr><th colspan="2"><hr><h2 style="margin:.3em 0">' . esc_html__( 'Google Tag Manager', 'ctc-vinkora' ) . '</h2></th></tr>';
		$this->row( esc_html__( 'Empujar al dataLayer', 'ctc-vinkora' ), $this->f_check( $o, 'gtm_push', __( 'Push a dataLayer al clic (para triggers en tu GTM)', 'ctc-vinkora' ) ) );
		$this->row( '<label for="' . esc_attr( $this->id( 'gtm_event' ) ) . '">' . esc_html__( 'Nombre del evento (dataLayer)', 'ctc-vinkora' ) . '</label>', $this->f_text( $o, 'gtm_event' ) );
		$this->row( esc_html__( 'Inyectar contenedor GTM', 'ctc-vinkora' ), $this->f_check( $o, 'gtm_inject', __( 'Insertar el contenedor GTM en el sitio', 'ctc-vinkora' ) ) . '<p class="description" style="color:#b32d2e">' . esc_html__( '⚠ Actívalo SOLO si el sitio no tiene ya Google Tag Manager (se duplicaría).', 'ctc-vinkora' ) . '</p>' );
		$this->row( '<label for="' . esc_attr( $this->id( 'gtm_id' ) ) . '">' . esc_html__( 'ID del contenedor', 'ctc-vinkora' ) . '</label>', $this->f_text( $o, 'gtm_id', 'GTM-XXXXXXX' ) );

		echo '<tr><th colspan="2"><hr><h2 style="margin:.3em 0">' . esc_html__( 'Meta Pixel', 'ctc-vinkora' ) . '</h2></th></tr>';
		$this->row( esc_html__( 'Activar evento del Pixel', 'ctc-vinkora' ), $this->f_check( $o, 'pixel_enabled', __( 'Disparar fbq(track) al clic (si el Pixel está en la página)', 'ctc-vinkora' ) ) );
		$this->row( '<label for="' . esc_attr( $this->id( 'pixel_event' ) ) . '">' . esc_html__( 'Evento', 'ctc-vinkora' ) . '</label>', $this->f_text( $o, 'pixel_event' ) . '<p class="description">' . esc_html__( 'Estándar: Contact, Lead. También vale uno personalizado.', 'ctc-vinkora' ) . '</p>' );
		$this->form_close();
	}

	/* ------------------------------------------------------------------ *
	 *  Pestaña: Avanzado
	 * ------------------------------------------------------------------ */
	private function tab_advanced( $o ) {
		$this->form_open( 'advanced' );
		$this->row( '<label for="' . esc_attr( $this->id( 'delay_ms' ) ) . '">' . esc_html__( 'Retraso antes de mostrar (ms)', 'ctc-vinkora' ) . '</label>', $this->f_number( $o, 'delay_ms', 0, 60000, 100 ) );
		$this->row( '<label for="' . esc_attr( $this->id( 'zindex' ) ) . '">' . esc_html__( 'z-index', 'ctc-vinkora' ) . '</label>', $this->f_number( $o, 'zindex', 1, 2147483000 ) );
		$this->row( esc_html__( 'Badge de notificación', 'ctc-vinkora' ), $this->f_check( $o, 'badge_enabled', __( 'Mostrar un globo con número sobre el botón', 'ctc-vinkora' ) ) . ' &nbsp; ' . $this->f_text( $o, 'badge_text', '1', 'small-text' ) );
		$this->row( '<label for="' . esc_attr( $this->id( 'custom_css' ) ) . '">' . esc_html__( 'CSS personalizado', 'ctc-vinkora' ) . '</label>', $this->f_textarea( $o, 'custom_css', 5 ) . '<p class="description">' . esc_html__( 'Se añade al final del CSS del botón. Sin etiquetas <style>.', 'ctc-vinkora' ) . '</p>' );
		$this->form_close();
	}

	/* ------------------------------------------------------------------ *
	 *  Ayudas
	 * ------------------------------------------------------------------ */
	private function shortcode_help() {
		echo '<hr><h2>' . esc_html__( 'Insertar en cualquier parte', 'ctc-vinkora' ) . '</h2>';
		echo '<p><code>[whatsapp_button text="Escríbenos"]</code> ' . esc_html__( 'o añade la clase CSS', 'ctc-vinkora' ) . ' <code>ctcv-whatsapp</code> ' . esc_html__( 'a cualquier botón/enlace.', 'ctc-vinkora' ) . '</p>';
	}

	/** JS mínimo para los repetidores (añadir/quitar filas). Solo en esta página de admin. */
	private function repeater_js() {
		?>
		<script>
		(function(){
			function nextIndex(container){
				var max = -1;
				container.querySelectorAll('[name]').forEach(function(el){
					var m = el.name.match(/\[(\d+)\]/);
					if (m) { max = Math.max(max, parseInt(m[1],10)); }
				});
				return max + 1;
			}
			function addRow(btnId, containerId, protoId){
				var btn = document.getElementById(btnId);
				var container = document.getElementById(containerId);
				var proto = document.getElementById(protoId);
				if (!btn || !container || !proto) { return; }
				btn.addEventListener('click', function(){
					var i = nextIndex(container);
					var html = proto.innerHTML.replace(/__i__/g, i);
					var tmp = document.createElement('div');
					tmp.innerHTML = html;
					var node = tmp.firstElementChild;
					container.appendChild(node);
				});
			}
			document.addEventListener('click', function(e){
				var b = e.target.closest ? e.target.closest('.ctcv-remove-row') : null;
				if (b){ e.preventDefault(); var r = b.closest('.ctcv-repeat-row'); if (r) { r.parentNode.removeChild(r); } }
			});
			addRow('ctcv-add-field','ctcv-form-fields','ctcv-field-proto');
			addRow('ctcv-add-agent','ctcv-agents','ctcv-agent-proto');
		})();
		</script>
		<?php
	}
}

endif;
