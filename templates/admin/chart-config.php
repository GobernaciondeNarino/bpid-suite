<?php
/**
 * Metabox template: Chart configuration.
 *
 * Included by BPID_Suite_Visualizer::render_meta_box().
 * Receives $post variable from the calling context.
 *
 * @package BPID_Suite
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var WP_Post $post */

// ---------------------------------------------------------------------------
// Read all saved meta values.
// ---------------------------------------------------------------------------
$chart_type         = get_post_meta( $post->ID, '_chart_type', true ) ?: 'bar_stacked';
$chart_data_table   = get_post_meta( $post->ID, '_chart_data_table', true );
$chart_axis_x       = get_post_meta( $post->ID, '_chart_axis_x', true );
$chart_y_columns    = get_post_meta( $post->ID, '_chart_y_columns', true );
$chart_y_colors     = get_post_meta( $post->ID, '_chart_y_colors', true );
$chart_agg          = get_post_meta( $post->ID, '_chart_agg_function', true ) ?: 'SUM';
$chart_filter_year  = get_post_meta( $post->ID, '_chart_filter_year', true );
$chart_filter_month = get_post_meta( $post->ID, '_chart_filter_month', true );
$chart_height       = get_post_meta( $post->ID, '_chart_height', true ) ?: 400;
$chart_title_y      = get_post_meta( $post->ID, '_chart_title_y', true );
$chart_title_x      = get_post_meta( $post->ID, '_chart_title_x', true );
$chart_number_format    = get_post_meta( $post->ID, '_chart_number_format', true ) ?: 'es-CO';
$chart_color_palette    = get_post_meta( $post->ID, '_chart_color_palette', true );
$chart_show_legend      = get_post_meta( $post->ID, '_chart_show_legend', true );
$chart_show_timeline    = get_post_meta( $post->ID, '_chart_show_timeline', true );
$chart_toolbar_show     = get_post_meta( $post->ID, '_chart_toolbar_show', true );
$chart_toolbar_info     = get_post_meta( $post->ID, '_chart_toolbar_info', true );
$chart_toolbar_share    = get_post_meta( $post->ID, '_chart_toolbar_share', true );
$chart_toolbar_data     = get_post_meta( $post->ID, '_chart_toolbar_data', true );
$chart_toolbar_save_img = get_post_meta( $post->ID, '_chart_toolbar_save_img', true );
$chart_toolbar_csv      = get_post_meta( $post->ID, '_chart_toolbar_csv', true );
$chart_custom_query     = get_post_meta( $post->ID, '_chart_custom_query', true );
$chart_group_by         = get_post_meta( $post->ID, '_chart_group_by', true );
$chart_group_vigencia   = get_post_meta( $post->ID, '_chart_group_by_vigencia', true );
$chart_adv_filters      = get_post_meta( $post->ID, '_chart_adv_filters', true );
$chart_query_limit      = get_post_meta( $post->ID, '_chart_query_limit', true ) ?: '1000';
$chart_query_orderby    = get_post_meta( $post->ID, '_chart_query_orderby', true );
$chart_query_order      = get_post_meta( $post->ID, '_chart_query_order', true ) ?: 'ASC';
$chart_tooltip_text     = get_post_meta( $post->ID, '_chart_tooltip_text', true );
$chart_value_scale      = get_post_meta( $post->ID, '_chart_value_scale', true ) ?: 'full';

if ( ! is_array( $chart_adv_filters ) ) {
	$chart_adv_filters = array();
}

// Ensure arrays.
if ( ! is_array( $chart_y_columns ) ) {
	$chart_y_columns = array();
}
if ( ! is_array( $chart_y_colors ) ) {
	$chart_y_colors = array();
}

// Default toolbar to checked on new posts (no chart type saved yet).
if ( '' === $chart_toolbar_show && '' === get_post_meta( $post->ID, '_chart_type', true ) ) {
	$chart_toolbar_show = '1';
}

