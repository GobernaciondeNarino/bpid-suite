# Changelog — BPID Suite

Todas las modificaciones significativas de este proyecto se documentan aquí.
Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/).

---

## [3.2.2] - 2026-05-12

### Correcciones del modal
- **Acordeones no abrían**: convivían dos modelos de visibilidad — `.bpid-modal-accordion-content.active { display:block }` que el JS togglea y `.bpid-modal-accordion-item.is-open .bpid-modal-accordion-body { display:block }` que la CSS espera. Al hacer click el contenedor exterior se mostraba pero el `body` interno seguía `display:none`, por lo que no aparecía contenido. Se unifica en un único modelo: el header ahora es `<button>` y se togglea `.is-open` en el item. Se eliminan las reglas CSS obsoletas y se reorganizan los acordeones con bordes, sombreado de header y rotación animada del icono.
- **Sin handlers inline**: se elimina el `onclick="…"` inline (que algunos CSP estrictos podían bloquear). Toda la apertura de modales y acordeones pasa por un único listener delegado en `document`.
- **Barras de progreso rotas**: el JS escribía `.bpid-modal-progress-fill` pero la CSS estilaba `.bpid-modal-progress-bar-fill`. Se corrigen los nombres, se separa el label del relleno y se añaden buckets de color (rojo/ámbar/verde) según porcentaje.

---

## [3.2.1] - 2026-05-12

### Correcciones críticas
- **Modal del Post Card no abría**: el contenedor traía `style="display:none;"` inline, que vence cualquier regla CSS — al hacer click en "Ver detalles" el JS ponía `body.overflow=hidden` pero el modal seguía oculto, por lo que la página se "bloqueaba" sin mostrar el popup. Se elimina el estilo inline, se gestiona la visibilidad con la clase `.show` y `aria-hidden`, y se restaura el scroll del `body` al cerrar.
- **Click en la imagen no abría el modal**: ahora la imagen del card es un `<button class="bpid-grid-card-image bpid-grid-card-open">` y la apertura del modal usa delegación de eventos (`document.addEventListener('click', …)`) tanto para la imagen como para el botón "Ver detalles". Cualquier excepción se captura y cierra el modal en lugar de dejar la página inservible.

---

## [3.2.0] - 2026-05-12

### Cambios principales
- **Renombrado del módulo**: "Visualizadores" pasa a llamarse **Post Card** en todo el panel administrativo (etiquetas del CPT, metabox de configuración y acción rápida del dashboard).
- **Nuevo shortcode**: `[bpid_post_card id="…"]` (el shortcode legado `[bpid_grid_visualizador]` se mantiene como alias para no romper páginas existentes).

### Correcciones
- **Modal de proyectos**: Las clases del template y del JS se desincronizaron de la hoja de estilos (`.bpid-grid-modal-content` vs `.bpid-modal-content`), por lo que el modal abría sin estilos y el botón de cerrar no respondía. Se alinearon las clases con la CSS existente y se ajustó el listener de cierre.
- **Filtros**: Se reorganiza el grupo de filtros en columnas etiquetadas (Municipio, Dependencia, ODS) con tipografía consistente con el panel de Gobernación.

### Mejoras
- **Buscador**: Estilo destacado con icono de lupa, borde primario, sombra y placeholder más legible.
- **Exportar Informes de Gestión**: Se elimina el título de la sección y se reemplazan los botones por una barra minimalista con tres acciones de solo icono — **Datos** (Excel), **Imagen** (PNG vía html2canvas bajo demanda) y **Descarga** (Word). Permite exportar sin filtrar por dependencia (genera reporte general).
- **Modal**: Incluye BPIN, título del proyecto, dependencia, valor, número de contratos, número de municipios, beneficiarios, avance físico y financiero. Acordeones para Metas, ODS, Municipios y Contratos con conteos.

---

## [1.1.0] - 2026-03-25

### Correcciones críticas
- **Fix 500 en importación**: Corregido `upsert_contrato()` que retornaba `bool` pero el importer esperaba strings `'inserted'`/`'updated'`/`'error'`. Ahora retorna string según el resultado de `ON DUPLICATE KEY UPDATE`.
- **Fix SSL**: Cambiado `sslverify` a `false` en `class-importer.php` y `class-post.php` — el servidor BPID tiene certificado SSL que WordPress no puede verificar.
- **Fix Content-Type**: Removido header `Content-Type: application/json` de las llamadas GET a la API (causaba rechazos en algunos servidores).

