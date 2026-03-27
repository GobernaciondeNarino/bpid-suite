You are an expert WordPress Security & Quality Engineer with deep specialization in PHP 8.1+, WordPress plugin architecture, Colombian government open data systems (datascience analytic), D3plus data visualization library, and secure government web development. You combine the rigor of an OWASP security auditor with the practical knowledge of a senior WordPress developer and a D3plus visualization expert.

# Update

## Cambios Implementados — v2.0.0 (Marzo 2026)

### Editor de Gráficos (Admin)
- **Grid de tipos de gráfico**: Reemplazado el select dropdown por un grid visual de cards con íconos SVG para 11 tipos de gráfico (bar, bar_horizontal, bar_stacked, bar_grouped, line, area, area_stacked, pie, donut, treemap, radar).
- **Fuente de datos dinámica**: Selector de tabla por AJAX (`bpid_get_tables`), columnas cargadas dinámicamente (`bpid_get_columns`).
- **Múltiples columnas Y con color individual**: Soporte completo para N variables en el Eje Y, cada una con color independiente asignado mediante color picker. Badges Y1, Y2, Y3... con re-indexación automática.
- **Sección de Filtros**: Filtros por Año y Mes integrados en el editor de gráficos.
- **Sección de Apariencia**: Altura configurable, títulos de ejes, formato de números (Colombiano, Internacional, Europeo, Abreviado, Sin formato), paleta de colores con swatches interactivos, leyenda y timeline opcionales.
- **Barra de herramientas configurable**: Checkboxes para habilitar/deshabilitar cada botón de la toolbar (Detalle, Compartir, Datos, Imagen, CSV).
- **Query SQL personalizada**: Sección colapsable para consultas SELECT avanzadas con validación de seguridad.
- **Vista previa en vivo**: Meta box de preview con renderizado AJAX sin necesidad de guardar.
- **Shortcode en sidebar**: Meta box lateral con shortcode copiable y texto de ayuda.
- **Avisos contextuales**: Notificaciones dinámicas según el tipo de gráfico seleccionado.
- **Validaciones UX**: Advertencias para bar_stacked con <2 Y columns, pie/donut con >1 Y column.

### Renderizado Frontend
- **Motor dual Chart.js + D3plus**: Chart.js como motor primario para los 11 tipos principales, D3plus como fallback para tipos legacy (treemap, network, scatter, etc.).
- **Barra de herramientas v2**: Siempre visible con botones Detalle, Compartir, Datos, Imagen y Descarga CSV con íconos SVG.
- **Tabla de datos inline**: Toggle para mostrar/ocultar tabla de datos debajo del gráfico.
- **Exportación CSV con BOM UTF-8**: Compatible con Excel en español.
- **Exportación de imagen PNG**: Descarga directa desde Chart.js.
- **Toast de confirmación**: Notificaciones no intrusivas para acciones como "Enlace copiado".
- **Formato de números colombiano**: Soporte para notación COP (MMII, MM, K) en ejes Y.
- **Estética mejorada**: Fondo #fdfdf8, sin cuadrícula vertical, tipografía heredada del tema.

### Backend PHP
- **5 AJAX endpoints seguros**: `bpid_get_tables`, `bpid_get_columns`, `bpid_get_filter_values`, `bpid_chart_preview`, `bpid_chart_data` — todos con verificación de nonce y capacidad.
- **20+ meta fields**: Soporte completo para todos los campos de configuración del gráfico.
- **Validación de seguridad**: Tablas validadas contra BD real, columnas validadas contra tabla, queries personalizadas solo SELECT, sanitización de colores hex.
- **Funciones de agregación extendidas**: SUM, AVG, COUNT, MAX, MIN.
- **Múltiples Y columns en data query**: Soporte para construir datasets con N columnas de valor.

### CSS
- **Admin**: Estilos para chart type grid, Y-axis rows, color swatches, toolbar options, custom query section, preview container, shortcode sidebar.
- **Frontend**: Estilos para chart wrapper, toolbar v2 con botones etiquetados, tabla de datos inline, toast de confirmación.

## Mejoras Futuras a Realizar

### Prioridad Alta
1. **Filtros dinámicos adicionales**: Implementar el botón `[+ Agregar Filtro]` para filtros tipo `[Columna] [Operador] [Valor]` con filas dinámicas en el editor.
2. **Filtro por Destino**: Select dinámico poblado por AJAX según la tabla seleccionada.
3. **Plugin chartjs-plugin-datalabels**: Integrar etiquetas flotantes dentro de las barras (año/categoría en texto blanco centrado).
4. **Treemap con Chart.js**: Evaluar migración de treemap de D3plus a librería Chart.js compatible o mantener dual.
5. **Cache de consultas**: Implementar transient cache para las queries de datos de gráficos con TTL configurable.