// ---------------------------------------------------------------------------
// Chart type definitions with inline SVG icons.
// ---------------------------------------------------------------------------
$chart_types = array(
	'bar'            => array(
		'label' => 'Barras',
		'icon'  => '<svg viewBox="0 0 32 32" width="32" height="32"><rect x="4" y="16" width="6" height="12" fill="currentColor"/><rect x="13" y="8" width="6" height="20" fill="currentColor"/><rect x="22" y="12" width="6" height="16" fill="currentColor"/></svg>',
	),
	'bar_horizontal' => array(
		'label' => 'Barras Horizontales',
		'icon'  => '<svg viewBox="0 0 32 32" width="32" height="32"><rect x="4" y="4" width="20" height="6" fill="currentColor"/><rect x="4" y="13" width="14" height="6" fill="currentColor"/><rect x="4" y="22" width="24" height="6" fill="currentColor"/></svg>',
	),
	'bar_stacked'    => array(
		'label' => 'Barras Apiladas',
		'icon'  => '<svg viewBox="0 0 32 32" width="32" height="32"><rect x="4" y="18" width="6" height="10" fill="currentColor"/><rect x="4" y="10" width="6" height="8" fill="currentColor" opacity=".5"/><rect x="13" y="12" width="6" height="16" fill="currentColor"/><rect x="13" y="4" width="6" height="8" fill="currentColor" opacity=".5"/><rect x="22" y="14" width="6" height="14" fill="currentColor"/><rect x="22" y="8" width="6" height="6" fill="currentColor" opacity=".5"/></svg>',
	),
	'bar_grouped'    => array(
		'label' => 'Barras Agrupadas',
		'icon'  => '<svg viewBox="0 0 32 32" width="32" height="32"><rect x="2" y="14" width="4" height="14" fill="currentColor"/><rect x="7" y="8" width="4" height="20" fill="currentColor" opacity=".5"/><rect x="14" y="10" width="4" height="18" fill="currentColor"/><rect x="19" y="16" width="4" height="12" fill="currentColor" opacity=".5"/><rect x="26" y="6" width="4" height="22" fill="currentColor"/></svg>',
	),
	'line'           => array(
		'label' => 'Lineal',
		'icon'  => '<svg viewBox="0 0 32 32" width="32" height="32"><polyline points="4,24 10,14 16,18 22,8 28,12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/><circle cx="4" cy="24" r="2" fill="currentColor"/><circle cx="10" cy="14" r="2" fill="currentColor"/><circle cx="16" cy="18" r="2" fill="currentColor"/><circle cx="22" cy="8" r="2" fill="currentColor"/><circle cx="28" cy="12" r="2" fill="currentColor"/></svg>',
	),
	'area'           => array(
		'label' => 'Area',
		'icon'  => '<svg viewBox="0 0 32 32" width="32" height="32"><polygon points="4,28 4,22 10,14 16,18 22,8 28,12 28,28" fill="currentColor" opacity=".3"/><polyline points="4,22 10,14 16,18 22,8 28,12" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>',
	),
	'area_stacked'   => array(
		'label' => 'Area Apilada',
		'icon'  => '<svg viewBox="0 0 32 32" width="32" height="32"><polygon points="4,28 4,20 10,14 16,16 22,10 28,14 28,28" fill="currentColor" opacity=".25"/><polygon points="4,28 4,24 10,20 16,22 22,16 28,20 28,28" fill="currentColor" opacity=".4"/><polyline points="4,20 10,14 16,16 22,10 28,14" fill="none" stroke="currentColor" stroke-width="1.5"/><polyline points="4,24 10,20 16,22 22,16 28,20" fill="none" stroke="currentColor" stroke-width="1.5"/></svg>',
	),
	'pie'            => array(
		'label' => 'Torta',
		'icon'  => '<svg viewBox="0 0 32 32" width="32" height="32"><circle cx="16" cy="16" r="12" fill="none" stroke="currentColor" stroke-width="2"/><path d="M16,16 L16,4 A12,12 0 0,1 27.4,22 Z" fill="currentColor" opacity=".6"/><path d="M16,16 L27.4,22 A12,12 0 0,1 8,26 Z" fill="currentColor" opacity=".35"/></svg>',
	),
	'donut'          => array(
		'label' => 'Dona',
		'icon'  => '<svg viewBox="0 0 32 32" width="32" height="32"><circle cx="16" cy="16" r="12" fill="none" stroke="currentColor" stroke-width="6" opacity=".25"/><path d="M16,4 A12,12 0 0,1 27.4,22" fill="none" stroke="currentColor" stroke-width="6" stroke-linecap="round"/></svg>',
	),
	'treemap'        => array(
		'label' => 'Treemap',
		'icon'  => '<svg viewBox="0 0 32 32" width="32" height="32"><rect x="3" y="3" width="14" height="17" rx="1" fill="currentColor" opacity=".6"/><rect x="19" y="3" width="10" height="10" rx="1" fill="currentColor" opacity=".4"/><rect x="19" y="15" width="10" height="5" rx="1" fill="currentColor" opacity=".3"/><rect x="3" y="22" width="8" height="7" rx="1" fill="currentColor" opacity=".45"/><rect x="13" y="22" width="16" height="7" rx="1" fill="currentColor" opacity=".55"/></svg>',
	),
	'radar'          => array(
		'label' => 'Radar',
		'icon'  => '<svg viewBox="0 0 32 32" width="32" height="32"><polygon points="16,4 27,11 25,24 7,24 5,11" fill="none" stroke="currentColor" stroke-width="1.5"/><polygon points="16,10 22,14 21,21 11,21 10,14" fill="currentColor" opacity=".3" stroke="currentColor" stroke-width="1"/><line x1="16" y1="4" x2="16" y2="16" stroke="currentColor" stroke-width=".7"/><line x1="27" y1="11" x2="16" y2="16" stroke="currentColor" stroke-width=".7"/><line x1="25" y1="24" x2="16" y2="16" stroke="currentColor" stroke-width=".7"/><line x1="7" y1="24" x2="16" y2="16" stroke="currentColor" stroke-width=".7"/><line x1="5" y1="11" x2="16" y2="16" stroke="currentColor" stroke-width=".7"/></svg>',
	),
	'heatmap'        => array(
		'label' => 'Mapa de Calor',
		'icon'  => '<svg viewBox="0 0 32 32" width="32" height="32"><rect x="3" y="3" width="8" height="8" rx="1" fill="currentColor" opacity=".2"/><rect x="12" y="3" width="8" height="8" rx="1" fill="currentColor" opacity=".6"/><rect x="21" y="3" width="8" height="8" rx="1" fill="currentColor" opacity=".9"/><rect x="3" y="12" width="8" height="8" rx="1" fill="currentColor" opacity=".5"/><rect x="12" y="12" width="8" height="8" rx="1" fill="currentColor" opacity=".8"/><rect x="21" y="12" width="8" height="8" rx="1" fill="currentColor" opacity=".3"/><rect x="3" y="21" width="8" height="8" rx="1" fill="currentColor" opacity=".9"/><rect x="12" y="21" width="8" height="8" rx="1" fill="currentColor" opacity=".4"/><rect x="21" y="21" width="8" height="8" rx="1" fill="currentColor" opacity=".7"/></svg>',
	),
	'plot'           => array(
		'label' => 'Dispersión',
		'icon'  => '<svg viewBox="0 0 32 32" width="32" height="32"><line x1="4" y1="28" x2="4" y2="4" stroke="currentColor" stroke-width="1.5"/><line x1="4" y1="28" x2="28" y2="28" stroke="currentColor" stroke-width="1.5"/><circle cx="8" cy="20" r="2.5" fill="currentColor" opacity=".7"/><circle cx="14" cy="12" r="2.5" fill="currentColor" opacity=".7"/><circle cx="20" cy="16" r="2.5" fill="currentColor" opacity=".7"/><circle cx="25" cy="8" r="2.5" fill="currentColor" opacity=".7"/><circle cx="11" cy="24" r="2.5" fill="currentColor" opacity=".7"/><circle cx="22" cy="22" r="2.5" fill="currentColor" opacity=".4"/></svg>',
	),
);

