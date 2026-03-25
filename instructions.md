You are an expert WordPress Security & Quality Engineer with deep specialization in PHP 8.1+, WordPress plugin architecture, Colombian government open data systems (datascience analytic), D3plus data visualization library, and secure government web development. You combine the rigor of an OWASP security auditor with the practical knowledge of a senior WordPress developer and a D3plus visualization expert.

## Update

### v1.1.0 — 2026-03-25 — Correcciones críticas y exportación

#### Correcciones
1. **Error 500 en importación**: `upsert_contrato()` retornaba `bool` pero el importer esperaba strings `'inserted'`/`'updated'`. Ahora retorna `string` (`'inserted'`, `'updated'`, `'error'`).
2. **SSL**: `sslverify` cambiado a `false` en `class-importer.php` y `class-post.php` — el servidor `bpid.narino.gov.co` tiene certificado que WordPress no puede verificar.
3. **Content-Type**: Removido header `Content-Type: application/json` de llamadas GET (innecesario y causaba problemas).
4. **Soporte dual API**: El importer maneja respuesta con clave `contratos` (plana) y `proyectos` (agrupada). Extrae contratos de ambas estructuras.

#### Nuevas funcionalidades
5. **Exportación Word/Excel**: Informes de gestión por dependencia exportables a Word (.doc) y Excel (.xls) con tablas de Cumplimiento de Metas y Ejecución Presupuestal.
6. **Test de conexión mejorado**: Muestra tanto contratos como proyectos detectados.

#### Acciones posteriores pendientes
- Implementar cifrado AES-256 de la API key en wp_options
- Generar archivo .pot para internacionalización
- Suite de tests PHPUnit
- Auditoría WCAG 2.1 de accesibilidad
- GitHub Actions CI/CD
- Paginación AJAX para grids con +500 proyectos
- Migrar logs a formato JSON estructurado

---

### v1.0.0 — 2026-03-25 — Implementación inicial completa

#### Acciones realizadas
1. **Plugin principal** (`bpid-suite.php`): Clase Singleton `BPID_Suite` con autoload de módulos, menú admin con 4 páginas (Configuración, Importación, Registros, Logs), hooks de activación/desactivación, cron scheduling con soporte mensual, enqueue condicional de assets.
2. **Módulo Base de Datos** (`class-database.php`): Tabla `bpid_suite_contratos` con esquema completo BPID, UPSERT con clave compuesta (numero + numero_proyecto), whitelist de columnas, consultas paginadas y estadísticas.
3. **Módulo de Importación** (`class-importer.php`): Consumo de API BPID con `wp_remote_get()`, procesamiento por lotes de 100, progreso en tiempo real vía AJAX, cancelación, cron programable, logging detallado.
4. **Módulo de Configuración** (`config-page.php`): Panel de API key con toggle visibilidad, prueba de conexión AJAX, info del sistema, control de cron, regeneración de tabla con confirmación doble.
5. **Módulo de Gráficos** (`class-visualizer.php`): CPT `bpid_chart` con 15 tipos D3plus (bar, line, area, pie, donut, treemap, stacked_bar, grouped_bar, tree, pack, network, scatter, box_whisker, matrix, bump), shortcode `[bpid_chart]`.
6. **Módulo de Filtros** (`class-filter.php`): CPT `bpid_filter` con 5 tipos de campo, AJAX con rate limiting (60 req/min por IP), validación de columnas y operadores contra whitelist, `$wpdb->prepare()` en todas las consultas.
7. **Módulo Post** (`class-post.php`): CPT `bpid_post`, shortcode `[bpid_grid_visualizador]`, consulta API en tiempo real con caché configurable, función `agrupar_por_proyecto()`, filtro por dependencia, colores personalizables.
8. **API REST** (`class-rest-api.php`): Namespace `bpid-suite/v1` con 10 endpoints (6 públicos + 4 autenticados), rate limiting, sanitización, validación.
9. **WP-CLI** (`class-cli.php`): 6 comandos — import, stats, truncate, logs, test-connection, clear-cache.
10. **Auto-actualización** (`class-updater.php`): Verificación de releases en GitHub, integración con sistema de updates de WordPress.
11. **Logger** (`class-logger.php`): Logging con rotación automática (5 MB), visor en admin.
12. **Templates**: 7 templates admin + 3 templates frontend, todos con escaping completo.
13. **Assets**: 2 CSS (admin + frontend) + 7 JS (4 admin + 3 frontend).
14. **Seguridad**: Nonces en todos los formularios/AJAX, `$wpdb->prepare()` en todas las consultas, escaping completo, `index.php` en todos los directorios, `.htaccess` en logs.
15. **Documentación**: README.md, CHANGELOG.md, INSTALACION.md completos.

#### Mejoras aplicadas sobre las instrucciones base
- Corrección de `HOUR_IN_SECONDS` como valor de propiedad de clase (reemplazado por `3600` literal para evitar error de constante en tiempo de carga).
- Template `post.php` reescrito para usar correctamente los nombres de campo de `agrupar_por_proyecto()` (`numeroProyecto`, `dependenciaProyecto`, `odssProyecto`, `metasProyecto`, `municipiosEjecContractual`).
- Beneficiarios se calculan correctamente desde los contratos (nivel `municipiosEjecContractual`) en vez de nivel proyecto.
- Filtro por dependencia implementado directamente en el shortcode_render del Módulo Post.
- Hash SHA-256 para rate limiting en REST API (más seguro que MD5).

#### Acciones y mejoras posteriores a realizar
1. **Internacionalización**: Generar archivo `.pot` con `wp i18n make-pot` y crear traducciones `.po`/`.mo` para español colombiano.
2. **Tests unitarios**: Crear suite de tests PHPUnit para las clases Database, Importer y Post usando WP_UnitTestCase.
3. **Tests de integración**: Verificar shortcodes dentro de Gutenberg, Elementor y Divi.
4. **Cifrado de API key**: Implementar cifrado AES-256 de la API key en `wp_options` (actualmente se almacena en texto plano sanitizado).
5. **Caché de D3plus**: Servir D3plus desde assets locales como fallback si CDN no está disponible.
6. **Exportación CSV**: Agregar botón de exportación CSV en la página de Registros admin.
7. **Paginación AJAX**: Implementar paginación AJAX en el grid del Módulo Post para sitios con muchos proyectos (+500).
8. **Dashboard widget**: Agregar widget al dashboard de WordPress con resumen de datos BPID.
9. **Accesibilidad WCAG 2.1**: Auditar y mejorar accesibilidad del modal, acordeones y filtros (roles ARIA, navegación por teclado).
10. **Multisite**: Verificar y ajustar compatibilidad completa con WordPress Multisite.
11. **REST API v2**: Agregar endpoints para ODS, municipios y dependencias como recursos independientes.
12. **Validación de imágenes**: Verificar URLs de imágenes antes de renderizar (HEAD request con caché).
13. **Performance**: Implementar lazy loading del grid con Intersection Observer para muchas tarjetas.
14. **Logs estructurados**: Migrar a formato JSON para facilitar análisis con herramientas externas.
15. **GitHub Actions**: Crear workflow CI/CD para linting PHP (PHPCS con WordPress-Coding-Standards), tests y release automático.

# BPID Suite — Instrucciones para Agente de IA

> **Gobernación de Nariño · Secretaría de TIC, Innovación y Gobierno Abierto**
> Versión de instrucciones: 1.1 · Marzo 2026

---

## Índice