### Prioridad Media
6. **Línea de referencia de valor máximo**: Mostrar el valor máximo formateado con línea horizontal en el eje Y.
7. **Timeline interactivo**: Implementar slider de rango temporal para tipos line/area cuando `show_timeline` está habilitado.
8. **Presets de paletas de colores**: Ofrecer paletas predefinidas (Gobierno, Corporativo, Accesibilidad, Monocromático) seleccionables con un clic.
9. **Duplicar gráfico**: Botón para clonar un gráfico existente con toda su configuración.
10. **Drag & drop para filas Y**: Reordenar columnas Y mediante arrastrar y soltar.
11. **Importación/Exportación de configuración**: Exportar config de gráfico como JSON e importar en otro gráfico.

### Prioridad Baja
12. **Modo oscuro frontend**: Detectar `prefers-color-scheme: dark` y adaptar colores de gráficos.
13. **Animaciones de entrada**: Transiciones suaves al renderizar gráficos (fade-in, grow).
14. **Responsive breakpoints en gráficos**: Adaptar labels y leyendas según ancho del contenedor.
15. **Embed externo**: Endpoint para embeber gráficos en sitios externos via iframe seguro.
16. **API pública de gráficos**: Extender REST API para exponer datos de gráficos con autenticación por API key.
17. **Gutenberg Block**: Crear bloque nativo de Gutenberg para insertar gráficos con preview en el editor.
18. **Widget Elementor**: Widget dedicado para insertar gráficos BPID en Elementor.
19. **Accesibilidad WCAG 2.1**: Describir gráficos con `aria-label` dinámico, soporte keyboard para toolbar, tabla de datos como alternativa accesible.
20. **Tests unitarios**: PHPUnit para AJAX handlers, validación de meta fields y query builder.

---

# BPID Suite — Instrucciones para Agente de IA

> **Gobernación de Nariño · Secretaría de TIC, Innovación y Gobierno Abierto**
> Versión de instrucciones: v2.0.0 · Marzo 2026

name: charts module
description: >
  Instrucciones completas para implementar o actualizar el módulo de Gráficos del plugin 
  en WordPress. Úsalo siempre que el desarrollador necesite construir, modificar o replicar la interfaz
  de administración de gráficos (Chart Editor), incluyendo: selector de tipo de gráfico con íconos,
  fuente de datos dinámica, múltiples variables en el Eje Y con asignación individual de color (color swatch),
  filtros configurables, opciones de apariencia, barra de herramientas y vista previa en vivo.
  Aplica también cuando se hable de "módulo de gráficos", "editor de gráficos", "Eje Y múltiple",
  "paleta de colores por variable", "shortcode de gráfico" o "bpid chart".
---

# Charts Module — Skill de Implementación

## Visión General

Este skill describe la arquitectura de interfaz y lógica funcional del módulo **Gráficos** de bpid Suite.
La imagen de referencia es el editor `Editar Gráfico` del panel de administración de WordPress.

## 1. Shotcode
El módulo registra un Custom Post Type `[name]_chart`. Cada gráfico es un post con metadatos que
definen tipo, fuente, columnas, filtros y apariencia. Se renderiza vía shortcode `[bpid_chart]`, `[bpid_chart id="X"]`, ó `[bpid_chart id="X" width="X"]`.



## 2. Interfaz de Administración — Especificación Completa

### 2.1 Cabecera del Editor

- **Título del gráfico**: Input `post_title` estándar de WordPress, ancho completo.
- **Shortcode**: Meta box lateral (sidebar) tipo `side`, prioridad `high`.
  - Muestra `[bpid_chart id="POST_ID"]` en un `<code>` con botón "Copiar".
  - Texto de ayuda: *"Copia y pega este shortcode en cualquier página o entrada."*

---

### 2.2 Meta Box: Configuración de la Gráfica

Panel principal (`normal`, prioridad `high`). Secciones en orden vertical:

#### Sección A — Tipo de Gráfica

Grid de botones tipo "card" con ícono SVG + etiqueta. Cada card es un `<label>` con `<input type="radio" name="chart_type">` oculto. La card seleccionada recibe clase `active` con borde azul primario.

| Valor interno | Etiqueta UI | Ícono |
|---|---|---|
| `bar` | Barras | Barras verticales agrupadas |
| `bar_horizontal` | Barras Horizontales | Barras horizontales |
| `bar_stacked` | Barras Apiladas | Barras apiladas (selección por defecto) |
| `bar_grouped` | Barras Agrupadas | Barras agrupadas múltiples |
| `line` | Líneas | Gráfico de líneas |
| `area` | Área | Área rellena bajo línea |
| `area_stacked` | Área Apilada | Área apilada |
| `pie` | Pie / Torta | Gráfico circular |
| `donut` | Donut | Gráfico de dona |
| `treemap` | Treemap | Mosaico de áreas |
| `radar` | Radar | Gráfico de araña |

**CSS clave:**
```css
.chart-type-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
  gap: 8px;
  margin: 12px 0;
}
.chart-type-card {
  border: 2px solid #ddd;
  border-radius: 6px;
  padding: 10px 6px;
  text-align: center;
  cursor: pointer;
  transition: border-color 0.2s, background 0.2s;
}
.chart-type-card:hover { border-color: #aaa; background: #f9f9f9; }
.chart-type-card.active { border-color: #2271b1; background: #f0f6fc; }
.chart-type-card svg { width: 36px; height: 32px; display: block; margin: 0 auto 6px; }
.chart-type-card span { font-size: 11px; color: #444; }
```