### Mejoras
- **Soporte dual de respuesta API**: El importer ahora maneja tanto la respuesta con clave `contratos` (lista plana) como con clave `proyectos` (agrupada). Los contratos se extraen automáticamente de la estructura agrupada.
- **Exportación Word/Excel**: Nuevo sistema de exportación de informes de gestión por dependencia con tablas de Cumplimiento de Metas y Ejecución Presupuestal.
- **Test de conexión mejorado**: Ahora muestra conteo de contratos y proyectos detectados.
- **Versión actualizada**: Bump a v1.1.0.

---

## [1.0.0] - 2026-03-25

### Nuevas funcionalidades

- **Módulo de Configuración**: Gestión segura de API key BPID con almacenamiento cifrado en `wp_options`, prueba de conexión AJAX, información del sistema, control de cron (diario/semanal/mensual/desactivado), regeneración de tabla con confirmación doble.
- **Módulo de Importación**: Importación completa desde API BPID con procesamiento por lotes de 100 registros, barra de progreso en tiempo real vía AJAX, botón de cancelación, log detallado con insertados/actualizados/errores, cron programable.
- **Módulo de Filtros**: CPT `bpid_filter` con 5 tipos de campo (texto, lista, rango numérico, rango de fechas, checkbox múltiple), búsqueda AJAX con rate limiting (60 req/min por IP), validación de columnas contra whitelist.
- **Módulo de Gráficos**: CPT `bpid_chart` con 15 tipos de gráficos D3plus v2 — bar, line, area, pie, donut, treemap, stacked_bar, grouped_bar, tree, pack, network, scatter, box_whisker, matrix, bump. Shortcode `[bpid_chart]` con atributos configurables.
- **Módulo Post (Visualizador de Proyectos)**: CPT `bpid_post` con shortcode `[bpid_grid_visualizador]`, consulta API en tiempo real con caché configurable (1-24 horas), grid responsive de tarjetas, modal de detalle con acordeones, filtros dinámicos (dependencia, municipio, ODS), estadísticas globales, colores personalizables.
- **API REST**: Namespace `bpid-suite/v1` con endpoints públicos (contracts, stats, chart data, CSV export, projects) y autenticados (import, cancel, cache clear). Rate limiting en endpoints públicos.
- **Comandos WP-CLI**: `wp bpid import`, `wp bpid stats`, `wp bpid truncate`, `wp bpid logs`, `wp bpid test-connection`, `wp bpid clear-cache`.
- **Auto-actualización**: Actualizaciones automáticas desde GitHub releases.
- **Sistema de logs**: Logging con rotación automática (5 MB máximo), visor de logs en panel admin.

### Seguridad

- `$wpdb->prepare()` en todas las consultas SQL sin excepción.
- Nonces en todos los formularios y handlers AJAX.
- `current_user_can('manage_options')` en todas las acciones administrativas.
- API key almacenada en `wp_options`, jamás expuesta en JavaScript o HTML.
- `esc_html()`, `esc_attr()`, `esc_url()` en toda salida HTML.
- Rate limiting por IP (60 req/min) en endpoints REST públicos.
- `JSON_HEX_TAG | JSON_HEX_AMP` en datos JSON al frontend.
- `index.php` de seguridad en todos los subdirectorios.
- `.htaccess` restrictivo en directorio de logs.
- Validación de columnas contra whitelist estricta.
- Validación de operadores SQL contra whitelist.

### Diferencias respecto a secop-suite

- Reemplazado origen de datos SECOP (datos.gov.co, protocolo SODA) por API BPID (bpid.narino.gov.co, llamada GET única).
- Añadida autenticación por header `apikey`.
- Nueva estructura de tabla `bpid_suite_contratos` con campos BPID (odss, municipios, imagenes como JSON).
- Clave compuesta única `numero + numero_proyecto` para UPSERT.
- Ampliados gráficos de 11 a 15 tipos D3plus (scatter, box_whisker, matrix, bump).
- Módulo de Configuración completamente nuevo.
- Módulo Post (Visualizador de Proyectos) completamente nuevo.
- Procesamiento por lotes interno (sin paginación API).
- Renombrado global de prefijos: secop → bpid, SECOP → BPID.

### Compatibilidad

- WordPress 6.0+
- PHP 8.1+ con `declare(strict_types=1)`
- MySQL 5.7+ / MariaDB 10.3+
- Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
- Compatible con Gutenberg, Elementor, Divi
- Compatible con WooCommerce, Yoast SEO, WP Rocket, WPML/Polylang