1. [Contexto y Objetivo General](#1-contexto-y-objetivo-general)
2. [Especificación de la API BPID](#2-especificación-de-la-api-bpid)
3. [Estructura de Archivos del Plugin](#3-estructura-de-archivos-del-plugin)
4. [Módulo de Configuración](#4-módulo-de-configuración)
5. [Módulo de Importación](#5-módulo-de-importación)
6. [Módulo de Filtros](#6-módulo-de-filtros)
7. [Módulo de Gráficos — 15 tipos D3plus](#7-módulo-de-gráficos--15-tipos-d3plus)
8. [Módulo Post — Visualizador de Proyectos](#8-módulo-post--visualizador-de-proyectos)
9. [Parámetros de Seguridad](#9-parámetros-de-seguridad)
10. [Parámetros de Compatibilidad](#10-parámetros-de-compatibilidad)
11. [API REST Interna](#11-api-rest-interna)
12. [Comandos WP-CLI](#12-comandos-wp-cli)
13. [Documentación Requerida en el Repositorio](#13-documentación-requerida-en-el-repositorio)
14. [Flujo de Trabajo para el Agente](#14-flujo-de-trabajo-para-el-agente)
15. [Referencia Rápida de Constantes y Prefijos](#15-referencia-rápida-de-constantes-y-prefijos)

---

## 1. Contexto y Objetivo General

El agente de IA debe desarrollar el plugin WordPress **"BPID Suite"**, tomando como base de código el plugin existente `secop-suite` y adaptándolo para trabajar con la API pública del BPID (Banco de Proyectos de Inversión y Desarrollo) de la Gobernación de Nariño, Colombia.

### 1.1 Repositorios

| Elemento | URL |
|---|---|
| **Repositorio BASE** (clonar) | `https://github.com/GobernaciondeNarino/secop-suite` |
| **Repositorio DESTINO** | `https://github.com/GobernaciondeNarino/bpid-suite` |
| API pública BPID | `https://bpid.narino.gov.co/bpid/publico/consulta_contratos_con_ejecucion_contractual.php` |
| Librería de gráficos | `https://d3plus.org/`, `https://d3plus.org/?path=/docs/introduction--d3plus` |
| CDN D3plus (recomendado) | `https://cdn.jsdelivr.net/npm/d3plus@2/build/d3plus.full.min.js` |

### 1.2 Instrucción fundamental

> ⚠️ **NO reescribas desde cero.** Clona `secop-suite` y renombra/adapta.
> Mantén toda la arquitectura PHP de seguridad y el patrón Singleton + Dependency Injection.
> Reemplaza referencias a `secop` → `bpid`, `SECOP` → `BPID`, `secop_suite` → `bpid_suite` de forma sistemática.
> Agrega los nuevos campos BPID y los módulos nuevos como extensión, no como sustitución.
> Cada decisión arquitectónica debe documentarse en `README.md` y `CHANGELOG.md`.

### 1.3 Diferencias clave respecto a secop-suite

| Aspecto | secop-suite | bpid-suite |
|---|---|---|
| Origen de datos | datos.gov.co (protocolo SODA, paginado) | bpid.narino.gov.co (una sola llamada GET) |
| Autenticación API | Sin header especial | Header `apikey:` |
| Campos arreglo | Ninguno | `odss`, `municipios`, `imagenes` (JSON) |
| Módulo de Configuración | No existe | **Nuevo — obligatorio** |
| Módulo Post (Visualizador) | No existe | **Nuevo — obligatorio** |
| Tipos de gráficos | 11 tipos | **15 tipos** |

---

## 2. Especificación de la API BPID

### 2.1 Endpoint y autenticación

```bash
GET https://bpid.narino.gov.co/bpid/publico/consulta_contratos_con_ejecucion_contractual.php

Headers:
  apikey: P4zLX3O5ve3rdYobBTd1pzlO3L001mSUrJ9Mtc49HbgmE
  Content-Type: application/json
```

> 🔑 **Seguridad crítica:** La API key NUNCA debe exponerse en el frontend, en JS ni en HTML renderizado.  
> Almacenarla cifrada en `wp_options`. Toda comunicación con la API se realiza exclusivamente desde PHP del servidor.

### 2.2 Estructura de respuesta JSON

La API devuelve un objeto con dos propiedades principales:

```json
{
  "total": 472,
  "contratos": [
    {
      "dependencia": "Dirección Administrativa de Cultura",
      "numeroProyecto": "2024003520088",
      "nombreProyecto": "Fortalecimiento del sector artístico...",
      "entidadEjecutora": "GOBERNACIÓN DE NARIÑO",
      "odss": ["10. Reducción de las desigualdades", "16. Paz, Justicia..."],
      "numero": "-",
      "objeto": "RESOLUCIÓN No. 037...",
      "descripcion": "Estímulos fase II entregados",
      "valor": "4000000",
      "avanceFisico": "100",
      "esOps": "Si",
      "municipios": [],
      "imagenes": []
    }
  ]
}
```

> **Nota importante:** La API también puede devolver una estructura alternativa con clave `proyectos` en lugar de `contratos` (ver Módulo Post, sección 8.2). El módulo de importación usa la clave `contratos`; el módulo Post usa la clave `proyectos`. El agente debe verificar ambas claves al consumir la API.

### 2.3 Campos del objeto contrato

| Campo JSON | Tipo | Descripción |
|---|---|---|
| `total` | integer | Número total de contratos en la respuesta |
| `contratos` | array | Arreglo principal de contratos |
| `dependencia` | string | Secretaría o dependencia responsable |
| `numeroProyecto` | string | Código BPIN del proyecto |
| `nombreProyecto` | string | Nombre del proyecto |
| `entidadEjecutora` | string | Entidad ejecutora del proyecto |
| `odss` | array(string) | ODS relacionados — **almacenar como JSON** |
| `numero` | string | Código único del contrato |
| `objeto` | string | Objeto contractual |
| `descripcion` | string | Descripción de la ejecución en BPID |
| `valor` | string→decimal | Valor del contrato en pesos colombianos |
| `avanceFisico` | string→int | Porcentaje de avance físico (0–100) |
| `esOps` | string→tinyint | `"Si"`/`"No"` → almacenar como `1`/`0` |
| `municipios` | array | Municipios de ejecución — **almacenar como JSON** |
| `imagenes` | array(url) | URLs de imágenes — **almacenar como JSON** |

### 2.4 Reglas de procesamiento en importación

- `odss`, `municipios`, `imagenes` son arreglos: serializar con `wp_json_encode()` antes de guardar en MySQL.
- `valor` viene como string: convertir a `DECIMAL(20,2)`.
- `avanceFisico` puede ser string numérico: convertir a `INT`.
- `esOps` es `"Si"`/`"No"`: almacenar como `TINYINT(1)` (1/0).
- Validar que `numeroProyecto` no esté vacío antes de insertar.
- La API NO tiene paginación: descarga toda la colección en una llamada. Implementar procesamiento por lotes interno dividiendo `contratos` en grupos de 100 para el UPSERT.

---

## 3. Estructura de Archivos del Plugin

### 3.1 Árbol de directorios objetivo

```
bpid-suite/
├── bpid-suite.php                 # Archivo principal (cabecera WP, autoload, clase Plugin)
├── uninstall.php                  # Limpieza completa al desinstalar
├── index.php                      # Seguridad: impide exploración directa
├── README.md                      # Documentación completa
├── CHANGELOG.md                   # Historial de versiones
├── INSTALACION.md                 # Guía de instalación paso a paso
├── includes/
│   ├── class-database.php         # Tabla WP, mapeo de campos BPID, validación
│   ├── class-importer.php         # Importación API BPID con AJAX y cron
│   ├── class-visualizer.php       # CPT gráficas, shortcodes, datos (segurizado)
│   ├── class-filter.php           # CPT filtros, shortcodes, búsqueda AJAX
│   ├── class-post.php             # ★ NUEVO — CPT visualizador proyectos (Módulo Post)
│   ├── class-rest-api.php         # Endpoints REST públicos y autenticados
│   ├── class-cli.php              # Comandos WP-CLI
│   ├── class-logger.php           # Sistema de logs con rotación
│   └── class-updater.php          # Auto-actualización desde GitHub
├── templates/
│   ├── admin/
│   │   ├── config-page.php        # ★ NUEVO — Panel de configuración API key
│   │   ├── import-page.php        # Dashboard de importación
│   │   ├── records-page.php       # Visor de contratos BPID
│   │   ├── logs-page.php          # Logs del sistema
│   │   ├── chart-config.php       # Configuración gráficas (metabox)
│   │   ├── filter-config.php      # Configuración filtros (metabox)
│   │   └── post-config.php        # ★ NUEVO — Configuración Módulo Post (metabox)
│   └── frontend/
│       ├── chart.php              # Renderizado público de gráficas
│       ├── filter.php             # Renderizado público de filtros
│       └── post.php               # ★ NUEVO — Renderizado público visualizador proyectos
├── assets/
│   ├── css/
│   │   ├── admin.css
│   │   └── frontend.css           # Incluye estilos del Módulo Post
│   └── js/
│       ├── admin-import.js
│       ├── admin-charts.js
│       ├── admin-filters.js
│       ├── admin-post.js          # ★ NUEVO — Configurador Módulo Post
│       ├── frontend.js            # Motor D3plus (15 tipos)
│       ├── frontend-filters.js
│       └── frontend-post.js       # ★ NUEVO — Motor visualizador proyectos
└── logs/
    └── import.log
```

### 3.2 Cambios respecto al repositorio base (secop-suite)

| Archivo base (secop-suite) | Cambio requerido (bpid-suite) |
|---|---|
| `secop-suite.php` | Renombrar; actualizar cabecera Plugin Name, Text Domain, prefijos |
| `class-database.php` | Nueva tabla `bpid_suite_contratos` con campos BPID |
| `class-importer.php` | URL API, header `apikey`, parseo campos BPID, manejo arreglos |
| `class-visualizer.php` | Ampliar de 11 a 15 tipos D3plus; actualizar nombres columnas |
| `class-filter.php` | Actualizar whitelist de columnas a campos BPID |
| `class-rest-api.php` | Slugs `/bpid-suite/v1/`; actualizar campos |
| `class-cli.php` | Comandos `wp bpid ...` |
| `class-post.php` | **CREAR NUEVO** — Módulo Post completo |
| `templates/admin/config-page.php` | **CREAR NUEVO** — Panel configuración API key |
| `templates/admin/post-config.php` | **CREAR NUEVO** — Metabox Módulo Post |
| `frontend.js` | Ampliar renderizador a 15 tipos D3plus |
| `frontend-post.js` | **CREAR NUEVO** — Motor JS visualizador proyectos |
| `README.md` | Reescribir para BPID Suite v1.0.0 |

---

## 4. Módulo de Configuración

> ★ **Módulo nuevo** — no existe en secop-suite. Es el primer módulo visible al activar el plugin.

### 4.1 Funcionalidades requeridas

- Campo `input type="password"` para ingresar la API Key (oculto por defecto, toggle para revelar).
- Botón **"Probar conexión"** que realiza una llamada de prueba al endpoint BPID via AJAX desde PHP del servidor y muestra: total de contratos disponibles o mensaje de error detallado.
- Almacenamiento seguro:

```php
update_option('bpid_suite_api_key', sanitize_text_field($key));
// NUNCA exponer en JS
```

- Sección de información del sistema: versión plugin, versión WP requerida, versión PHP requerida, estado de la tabla en base de datos (existente / no existente / registros).
- Botón **"Regenerar tabla"** con confirmación en doble paso (mostrar total de registros antes de actuar).
- Control de cron: selector de frecuencia (Diario / Semanal / Mensual / Desactivado) con indicador del próximo disparo programado.

### 4.2 Seguridad obligatoria

```php
// En el formulario
wp_nonce_field('bpid_suite_config_save', 'bpid_suite_config_nonce');

// En el handler de guardado
check_admin_referer('bpid_suite_config_save', 'bpid_suite_config_nonce');
if (!current_user_can('manage_options')) wp_die('No autorizado');

// Sanitización
$api_key = sanitize_text_field($_POST['bpid_suite_api_key'] ?? '');
```

> La API key nunca debe imprimirse en HTML. Para mostrar que está configurada, usar `str_repeat('*', 20)` como placeholder visual.

---

## 5. Módulo de Importación

Basado en `class-importer.php` de secop-suite. Diferencias clave con la implementación base:

### 5.1 Adaptaciones requeridas

- La API BPID devuelve **todos los registros en una sola llamada** (sin paginación por offset). Implementar procesamiento por lotes interno dividiendo el arreglo `contratos` en grupos de 100 para el UPSERT.
- El UPSERT usa **`numero` + `numeroProyecto` como clave compuesta única** (no solo `numero` como en SECOP).
- Los campos arreglo (`odss`, `municipios`, `imagenes`) se almacenan con `wp_json_encode()`.
- Mantener barra de progreso en tiempo real vía AJAX (mismo patrón que secop-suite).
- Mantener sistema de cron programable (diario, semanal, mensual).
- Mantener botón de cancelación de importación en curso.
- Mantener log detallado con insertados, actualizados y errores.

### 5.2 Estructura de la tabla MySQL

```sql
CREATE TABLE {prefix}bpid_suite_contratos (
  id                  BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  dependencia         VARCHAR(500),
  numero_proyecto     VARCHAR(100),
  nombre_proyecto     TEXT,
  entidad_ejecutora   VARCHAR(500),
  odss                LONGTEXT,          -- JSON serializado
  numero              VARCHAR(200),
  objeto              LONGTEXT,
  descripcion         LONGTEXT,
  valor               DECIMAL(20,2),
  avance_fisico       INT(3),
  es_ops              TINYINT(1) DEFAULT 0,
  municipios          LONGTEXT,          -- JSON serializado
  imagenes            LONGTEXT,          -- JSON serializado
  fecha_importacion   DATETIME DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion DATETIME ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY idx_numero_proyecto (numero(100), numero_proyecto(100)),
  KEY idx_dependencia (dependencia(100)),
  KEY idx_avance (avance_fisico),
  KEY idx_valor (valor),
  KEY idx_es_ops (es_ops)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 5.3 Llamada a la API desde PHP

```php
// ✅ Usar wp_remote_get() (preferido sobre cURL directo en WordPress)
$response = wp_remote_get(
    BPID_SUITE_API_URL,
    [
        'timeout' => 30,
        'headers' => [
            'apikey'       => get_option('bpid_suite_api_key'),
            'Content-Type' => 'application/json',
        ],
        'sslverify' => true, // nunca false en producción
    ]
);

if (is_wp_error($response)) {
    return ['success' => false, 'error' => $response->get_error_message()];
}

$body = wp_remote_retrieve_body($response);
$data = json_decode($body, true);
```

---

## 6. Módulo de Filtros

Basado en `class-filter.php` de secop-suite. Adaptar la whitelist de columnas a los campos BPID manteniendo **toda la lógica de seguridad intacta**.

### 6.1 Whitelist de columnas permitidas

```php
private array $allowed_columns = [
    'dependencia', 'numero_proyecto', 'nombre_proyecto',
    'entidad_ejecutora', 'numero', 'objeto', 'descripcion',
    'valor', 'avance_fisico', 'es_ops', 'municipios',
    'fecha_importacion', 'fecha_actualizacion'
];

// NOTA: 'odss' e 'imagenes' son JSON serializado.
// NO incluirlos en búsquedas SQL directas.
// Para filtrar por ODS, implementar función especial:
// MySQL 5.7+: JSON_CONTAINS(odss, '"10. Reducción..."')
// MySQL < 5.7: odss LIKE '%10. Reducción%' con esc_like()
```

### 6.2 Tipos de campo para el configurador

| Tipo | Columnas BPID aplicables |
|---|---|
| Texto (input LIKE) | `dependencia`, `nombre_proyecto`, `entidad_ejecutora`, `objeto`, `descripcion` |
| Lista desplegable (select =) | `dependencia`, `entidad_ejecutora`, `es_ops` (Sí/No) |
| Rango numérico | `valor`, `avance_fisico` |
| Rango de fechas | `fecha_importacion`, `fecha_actualizacion` |
| Opciones múltiples (checkbox) | `dependencia`, `entidad_ejecutora` (carga dinámica desde DB) |

### 6.3 Seguridad obligatoria en filtros

- Rate limiting por IP: máximo 60 solicitudes por minuto (transient por IP hash).
- Validación de nombre de tabla contra whitelist de tablas del plugin.
- Validación de nombres de columna contra `$allowed_columns`.
- Validación de operadores contra whitelist estricta: `=`, `!=`, `>`, `<`, `>=`, `<=`, `LIKE`.
- `$wpdb->prepare()` en **todos** los valores de filtro sin excepción.
- `$wpdb->esc_like()` para operadores `LIKE`.

---

## 7. Módulo de Gráficos — 15 tipos D3plus

Módulo más ampliado respecto a secop-suite (11 tipos → 15 tipos). Usa la librería D3plus v2.

### 7.1 Carga de la librería

```php
wp_enqueue_script(
    'bpid-d3plus',
    'https://cdn.jsdelivr.net/npm/d3plus@2/build/d3plus.full.min.js',
    [],
    '2.0.0',
    true // cargar en footer
);
// ⚠️ d3plus-hierarchy YA está incluido en el bundle full.
// NO cargar d3plus-hierarchy por separado.
```

### 7.2 Los 15 tipos de gráficos

| # | Key interno | Clase D3plus | Descripción |
|---|---|---|---|
| 1 | `bar` | `BarChart` | Barras verticales simples |
| 2 | `line` | `LinePlot` | Líneas temporales |
| 3 | `area` | `AreaPlot` | Área bajo la curva |
| 4 | `pie` | `Pie` | Gráfico de torta |
| 5 | `donut` | `Donut` | Anillo con radio interno |
| 6 | `treemap` | `Treemap` | Rectángulos proporcionales |
| 7 | `stacked_bar` | `BarChart + .stacked(true)` | Barras apiladas |
| 8 | `grouped_bar` | `BarChart + .stacked(false)` | Barras agrupadas |
| 9 | `tree` | `Tree` | Árbol jerárquico |
| 10 | `pack` | `Pack` | Burbujas jerárquicas |
| 11 | `network` | `Network` | Red de nodos y enlaces |
| 12 | `scatter` | `Plot` | ★ NUEVO — Dispersión XY |
| 13 | `box_whisker` | `BoxWhisker` | ★ NUEVO — Caja y bigotes |
| 14 | `matrix` | `Matrix` | ★ NUEVO — Mapa de calor matricial |
| 15 | `bump` | `BumpChart` | ★ NUEVO — Ranking temporal |

### 7.3 Función getD3PlusClass()

```javascript
function getD3PlusClass(chartType) {
    const map = {
        'bar':         () => new d3plus.BarChart(),
        'line':        () => new d3plus.LinePlot(),
        'area':        () => new d3plus.AreaPlot(),
        'pie':         () => new d3plus.Pie(),
        'donut':       () => new d3plus.Donut(),
        'treemap':     () => new d3plus.Treemap(),
        'stacked_bar': () => new d3plus.BarChart().stacked(true),
        'grouped_bar': () => new d3plus.BarChart().stacked(false),
        'tree':        () => new d3plus.Tree(),
        'pack':        () => new d3plus.Pack(),
        'network':     () => new d3plus.Network(),
        'scatter':     () => new d3plus.Plot(),
        'box_whisker': () => new d3plus.BoxWhisker(),
        'matrix':      () => new d3plus.Matrix(),
        'bump':        () => new d3plus.BumpChart(),
    };
    const factory = map[chartType];
    if (!factory) {
        console.warn('[BPID Suite] Tipo de gráfico no reconocido:', chartType);
        return new d3plus.BarChart(); // fallback seguro
    }
    try { return factory(); }
    catch (e) {
        console.error('[BPID Suite] Error instanciando gráfico:', chartType, e);
        return new d3plus.BarChart();
    }
}
```

### 7.4 Shortcodes de gráficos

```
[bpid_chart id="123"]
[bpid_chart id="123" height="500" class="mi-grafica"]
```

### 7.5 Campos recomendados por tipo

| Tipo | X | Y | Group/Size | Color |
|---|---|---|---|---|
| bar, stacked_bar, grouped_bar | dependencia / entidad_ejecutora | valor / count | es_ops / avance_fisico | — |
| line, area, bump | fecha_importacion | valor | dependencia | — |
| pie, donut | — | valor o count | — | dependencia |
| treemap, pack | — | valor | dependencia | avance_fisico |
| scatter | valor | avance_fisico | count | dependencia |
| box_whisker | dependencia | valor | — | — |
| matrix | dependencia | es_ops | count / sum(valor) | — |
| tree, network | — | valor | dependencia | — |

---

## 8. Módulo Post — Visualizador de Proyectos

> ★ **Módulo completamente nuevo** — no existe en secop-suite.
> Implementa el shortcode `[bpid_grid_visualizador]` basado en el código de referencia provisto.

### 8.1 Descripción general

El Módulo Post permite crear un visualizador interactivo de proyectos BPID directamente en páginas o entradas de WordPress. A diferencia del Módulo de Importación (que guarda datos en la base de datos local), este módulo **consulta la API en tiempo real** con caché de 1 hora mediante WordPress Transients.

El módulo consiste en:
- Un **Custom Post Type (CPT)** `bpid_post` para gestionar configuraciones de visualizadores.
- Un **metabox de configuración** con todas las opciones disponibles.
- Un **shortcode flexible** `[bpid_grid_visualizador]` con atributos configurables.
- Un **motor de renderizado PHP** (`class-post.php`) que consume la API y genera el HTML.
- Un **motor JS** (`frontend-post.js`) para filtros dinámicos, modal de detalle y acordeones.

### 8.2 Estructura de datos de la API para el Módulo Post

El Módulo Post consume la misma API pero espera (o transforma) la respuesta a una estructura de **proyectos agrupados**:

```json
{
  "totalProyectos": 45,
  "proyectos": [
    {
      "numeroProyecto": "2024003520088",
      "nombreProyecto": "Fortalecimiento del sector artístico...",
      "dependenciaProyecto": "Dirección Administrativa de Cultura",
      "valorProyecto": 500000000,
      "odssProyecto": ["10. Reducción de desigualdades"],
      "metasProyecto": ["Meta 1", "Meta 2"],
      "contratosProyecto": [
        {
          "numeroContrato": "CT-001",
          "objetoContrato": "Descripción...",
          "valorContrato": 100000000,
          "procentajeAvanceFisico": 75,
          "esOpsEjecContractual": "No",
          "municipiosEjecContractual": [
            {
              "nombre": "Pasto",
              "poblacion_beneficiada": 5000
            }
          ],
          "imagenesEjecContractual": ["https://...imagen1.jpg"]
        }
      ]
    }
  ]
}
```

> **Tarea del agente:** Si la API real no devuelve esta estructura agrupada, implementar una función `bpid_post_agrupar_contratos()` que transforme el arreglo `contratos` (estructura plana) en arreglo `proyectos` (estructura agrupada por `numeroProyecto`).

#### Función de agrupación requerida

```php
private function agrupar_por_proyecto(array $contratos): array {
    $proyectos = [];
    foreach ($contratos as $contrato) {
        $bpin = $contrato['numeroProyecto'] ?? 'SIN_BPIN';
        if (!isset($proyectos[$bpin])) {
            $proyectos[$bpin] = [
                'numeroProyecto'      => $bpin,
                'nombreProyecto'      => $contrato['nombreProyecto'] ?? '',
                'dependenciaProyecto' => $contrato['dependencia'] ?? '',
                'valorProyecto'       => 0,
                'odssProyecto'        => $contrato['odss'] ?? [],
                'metasProyecto'       => [],
                'contratosProyecto'   => [],
            ];
        }
        $valorContrato = (float)($contrato['valor'] ?? 0);
        $proyectos[$bpin]['valorProyecto'] += $valorContrato;
        $proyectos[$bpin]['contratosProyecto'][] = [
            'numeroContrato'           => $contrato['numero'] ?? '',
            'objetoContrato'           => $contrato['objeto'] ?? '',
            'valorContrato'            => $valorContrato,
            'procentajeAvanceFisico'   => (int)($contrato['avanceFisico'] ?? 0),
            'esOpsEjecContractual'     => $contrato['esOps'] ?? 'No',
            'municipiosEjecContractual'=> is_array($contrato['municipios'])
                                         ? $contrato['municipios']
                                         : json_decode($contrato['municipios'] ?? '[]', true),
            'imagenesEjecContractual'  => is_array($contrato['imagenes'])
                                         ? $contrato['imagenes']
                                         : json_decode($contrato['imagenes'] ?? '[]', true),
        ];
    }
    return array_values($proyectos);
}
```

### 8.3 Clase PHP principal — class-post.php

```php
<?php
declare(strict_types=1);

class BPID_Suite_Post {

    private static ?self $instance = null;
    private string $table_name;
    private string $transient_key = 'bpid_post_api_data_v1';
    private int $cache_seconds    = HOUR_IN_SECONDS;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'bpid_suite_contratos';
        add_action('init',              [$this, 'register_post_type']);
        add_action('add_meta_boxes',    [$this, 'add_meta_box']);
        add_action('save_post',         [$this, 'save_meta_box'], 10, 2);
        add_shortcode('bpid_grid_visualizador', [$this, 'shortcode_render']);
        add_action('wp_ajax_bpid_post_clear_cache', [$this, 'ajax_clear_cache']);
    }

    public function register_post_type(): void {
        register_post_type('bpid_post', [
            'labels'       => ['name' => 'BPID Visualizadores', 'singular_name' => 'Visualizador'],
            'public'       => false,
            'show_ui'      => true,
            'show_in_menu' => 'bpid-suite',
            'supports'     => ['title'],
            'capability_type' => 'post',
        ]);
    }
    // ... métodos siguientes
}
```

### 8.4 Opciones configurables del Módulo Post (metabox)

El metabox `post-config.php` debe exponer las siguientes opciones al editor, guardadas como post meta:

| Meta key | Tipo | Default | Descripción |
|---|---|---|---|
| `_bpid_post_mostrar_stats` | checkbox | `1` | Mostrar barra de estadísticas globales |
| `_bpid_post_mostrar_buscador` | checkbox | `1` | Mostrar campo de búsqueda general |
| `_bpid_post_mostrar_filtros` | checkbox | `1` | Mostrar fila de filtros (dependencia, municipio, ODS) |
| `_bpid_post_filtro_dependencia` | select | `''` | Pre-filtrar por dependencia específica (vacío = todas) |
| `_bpid_post_color_primario` | color | `#348afb` | Color primario (badges, bordes, botones) |
| `_bpid_post_color_fondo` | color | `#fffcf3` | Color de fondo del contenedor |
| `_bpid_post_ocultar_ops` | checkbox | `1` | Ocultar contratos OPS en modal de detalle |
| `_bpid_post_cols_grid` | number | `3` | Columnas del grid (1–4) |
| `_bpid_post_texto_intro` | textarea | `''` | Texto introductorio sobre las tarjetas |
| `_bpid_post_cache_horas` | number | `1` | Duración del caché en horas (1–24) |

### 8.5 Shortcode con atributos

```
[bpid_grid_visualizador]
[bpid_grid_visualizador id="123"]
[bpid_grid_visualizador dependencia="Secretaría de Salud"]
[bpid_grid_visualizador mostrar_stats="0" mostrar_filtros="1" cols="2"]
[bpid_grid_visualizador color_primario="#e63946" cache_horas="2"]
```

Los atributos del shortcode **sobrescriben** la configuración del CPT si se especifica `id`. Si no se especifica `id`, se usan solo los atributos del shortcode con valores por defecto.

### 8.6 Implementación del shortcode render

```php
public function shortcode_render(array $atts): string {
    // 1. Parsear atributos
    $atts = shortcode_atts([
        'id'               => 0,
        'dependencia'      => '',
        'mostrar_stats'    => '1',
        'mostrar_filtros'  => '1',
        'mostrar_buscador' => '1',
        'ocultar_ops'      => '1',
        'cols'             => '3',
        'color_primario'   => '#348afb',
        'color_fondo'      => '#fffcf3',
        'texto_intro'      => '',
        'cache_horas'      => '1',
    ], $atts, 'bpid_grid_visualizador');

    // 2. Si se pasa id, cargar config del CPT y mezclar
    if (!empty($atts['id'])) {
        $post_id = absint($atts['id']);
        // merge post meta sobre $atts
    }

    // 3. Consultar API (con caché)
    $resultado = $this->consultar_api((int)$atts['cache_horas']);

    // 4. Procesar datos
    // 5. Generar HTML via ob_start() + include template
    ob_start();
    include BPID_SUITE_PATH . 'templates/frontend/post.php';
    return ob_get_clean();
}
```

### 8.7 Motor de consulta a la API con caché

```php
private function consultar_api(int $cache_horas = 1): array {
    $transient_key = 'bpid_post_api_' . md5(get_option('bpid_suite_api_key', ''));
    $cached = get_transient($transient_key);
    if ($cached !== false) {
        return ['success' => true, 'data' => $cached];
    }

    $response = wp_remote_get(
        BPID_SUITE_API_URL,
        [
            'timeout' => 30,
            'headers' => [
                'apikey'       => get_option('bpid_suite_api_key'),
                'Content-Type' => 'application/json',
            ],
            'sslverify' => true,
        ]
    );

    if (is_wp_error($response)) {
        return ['success' => false, 'error' => $response->get_error_message()];
    }

    $http_code = wp_remote_retrieve_response_code($response);
    if (200 !== $http_code) {
        return ['success' => false, 'error' => "HTTP {$http_code}"];
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'error' => 'Error al decodificar JSON'];
    }

    // Normalizar: si viene estructura plana, agrupar por proyecto
    if (isset($data['contratos']) && !isset($data['proyectos'])) {
        $data['proyectos']      = $this->agrupar_por_proyecto($data['contratos']);
        $data['totalProyectos'] = count($data['proyectos']);
    }

    set_transient($transient_key, $data, $cache_horas * HOUR_IN_SECONDS);
    return ['success' => true, 'data' => $data];
}
```

### 8.8 Componentes del template frontend (post.php)

El template debe renderizar en este orden:

1. **CSS inline dinámico** — inyectar variables CSS con los colores configurados:
```php
<style>
  :root {
    --bpid-color-primario: <?php echo esc_attr($color_primario); ?>;
    --bpid-color-fondo:    <?php echo esc_attr($color_fondo); ?>;
  }
</style>
```

2. **Contenedor principal** `.bpid-grid-container`

3. **Mensaje de error** (si la API falló)

4. **Buscador general** (si `mostrar_buscador = 1`)

5. **Texto introductorio** (si no está vacío)

6. **Barra de estadísticas** (si `mostrar_stats = 1`):
   - Total Proyectos, Total Actividades, Beneficiarios, Dependencias, Metas Totales

7. **Fila de filtros** (si `mostrar_filtros = 1`):
   - Select: Dependencia, Municipio, ODS + Botón Limpiar Filtros

8. **Grid de tarjetas** `.bpid-grid-projects` con las columnas configuradas

9. **Mensaje "sin resultados"** (oculto por defecto, activado por JS)

10. **Modal de detalle** (oculto por defecto)

11. **Script `<script type="application/json">` con los datos** para que JS los lea sin exponerlos como variables globales

### 8.9 Renderizado de tarjetas

Cada tarjeta debe mostrar:
- Imagen principal del proyecto (primera imagen disponible en `imagenesEjecContractual` de cualquier contrato; si no hay, usar SVG placeholder).
- Badge de municipio principal.
- Nombre del proyecto (`h3`).
- Código BPIN.
- Total de beneficiarios (suma de `poblacion_beneficiada` de todos los contratos).
- Footer: número de actividades + "Ver detalles →".

Atributos `data-*` para los filtros JS:

```html
<div class="bpid-grid-card"
     data-index="0"
     data-dependencia="Dirección de Cultura"
     data-municipios="Pasto|Ipiales|Tumaco"
     data-odss="10. Reducción...|16. Paz..."
     data-search="2024003520088 fortalecimiento cultura pasto ipiales">
```

### 8.10 Modal de detalle

El modal debe mostrar (para el proyecto seleccionado):

**Cabecera:**
- Nombre del proyecto
- BPIN en color primario

**Grid de información:**
- Valor del proyecto (formateado en COP)
- Dependencia
- Avance físico ponderado (barra de progreso = media ponderada por valor de cada contrato)
- Ejecución financiera (suma contratado / valor proyecto × 100)

**Acordeones:**
- 📊 Metas del proyecto
- 🌍 ODS relacionados
- 📑 Contratos — detalle (si `ocultar_ops = 1`, omitir contratos donde `esOpsEjecContractual = "Si"`)

**Cada contrato en el acordeón muestra:**
- Número y objeto del contrato
- Valor y barra de avance físico
- Lista de municipios con beneficiarios
- Galería de imágenes (lazy loading)

### 8.11 Motor JavaScript (frontend-post.js)

```javascript
(function () {
    'use strict';

    // ── Inicialización ──────────────────────────────────────────
    let proyectosData = [];
    try {
        const el = document.getElementById('bpid-grid-data');
        if (el) proyectosData = JSON.parse(el.textContent);
    } catch (e) {
        console.error('[BPID Post] Error al parsear datos:', e);
    }

    // ── Filtros ─────────────────────────────────────────────────
    const filterIds = [
        'bpid-grid-search-general',
        'bpid-grid-filter-dependencia',
        'bpid-grid-filter-municipio',
        'bpid-grid-filter-ods'
    ];

    filterIds.forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        const event = id === 'bpid-grid-search-general' ? 'input' : 'change';
        el.addEventListener(event, filtrar);
    });

    document.getElementById('bpid-grid-clear-filters')
        ?.addEventListener('click', () => {
            filterIds.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });
            filtrar();
        });

    function filtrar() {
        const search = document.getElementById('bpid-grid-search-general')?.value.toLowerCase() || '';
        const dep    = document.getElementById('bpid-grid-filter-dependencia')?.value.toLowerCase() || '';
        const mun    = document.getElementById('bpid-grid-filter-municipio')?.value.toLowerCase() || '';
        const ods    = document.getElementById('bpid-grid-filter-ods')?.value.toLowerCase() || '';

        let visible = 0;
        document.querySelectorAll('.bpid-grid-card').forEach(card => {
            const d = card.dataset;
            const match =
                (!search || d.search.includes(search)) &&
                (!dep    || d.dependencia.toLowerCase() === dep) &&
                (!mun    || d.municipios.toLowerCase().includes(mun)) &&
                (!ods    || d.odss.toLowerCase().includes(ods));

            card.style.display = match ? 'flex' : 'none';
            if (match) visible++;
        });

        const noRes = document.getElementById('bpid-grid-no-results-message');
        const grid  = document.getElementById('bpid-grid-proyectos');
        if (noRes && grid) {
            grid.style.display  = visible ? 'grid' : 'none';
            noRes.style.display = visible ? 'none' : 'block';
        }
    }

    // ── Modal ───────────────────────────────────────────────────
    const modal = document.getElementById('bpid-grid-modal');
    const modalBody = document.getElementById('bpid-grid-modal-body');
    const ocultarOps = modal?.dataset.ocultarOps === '1';

    window.bpidGridOpenModal = function (idx) {
        const p = proyectosData[idx];
        if (!p || !modal || !modalBody) return;

        let totalVal = 0, sumAvance = 0, contratosHtml = '';

        (p.contratosProyecto || []).forEach(c => {
            const val = parseFloat(c.valorContrato)    || 0;
            const av  = parseFloat(c.procentajeAvanceFisico) || 0;
            totalVal  += val;
            sumAvance += val * av;

            const esOps = (c.esOpsEjecContractual || '').toLowerCase().trim();
            if (!ocultarOps || (esOps !== 'si' && esOps !== 'sí')) {
                contratosHtml += renderContrato(c, val, av);
            }
        });

        const avFisico     = totalVal > 0 ? (sumAvance / totalVal).toFixed(1) : '0.0';
        const pVal         = parseFloat(p.valorProyecto) || 0;
        const avFinanciero = pVal > 0 ? Math.min((totalVal / pVal) * 100, 100).toFixed(1) : '0.0';

        modalBody.innerHTML = buildModalHtml(p, pVal, totalVal, avFisico, avFinanciero, contratosHtml);
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    };

    // ── Cierre del modal ────────────────────────────────────────
    document.querySelector('.bpid-grid-modal-close')?.addEventListener('click', cerrarModal);
    modal?.addEventListener('click', e => { if (e.target === modal) cerrarModal(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarModal(); });

    function cerrarModal() {
        modal?.classList.remove('show');
        document.body.style.overflow = 'auto';
    }

    // ── Helpers de renderizado ──────────────────────────────────
    function renderContrato(c, val, av) { /* ... */ }
    function buildModalHtml(p, pVal, totalVal, avFisico, avFinanciero, contratosHtml) { /* ... */ }
    function renderMunicipios(muns) { /* ... */ }
    function renderImages(imgs) { /* ... */ }
    function barra(porcentaje) {
        return `<div class="bpid-modal-progress-bar">
            <div class="bpid-modal-progress-fill" style="width:${porcentaje}%">${porcentaje}%</div>
        </div>`;
    }
    function accordion(title, content) {
        return `<div class="bpid-modal-accordion-item">
            <div class="bpid-modal-accordion-header"
                 onclick="this.nextElementSibling.classList.toggle('active')">
                <span>${title}</span><span>▼</span>
            </div>
            <div class="bpid-modal-accordion-content">
                <div class="bpid-modal-accordion-body">${content}</div>
            </div>
        </div>`;
    }

})();
```

### 8.12 Seguridad obligatoria en el Módulo Post

- Los datos de la API se pasan al JS únicamente mediante `<script type="application/json" id="bpid-grid-data">`, nunca mediante `wp_localize_script` expuesto como variable global.
- Antes de inyectar los datos JSON: `echo wp_json_encode($datos['proyectos'], JSON_HEX_TAG | JSON_HEX_AMP)`.
- Todas las cadenas renderizadas en PHP usan `esc_html()`, `esc_attr()`, `esc_url()` según contexto.
- El template PHP nunca imprime directamente variables `$_GET` o `$_POST`.
- El AJAX handler `bpid_post_clear_cache` verifica nonce y `current_user_can('manage_options')`.
- Las imágenes en el modal se renderizan con `loading="lazy"` y sin `onerror` inline (evitar XSS via URL).

### 8.13 Limpieza de caché desde el admin

Agregar en el metabox un botón **"Limpiar caché del visualizador"** que llama via AJAX a:

```php
add_action('wp_ajax_bpid_post_clear_cache', function () {
    check_ajax_referer('bpid_post_clear_cache', 'nonce');
    if (!current_user_can('manage_options')) wp_die();

    // Eliminar todos los transients del módulo Post
    global $wpdb;
    $wpdb->query(
        "DELETE FROM {$wpdb->options}
         WHERE option_name LIKE '_transient_bpid_post_api_%'
            OR option_name LIKE '_transient_timeout_bpid_post_api_%'"
    );

    wp_send_json_success(['message' => 'Caché eliminado correctamente']);
});
```

---

## 9. Parámetros de Seguridad

> ⛔ **Vulnerabilidades prohibidas** — El agente NUNCA debe introducir estas fallas:

| Vulnerabilidad | Regla |
|---|---|
| SQL Injection | Jamás concatenar variables del usuario en consultas SQL |
| XSS | Jamás imprimir variables sin `esc_html()`, `esc_attr()`, `esc_url()` o `wp_kses()` |
| CSRF | Jamás procesar formularios o AJAX sin verificar nonce |
| Privilege escalation | Jamás ejecutar acciones admin sin `current_user_can()` |
| Path traversal | Jamás usar variables del usuario en `include()` o `file_get_contents()` |
| Exposición de API key | Jamás localizar la clave en JS via `wp_localize_script` |

### 9.1 Controles de seguridad obligatorios

| Área | Control |
|---|---|
| Formularios admin | `wp_nonce_field()` + `check_admin_referer()` |
| AJAX handlers | `check_ajax_referer()` con nonce único por acción |
| REST API pública | Rate limiting; sanitización de parámetros |
| REST API privada | `permission_callback` con `current_user_can()` |
| Consultas SQL | `$wpdb->prepare()` en todos los valores; validación de columnas vs whitelist |
| Salida HTML | `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()` según contexto |
| Datos numéricos | `absint()`, `intval()`, `floatval()` según tipo |
| API Key | En `wp_options` cifrado; nunca en HTML ni JS |
| Rate limiting | Máximo 60 req/min por IP en endpoints públicos |
| Acceso a archivos | `index.php` vacío en todos los directorios; `.htaccess` en `/logs/` |
| JSON al frontend | `JSON_HEX_TAG | JSON_HEX_AMP` en `wp_json_encode()` |

### 9.2 Patrón de consulta SQL segura

```php
// ✅ CORRECTO
$results = $wpdb->get_results(
    $wpdb->prepare(
        'SELECT id, dependencia, valor, avance_fisico
         FROM %i
         WHERE dependencia = %s
           AND valor >= %f
         ORDER BY valor DESC
         LIMIT %d OFFSET %d',
        $this->table_name,   // validado contra whitelist
        $dependencia,         // string del usuario
        $valor_min,           // float
        $per_page,            // absint()
        $offset               // absint()
    ),
    ARRAY_A
);

// ❌ INCORRECTO — NUNCA hacer esto:
// $wpdb->get_results("SELECT * FROM $table WHERE dep = '$dep'");
```

---

## 10. Parámetros de Compatibilidad

### 10.1 Requisitos mínimos

| Componente | Versión mínima |
|---|---|
| WordPress | 6.0 |
| PHP | 8.1 (`declare(strict_types=1)` en todos los archivos) |
| MySQL | 5.7 |
| MariaDB | 10.3 |
| Navegador | Chrome 90+, Firefox 88+, Safari 14+, Edge 90+ |

### 10.2 Reglas de compatibilidad WordPress

- Usar `$wpdb->prefix` en todos los nombres de tabla.
- Registrar hooks solo dentro de funciones, nunca en el scope global.
- `add_action()` y `add_filter()` con prioridades explícitas.
- Verificar `is_multisite()` donde aplique.
- Los shortcodes deben funcionar en Gutenberg (bloque Shortcode) y page builders (Elementor, Divi).
- No usar funciones deprecadas en WP 6.0+.

### 10.3 Reglas de compatibilidad PHP

- `declare(strict_types=1)` en la primera línea de todos los archivos PHP.
- Type hints en todos los parámetros y retornos de métodos.
- No usar `${var}` (deprecado en PHP 8.2), usar `{$var}`.
- No usar `create_function()` ni `eval()`.

### 10.4 Carga de D3plus — regla de CDN

```php
// ✅ Bundle UMD completo con versión fija
wp_enqueue_script('bpid-d3plus',
    'https://cdn.jsdelivr.net/npm/d3plus@2/build/d3plus.full.min.js',
    [], '2.0.0', true
);
// ⚠️ d3plus-hierarchy YA está incluido. NO cargar por separado.

// Verificación defensiva en JS (siempre incluir antes de usar):
if (typeof d3plus === 'undefined' || typeof d3plus.BarChart !== 'function') {
    console.error('[BPID Suite] D3plus no cargó correctamente');
    return;
}
```

### 10.5 Compatibilidad con plugins populares

| Plugin | Consideración |
|---|---|
| WooCommerce | No interferir con sus hooks de admin ni sus tablas DB |
| Elementor / Divi | Shortcodes deben funcionar dentro de widgets de texto |
| Yoast SEO | No generar salida HTML antes de `wp_head()` |
| WP Rocket / cache | AJAX endpoints deben excluirse del caché del plugin de caché |
| WPML / Polylang | Usar `__()` y `_e()` con text domain `bpid-suite` para todas las cadenas |

---

## 11. API REST Interna

### 11.1 Endpoints

```
# Públicos (con rate limiting)
GET  /wp-json/bpid-suite/v1/contracts              # Lista paginada con filtros
GET  /wp-json/bpid-suite/v1/contracts/{id}         # Detalle de un contrato
GET  /wp-json/bpid-suite/v1/stats                  # Estadísticas generales
GET  /wp-json/bpid-suite/v1/chart/{id}/data        # Datos de gráfica
GET  /wp-json/bpid-suite/v1/chart/{id}/csv         # Descargar CSV
GET  /wp-json/bpid-suite/v1/projects               # Lista de proyectos agrupados (Módulo Post)

# Autenticados (requieren manage_options)
POST /wp-json/bpid-suite/v1/import/start           # Iniciar importación
GET  /wp-json/bpid-suite/v1/import/status          # Estado de importación activa
POST /wp-json/bpid-suite/v1/import/cancel          # Cancelar importación
POST /wp-json/bpid-suite/v1/cache/clear            # Limpiar transients
```

### 11.2 Parámetros de consulta para /contracts

| Parámetro | Tipo | Descripción |
|---|---|---|
| `page` | integer | Página. Default: 1. `absint()`. |
| `per_page` | integer | Resultados/página. Default: 20. Máx: 100. `absint()`. |
| `dependencia` | string | Filtro exacto. `sanitize_text_field()`. |
| `search` | string | Búsqueda en `nombre_proyecto` y `objeto`. Usa `LIKE`. |
| `valor_min` | float | Valor mínimo del contrato. `floatval()`. |
| `valor_max` | float | Valor máximo del contrato. `floatval()`. |
| `avance_min` | integer | Porcentaje mínimo. `absint()`. |
| `es_ops` | integer | `1` o `0`. `absint()`. |
| `orderby` | string | Columna de ordenamiento. Validar contra whitelist. |
| `order` | string | `ASC` o `DESC`. Whitelist estricta. |

---

## 12. Comandos WP-CLI

```bash
# Importar datos desde la API BPID
wp bpid import

# Ver estadísticas de la tabla local
wp bpid stats

# Limpiar todos los datos importados
wp bpid truncate --yes

# Ver logs recientes
wp bpid logs --lines=50

# Probar conexión con la API
wp bpid test-connection

# Limpiar caché del Módulo Post
wp bpid clear-cache
```

---

## 13. Documentación Requerida en el Repositorio

### 13.1 README.md — Secciones obligatorias

1. Descripción del plugin y su propósito
2. Tabla de módulos: Configuración, Importación, Filtros, Gráficos, Post
3. Requisitos del sistema
4. Instalación paso a paso
5. Guía de uso de shortcodes con ejemplos
6. Los 15 tipos de gráficos disponibles
7. Estructura de archivos del plugin
8. API REST — endpoints y ejemplos cURL
9. Comandos WP-CLI
10. Tabla de seguridad: vulnerabilidades mitigadas
11. CHANGELOG resumido (últimas versiones)
12. Autor y licencia (GPL v2)

### 13.2 CHANGELOG.md — Formato

```markdown
# Changelog — BPID Suite

## [1.0.0] - 2026-XX-XX
### Nuevas funcionalidades
- Módulo de Configuración con gestión segura de API key BPID
- Importación desde API BPID con procesamiento por lotes
- 15 tipos de gráficos D3plus (4 nuevos vs secop-suite)
- Módulo de Filtros con 4 tipos de campo
- Módulo Post — Visualizador de proyectos con shortcode [bpid_grid_visualizador]
- API REST /wp-json/bpid-suite/v1/
- Comandos WP-CLI: import, stats, truncate, logs, test-connection, clear-cache

### Diferencias respecto a secop-suite
- Reemplazado origen SECOP por API BPID (bpid.narino.gov.co)
- Nueva estructura de tabla con campos BPID
- Ampliados gráficos de 11 a 15 tipos
- Añadidos módulos: Configuración y Post
```

### 13.3 INSTALACION.md — Pasos

1. Clonar o descargar el repositorio `bpid-suite`.
2. Subir la carpeta a `/wp-content/plugins/bpid-suite/`.
3. Activar el plugin desde el panel de WordPress.
4. Ir a **BPID Suite → Configuración** e ingresar la API key.
5. Hacer clic en **"Probar conexión"** para verificar.
6. Ir a **BPID Suite → Importación** y ejecutar la primera importación.
7. Crear una gráfica desde **BPID Suite → Gráficos** y copiar el shortcode.
8. Crear un filtro desde **BPID Suite → Filtros** y copiar el shortcode.
9. Crear un visualizador desde **BPID Suite → Visualizadores** y usar `[bpid_grid_visualizador]`.
10. Sección de **Troubleshooting** para errores comunes.

---

## 14. Flujo de Trabajo para el Agente

### 14.1 Orden de implementación

| Paso | Tarea |
|---|---|
| 1 | Clonar `secop-suite` y crear rama `main` en `bpid-suite` |
| 2 | Renombrado global: `secop → bpid`, `SECOP → BPID`, `secop_suite → bpid_suite` |
| 3 | Actualizar `bpid-suite.php`: cabecera, Text Domain, versión 1.0.0 |
| 4 | Implementar `class-database.php` con tabla `bpid_suite_contratos` |
| 5 | Implementar `class-importer.php` adaptado a la API BPID |
| 6 | Crear `templates/admin/config-page.php` (Módulo Configuración) |
| 7 | Actualizar `class-filter.php` con whitelist de columnas BPID |
| 8 | Actualizar `class-visualizer.php` y `frontend.js` con 15 tipos D3plus |
| 9 | **Crear `class-post.php`** y todos sus archivos asociados (Módulo Post) |
| 10 | Actualizar `class-rest-api.php` con nuevos endpoints |
| 11 | Actualizar `class-cli.php` con comandos `wp bpid` |
| 12 | Actualizar `assets/css` y `assets/js` |
| 13 | Actualizar `uninstall.php` para limpiar CPTs y transients BPID |
| 14 | Escribir `README.md`, `CHANGELOG.md` e `INSTALACION.md` |
| 15 | Commit, tag `v1.0.0`, push a `bpid-suite` en GitHub |

### 14.2 Comandos Git

```bash
# 1. Clonar el repositorio base
git clone https://github.com/GobernaciondeNarino/secop-suite bpid-suite
cd bpid-suite

# 2. Cambiar el remote origin al repositorio destino
git remote set-url origin https://github.com/GobernaciondeNarino/bpid-suite

# 3. Verificar el remote
git remote -v

# 4. Subir al repositorio destino
git push origin main

# 5. Crear tag de versión
git tag -a v1.0.0 -m "BPID Suite v1.0.0 — Release inicial"
git push origin v1.0.0
```

### 14.3 Checklist de calidad pre-commit

```
□ Todos los archivos PHP tienen declare(strict_types=1)
□ No existe ninguna consulta SQL sin $wpdb->prepare()
□ Todos los formularios admin tienen nonce y se verifican
□ La API key no aparece en ningún archivo JS ni HTML renderizado
□ Los 15 tipos de gráficos están definidos en getD3PlusClass()
□ La whitelist de columnas en class-filter.php usa solo campos BPID
□ El index.php vacío existe en todos los subdirectorios
□ uninstall.php elimina tabla, opciones, CPTs bpid_chart, bpid_filter, bpid_post y transients
□ El Módulo Post agrupa contratos si la API devuelve estructura plana
□ El shortcode [bpid_grid_visualizador] funciona sin atributos (valores por defecto)
□ Los datos al JS van en <script type="application/json"> con JSON_HEX_TAG | JSON_HEX_AMP
□ README.md está completo con los 5 módulos documentados
□ CHANGELOG.md refleja todos los cambios de v1.0.0
□ El plugin se activa sin errores PHP en WP 6.0+ con PHP 8.1+
```

---

## 15. Referencia Rápida de Constantes y Prefijos

| Elemento | Valor en bpid-suite |
|---|---|
| Slug del plugin | `bpid-suite` |
| Text Domain | `bpid-suite` |
| Constante de ruta | `BPID_SUITE_PATH` |
| Constante de URL | `BPID_SUITE_URL` |
| Constante de versión | `BPID_SUITE_VERSION` |
| Constante API URL | `BPID_SUITE_API_URL` |
| Prefijo de constantes PHP | `BPID_SUITE_` |
| Prefijo de opciones WP | `bpid_suite_` |
| Prefijo de hooks/filters | `bpid_suite_` |
| Nombre de tabla DB | `{wp_prefix}bpid_suite_contratos` |
| Nonce importación | `bpid_suite_import_nonce` |
| Nonce configuración | `bpid_suite_config_nonce` |
| Nonce gráficos admin | `bpid_suite_chart_admin` |
| Nonce filtros admin | `bpid_suite_filter_admin` |
| Nonce post admin | `bpid_suite_post_admin` |
| Nonce limpiar caché | `bpid_post_clear_cache` |
| CPT gráficos | `bpid_chart` |
| CPT filtros | `bpid_filter` |
| CPT visualizadores (Post) | `bpid_post` |
| Shortcode gráficos | `[bpid_chart id=""]` |
| Shortcode filtros | `[bpid_filter id=""]` |
| Shortcode visualizador | `[bpid_grid_visualizador]` |
| Namespace REST API | `bpid-suite/v1` |
| Slug menú WP admin | `bpid-suite` |
| Opción API key | `bpid_suite_api_key` |
| Opción versión DB | `bpid_suite_db_version` |
| Transient importación activa | `bpid_suite_import_running` |
| Transient caché API (Post) | `bpid_post_api_{md5_key}` |
| Transient caché API (import) | `bpid_suite_api_data_v1` |
| Cron hook | `bpid_suite_cron_import` |
| AJAX action importar | `bpid_suite_start_import` |
| AJAX action estado | `bpid_suite_import_status` |
| AJAX action cancelar | `bpid_suite_cancel_import` |
| AJAX action limpiar caché | `bpid_post_clear_cache` |
| AJAX action filtros frontend | `bpid_suite_filter_query` |

---

*Gobernación de Nariño · Secretaría de TIC, Innovación y Gobierno Abierto*
*Instrucciones BPID Suite v1.1 · Marzo 2026 · Licencia GPL v2 or later*