---

#### Sección B — Fuente de Datos

1. **Aviso contextual** (`.notice-info` amarillo/naranja): Se muestra dinámicamente según el tipo de gráfico.
   - Para `bar_stacked`: *"Barras apiladas: agregue 2 o más valores Y. Cada valor se apila como un segmento de color diferente. Ideal para ver composición y total."*
   - Para `pie`/`donut`: *"Pie/Donut: use exactamente 1 valor Y y 1 columna de agrupación."*
   - Para `line`/`area`: *"Líneas/Área: cada columna Y genera una serie independiente."*

2. **Tabla de Datos** — `<select name="chart_data_table">`:
   - Se puebla por AJAX llamando a `wp_ajax_bpid_get_tables`.
   - Retorna tablas disponibles en la BD.

3. **Columna de Agrupación (Eje X / Etiquetas)** — `<select name="chart_axis_x">`:
   - Se puebla dinámicamente al seleccionar la tabla (AJAX: `bpid_get_columns`).
   - Hint bajo el select: *"Columna para el eje X (ej. año). Las barras se apilan en cada posición."*

4. **Columnas de Valor (Eje Y)** — Bloque dinámico. Ver **Sección 2.3** para especificación completa.

5. **Función de Agregación** — `<select name="chart_agg_function">`:
   - Opciones: `SUM – Suma`, `AVG – Promedio`, `COUNT – Conteo`, `MAX – Máximo`, `MIN – Mínimo`.

---

#### Sección 2.3 — Columnas de Valor (Eje Y) con Color por Variable ⭐

Esta es la funcionalidad clave. Permite agregar N variables numéricas al Eje Y, cada una con su propio color asignado.

**HTML base del contenedor:**
```html
<div id="chart-y-axes-container">
  <!-- Las filas se generan dinámicamente por JS -->
</div>
<button type="button" id="add-y-axis" class="button button-secondary">
  <span class="dashicons dashicons-plus-alt2"></span> Agregar Valor Y
</button>
<p class="description">
  Cada columna de valor genera una serie independiente en el gráfico.
  Ej: agregar "nombre" y "valor" para comparar ambas métricas por año.
</p>
```

**HTML de una fila Y (generada por JS):**
```html
<div class="y-axis-row" data-index="0">
  <!-- Badge numerado -->
  <span class="y-axis-badge">Y1</span>

  <!-- Select de columna -->
  <select name="chart_y_columns[]" class="y-column-select">
    <!-- Opciones pobladas por AJAX según tabla seleccionada -->
    <option value="apropiado">Apropiado</option>
    <option value="recaudos_acumulados">Recaudos Acumulados</option>
    <option value="por_recaudar">Por Recaudar</option>
  </select>

  <!-- Selector de color con swatch visual -->
  <div class="y-color-picker-wrapper">
    <input
      type="color"
      name="chart_y_colors[]"
      class="y-color-input"
      value="#3eba6a"
      title="Color para esta serie"
    >
    <!-- Alternativamente: input text + swatch clickable -->
  </div>

  <!-- Botón eliminar fila -->
  <button type="button" class="y-axis-remove button-link-delete"
          title="Eliminar esta variable">
    <span class="dashicons dashicons-no-alt"></span>
  </button>
</div>
```

**CSS de las filas Y:**
```css
#chart-y-axes-container {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 10px;
}
.y-axis-row {
  display: flex;
  align-items: center;
  gap: 8px;
}
.y-axis-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 26px;
  height: 26px;
  border-radius: 50%;
  background: #2271b1;
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  flex-shrink: 0;
}
.y-column-select {
  flex: 1;
  min-width: 0;
}
.y-color-picker-wrapper {
  position: relative;
  flex-shrink: 0;
}
.y-color-input {
  width: 40px;
  height: 34px;
  padding: 2px;
  border: 1px solid #8c8f94;
  border-radius: 4px;
  cursor: pointer;
  background: none;
}
/* Versión swatch alternativa: muestra cuadrado de color + abre color picker */
.y-color-swatch {
  width: 34px;
  height: 34px;
  border-radius: 4px;
  border: 2px solid #8c8f94;
  cursor: pointer;
  display: inline-block;
  vertical-align: middle;
}
.y-axis-remove {
  color: #b32d2e;
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
  flex-shrink: 0;
}
.y-axis-remove:hover { color: #8a1f1f; }
```

