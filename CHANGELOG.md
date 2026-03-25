# Changelog — BPID Suite

Todas las modificaciones significativas de este proyecto se documentan aquí.
Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/).

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
