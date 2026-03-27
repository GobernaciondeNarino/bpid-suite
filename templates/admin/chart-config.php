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
?>

<div class="bpid-chart-config">

	<!-- ================================================================= -->
	<!-- Section A — Chart Type Grid                                       -->
	<!-- ================================================================= -->
	<div class="bpid-section bpid-section-chart-type">
		<h3><?php esc_html_e( 'Tipo de Gr&aacute;fico', 'bpid-suite' ); ?></h3>

		<div class="bpid-chart-type-grid">
			<?php foreach ( $chart_types as $type_key => $type_def ) : ?>
				<label class="bpid-chart-type-card<?php echo $chart_type === $type_key ? ' active' : ''; ?>">
					<input
						type="radio"
						name="chart_type"
						value="<?php echo esc_attr( $type_key ); ?>"
						<?php checked( $chart_type, $type_key ); ?>
						style="display:none;"
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
	</div>

	<!-- ================================================================= -->
	<!-- Section B — Data Source                                            -->
	<!-- ================================================================= -->
	<div class="bpid-section bpid-section-data-source">
		<h3><?php esc_html_e( 'Fuente de Datos', 'bpid-suite' ); ?></h3>

		<!-- Contextual notice — JS toggles visibility based on selected chart type -->
		<div class="notice notice-info inline bpid-chart-type-notice" style="margin:0 0 12px;padding:8px 12px;">
			<p class="bpid-notice-bar-stacked" style="display:none;">
				<?php esc_html_e( 'Barras apiladas: agregue 2 o m&aacute;s valores Y para apilar las series en cada categor&iacute;a del eje X.', 'bpid-suite' ); ?>
			</p>
			<p class="bpid-notice-pie-donut" style="display:none;">
				<?php esc_html_e( 'Pie/Donut: use exactamente 1 valor Y y 1 columna de agrupaci&oacute;n.', 'bpid-suite' ); ?>
			</p>
			<p class="bpid-notice-line-area" style="display:none;">
				<?php esc_html_e( 'L&iacute;neas/&Aacute;rea: cada columna Y genera una serie independiente.', 'bpid-suite' ); ?>
			</p>
			<p class="bpid-notice-default">
				<?php esc_html_e( 'Seleccione la tabla de datos y configure los ejes del gr&aacute;fico.', 'bpid-suite' ); ?>
			</p>
		</div>

		<table class="form-table">
			<!-- Data Table -->
			<tr>
				<th scope="row">
					<label for="chart_data_table"><?php esc_html_e( 'Tabla de datos', 'bpid-suite' ); ?></label>
				</th>
				<td>
					<select name="chart_data_table" id="chart_data_table" class="regular-text">
						<?php if ( $chart_data_table ) : ?>
							<option value="<?php echo esc_attr( $chart_data_table ); ?>" selected>
								<?php echo esc_html( $chart_data_table ); ?>
							</option>
						<?php else : ?>
							<option value=""><?php esc_html_e( '— Cargando tablas... —', 'bpid-suite' ); ?></option>
						<?php endif; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Las tablas disponibles se cargan autom&aacute;ticamente por AJAX.', 'bpid-suite' ); ?></p>
				</td>
			</tr>

			<!-- X Axis Column -->
			<tr>
				<th scope="row">
					<label for="chart_axis_x"><?php esc_html_e( 'Columna Eje X', 'bpid-suite' ); ?></label>
				</th>
				<td>
					<select name="chart_axis_x" id="chart_axis_x" class="regular-text">
						<?php if ( $chart_axis_x ) : ?>
							<option value="<?php echo esc_attr( $chart_axis_x ); ?>" selected>
								<?php echo esc_html( $chart_axis_x ); ?>
							</option>
						<?php else : ?>
							<option value=""><?php esc_html_e( '— Seleccione tabla primero —', 'bpid-suite' ); ?></option>
						<?php endif; ?>
					</select>
				</td>
			</tr>

			<!-- Y Axis Columns -->
			<tr>
				<th scope="row">
					<label><?php esc_html_e( 'Valores Eje Y', 'bpid-suite' ); ?></label>
				</th>
				<td>
					<div id="chart-y-axes-container">
						<!-- Y-axis rows are populated by JavaScript from saved data -->
					</div>
					<button type="button" class="button button-secondary" id="bpid-add-y-axis">
						+ <?php esc_html_e( 'Agregar Valor Y', 'bpid-suite' ); ?>
					</button>
				</td>
			</tr>

			<!-- Aggregation Function -->
			<tr>
				<th scope="row">
					<label for="chart_agg_function"><?php esc_html_e( 'Funci&oacute;n de Agregaci&oacute;n', 'bpid-suite' ); ?></label>
				</th>
				<td>
					<select name="chart_agg_function" id="chart_agg_function">
						<?php foreach ( $agg_functions as $agg_key => $agg_label ) : ?>
							<option value="<?php echo esc_attr( $agg_key ); ?>" <?php selected( $chart_agg, $agg_key ); ?>>
								<?php echo esc_html( $agg_label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
		</table>
	</div>

	<!-- ================================================================= -->
	<!-- Section C — Filters                                               -->
	<!-- ================================================================= -->
	<div class="bpid-section bpid-section-filters">
		<h3><?php esc_html_e( 'Filtros', 'bpid-suite' ); ?></h3>

		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="chart_filter_year"><?php esc_html_e( 'A&ntilde;o', 'bpid-suite' ); ?></label>
				</th>
				<td>
					<input
						type="number"
						name="chart_filter_year"
						id="chart_filter_year"
						value="<?php echo esc_attr( $chart_filter_year ); ?>"
						min="0"
						class="small-text"
					/>
					<p class="description"><?php esc_html_e( '0 o vac&iacute;o = todos los a&ntilde;os.', 'bpid-suite' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="chart_filter_month"><?php esc_html_e( 'Mes', 'bpid-suite' ); ?></label>
				</th>
				<td>
					<input
						type="number"
						name="chart_filter_month"
						id="chart_filter_month"
						value="<?php echo esc_attr( $chart_filter_month ); ?>"
						min="0"
						max="12"
						class="small-text"
					/>
					<p class="description"><?php esc_html_e( '0 o vac&iacute;o = todos los meses.', 'bpid-suite' ); ?></p>
				</td>
			</tr>
		</table>
	</div>

	<!-- ================================================================= -->
	<!-- Section D — Appearance                                            -->
	<!-- ================================================================= -->
	<div class="bpid-section bpid-section-appearance">
		<h3><?php esc_html_e( 'Apariencia', 'bpid-suite' ); ?></h3>

		<table class="form-table">
			<!-- Chart Height -->
			<tr>
				<th scope="row">
					<label for="chart_height"><?php esc_html_e( 'Altura del Gr&aacute;fico', 'bpid-suite' ); ?></label>
				</th>
				<td>
					<input
						type="number"
						name="chart_height"
						id="chart_height"
						value="<?php echo esc_attr( $chart_height ); ?>"
						min="200"
						max="900"
						step="10"
						class="small-text"
					/>
					<span class="description">px</span>
				</td>
			</tr>

			<!-- Y Axis Title -->
			<tr>
				<th scope="row">
					<label for="chart_title_y"><?php esc_html_e( 'T&iacute;tulo Eje Y', 'bpid-suite' ); ?></label>
				</th>
				<td>
					<input
						type="text"
						name="chart_title_y"
						id="chart_title_y"
						value="<?php echo esc_attr( $chart_title_y ); ?>"
						class="regular-text"
						placeholder="<?php esc_attr_e( 'Valor en Pesos Colombianos', 'bpid-suite' ); ?>"
					/>
				</td>
			</tr>

			<!-- X Axis Title -->
			<tr>
				<th scope="row">
					<label for="chart_title_x"><?php esc_html_e( 'T&iacute;tulo Eje X', 'bpid-suite' ); ?></label>
				</th>
				<td>
					<input
						type="text"
						name="chart_title_x"
						id="chart_title_x"
						value="<?php echo esc_attr( $chart_title_x ); ?>"
						class="regular-text"
					/>
				</td>
			</tr>

			<!-- Number Format -->
			<tr>
				<th scope="row">
					<label for="chart_number_format"><?php esc_html_e( 'Formato de N&uacute;meros', 'bpid-suite' ); ?></label>
				</th>
				<td>
					<select name="chart_number_format" id="chart_number_format">
						<?php foreach ( $number_formats as $fmt_key => $fmt_label ) : ?>
							<option value="<?php echo esc_attr( $fmt_key ); ?>" <?php selected( $chart_number_format, $fmt_key ); ?>>
								<?php echo esc_html( $fmt_label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>

			<!-- Color Palette -->
			<tr>
				<th scope="row">
					<label for="chart_color_palette"><?php esc_html_e( 'Paleta de Colores', 'bpid-suite' ); ?></label>
				</th>
				<td>
					<input
						type="text"
						name="chart_color_palette"
						id="chart_color_palette"
						value="<?php echo esc_attr( $chart_color_palette ); ?>"
						class="regular-text"
						placeholder="#2271b1, #d63638, #00a32a, #dba617, #8c5e58"
					/>
					<div id="color-swatches" style="display:flex;gap:4px;margin-top:6px;"></div>
					<p class="description"><?php esc_html_e( 'Colores HEX separados por coma.', 'bpid-suite' ); ?></p>
				</td>
			</tr>

			<!-- Show Legend -->
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Leyenda', 'bpid-suite' ); ?>
				</th>
				<td>
					<label>
						<input
							type="checkbox"
							name="chart_show_legend"
							value="1"
							<?php checked( $chart_show_legend, '1' ); ?>
						/>
						<?php esc_html_e( 'Mostrar leyenda', 'bpid-suite' ); ?>
					</label>
				</td>
			</tr>

			<!-- Show Interactive Timeline -->
			<tr>
				<th scope="row">
					<?php esc_html_e( 'L&iacute;nea de Tiempo', 'bpid-suite' ); ?>
				</th>
				<td>
					<label>
						<input
							type="checkbox"
							name="chart_show_timeline"
							value="1"
							<?php checked( $chart_show_timeline, '1' ); ?>
						/>
						<?php esc_html_e( 'Mostrar l&iacute;nea de tiempo interactiva', 'bpid-suite' ); ?>
					</label>
				</td>
			</tr>
		</table>
	</div>

	<!-- ================================================================= -->
	<!-- Section E — Toolbar                                               -->
	<!-- ================================================================= -->
	<div class="bpid-section bpid-section-toolbar">
		<h3><?php esc_html_e( 'Barra de Herramientas', 'bpid-suite' ); ?></h3>

		<table class="form-table">
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Toolbar', 'bpid-suite' ); ?>
				</th>
				<td>
					<label>
						<input
							type="checkbox"
							name="chart_toolbar_show"
							id="chart_toolbar_show"
							value="1"
							<?php checked( $chart_toolbar_show, '1' ); ?>
						/>
						<?php esc_html_e( 'Mostrar barra de herramientas', 'bpid-suite' ); ?>
					</label>

					<fieldset class="bpid-toolbar-options" style="margin-top:10px;padding-left:20px;">
						<label style="display:block;margin-bottom:4px;">
							<input
								type="checkbox"
								name="chart_toolbar_info"
								value="1"
								<?php checked( $chart_toolbar_info, '1' ); ?>
							/>
							<?php esc_html_e( 'Detalle (Info)', 'bpid-suite' ); ?>
						</label>
						<label style="display:block;margin-bottom:4px;">
							<input
								type="checkbox"
								name="chart_toolbar_share"
								value="1"
								<?php checked( $chart_toolbar_share, '1' ); ?>
							/>
							<?php esc_html_e( 'Compartir', 'bpid-suite' ); ?>
						</label>
						<label style="display:block;margin-bottom:4px;">
							<input
								type="checkbox"
								name="chart_toolbar_data"
								value="1"
								<?php checked( $chart_toolbar_data, '1' ); ?>
							/>
							<?php esc_html_e( 'Ver Datos', 'bpid-suite' ); ?>
						</label>
						<label style="display:block;margin-bottom:4px;">
							<input
								type="checkbox"
								name="chart_toolbar_save_img"
								value="1"
								<?php checked( $chart_toolbar_save_img, '1' ); ?>
							/>
							<?php esc_html_e( 'Guardar Imagen', 'bpid-suite' ); ?>
						</label>
						<label style="display:block;margin-bottom:4px;">
							<input
								type="checkbox"
								name="chart_toolbar_csv"
								value="1"
								<?php checked( $chart_toolbar_csv, '1' ); ?>
							/>
							<?php esc_html_e( 'Descargar CSV', 'bpid-suite' ); ?>
						</label>
					</fieldset>
				</td>
			</tr>
		</table>
	</div>

	<!-- ================================================================= -->
	<!-- Section F — Custom Query (Advanced)                               -->
	<!-- ================================================================= -->
	<div class="bpid-section bpid-section-custom-query">
		<details<?php echo $chart_custom_query ? ' open' : ''; ?>>
			<summary>
				<h3 style="display:inline;"><?php esc_html_e( 'Consulta Personalizada (Avanzado)', 'bpid-suite' ); ?></h3>
			</summary>

			<div style="margin-top:10px;">
				<p style="color:#d63638;font-weight:600;">
					<?php esc_html_e( 'Solo se permiten consultas SELECT. No utilice INSERT, UPDATE, DELETE, DROP ni otras sentencias que modifiquen datos.', 'bpid-suite' ); ?>
				</p>

				<textarea
					name="chart_custom_query"
					id="chart_custom_query"
					rows="6"
					class="large-text code"
					placeholder="SELECT columna_x, SUM(columna_y) FROM tabla WHERE anio = 2025 GROUP BY columna_x"
				><?php echo esc_textarea( $chart_custom_query ); ?></textarea>

				<p class="description">
					<?php esc_html_e( 'Si se proporciona una consulta personalizada, se ignorar&aacute;n las opciones de tabla, ejes y filtros configuradas arriba.', 'bpid-suite' ); ?>
				</p>
			</div>
		</details>
	</div>

	<!-- ================================================================= -->
	<!-- Shortcode Preview                                                 -->
	<!-- ================================================================= -->
	<div class="bpid-section bpid-section-shortcode" style="margin-top:16px;padding:12px;background:#f0f0f1;border-left:4px solid #2271b1;border-radius:2px;">
		<strong><?php esc_html_e( 'Shortcode:', 'bpid-suite' ); ?></strong>
		<code>[bpid_chart id="<?php echo esc_attr( (string) $post->ID ); ?>"]</code>
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
</script>