**JavaScript — lógica de filas Y (`charts-admin.js`):**
```javascript
// Paleta de colores por defecto asignados a cada nueva fila (cíclico)
const DEFAULT_COLORS = [
  '#3eba6a', '#e84c4c', '#4a90d9', '#f5a623',
  '#9b59b6', '#1abc9c', '#844c00', '#ff7300'
];

let yAxisCount = 0;

function addYAxisRow(columnValue = '', colorValue = null) {
  const index = yAxisCount++;
  const color = colorValue || DEFAULT_COLORS[index % DEFAULT_COLORS.length];
  const badgeLabel = `Y${index + 1}`;

  const row = document.createElement('div');
  row.className = 'y-axis-row';
  row.dataset.index = index;

  // Obtener opciones de columna actuales
  const columnOptions = getAvailableColumnOptions(columnValue);

  row.innerHTML = `
    <span class="y-axis-badge">${badgeLabel}</span>
    <select name="chart_y_columns[]" class="y-column-select">
      ${columnOptions}
    </select>
    <input type="color" name="chart_y_colors[]"
           class="y-color-input" value="${color}" title="Color de la serie ${badgeLabel}">
    <button type="button" class="y-axis-remove" title="Eliminar">
      <span class="dashicons dashicons-no-alt"></span>
    </button>
  `;

  row.querySelector('.y-axis-remove').addEventListener('click', () => {
    row.remove();
    reindexYRows();
  });

  document.getElementById('chart-y-axes-container').appendChild(row);
}

function reindexYRows() {
  document.querySelectorAll('.y-axis-row').forEach((row, i) => {
    row.dataset.index = i;
    row.querySelector('.y-axis-badge').textContent = `Y${i + 1}`;
    // Actualizar title del color
    const colorInput = row.querySelector('.y-color-input');
    if (colorInput) colorInput.title = `Color de la serie Y${i + 1}`;
  });
  yAxisCount = document.querySelectorAll('.y-axis-row').length;
}

document.getElementById('add-y-axis')?.addEventListener('click', () => addYAxisRow());

// Inicialización: restaurar filas guardadas desde los metadatos
function initYAxisRows(savedColumns, savedColors) {
  savedColumns.forEach((col, i) => {
    addYAxisRow(col, savedColors[i] || null);
  });
  if (savedColumns.length === 0) {
    addYAxisRow(); // Al menos una fila vacía
  }
}
```

**Guardado en PHP:**
```php
// En save_post hook
$y_columns = isset($_POST['chart_y_columns']) ? array_map('sanitize_text_field', $_POST['chart_y_columns']) : [];
$y_colors  = isset($_POST['chart_y_colors'])  ? array_map('sanitize_hex_color', $_POST['chart_y_colors'])  : [];

// Emparejar columnas con colores; si faltan colores, asignar paleta por defecto
$default_palette = ['#3eba6a','#e84c4c','#4a90d9','#f5a623','#9b59b6','#1abc9c','#844c00','#ff7300'];
foreach ($y_columns as $i => $col) {
  if (empty($y_colors[$i])) {
    $y_colors[$i] = $default_palette[$i % count($default_palette)];
  }
}

update_post_meta($post_id, '_chart_y_columns', $y_columns);
update_post_meta($post_id, '_chart_y_colors',  $y_colors);
```

---

#### Sección C — Filtros

Fila horizontal con 3 campos + botón para filtros adicionales:

```
[ Año ] [ Mes ] [ Destino ▼ ]
[+ Agregar Filtro]
```

- **Año**: `<input type="number" name="chart_filter_year" min="0">` — `0` = todos los años.
  - Helper: *"Dejar en 0 para todos los años."*
- **Mes**: `<input type="number" name="chart_filter_month" min="0" max="12">` — `0` = todos.
  - Helper: *"Dejar en 0 para todos los meses."*
- **Destino**: `<select name="chart_filter_destino">` — Se puebla dinámicamente según tabla. Primera opción: `Todos`.
- **Filtros Adicionales**: Botón `[+ Agregar Filtro]` que abre filas dinámicas con `[Columna ▼] [Operador ▼] [Valor]` + botón eliminar.

---

#### Sección D — Apariencia

| Campo | Tipo | Defecto | Notas |
|---|---|---|---|
| Altura de la Gráfica | `number` + label `px` | `400` | Rango sugerido 200–900 |
| Título Eje Y | `text` placeholder | `"Valor en Pesos Colombianos"` | Label del eje vertical |
| Título Eje X | `text` | `""` | Label del eje horizontal |
| Formato de Números | `select` | `Colombiano (1.000.000)` | Ver opciones abajo |
| Paleta de Colores | `text` + swatches | 8 colores hex | Ver subespecificación |
| Mostrar leyenda | `checkbox` | desmarcado | |
| Mostrar línea de tiempo interactiva | `checkbox` | desmarcado | Solo tipos compatibles |

**Formato de Números — opciones del select:**
```
Colombiano (1.000.000)     → es-CO locale, sin decimales
Internacional (1,000,000)  → en-US locale
Europeo (1.000.000,00)     → de-DE locale
Abreviado (1M, 500K)       → Notación compacta
Sin formato                → Número plano
```

**Paleta de Colores — Comportamiento:**

El campo `Paleta de Colores` (`name="chart_color_palette"`) almacena colores hex separados por coma:
```
#3eba6a,#e84c4c,#4a90d9,#f5a623,#9b59b6,#1abc9c,#844c00,#ff7300
```

