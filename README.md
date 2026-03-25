# BPID Suite — Plugin WordPress

> **Gobernación de Nariño · Secretaría de TIC, Innovación y Gobierno Abierto**
> Versión 1.0.0 · Marzo 2026 · Licencia GPL v2 or later

Plugin WordPress para importar, filtrar, graficar y visualizar datos del **Banco de Proyectos de Inversión y Desarrollo (BPID)** de la Gobernación de Nariño, Colombia.

---

## Descripción

BPID Suite conecta el sitio WordPress de la Gobernación de Nariño con la API pública del BPID, permitiendo:

- **Importar** contratos y proyectos de inversión desde la API del BPID.
- **Filtrar** datos con múltiples criterios (dependencia, valor, avance, municipio, ODS).
- **Visualizar** datos con 15 tipos de gráficos interactivos (D3plus).
- **Mostrar** un grid de proyectos con tarjetas, modal de detalle y estadísticas en tiempo real.

---

## Módulos

| Módulo | Descripción | Shortcode |
|---|---|---|
| **Configuración** | Gestión segura de API key, cron, estado del sistema | — (panel admin) |
| **Importación** | Descarga de contratos desde la API BPID con progreso en tiempo real | — (panel admin) |
| **Filtros** | Búsqueda y filtrado dinámico de contratos almacenados | `[bpid_filter id="123"]` |
| **Gráficos** | 15 tipos de gráficos D3plus configurables | `[bpid_chart id="123"]` |
| **Post (Visualizador)** | Grid interactivo de proyectos con consulta API en tiempo real | `[bpid_grid_visualizador]` |

---

## Requisitos del Sistema

| Componente | Versión Mínima |
|---|---|
| WordPress | 6.0 |
| PHP | 8.1 |
| MySQL | 5.7 |
| MariaDB | 10.3 |
| Navegador | Chrome 90+, Firefox 88+, Safari 14+, Edge 90+ |

---

## Instalación

Consulta [INSTALACION.md](INSTALACION.md) para la guía completa paso a paso.

### Instalación rápida

1. Descargar o clonar este repositorio.
2. Subir la carpeta `bpid-suite` a `/wp-content/plugins/`.
3. Activar el plugin en WordPress.
4. Ir a **BPID Suite → Configuración** e ingresar la API key.
5. Ejecutar la primera importación desde **BPID Suite → Importación**.

---

## Guía de Shortcodes

### Gráficos

```
[bpid_chart id="123"]
[bpid_chart id="123" height="500" class="mi-grafica"]
```

### Filtros

```
[bpid_filter id="456"]
[bpid_filter id="456" class="mis-filtros"]
```

### Visualizador de Proyectos

```
[bpid_grid_visualizador]
[bpid_grid_visualizador id="789"]
[bpid_grid_visualizador dependencia="Secretaría de Salud"]
[bpid_grid_visualizador mostrar_stats="1" mostrar_filtros="1" cols="2"]
[bpid_grid_visualizador color_primario="#e63946" color_fondo="#f1faee" cache_horas="2"]
```

#### Atributos del visualizador

| Atributo | Tipo | Default | Descripción |
|---|---|---|---|
| `id` | int | 0 | ID del CPT Visualizador (carga su configuración) |
| `dependencia` | string | `''` | Pre-filtrar por dependencia |
| `mostrar_stats` | 0/1 | `1` | Mostrar barra de estadísticas |
| `mostrar_filtros` | 0/1 | `1` | Mostrar filtros (dependencia, municipio, ODS) |
| `mostrar_buscador` | 0/1 | `1` | Mostrar campo de búsqueda |
| `ocultar_ops` | 0/1 | `1` | Ocultar contratos OPS en modal |
| `cols` | 1-4 | `3` | Columnas del grid |
| `color_primario` | color | `#348afb` | Color primario |
| `color_fondo` | color | `#fffcf3` | Color de fondo |
| `texto_intro` | string | `''` | Texto introductorio |
| `cache_horas` | 1-24 | `1` | Horas de caché API |

---

## 15 Tipos de Gráficos D3plus

| # | Tipo | Clase D3plus | Descripción |
|---|---|---|---|
| 1 | `bar` | BarChart | Barras verticales simples |
| 2 | `line` | LinePlot | Líneas temporales |
| 3 | `area` | AreaPlot | Área bajo la curva |
| 4 | `pie` | Pie | Gráfico de torta |
| 5 | `donut` | Donut | Anillo con radio interno |
| 6 | `treemap` | Treemap | Rectángulos proporcionales |
| 7 | `stacked_bar` | BarChart (stacked) | Barras apiladas |
| 8 | `grouped_bar` | BarChart (grouped) | Barras agrupadas |
| 9 | `tree` | Tree | Árbol jerárquico |
| 10 | `pack` | Pack | Burbujas jerárquicas |
| 11 | `network` | Network | Red de nodos y enlaces |
| 12 | `scatter` | Plot | Dispersión XY |
| 13 | `box_whisker` | BoxWhisker | Caja y bigotes |
| 14 | `matrix` | Matrix | Mapa de calor matricial |
| 15 | `bump` | BumpChart | Ranking temporal |

