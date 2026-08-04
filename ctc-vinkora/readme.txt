=== CTC Vinkora ===
Contributors: vinkora
Tags: whatsapp, click to chat, utm, ctc, crm, gohighlevel, tracking, ga4, gtm, meta pixel
Requires at least: 5.2
Tested up to: 6.8
Requires PHP: 7.2
Stable tag: 2.0.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Click To Chat de Vinkora: botón de WhatsApp con estilos, ventana de saludo, formulario, multi-agente, visibilidad por página y rastreo (UTMs + GA4 + GTM + Meta Pixel). https://vinkora.net

== Description ==

CTC Vinkora funciona en CUALQUIER sitio WordPress. Panel con pestañas:

* **General** — número, mensaje (variables {site} {title} {url}), tooltip y CTA.
* **Botón** — 7 estilos (ícono redondo, cuadrado, ícono con padding, botón con texto, chip, texto
  plano, imagen propia), color, tamaño escritorio/móvil, posición, offsets, redondez, pulso,
  badge y etiqueta/CTA (mostrar / ocultar / al pasar el ratón).
* **Saludo** — ventana de saludo o formulario. El formulario SOLO rellena el mensaje de WhatsApp
  (sin backend): nombre, correo, teléfono, desplegable, fecha, etc.
* **Agentes** — multi-agente: varios asesores con número, rol, avatar, saludo y horario
  (online/offline).
* **Visibilidad** — dónde mostrar por tipo de página (portada, blog, páginas, entradas, archivos,
  categorías, búsqueda, 404, CPTs) + incluir/excluir por ID y categoría.
* **Rastreo** — arrastra todas las UTMs/click-ids al mensaje (utm_source…, gclid, fbclid…) para
  atribución en tu CRM, y dispara eventos al clic en **Google Analytics 4**, **Google Tag Manager**
  (dataLayer, con opción de inyectar el contenedor) y **Meta Pixel**.
* **Avanzado** — retraso, z-index, badge, CSS personalizado.

Un producto de Vinkora — https://vinkora.net

**Por qué "no se rompe":**

* Solo APIs de núcleo estables (Options API, add_menu_page, add_shortcode, wp_footer, wp_head,
  wp_body_open). Sin REST, sin admin-ajax, sin base de datos propia, sin jQuery ni librerías.
* El botón es un enlace `wa.me` real renderizado desde el servidor: funciona aunque un optimizador
  elimine el JavaScript.
* PHP 7.2 a 8.5+, UTF-8 sin BOM.

== Installation ==

1. Plugins → Añadir nuevo → Subir plugin → elige el .zip → Instalar → Activar.
2. Menú lateral **CTC Vinkora** → pestaña **General** → escribe el número de WhatsApp.
3. Ajusta el resto de pestañas a tu gusto. Guarda cada pestaña por separado.

== Uso avanzado ==

* Shortcode: [whatsapp_button text="Escríbenos por WhatsApp"]
* Cualquier enlace/botón existente: añádele la clase CSS `ctcv-whatsapp`.

== Changelog ==

= 2.0.0 =
* Reescritura con panel de pestañas: estilos de botón, ventana de saludo, formulario (rellena el
  mensaje), multi-agente, reglas de visibilidad por página y pestaña de rastreo (GA4 + GTM +
  Meta Pixel) además de las UTMs. Compatible con la configuración de la v1.

= 1.0.0 =
* Botón flotante de WhatsApp con captura y persistencia de UTMs/click-ids.