**Importante:** La paleta global es el fallback. Los colores asignados individualmente en cada fila Y tienen prioridad sobre la paleta.

Bajo el input de texto se renderizan swatches interactivos:
```javascript
function renderColorSwatches(paletteString) {
  const colors = paletteString.split(',').map(c => c.trim()).filter(c => /^#[0-9a-f]{6}$/i.test(c));
  const container = document.getElementById('color-swatches');
  container.innerHTML = '';
  colors.forEach((color, i) => {
    const swatch = document.createElement('span');
    swatch.className = 'color-swatch';
    swatch.style.background = color;
    swatch.title = color;
    swatch.addEventListener('click', () => {
      // Abrir color picker nativo
      const picker = document.createElement('input');
      picker.type = 'color';
      picker.value = color;
      picker.addEventListener('change', (e) => {
        colors[i] = e.target.value;
        document.getElementById('chart_color_palette').value = colors.join(',');
        renderColorSwatches(colors.join(','));
      });
      picker.click();
    });
    container.appendChild(swatch);
  });
}
```

CSS swatches:
```css
#color-swatches { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
.color-swatch {
  display: inline-block;
  width: 28px; height: 28px;
  border-radius: 4px;
  border: 2px solid rgba(0,0,0,0.15);
  cursor: pointer;
  transition: transform 0.15s;
}
.color-swatch:hover { transform: scale(1.15); border-color: #555; }
```

---

#### Sección E — Barra de Herramientas

- **Mostrar Barra** (`checkbox` `name="chart_show_toolbar"`, defecto: marcado).
- **Opciones a Mostrar** (checkboxes independientes):
  - `Detalle (Info)` — `chart_toolbar_info`
  - `Compartir` — `chart_toolbar_share`
  - `Ver Datos` — `chart_toolbar_data`
  - `Guardar Imagen` — `chart_toolbar_save_img`
  - `Descargar CSV` — `chart_toolbar_csv`

---

#### Sección F — Query Personalizada (Avanzado)

Sección colapsable (`<details>` o botón toggle). Contiene:
- `<textarea name="chart_custom_query">` — SQL seguro (solo SELECT).
- Advertencia en rojo: *"Usar solo consultas SELECT. Esta opción sobreescribe la configuración de Fuente de Datos."*
- Botón "Expandir" / "Contraer" en el borde derecho.

---

### 2.4 Meta Box: Vista Previa

Meta box inferior (`normal`, prioridad `low`) con:
- Botón `[🔄 Actualizar Vista Previa]` (id: `btn-update-preview`).
- `<div id="chart-preview-container">` — Muestra el gráfico renderizado vía AJAX.
- Mensaje inicial: *"Configure la gráfica y haga clic en 'Actualizar Vista Previa' para ver el gráfico D3plus."*

**AJAX de preview:**
```javascript
document.getElementById('btn-update-preview')?.addEventListener('click', () => {
  const formData = new FormData(document.getElementById('post'));
  formData.append('action', 'bpid_chart_preview');
  formData.append('nonce', bpidCharts.nonce);

  fetch(ajaxurl, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        document.getElementById('chart-preview-container').innerHTML = data.data.html;
        // Re-inicializar librería de gráficos en el nuevo contenido
        initChartLibrary(data.data.chart_config);
      }
    });
});
```

---

## 3. Lógica de Renderizado Frontend

### 3.1 Shortcode `[bpid_chart id="X"]`

```php
class bpid_Charts_Renderer {

  public function render_shortcode($atts) {
    $atts = shortcode_atts(['id' => 0], $atts);
    $post_id = intval($atts['id']);
    if (!$post_id) return '';

    $config = $this->build_config($post_id);
    $data   = $this->query_data($config);

    wp_enqueue_script('bpid-charts-frontend');
    wp_enqueue_style('bpid-charts-frontend');

    $uid = 'bpid-chart-' . $post_id . '-' . uniqid();

    ob_start();
    include plugin_dir_path(__FILE__) . 'views/shortcode-output.php';
    return ob_get_clean();
  }

  private function build_config($post_id): array {
    $y_columns = get_post_meta($post_id, '_chart_y_columns', true) ?: [];
    $y_colors  = get_post_meta($post_id, '_chart_y_colors',  true) ?: [];

    return [
      'type'         => get_post_meta($post_id, '_chart_type', true),
      'table'        => get_post_meta($post_id, '_chart_data_table', true),
      'axis_x'       => get_post_meta($post_id, '_chart_axis_x', true),
      'y_columns'    => $y_columns,                    // array de nombres de columna
      'y_colors'     => $y_colors,                     // array de hex, mismo índice que y_columns
      'agg_function' => get_post_meta($post_id, '_chart_agg_function', true) ?: 'SUM',
      'height'       => intval(get_post_meta($post_id, '_chart_height', true) ?: 400),
      'title_y'      => get_post_meta($post_id, '_chart_title_y', true),
      'title_x'      => get_post_meta($post_id, '_chart_title_x', true),
      'number_format'=> get_post_meta($post_id, '_chart_number_format', true) ?: 'es-CO',
      'color_palette'=> get_post_meta($post_id, '_chart_color_palette', true),
      'show_legend'  => (bool) get_post_meta($post_id, '_chart_show_legend', true),
      'show_timeline'=> (bool) get_post_meta($post_id, '_chart_show_timeline', true),
      'toolbar'      => $this->get_toolbar_config($post_id),
      'filters'      => $this->get_filters($post_id),
    ];
  }
}
```