---

## Estructura de Archivos

```
bpid-suite/
├── bpid-suite.php                 # Archivo principal
├── uninstall.php                  # Limpieza al desinstalar
├── index.php                      # Seguridad
├── README.md
├── CHANGELOG.md
├── INSTALACION.md
├── includes/
│   ├── class-database.php         # Tabla WP, mapeo de campos BPID
│   ├── class-importer.php         # Importación API BPID con AJAX y cron
│   ├── class-visualizer.php       # CPT gráficas, shortcodes, 15 tipos D3plus
│   ├── class-filter.php           # CPT filtros, búsqueda AJAX
│   ├── class-post.php             # CPT visualizador proyectos
│   ├── class-rest-api.php         # Endpoints REST
│   ├── class-cli.php              # Comandos WP-CLI
│   ├── class-logger.php           # Sistema de logs
│   └── class-updater.php          # Auto-actualización GitHub
├── templates/
│   ├── admin/
│   │   ├── config-page.php        # Panel configuración API key
│   │   ├── import-page.php        # Dashboard importación
│   │   ├── records-page.php       # Visor de contratos
│   │   ├── logs-page.php          # Logs del sistema
│   │   ├── chart-config.php       # Metabox gráficas
│   │   ├── filter-config.php      # Metabox filtros
│   │   └── post-config.php        # Metabox visualizador
│   └── frontend/
│       ├── chart.php              # Renderizado gráficas
│       ├── filter.php             # Renderizado filtros
│       └── post.php               # Renderizado visualizador
├── assets/
│   ├── css/
│   │   ├── admin.css
│   │   └── frontend.css
│   └── js/
│       ├── admin-import.js
│       ├── admin-charts.js
│       ├── admin-filters.js
│       ├── admin-post.js
│       ├── frontend.js            # Motor D3plus (15 tipos)
│       ├── frontend-filters.js
│       └── frontend-post.js       # Motor visualizador
└── logs/
    └── import.log
```

---

## API REST

### Endpoints Públicos

```bash
# Lista paginada de contratos
GET /wp-json/bpid-suite/v1/contracts?page=1&per_page=20&dependencia=Cultura

# Detalle de un contrato
GET /wp-json/bpid-suite/v1/contracts/42

# Estadísticas generales
GET /wp-json/bpid-suite/v1/stats

# Datos de una gráfica
GET /wp-json/bpid-suite/v1/chart/123/data

# Descargar CSV de gráfica
GET /wp-json/bpid-suite/v1/chart/123/csv

# Proyectos agrupados
GET /wp-json/bpid-suite/v1/projects
```

### Endpoints Autenticados (requieren `manage_options`)

```bash
# Iniciar importación
POST /wp-json/bpid-suite/v1/import/start

# Estado de importación
GET /wp-json/bpid-suite/v1/import/status

# Cancelar importación
POST /wp-json/bpid-suite/v1/import/cancel

# Limpiar caché
POST /wp-json/bpid-suite/v1/cache/clear
```

### Parámetros de /contracts

| Parámetro | Tipo | Descripción |
|---|---|---|
| `page` | integer | Página (default: 1) |
| `per_page` | integer | Resultados por página (default: 20, max: 100) |
| `dependencia` | string | Filtro exacto por dependencia |
| `search` | string | Búsqueda en nombre_proyecto y objeto |
| `valor_min` | float | Valor mínimo |
| `valor_max` | float | Valor máximo |
| `avance_min` | integer | Porcentaje mínimo de avance |
| `es_ops` | integer | 1 o 0 |
| `orderby` | string | Columna de ordenamiento |
| `order` | string | ASC o DESC |

Rate limiting: 60 solicitudes por minuto por IP.

---

## Comandos WP-CLI

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

## Seguridad

| Vulnerabilidad | Mitigación |
|---|---|
| SQL Injection | `$wpdb->prepare()` en todas las consultas |
| XSS | `esc_html()`, `esc_attr()`, `esc_url()` en toda salida |
| CSRF | Nonces en todos los formularios y AJAX |
| Escalamiento de privilegios | `current_user_can()` en todas las acciones admin |
| Exposición de API key | Almacenada en `wp_options`, nunca en JS/HTML |
| Path traversal | Sin variables de usuario en `include()` |
| Rate limiting | 60 req/min por IP en endpoints públicos |
| Acceso a directorios | `index.php` vacío en todos los directorios |
| Acceso a logs | `.htaccess` con deny from all |
| JSON injection | `JSON_HEX_TAG \| JSON_HEX_AMP` en datos al frontend |

---

## Changelog

Consulta [CHANGELOG.md](CHANGELOG.md) para el historial completo.

---

## Autor

**Gobernación de Nariño**
Secretaría de TIC, Innovación y Gobierno Abierto

## Licencia

GPL v2 or later — [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)