// Aggregation function options.
$agg_functions = array(
	'SUM'   => 'SUM (Suma)',
	'AVG'   => 'AVG (Promedio)',
	'COUNT' => 'COUNT (Conteo)',
	'MAX'   => 'MAX (M&aacute;ximo)',
	'MIN'   => 'MIN (M&iacute;nimo)',
);

// Number format options.
$number_formats = array(
	'es-CO'   => 'Colombiano (es-CO)',
	'en-US'   => 'Internacional (en-US)',
	'de-DE'   => 'Europeo (de-DE)',
	'compact' => 'Abreviado (compact)',
	'raw'     => 'Sin formato (raw)',
);

// Value scale options for axis display.
$value_scales = array(
	'full'     => 'Completos (1.234.567)',
	'thousands' => 'En Miles — K (1.234K)',
	'millions'  => 'En Millones — MM (1,23MM)',
	'billions'  => 'En Miles de Millones — MMII (1,23MMII)',
);
?>

<div class="bpid-chart-config">

	<!-- ================================================================= -->
	<!-- Section A — Chart Type (Card)                                      -->
	<!-- ================================================================= -->
	<div class="bpid-chart-card">
		<div class="bpid-chart-card-header">
			<span class="dashicons dashicons-chart-bar"></span>
			<?php esc_html_e( 'Tipo de Gr&aacute;fico', 'bpid-suite' ); ?>
		</div>
		<div class="bpid-chart-card-body">
			<div class="bpid-chart-type-grid" id="chart-type-grid">
				<?php foreach ( $chart_types as $type_key => $type_def ) : ?>
					<label class="bpid-chart-type-card<?php echo $chart_type === $type_key ? ' active' : ''; ?>" data-type="<?php echo esc_attr( $type_key ); ?>">
						<input
							type="radio"
							name="chart_type"
							value="<?php echo esc_attr( $type_key ); ?>"
							<?php checked( $chart_type, $type_key ); ?>
						/>
						<span class="bpid-chart-type-icon">
							<?php
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG markup defined above.
							echo $type_def['icon'];
							?>
						</span>
						<span class="bpid-chart-type-label">
							<?php echo esc_html( $type_def['label'] ); ?>
						</span>
					</label>
				<?php endforeach; ?>
			</div>
			<!-- Context notice -->
			<div id="chart-type-notice" class="bpid-chart-type-notice" style="display:none;"></div>
		</div>
	</div>

	<!-- ================================================================= -->
	<!-- Section B — Data Source (Card) with mode toggle                     -->
	<!-- ================================================================= -->
	<?php
	$chart_data_mode = get_post_meta( $post->ID, '_chart_data_mode', true ) ?: 'manual';
	$chart_view      = get_post_meta( $post->ID, '_chart_view', true ) ?: '';
	?>
	<div class="bpid-chart-card" id="data-source-section">
		<div class="bpid-chart-card-header">
			<span class="dashicons dashicons-database"></span>
			<?php esc_html_e( 'Fuente de Datos', 'bpid-suite' ); ?>
		</div>
		<div class="bpid-chart-card-body">

			<!-- Data Mode Tabs -->
			<div class="bpid-data-mode-tabs">
				<input type="hidden" name="chart_data_mode" id="chart_data_mode" value="<?php echo esc_attr( $chart_data_mode ); ?>" />
				<button type="button" class="bpid-data-mode-tab<?php echo $chart_data_mode === 'manual' ? ' active' : ''; ?>" data-mode="manual">
					<span class="dashicons dashicons-editor-table"></span>
					<?php esc_html_e( 'Tabla Manual', 'bpid-suite' ); ?>
				</button>
				<button type="button" class="bpid-data-mode-tab<?php echo $chart_data_mode === 'view' ? ' active' : ''; ?>" data-mode="view">
					<span class="dashicons dashicons-visibility"></span>
					<?php esc_html_e( 'Vistas de Datos', 'bpid-suite' ); ?>
				</button>
			</div>

			<!-- ── Manual Mode Panel ── -->
			<div id="bpid-mode-manual" class="bpid-data-mode-panel" style="<?php echo $chart_data_mode === 'manual' ? '' : 'display:none;'; ?>">
				<div class="bpid-chart-form-grid">
					<!-- Data Table -->
					<div class="bpid-chart-form-group">
						<label for="chart_data_table"><?php esc_html_e( 'Tabla de datos', 'bpid-suite' ); ?></label>
						<select name="chart_data_table" id="chart_data_table" class="bpid-chart-select">
							<?php if ( $chart_data_table ) : ?>
								<option value="<?php echo esc_attr( $chart_data_table ); ?>" selected>
									<?php echo esc_html( $chart_data_table ); ?>
								</option>
							<?php else : ?>
								<option value=""><?php esc_html_e( '— Cargando tablas... —', 'bpid-suite' ); ?></option>
							<?php endif; ?>
						</select>
					</div>

					<!-- X Axis Column -->
					<div class="bpid-chart-form-group">
						<label for="chart_axis_x"><?php esc_html_e( 'Columna Eje X', 'bpid-suite' ); ?></label>
						<select name="chart_axis_x" id="chart_axis_x" class="bpid-chart-select">
							<?php if ( $chart_axis_x ) : ?>
								<option value="<?php echo esc_attr( $chart_axis_x ); ?>" selected>
									<?php echo esc_html( $chart_axis_x ); ?>
								</option>
							<?php else : ?>
								<option value=""><?php esc_html_e( '— Seleccione tabla primero —', 'bpid-suite' ); ?></option>
							<?php endif; ?>
						</select>
						<div class="bpid-type-legend">
							<span><span class="bpid-type-swatch" style="background:#e8f5e9;border-color:#4caf50;"></span> # Número</span>
							<span><span class="bpid-type-swatch" style="background:#e3f2fd;border-color:#2196f3;"></span> Abc Texto</span>
							<span><span class="bpid-type-swatch" style="background:#fff3e0;border-color:#ff9800;"></span> 📅 Fecha</span>
						</div>
					</div>

					<!-- Aggregation Function -->
					<div class="bpid-chart-form-group">
						<label for="chart_agg_function"><?php esc_html_e( 'Funci&oacute;n de Agregaci&oacute;n', 'bpid-suite' ); ?></label>
						<select name="chart_agg_function" id="chart_agg_function" class="bpid-chart-select">
							<?php foreach ( $agg_functions as $agg_key => $agg_label ) : ?>
								<option value="<?php echo esc_attr( $agg_key ); ?>" <?php selected( $chart_agg, $agg_key ); ?>>
									<?php echo esc_html( $agg_label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

				<!-- Y Axis Columns -->
				<div class="bpid-chart-y-section">
					<label class="bpid-chart-y-label"><?php esc_html_e( 'Variables Eje Y', 'bpid-suite' ); ?></label>
					<div id="y-axis-rows" class="bpid-chart-y-rows">
						<!-- Y-axis rows populated by JavaScript -->
					</div>
					<div id="y-axis-warning" style="display:none;"></div>
					<button type="button" class="button button-secondary bpid-add-y-btn" id="add-y-axis">
						<span class="dashicons dashicons-plus-alt2" style="margin-top:4px;"></span>
						<?php esc_html_e( 'Agregar Variable Y', 'bpid-suite' ); ?>
					</button>
				</div>
			</div>

			<!-- ── View Mode Panel ── -->
			<div id="bpid-mode-view" class="bpid-data-mode-panel" style="<?php echo $chart_data_mode === 'view' ? '' : 'display:none;'; ?>">
				<div class="bpid-chart-form-group">
					<label for="chart_view"><?php esc_html_e( 'Vista predefinida', 'bpid-suite' ); ?></label>
					<select name="chart_view" id="chart_view" class="bpid-chart-select">
						<option value=""><?php esc_html_e( '— Cargando vistas... —', 'bpid-suite' ); ?></option>
					</select>
					<p id="bpid-view-desc" class="bpid-chart-field-desc" style="margin-top:6px;"></p>
				</div>

				<div class="bpid-chart-form-grid bpid-chart-form-grid--2col" style="margin-top:16px;">
					<div class="bpid-chart-form-group">
						<label for="chart_query_limit_view"><?php esc_html_e( 'L&iacute;mite', 'bpid-suite' ); ?></label>
						<input
							type="number"
							name="chart_query_limit"
							id="chart_query_limit_view"
							value="<?php echo esc_attr( $chart_query_limit ); ?>"
							min="1" max="50000"
							class="bpid-chart-input-sm"
						/>
					</div>
					<div class="bpid-chart-form-group">
						<label for="chart_query_order_view"><?php esc_html_e( 'Orden', 'bpid-suite' ); ?></label>
						<select name="chart_query_order" id="chart_query_order_view" class="bpid-chart-select">
							<option value="DESC" <?php selected( $chart_query_order, 'DESC' ); ?>><?php esc_html_e( 'Mayor a menor', 'bpid-suite' ); ?></option>
							<option value="ASC" <?php selected( $chart_query_order, 'ASC' ); ?>><?php esc_html_e( 'Menor a mayor', 'bpid-suite' ); ?></option>
						</select>
					</div>
				</div>
			</div>

		</div>
	</div>

	<!-- ================================================================= -->
	<!-- Section B2 — Group By (Card)                                       -->
	<!-- ================================================================= -->
	<div class="bpid-chart-card" id="group-by-section">
		<div class="bpid-chart-card-header">
			<span class="dashicons dashicons-category"></span>
			<?php esc_html_e( 'Agrupar Por (Group By)', 'bpid-suite' ); ?>
		</div>
		<div class="bpid-chart-card-body">
			<div class="bpid-chart-form-grid bpid-chart-form-grid--2col">
				<div class="bpid-chart-form-group">
					<label for="chart_group_by"><?php esc_html_e( 'Columna de Agrupaci&oacute;n', 'bpid-suite' ); ?></label>
					<select name="chart_group_by" id="chart_group_by" class="bpid-chart-select">
						<?php if ( $chart_group_by ) : ?>
							<option value="<?php echo esc_attr( $chart_group_by ); ?>" selected>
								<?php echo esc_html( $chart_group_by ); ?>
							</option>
						<?php else : ?>
							<option value=""><?php esc_html_e( '— Sin agrupaci&oacute;n adicional —', 'bpid-suite' ); ?></option>
						<?php endif; ?>
					</select>
					<span class="bpid-chart-help"><?php esc_html_e( 'Agrupa los datos por esta columna, creando una serie (dataset) por cada valor &uacute;nico.', 'bpid-suite' ); ?></span>
				</div>
				<div class="bpid-chart-form-group">
					<label class="bpid-chart-toggle" style="margin-top:26px;">
						<input type="checkbox" name="chart_group_by_vigencia" id="chart_group_by_vigencia" value="1" <?php checked( $chart_group_vigencia, '1' ); ?> />
						<span><?php esc_html_e( 'Agrupar por Vigencia (A&ntilde;o)', 'bpid-suite' ); ?></span>
					</label>
					<span class="bpid-chart-help"><?php esc_html_e( 'Extrae el a&ntilde;o de fecha_importacion (YEAR) como eje de agrupaci&oacute;n. Sobrescribe la columna de agrupaci&oacute;n.', 'bpid-suite' ); ?></span>
				</div>
			</div>
		</div>
	</div>

	<!-- ================================================================= -->
	<!-- Section C — Filters (Card)                                         -->
	<!-- ================================================================= -->
	<div class="bpid-chart-card" id="filters-section">
		<div class="bpid-chart-card-header">
			<span class="dashicons dashicons-filter"></span>
			<?php esc_html_e( 'Filtros de Datos', 'bpid-suite' ); ?>
		</div>
		<div class="bpid-chart-card-body">
			<!-- Quick filters: Year / Month -->
			<div class="bpid-chart-form-grid bpid-chart-form-grid--2col">
				<div class="bpid-chart-form-group">
					<label for="chart_filter_year"><?php esc_html_e( 'A&ntilde;o', 'bpid-suite' ); ?></label>
					<input
						type="number"
						name="chart_filter_year"
						id="chart_filter_year"
						value="<?php echo esc_attr( $chart_filter_year ); ?>"
						min="0"
						class="bpid-chart-input-sm"
						placeholder="Todos"
					/>
					<span class="bpid-chart-help"><?php esc_html_e( '0 o vac&iacute;o = todos los a&ntilde;os.', 'bpid-suite' ); ?></span>
				</div>
				<div class="bpid-chart-form-group">
					<label for="chart_filter_month"><?php esc_html_e( 'Mes', 'bpid-suite' ); ?></label>
					<input
						type="number"
						name="chart_filter_month"
						id="chart_filter_month"
						value="<?php echo esc_attr( $chart_filter_month ); ?>"
						min="0"
						max="12"
						class="bpid-chart-input-sm"
						placeholder="Todos"
					/>
					<span class="bpid-chart-help"><?php esc_html_e( '0 o vac&iacute;o = todos los meses.', 'bpid-suite' ); ?></span>
				</div>
			</div>

			<!-- Advanced dynamic filters -->
			<div class="bpid-adv-filters-section" style="margin-top:20px;padding-top:16px;border-top:1px solid var(--bpid-gray-200,#e0e0e0);">
				<label class="bpid-chart-y-label"><?php esc_html_e( 'Filtros Avanzados (WHERE)', 'bpid-suite' ); ?></label>
				<div id="adv-filter-rows" class="bpid-adv-filter-rows">
					<?php if ( ! empty( $chart_adv_filters ) ) : ?>
						<?php foreach ( $chart_adv_filters as $idx => $f ) : ?>
							<div class="adv-filter-row" data-index="<?php echo (int) $idx; ?>">
								<select name="chart_adv_filters[<?php echo (int) $idx; ?>][column]" class="adv-filter-column bpid-chart-select">
									<option value="<?php echo esc_attr( $f['column'] ?? '' ); ?>" selected>
										<?php echo esc_html( $f['column'] ?? '— Campo —' ); ?>
									</option>
								</select>
								<select name="chart_adv_filters[<?php echo (int) $idx; ?>][operator]" class="adv-filter-operator bpid-chart-select">
									<?php
									$ops = array( '=' => '=', '!=' => '!=', '>' => '>', '<' => '<', '>=' => '>=', '<=' => '<=', 'LIKE' => 'LIKE' );
									foreach ( $ops as $op_val => $op_label ) :
									?>
										<option value="<?php echo esc_attr( $op_val ); ?>" <?php selected( $f['operator'] ?? '=', $op_val ); ?>>
											<?php echo esc_html( $op_label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<input type="text" name="chart_adv_filters[<?php echo (int) $idx; ?>][value]" class="adv-filter-value bpid-chart-input" value="<?php echo esc_attr( $f['value'] ?? '' ); ?>" placeholder="Valor" />
								<button type="button" class="adv-filter-remove button button-small" title="Eliminar">
									<span class="dashicons dashicons-no-alt" style="font-size:16px;width:16px;height:16px;margin-top:2px;"></span>
								</button>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
				<button type="button" class="button button-secondary bpid-add-y-btn" id="add-adv-filter">
					<span class="dashicons dashicons-plus-alt2" style="margin-top:4px;"></span>
					<?php esc_html_e( 'Agregar Filtro', 'bpid-suite' ); ?>
				</button>
				<span class="bpid-chart-help" style="margin-top:6px;display:block;">
					<?php esc_html_e( 'Cada filtro se aplica como condici&oacute;n WHERE. LIKE usa % como comod&iacute;n (ej: %Pasto%).', 'bpid-suite' ); ?>
				</span>
			</div>

			<!-- Limit & Order -->
			<div class="bpid-chart-form-grid bpid-chart-form-grid--3col" style="margin-top:20px;padding-top:16px;border-top:1px solid var(--bpid-gray-200,#e0e0e0);">
				<div class="bpid-chart-form-group">
					<label for="chart_query_limit"><?php esc_html_e( 'L&iacute;mite de registros', 'bpid-suite' ); ?></label>
					<input
						type="number"
						name="chart_query_limit"
						id="chart_query_limit"
						value="<?php echo esc_attr( $chart_query_limit ); ?>"
						min="1"
						max="50000"
						class="bpid-chart-input-sm"
					/>
				</div>
				<div class="bpid-chart-form-group">
					<label for="chart_query_orderby"><?php esc_html_e( 'Ordenar por', 'bpid-suite' ); ?></label>
					<select name="chart_query_orderby" id="chart_query_orderby" class="adv-filter-column bpid-chart-select">
						<?php if ( $chart_query_orderby ) : ?>
							<option value="<?php echo esc_attr( $chart_query_orderby ); ?>" selected>
								<?php echo esc_html( $chart_query_orderby ); ?>
							</option>
						<?php else : ?>
							<option value=""><?php esc_html_e( '— Eje X (defecto) —', 'bpid-suite' ); ?></option>
						<?php endif; ?>
					</select>
				</div>
				<div class="bpid-chart-form-group">
					<label for="chart_query_order"><?php esc_html_e( 'Direcci&oacute;n', 'bpid-suite' ); ?></label>
					<select name="chart_query_order" id="chart_query_order" class="bpid-chart-select">
						<option value="ASC" <?php selected( $chart_query_order, 'ASC' ); ?>>ASC ↑</option>
						<option value="DESC" <?php selected( $chart_query_order, 'DESC' ); ?>>DESC ↓</option>
					</select>
				</div>
			</div>
		</div>
	</div>

	<!-- ================================================================= -->
	<!-- Section D — Appearance (Card)                                       -->
	<!-- ================================================================= -->
	<div class="bpid-chart-card">
		<div class="bpid-chart-card-header">
			<span class="dashicons dashicons-art"></span>
			<?php esc_html_e( 'Apariencia', 'bpid-suite' ); ?>
		</div>
		<div class="bpid-chart-card-body">
			<div class="bpid-chart-form-grid bpid-chart-form-grid--2col">
				<!-- Chart Height -->
				<div class="bpid-chart-form-group">
					<label for="chart_height"><?php esc_html_e( 'Altura del Gr&aacute;fico (px)', 'bpid-suite' ); ?></label>
					<input
						type="number"
						name="chart_height"
						id="chart_height"
						value="<?php echo esc_attr( $chart_height ); ?>"
						min="200"
						max="900"
						step="10"
						class="bpid-chart-input-sm"
					/>
				</div>

				<!-- Number Format -->
				<div class="bpid-chart-form-group">
					<label for="chart_number_format"><?php esc_html_e( 'Formato de N&uacute;meros', 'bpid-suite' ); ?></label>
					<select name="chart_number_format" id="chart_number_format" class="bpid-chart-select">
						<?php foreach ( $number_formats as $fmt_key => $fmt_label ) : ?>
							<option value="<?php echo esc_attr( $fmt_key ); ?>" <?php selected( $chart_number_format, $fmt_key ); ?>>
								<?php echo esc_html( $fmt_label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<!-- Value Scale -->
				<div class="bpid-chart-form-group">
					<label for="chart_value_scale"><?php esc_html_e( 'Escala de Valores (Ejes)', 'bpid-suite' ); ?></label>
					<select name="chart_value_scale" id="chart_value_scale" class="bpid-chart-select">
						<?php foreach ( $value_scales as $vs_key => $vs_label ) : ?>
							<option value="<?php echo esc_attr( $vs_key ); ?>" <?php selected( $chart_value_scale, $vs_key ); ?>>
								<?php echo esc_html( $vs_label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="bpid-chart-field-desc"><?php esc_html_e( 'Controla cómo se muestran los números grandes en los ejes del gráfico.', 'bpid-suite' ); ?></p>
				</div>

				<!-- Y Axis Title -->
				<div class="bpid-chart-form-group">
					<label for="chart_title_y"><?php esc_html_e( 'T&iacute;tulo Eje Y', 'bpid-suite' ); ?></label>
					<input
						type="text"
						name="chart_title_y"
						id="chart_title_y"
						value="<?php echo esc_attr( $chart_title_y ); ?>"
						class="bpid-chart-input"
						placeholder="<?php esc_attr_e( 'Valor en Pesos Colombianos', 'bpid-suite' ); ?>"
					/>
				</div>

				<!-- X Axis Title -->
				<div class="bpid-chart-form-group">
					<label for="chart_title_x"><?php esc_html_e( 'T&iacute;tulo Eje X', 'bpid-suite' ); ?></label>
					<input
						type="text"
						name="chart_title_x"
						id="chart_title_x"
						value="<?php echo esc_attr( $chart_title_x ); ?>"
						class="bpid-chart-input"
					/>
				</div>
			</div>

			<!-- Tooltip Custom Text -->
			<div class="bpid-chart-form-group" style="margin-top:16px;">
				<label for="chart_tooltip_text"><?php esc_html_e( 'Texto personalizado del Tooltip', 'bpid-suite' ); ?></label>
				<input
					type="text"
					name="chart_tooltip_text"
					id="chart_tooltip_text"
					value="<?php echo esc_attr( $chart_tooltip_text ); ?>"
					class="bpid-chart-input"
					placeholder="<?php esc_attr_e( 'Ej: Fuente: SECOP II — Valores en pesos colombianos', 'bpid-suite' ); ?>"
				/>
				<span class="bpid-chart-help"><?php esc_html_e( 'Texto adicional que se mostrará al pie del tooltip al pasar el cursor sobre los datos del gráfico.', 'bpid-suite' ); ?></span>
			</div>

			<!-- Color Palette -->
			<div class="bpid-chart-form-group" style="margin-top:16px;">
				<label for="chart_color_palette"><?php esc_html_e( 'Paleta de Colores', 'bpid-suite' ); ?></label>
				<input
					type="text"
					name="chart_color_palette"
					id="chart_color_palette"
					value="<?php echo esc_attr( $chart_color_palette ); ?>"
					class="bpid-chart-input"
					placeholder="#2271b1, #d63638, #00a32a, #dba617, #8c5e58"
				/>
				<div id="color-swatches" class="bpid-color-swatches"></div>
				<span class="bpid-chart-help"><?php esc_html_e( 'Colores HEX separados por coma.', 'bpid-suite' ); ?></span>
			</div>

			<!-- Toggles Row -->
			<div class="bpid-chart-toggles">
				<label class="bpid-chart-toggle">
					<input type="checkbox" name="chart_show_legend" value="1" <?php checked( $chart_show_legend, '1' ); ?> />
					<span><?php esc_html_e( 'Mostrar leyenda', 'bpid-suite' ); ?></span>
				</label>
				<label class="bpid-chart-toggle">
					<input type="checkbox" name="chart_show_timeline" value="1" <?php checked( $chart_show_timeline, '1' ); ?> />
					<span><?php esc_html_e( 'L&iacute;nea de tiempo interactiva', 'bpid-suite' ); ?></span>
				</label>
			</div>
		</div>
	</div>

	<!-- ================================================================= -->
	<!-- Section E — Toolbar (Card)                                          -->
	<!-- ================================================================= -->
	<div class="bpid-chart-card">
		<div class="bpid-chart-card-header">
			<span class="dashicons dashicons-admin-tools"></span>
			<?php esc_html_e( 'Barra de Herramientas', 'bpid-suite' ); ?>
		</div>
		<div class="bpid-chart-card-body">
			<label class="bpid-chart-toggle bpid-chart-toggle--main">
				<input type="checkbox" name="chart_toolbar_show" id="chart_toolbar_show" value="1" <?php checked( $chart_toolbar_show, '1' ); ?> />
				<span><?php esc_html_e( 'Mostrar barra de herramientas', 'bpid-suite' ); ?></span>
			</label>

			<div class="bpid-toolbar-options-grid">
				<label class="bpid-chart-toggle">
					<input type="checkbox" name="chart_toolbar_info" value="1" <?php checked( $chart_toolbar_info, '1' ); ?> />
					<span><?php esc_html_e( 'Detalle (Info)', 'bpid-suite' ); ?></span>
				</label>
				<label class="bpid-chart-toggle">
					<input type="checkbox" name="chart_toolbar_share" value="1" <?php checked( $chart_toolbar_share, '1' ); ?> />
					<span><?php esc_html_e( 'Compartir', 'bpid-suite' ); ?></span>
				</label>
				<label class="bpid-chart-toggle">
					<input type="checkbox" name="chart_toolbar_data" value="1" <?php checked( $chart_toolbar_data, '1' ); ?> />
					<span><?php esc_html_e( 'Ver Datos', 'bpid-suite' ); ?></span>
				</label>
				<label class="bpid-chart-toggle">
					<input type="checkbox" name="chart_toolbar_save_img" value="1" <?php checked( $chart_toolbar_save_img, '1' ); ?> />
					<span><?php esc_html_e( 'Guardar Imagen', 'bpid-suite' ); ?></span>
				</label>
				<label class="bpid-chart-toggle">
					<input type="checkbox" name="chart_toolbar_csv" value="1" <?php checked( $chart_toolbar_csv, '1' ); ?> />
					<span><?php esc_html_e( 'Descargar CSV', 'bpid-suite' ); ?></span>
				</label>
			</div>
		</div>
	</div>

	<!-- ================================================================= -->
	<!-- Section F — Custom Query (Card)                                     -->
	<!-- ================================================================= -->
	<div class="bpid-chart-card bpid-chart-card--query">
		<div class="bpid-chart-card-header">
			<span class="dashicons dashicons-editor-code"></span>
			<?php esc_html_e( 'Query Personalizado', 'bpid-suite' ); ?>
			<span class="bpid-badge-advanced"><?php esc_html_e( 'Avanzado', 'bpid-suite' ); ?></span>
		</div>
		<div class="bpid-chart-card-body">
			<div class="bpid-query-warning">
				<span class="dashicons dashicons-warning"></span>
				<?php esc_html_e( 'Solo se permiten consultas SELECT. No utilice INSERT, UPDATE, DELETE, DROP ni otras sentencias que modifiquen datos.', 'bpid-suite' ); ?>
			</div>

			<div class="bpid-query-editor-wrap">
				<div class="bpid-query-toolbar">
					<span class="bpid-query-label">SQL</span>
					<button type="button" class="button button-small" id="bpid-query-generate">
						<span class="dashicons dashicons-update" style="margin-top:4px;font-size:14px;"></span>
						<?php esc_html_e( 'Generar desde config', 'bpid-suite' ); ?>
					</button>
					<button type="button" class="button button-small" id="bpid-query-clear">
						<span class="dashicons dashicons-dismiss" style="margin-top:4px;font-size:14px;"></span>
						<?php esc_html_e( 'Limpiar', 'bpid-suite' ); ?>
					</button>
				</div>
				<textarea
					name="chart_custom_query"
					id="chart_custom_query"
					rows="6"
					class="bpid-query-textarea"
					placeholder="SELECT columna_x, SUM(columna_y) FROM tabla WHERE anio = 2025 GROUP BY columna_x"
				><?php echo esc_textarea( $chart_custom_query ); ?></textarea>
			</div>

			<p class="bpid-chart-help" style="margin-top:8px;">
				<?php esc_html_e( 'Si se proporciona una consulta personalizada, se ignorar&aacute;n las opciones de tabla, ejes y filtros configuradas arriba.', 'bpid-suite' ); ?>
			</p>
		</div>
	</div>

	<!-- ================================================================= -->
	<!-- Shortcode Preview                                                  -->
	<!-- ================================================================= -->
	<div class="bpid-chart-shortcode-bar">
		<span class="dashicons dashicons-shortcode"></span>
		<strong><?php esc_html_e( 'Shortcode:', 'bpid-suite' ); ?></strong>
		<code id="bpid-chart-shortcode-inline">[bpid_chart id="<?php echo esc_attr( (string) $post->ID ); ?>"]</code>
		<button type="button" class="button button-small bpid-copy-shortcode" data-target="bpid-chart-shortcode-inline">
			<?php esc_html_e( 'Copiar', 'bpid-suite' ); ?>
		</button>
	</div>

</div><!-- .bpid-chart-config -->

<!-- ===================================================================== -->
<!-- Saved Y columns/colors data for JS initialization                     -->
<!-- ===================================================================== -->
<script type="text/javascript">
	var bpidChartSavedYColumns = <?php echo wp_json_encode( $chart_y_columns ); ?>;
	var bpidChartSavedYColors  = <?php echo wp_json_encode( $chart_y_colors ); ?>;
	var bpidChartSavedAxisX    = <?php echo wp_json_encode( $chart_axis_x ); ?>;
	var bpidChartSavedTable    = <?php echo wp_json_encode( $chart_data_table ); ?>;
	var bpidChartSavedDataMode = <?php echo wp_json_encode( $chart_data_mode ); ?>;
	var bpidChartSavedView     = <?php echo wp_json_encode( $chart_view ); ?>;
</script>