### 3.2 Generación de Config para Chart.js

```javascript
// charts-frontend.js
function buildChartConfig(config, data) {
  const datasets = config.y_columns.map((col, i) => ({
    label: col,
    data: data.map(row => row[col]),
    backgroundColor: config.y_colors[i] || config.color_palette[i % config.color_palette.length],
    borderColor:     config.y_colors[i] || config.color_palette[i % config.color_palette.length],
    borderWidth: config.type.includes('line') ? 2 : 0,
    fill: config.type === 'area' || config.type === 'area_stacked',
  }));

  return {
    type: mapChartType(config.type), // Mapear nombres internos → Chart.js types
    data: {
      labels: data.map(row => row[config.axis_x]),
      datasets,
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: config.show_legend },
      },
      scales: {
        x: { title: { display: !!config.title_x, text: config.title_x } },
        y: { title: { display: !!config.title_y, text: config.title_y },
             stacked: config.type === 'bar_stacked' || config.type === 'area_stacked' },
      },
    },
  };
}

function mapChartType(internal) {
  const map = {
    bar: 'bar', bar_horizontal: 'bar', bar_stacked: 'bar', bar_grouped: 'bar',
    line: 'line', area: 'line', area_stacked: 'line',
    pie: 'pie', donut: 'doughnut', radar: 'radar',
  };
  return map[internal] || 'bar';
}
```

---

## 4. Registro de Meta Campos (PHP)

```php
// Todos los meta keys del módulo de gráficos
const CHART_META_KEYS = [
  '_chart_type',           // string: bar|bar_horizontal|bar_stacked|...
  '_chart_data_table',     // string: nombre de tabla BD
  '_chart_axis_x',         // string: columna para eje X
  '_chart_y_columns',      // array:  ['apropiado','recaudos_acumulados',...]
  '_chart_y_colors',       // array:  ['#3eba6a','#e84c4c',...]
  '_chart_agg_function',   // string: SUM|AVG|COUNT|MAX|MIN
  '_chart_height',         // int:    400
  '_chart_title_y',        // string
  '_chart_title_x',        // string
  '_chart_number_format',  // string: es-CO|en-US|de-DE|compact|raw
  '_chart_color_palette',  // string: hex separados por coma
  '_chart_show_legend',    // bool:   0|1
  '_chart_show_timeline',  // bool:   0|1
  '_chart_toolbar_show',   // bool:   0|1
  '_chart_toolbar_info',   // bool:   0|1
  '_chart_toolbar_share',  // bool:   0|1
  '_chart_toolbar_data',   // bool:   0|1
  '_chart_toolbar_save_img',// bool:  0|1
  '_chart_toolbar_csv',    // bool:   0|1
  '_chart_filter_year',    // int:    0=todos
  '_chart_filter_month',   // int:    0=todos
  '_chart_custom_query',   // string: SQL SELECT opcional
];
```

---

## 5. AJAX Endpoints Requeridos

| Action | Handler | Descripción |
|---|---|---|
| `bpid_get_tables` | `Ajax::get_tables()` | Lista tablas disponibles para el selector |
| `bpid_get_columns` | `Ajax::get_columns()` | Columnas de una tabla (para Eje X y Eje Y) |
| `bpid_get_filter_values` | `Ajax::get_filter_values()` | Valores únicos de una columna para filtros tipo select |
| `bpid_chart_preview` | `Ajax::chart_preview()` | Renderiza HTML del gráfico con config actual (no guardada aún) |
| `bpid_chart_data` | `Ajax::chart_data()` | Devuelve JSON de datos para un gráfico publicado |

Todos requieren nonce verificado con `check_ajax_referer('bpid_charts_nonce')`.

---

## 6. Validaciones y UX

- Mostrar aviso si se selecciona `bar_stacked` con menos de 2 columnas Y.
- Mostrar aviso si se selecciona `pie`/`donut` con más de 1 columna Y.
- Badge Y1, Y2, Y3... se re-numera automáticamente al eliminar filas.
- Al cambiar tabla, repoblar dinámicamente los selects de Eje X y todas las filas Y.
- Colores Y individuales tienen **prioridad** sobre la paleta global.
- Paleta global se usa como fallback si hay más series que colores individuales asignados.
- Custom Query deshabilita visualmente la sección "Fuente de Datos" (overlay gris + `disabled`).

---

7. Visualización Frontend del Shortcode — Estética y Barra de Herramientas
7.1 Diseño Visual del Gráfico Público
Cuando el shortcode [sysman_chart id="X"] se renderiza en una página o entrada, el gráfico adopta
las siguientes características estéticas observadas en la implementación de referencia:

Fondo: blanco roto / crema muy suave (#fdfdf8 o #fafaf5), sin borde ni sombra agresiva.
El gráfico "respira" dentro del contenedor.
Barras con colores únicos por año/categoría: cuando hay una sola serie Y (ej. Apropiado),
cada barra recibe un color distinto tomado cíclicamente de la paleta configurada. Esto se logra
pasando un array de backgroundColor en lugar de un color único a Chart.js.
Etiqueta flotante dentro de la barra: el año o categoría del Eje X se imprime en texto blanco
centrado verticalmente dentro de la barra (si la barra es suficientemente alta). Se implementa
con el plugin chartjs-plugin-datalabels o renderizado Canvas manual en el callback afterDraw.
Valor máximo con línea de referencia: la escala Y muestra el valor máximo formateado en
notación abreviada colombiana (ej. 545.72MMII = miles de millones) alineado al tope del eje.
Formato de eje Y abreviado: los ticks del eje Y usan notación compacta:
100MMII, 200MMII, 300MMII... donde MMII = miles de millones (billones en escala corta).
El formateador personalizado es:

javascript  function formatCOP(value) {
    if (Math.abs(value) >= 1e12) return (value / 1e12).toFixed(2) + 'B';
    if (Math.abs(value) >= 1e9)  return (value / 1e9).toFixed(2)  + 'MMII';
    if (Math.abs(value) >= 1e6)  return (value / 1e6).toFixed(2)  + 'MM';
    if (Math.abs(value) >= 1e3)  return (value / 1e3).toFixed(1)  + 'K';
    return value.toLocaleString('es-CO');
  }

Título del Eje Y rotado verticalmente a la izquierda (Valor en Pesos Colombianos),
usando scales.y.title en Chart.js con display: true y color: '#555'.
Sin cuadrícula vertical: scales.x.grid.display = false. Solo líneas horizontales
suaves (scales.y.grid.color = 'rgba(0,0,0,0.06)').
Tipografía: familia inherit para heredar la fuente del tema WordPress activo.
Tamaño de etiquetas de eje: 11–12px, color #666.

CSS del contenedor frontend:
css.sysman-chart-wrapper {
  background: #fdfdf8;
  border-radius: 8px;
  padding: 16px 20px 12px;
  position: relative;
  font-family: inherit;
}
.sysman-chart-canvas-container {
  position: relative;
  width: 100%;
  /* La altura se inyecta inline desde config.height */
}

7.2 Barra de Herramientas — Especificación Completa
La barra de herramientas se sitúa en la esquina superior derecha del wrapper, flotando sobre
el gráfico mediante position: absolute; top: 12px; right: 16px;. Se muestra solo si
chart_show_toolbar = true.
HTML generado:
html<div class="sysman-chart-toolbar" role="toolbar" aria-label="Herramientas del gráfico">
  <button class="sct-btn" data-action="info"    title="Ver detalle">
    <svg><!-- ícono info circular --></svg> Detalle
  </button>
  <button class="sct-btn" data-action="share"   title="Compartir gráfico">
    <svg><!-- ícono share/flechas --></svg> Compartir
  </button>
  <button class="sct-btn" data-action="data"    title="Ver datos en tabla">
    <svg><!-- ícono grid/tabla --></svg> Datos
  </button>
  <button class="sct-btn" data-action="image"   title="Guardar como imagen">
    <svg><!-- ícono imagen/foto --></svg> Imagen
  </button>
  <button class="sct-btn" data-action="csv"     title="Descargar CSV">
    <svg><!-- ícono descarga --></svg> Descarga
  </button>
</div>
Cada botón se muestra u oculta condicionalmente según los checkboxes de configuración
(chart_toolbar_info, chart_toolbar_share, etc.).
CSS de la barra y botones:
css.sysman-chart-toolbar {
  position: absolute;
  top: 12px;
  right: 16px;
  display: flex;
  align-items: center;
  gap: 4px;
  z-index: 10;
}
.sct-btn {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px 10px;
  border: none;
  background: transparent;
  color: #555;
  font-size: 13px;
  font-weight: 400;
  cursor: pointer;
  border-radius: 4px;
  transition: color 0.15s, background 0.15s;
  white-space: nowrap;
}
.sct-btn svg {
  width: 15px;
  height: 15px;
  stroke: currentColor;
  fill: none;
  stroke-width: 1.8;
  flex-shrink: 0;
}
.sct-btn:hover {
  color: #2271b1;
  background: rgba(34, 113, 177, 0.07);
}
/* Estado activo (ej. Descarga mientras procesa) */
.sct-btn.active,
.sct-btn:focus-visible {
  color: #2271b1;
  outline: 2px solid rgba(34, 113, 177, 0.35);
  outline-offset: 1px;
}
/* Separador visual entre grupos de botones (opcional) */
.sct-btn + .sct-btn { border-left: 1px solid transparent; }

7.3 Funcionalidades de Cada Botón
Detalle — Información del gráfico
Abre un modal o tooltip con metadatos del gráfico:

Título, fuente de datos, período cubierto, fecha de última actualización.
Se implementa con un <dialog> nativo o un div absolutamente posicionado que se togglea.

javascripttoolbarBtn('info', () => {
  const modal = document.getElementById(`${uid}-info-modal`);
  modal?.showModal?.() || modal?.classList.toggle('open');
});
Compartir — Compartir enlace
Copia al portapapeles la URL actual con un parámetro ?chart=POST_ID o la URL canónica de la página.
Muestra confirmación visual (el botón cambia brevemente a color verde + texto "¡Copiado!").
javascripttoolbarBtn('share', async () => {
  await navigator.clipboard.writeText(window.location.href);
  showToast('Enlace copiado al portapapeles');
});
Datos — Ver tabla de datos
Renderiza debajo del gráfico (o en modal) una tabla HTML <table> con los datos crudos
usados para construir el gráfico. Columnas: Eje X + cada columna Y con su nombre y valores formateados.
El botón actúa como toggle: un segundo clic oculta la tabla.
javascripttoolbarBtn('data', () => {
  const tableEl = document.getElementById(`${uid}-data-table`);
  tableEl.hidden = !tableEl.hidden;
  // Generar tabla si es la primera vez
  if (!tableEl.dataset.rendered) {
    tableEl.innerHTML = buildDataTable(chartData, config);
    tableEl.dataset.rendered = '1';
  }
});
CSS de la tabla de datos:
css.sysman-chart-data-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 16px;
  font-size: 13px;
}
.sysman-chart-data-table th {
  background: #f0f0f0;
  padding: 8px 12px;
  text-align: left;
  border-bottom: 2px solid #ddd;
  font-weight: 600;
  color: #333;
}
.sysman-chart-data-table td {
  padding: 7px 12px;
  border-bottom: 1px solid #eee;
  color: #444;
}
.sysman-chart-data-table tr:hover td { background: #fafafa; }
.sysman-chart-data-table td:not(:first-child) { text-align: right; font-variant-numeric: tabular-nums; }
Imagen — Guardar como PNG
Usa chart.toBase64Image('image/png', 1.0) de Chart.js para exportar el canvas a imagen.
Dispara descarga automática con el título del gráfico como nombre de archivo.
javascripttoolbarBtn('image', () => {
  const link = document.createElement('a');
  link.download = `${config.title || 'grafico'}.png`;
  link.href = chartInstance.toBase64Image('image/png', 1.0);
  link.click();
});
Descarga — Descargar CSV
Construye un CSV con BOM UTF-8 para compatibilidad con Excel en español.
Primera fila: encabezados (columna X + columnas Y). Filas siguientes: datos.
Nombre del archivo: {titulo-grafico}-datos.csv.
javascripttoolbarBtn('csv', () => {
  const headers = [config.axis_x, ...config.y_columns];
  const rows = chartData.map(row =>
    headers.map(h => JSON.stringify(row[h] ?? '')).join(',')
  );
  const bom = '\uFEFF'; // BOM para Excel
  const csv = bom + [headers.join(','), ...rows].join('\r\n');
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = `${slugify(config.title || 'datos')}.csv`;
  link.click();
  URL.revokeObjectURL(url);
});

7.4 Toast de Confirmación
Para acciones que no producen un archivo descargable (como "Compartir"), mostrar un toast
no intrusivo en la esquina inferior derecha:
javascriptfunction showToast(message, duration = 2500) {
  const toast = document.createElement('div');
  toast.className = 'sysman-chart-toast';
  toast.textContent = message;
  document.body.appendChild(toast);
  requestAnimationFrame(() => toast.classList.add('visible'));
  setTimeout(() => {
    toast.classList.remove('visible');
    setTimeout(() => toast.remove(), 300);
  }, duration);
}
css.sysman-chart-toast {
  position: fixed;
  bottom: 24px;
  right: 24px;
  background: #1d2327;
  color: #fff;
  padding: 10px 18px;
  border-radius: 6px;
  font-size: 13px;
  z-index: 99999;
  opacity: 0;
  transform: translateY(8px);
  transition: opacity 0.25s, transform 0.25s;
  pointer-events: none;
}
.sysman-chart-toast.visible { opacity: 1; transform: translateY(0); }

## 8. Notas de Integración con bpid Suite

- El módulo de gráficos se activa desde `Configuración > bpid Suite > Módulos`.
- Las tablas de datos provienen de `wp_bpid_import_*` (módulo bpid Import).
- El shortcode puede usarse en widgets Elementor, páginas clásicas o bloques Gutenberg.
- Compatibilidad con PHP 8.1+ y WordPress 6.0+. Sin jQuery obligatorio en frontend.

---

*Gobernación de Nariño · Secretaría de TIC, Innovación y Gobierno Abierto*
*Instrucciones BPID Suite v1.1 · Marzo 2026 · Licencia GPL v2 or later*
