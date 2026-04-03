<?php
/**
 * Post module visualizer template — Post GRID.
 *
 * Renders the BPID grid/card view of projects with configurable styles.
 *
 * @package BPID_Suite
 * @since   1.5.0
 *
 * Expected variables (set by class-post.php shortcode_render):
 * @var array $atts       Parsed shortcode attributes.
 * @var array $resultado  API result array.
 * @var array $proyectos  Array of grouped project data.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Extract configuration from attributes.
$color_primario    = ! empty( $atts['color_primario'] ) ? $atts['color_primario'] : '#348afb';
$color_fondo       = ! empty( $atts['color_fondo'] ) ? $atts['color_fondo'] : '#fffcf3';
$mostrar_stats     = ! empty( $atts['mostrar_stats'] ) && $atts['mostrar_stats'] !== '0';
$mostrar_buscador  = ! empty( $atts['mostrar_buscador'] ) && $atts['mostrar_buscador'] !== '0';
$mostrar_filtros   = ! empty( $atts['mostrar_filtros'] ) && $atts['mostrar_filtros'] !== '0';
$ocultar_ops       = ! empty( $atts['ocultar_ops'] ) && $atts['ocultar_ops'] !== '0';
$cols              = isset( $atts['cols'] ) ? max( 1, min( 4, absint( $atts['cols'] ) ) ) : 3;
$texto_intro       = isset( $atts['texto_intro'] ) ? $atts['texto_intro'] : '';

// New configurable fields.
$card_title_field  = ! empty( $atts['card_title_field'] ) ? $atts['card_title_field'] : 'nombre_proyecto';
$card_desc_field   = ! empty( $atts['card_desc_field'] ) ? $atts['card_desc_field'] : 'dependencia';
$card_extra_fields = ! empty( $atts['card_extra_fields'] ) && is_array( $atts['card_extra_fields'] ) ? $atts['card_extra_fields'] : [];
$default_image     = ! empty( $atts['default_image'] ) ? $atts['default_image'] : '';
$title_font_size   = ! empty( $atts['title_font_size'] ) ? absint( $atts['title_font_size'] ) : 15;
$title_color       = ! empty( $atts['title_color'] ) ? $atts['title_color'] : '#1d2327';
$desc_font_size    = ! empty( $atts['desc_font_size'] ) ? absint( $atts['desc_font_size'] ) : 13;
$desc_color        = ! empty( $atts['desc_color'] ) ? $atts['desc_color'] : '#646970';
$secondary_color   = ! empty( $atts['secondary_color'] ) ? $atts['secondary_color'] : '#348afb';
$search_border_color  = ! empty( $atts['search_border_color'] ) ? $atts['search_border_color'] : '#dcdcde';
$search_border_radius = isset( $atts['search_border_radius'] ) ? absint( $atts['search_border_radius'] ) : 8;
$search_font_size     = ! empty( $atts['search_font_size'] ) ? absint( $atts['search_font_size'] ) : 14;
$search_bg_color      = ! empty( $atts['search_bg_color'] ) ? $atts['search_bg_color'] : '#ffffff';
$stats_fields         = ! empty( $atts['stats_fields'] ) && is_array( $atts['stats_fields'] ) ? $atts['stats_fields'] : [];
$accordion_show_metas     = ! isset( $atts['accordion_show_metas'] ) || $atts['accordion_show_metas'] !== '0';
$accordion_show_ods       = ! isset( $atts['accordion_show_ods'] ) || $atts['accordion_show_ods'] !== '0';
$accordion_show_contratos = ! isset( $atts['accordion_show_contratos'] ) || $atts['accordion_show_contratos'] !== '0';
$accordion_contrato_fields = ! empty( $atts['accordion_contrato_fields'] ) && is_array( $atts['accordion_contrato_fields'] ) ? $atts['accordion_contrato_fields'] : [];

// Field mapping: DB column names → API project keys.
$field_map = [
	'nombre_proyecto'       => 'nombreProyecto',
	'numero_proyecto'       => 'numeroProyecto',
	'dependencia'           => 'dependenciaProyecto',
	'entidad_ejecutora'     => 'entidadEjecutora',
	'valor_proyecto'        => 'valorProyecto',
	'valor_contrato'        => 'valorContrato',
	'avance_fisico'         => 'procentajeAvanceFisico',
	'es_ops'                => 'esOpsEjecContractual',
	'municipios'            => 'municipios',
	'beneficiarios'         => 'beneficiarios',
	'numero_contrato'       => 'numeroContrato',
	'objeto_contrato'       => 'objetoContrato',
	'descripcion_contrato'  => 'descripcion',
	'metas'                 => 'metasProyecto',
	'odss'                  => 'odssProyecto',
];

// Pre-compute aggregate data from the grouped project structure.
$total_proyectos     = is_array( $proyectos ) ? count( $proyectos ) : 0;
$total_actividades   = 0;
$total_beneficiarios = 0;
$total_valor         = 0.0;
$dependencias_unicas = [];
$municipios_unicos   = [];
$ods_unicos          = [];
$metas_unicas        = [];

if ( is_array( $proyectos ) ) {
	foreach ( $proyectos as $proyecto ) {
		$total_valor += (float) ( $proyecto['valorProyecto'] ?? 0 );
		$contratos = $proyecto['contratosProyecto'] ?? [];
		if ( is_array( $contratos ) ) {
			$total_actividades += count( $contratos );
			foreach ( $contratos as $contrato ) {
				$muns = $contrato['municipiosEjecContractual'] ?? [];
				if ( is_array( $muns ) ) {
					foreach ( $muns as $mun ) {
						$nombre = '';
						$pob = 0;
						if ( is_array( $mun ) ) {
							$nombre = $mun['nombre'] ?? '';
							$pob = absint( $mun['poblacion_beneficiada'] ?? 0 );
						} elseif ( is_string( $mun ) ) {
							$nombre = $mun;
						}
						$total_beneficiarios += $pob;
						if ( $nombre ) {
							$municipios_unicos[ $nombre ] = $nombre;
						}
					}
				}
			}
		}
		$dep_name = is_string( $proyecto['dependenciaProyecto'] ?? null ) ? $proyecto['dependenciaProyecto'] : '';
		if ( $dep_name ) {
			$dependencias_unicas[ $dep_name ] = $dep_name;
		}
		$odss = $proyecto['odssProyecto'] ?? [];
		if ( is_array( $odss ) ) {
			foreach ( $odss as $ods_item ) {
				$ods_name = is_string( $ods_item ) ? $ods_item : '';
				if ( $ods_name ) {
					$ods_unicos[ $ods_name ] = $ods_name;
				}
			}
		}
		$metas = $proyecto['metasProyecto'] ?? [];
		if ( is_array( $metas ) ) {
			foreach ( $metas as $meta_item ) {
				$meta_name = is_string( $meta_item ) ? $meta_item : wp_json_encode( $meta_item );
				$metas_unicas[ $meta_name ] = $meta_name;
			}
		}
	}
}

sort( $municipios_unicos );
sort( $dependencias_unicas );
sort( $ods_unicos );

/**
 * Compute a stat value based on aggregation type.
 */
function bpid_compute_stat( string $field_key, string $aggregation, array $proyectos, array $field_map ): string {
	$api_key = $field_map[ $field_key ] ?? $field_key;
	$values = [];

	foreach ( $proyectos as $p ) {
		$val = $p[ $api_key ] ?? null;
		if ( $val !== null ) {
			$values[] = (float) $val;
		}
	}

	if ( empty( $values ) ) {
		return '0';
	}

	switch ( strtoupper( $aggregation ) ) {
		case 'SUM':
			return number_format( array_sum( $values ), 0, ',', '.' );
		case 'AVG':
			return number_format( array_sum( $values ) / count( $values ), 1, ',', '.' );
		case 'COUNT':
			return number_format( count( $values ), 0, ',', '.' );
		case 'MAX':
			return number_format( max( $values ), 0, ',', '.' );
		case 'MIN':
			return number_format( min( $values ), 0, ',', '.' );
		default:
			return number_format( count( $values ), 0, ',', '.' );
	}
}

/**
 * Get a project field value for card display.
 */
function bpid_get_project_field( array $proyecto, string $field_key, array $field_map ): string {
	$api_key = $field_map[ $field_key ] ?? $field_key;
	$val = $proyecto[ $api_key ] ?? '';
	if ( is_array( $val ) ) {
		return wp_json_encode( $val );
	}
	if ( ( $field_key === 'valor_contrato' || $field_key === 'valor_proyecto' ) && is_numeric( $val ) ) {
		return '$' . number_format( (float) $val, 0, ',', '.' );
	}
	return (string) $val;
}
?>

<style>
:root {
	--bpid-color-primario: <?php echo esc_attr( $color_primario ); ?>;
	--bpid-color-fondo: <?php echo esc_attr( $color_fondo ); ?>;
	--bpid-color-secondary: <?php echo esc_attr( $secondary_color ); ?>;
	--bpid-title-color: <?php echo esc_attr( $title_color ); ?>;
	--bpid-desc-color: <?php echo esc_attr( $desc_color ); ?>;
	--bpid-title-font-size: <?php echo esc_attr( $title_font_size ); ?>px;
	--bpid-desc-font-size: <?php echo esc_attr( $desc_font_size ); ?>px;
}
</style>

<div class="bpid-grid-container" style="background-color:<?php echo esc_attr( $color_fondo ); ?>">

	<?php if ( ! empty( $resultado['error'] ) ) : ?>
		<div class="bpid-grid-error">
			<p><?php echo esc_html( $resultado['error'] ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( $mostrar_buscador ) : ?>
		<div class="bpid-grid-search">
			<input
				type="text"
				id="bpid-grid-search-general"
				class="bpid-grid-search-input"
				placeholder="<?php esc_attr_e( 'Buscar proyectos...', 'bpid-suite' ); ?>"
				style="border-color:<?php echo esc_attr( $search_border_color ); ?>;border-radius:<?php echo esc_attr( $search_border_radius ); ?>px;font-size:<?php echo esc_attr( $search_font_size ); ?>px;background-color:<?php echo esc_attr( $search_bg_color ); ?>;"
			/>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $texto_intro ) ) : ?>
		<p class="bpid-grid-intro"><?php echo esc_html( $texto_intro ); ?></p>
	<?php endif; ?>

	<?php if ( $mostrar_stats ) : ?>
		<div class="bpid-grid-stats">
			<?php if ( ! empty( $stats_fields ) ) : ?>
				<?php foreach ( $stats_fields as $sf ) :
					$sf_field = $sf['field'] ?? '';
					$sf_label = $sf['label'] ?? '';
					$sf_agg   = $sf['aggregation'] ?? 'COUNT';
					if ( ! $sf_field ) continue;
					$stat_value = bpid_compute_stat( $sf_field, $sf_agg, $proyectos, $field_map );
					$stat_label = $sf_label ?: ( $sf_agg . ' ' . $sf_field );
				?>
					<div class="bpid-grid-stat-card">
						<span class="bpid-grid-stat-value" style="color:<?php echo esc_attr( $secondary_color ); ?>"><?php echo esc_html( $stat_value ); ?></span>
						<span class="bpid-grid-stat-label"><?php echo esc_html( $stat_label ); ?></span>
					</div>
				<?php endforeach; ?>
			<?php else : ?>
				<div class="bpid-grid-stat-card">
					<span class="bpid-grid-stat-value" style="color:<?php echo esc_attr( $secondary_color ); ?>"><?php echo esc_html( number_format( $total_proyectos, 0, ',', '.' ) ); ?></span>
					<span class="bpid-grid-stat-label"><?php esc_html_e( 'Total Proyectos', 'bpid-suite' ); ?></span>
				</div>
				<div class="bpid-grid-stat-card">
					<span class="bpid-grid-stat-value" style="color:<?php echo esc_attr( $secondary_color ); ?>"><?php echo esc_html( number_format( $total_actividades, 0, ',', '.' ) ); ?></span>
					<span class="bpid-grid-stat-label"><?php esc_html_e( 'Total Actividades', 'bpid-suite' ); ?></span>
				</div>
				<div class="bpid-grid-stat-card">
					<span class="bpid-grid-stat-value" style="color:<?php echo esc_attr( $secondary_color ); ?>"><?php echo esc_html( number_format( $total_beneficiarios, 0, ',', '.' ) ); ?></span>
					<span class="bpid-grid-stat-label"><?php esc_html_e( 'Beneficiarios', 'bpid-suite' ); ?></span>
				</div>
				<div class="bpid-grid-stat-card">
					<span class="bpid-grid-stat-value" style="color:<?php echo esc_attr( $secondary_color ); ?>"><?php echo esc_html( (string) count( $dependencias_unicas ) ); ?></span>
					<span class="bpid-grid-stat-label"><?php esc_html_e( 'Dependencias', 'bpid-suite' ); ?></span>
				</div>
				<div class="bpid-grid-stat-card">
					<span class="bpid-grid-stat-value" style="color:<?php echo esc_attr( $secondary_color ); ?>"><?php echo esc_html( (string) count( $metas_unicas ) ); ?></span>
					<span class="bpid-grid-stat-label"><?php esc_html_e( 'Metas Totales', 'bpid-suite' ); ?></span>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( $mostrar_filtros ) : ?>
		<div class="bpid-grid-filters">
			<select id="bpid-grid-filter-dependencia" class="bpid-grid-filter-select">
				<option value=""><?php esc_html_e( 'Todas las dependencias', 'bpid-suite' ); ?></option>
				<?php foreach ( $dependencias_unicas as $dep ) : ?>
					<option value="<?php echo esc_attr( $dep ); ?>"><?php echo esc_html( $dep ); ?></option>
				<?php endforeach; ?>
			</select>

			<select id="bpid-grid-filter-municipio" class="bpid-grid-filter-select">
				<option value=""><?php esc_html_e( 'Todos los municipios', 'bpid-suite' ); ?></option>
				<?php foreach ( $municipios_unicos as $mun ) : ?>
					<option value="<?php echo esc_attr( $mun ); ?>"><?php echo esc_html( $mun ); ?></option>
				<?php endforeach; ?>
			</select>

			<select id="bpid-grid-filter-ods" class="bpid-grid-filter-select">
				<option value=""><?php esc_html_e( 'Todos los ODS', 'bpid-suite' ); ?></option>
				<?php foreach ( $ods_unicos as $ods ) : ?>
					<option value="<?php echo esc_attr( $ods ); ?>"><?php echo esc_html( $ods ); ?></option>
				<?php endforeach; ?>
			</select>

			<button type="button" id="bpid-grid-clear-filters" class="bpid-grid-btn bpid-grid-btn--clear">
				<?php esc_html_e( 'Limpiar Filtros', 'bpid-suite' ); ?>
			</button>
		</div>
	<?php endif; ?>

	<?php if ( is_array( $proyectos ) && count( $proyectos ) > 0 ) : ?>
	<div class="bpid-grid-controls">
		<h3><?php esc_html_e( 'Exportar Informes de Gestión', 'bpid-suite' ); ?></h3>
		<div class="bpid-grid-export-btns">
			<button type="button" class="bpid-grid-btn bpid-grid-btn--word" id="bpid-grid-export-word">
				<?php esc_html_e( 'Exportar a Word', 'bpid-suite' ); ?>
			</button>
			<button type="button" class="bpid-grid-btn bpid-grid-btn--excel" id="bpid-grid-export-excel">
				<?php esc_html_e( 'Exportar a Excel', 'bpid-suite' ); ?>
			</button>
		</div>
		<div id="bpid-grid-export-status" class="bpid-grid-export-status" style="display:none;"></div>
	</div>
	<?php endif; ?>

	<div id="bpid-grid-proyectos" class="bpid-grid-proyectos" style="display:grid;grid-template-columns:repeat(<?php echo esc_attr( (string) $cols ); ?>, 1fr);gap:20px;">
		<?php if ( is_array( $proyectos ) ) : ?>
			<?php foreach ( $proyectos as $index => $proyecto ) :
				$dep_attr = is_string( $proyecto['dependenciaProyecto'] ?? null )
					? $proyecto['dependenciaProyecto']
					: '';

				$mun_names = [];
				$contratos_p = $proyecto['contratosProyecto'] ?? [];
				if ( is_array( $contratos_p ) ) {
					foreach ( $contratos_p as $c ) {
						$c_muns = $c['municipiosEjecContractual'] ?? [];
						if ( is_array( $c_muns ) ) {
							foreach ( $c_muns as $m ) {
								$nm = is_array( $m ) ? ( $m['nombre'] ?? '' ) : (string) $m;
								if ( $nm && ! in_array( $nm, $mun_names, true ) ) {
									$mun_names[] = $nm;
								}
							}
						}
					}
				}

				$ods_names = [];
				$odss_p = $proyecto['odssProyecto'] ?? [];
				if ( is_array( $odss_p ) ) {
					foreach ( $odss_p as $o ) {
						if ( is_string( $o ) && $o ) {
							$ods_names[] = $o;
						}
					}
				}

				// Find first image from any contract, fall back to default image.
				$card_image = '';
				if ( is_array( $contratos_p ) ) {
					foreach ( $contratos_p as $contrato ) {
						$imgs = $contrato['imagenesEjecContractual'] ?? [];
						if ( is_array( $imgs ) ) {
							foreach ( $imgs as $img ) {
								$url = is_string( $img ) ? $img : ( $img['url'] ?? '' );
								if ( $url && str_starts_with( $url, 'http' ) ) {
									$card_image = $url;
									break 2;
								}
							}
						}
					}
				}
				if ( ! $card_image && $default_image ) {
					$card_image = $default_image;
				}

				// Get card title and description from configured fields.
				$card_title = bpid_get_project_field( $proyecto, $card_title_field, $field_map );
				$card_desc  = bpid_get_project_field( $proyecto, $card_desc_field, $field_map );

				// Search text for JS filtering.
				$search_text = mb_strtolower(
					( $proyecto['numeroProyecto'] ?? '' ) . ' ' .
					( $proyecto['nombreProyecto'] ?? '' ) . ' ' .
					$dep_attr . ' ' .
					implode( ' ', $mun_names ) . ' ' .
					implode( ' ', $ods_names )
				);

				$num_actividades = is_array( $contratos_p ) ? count( $contratos_p ) : 0;

				$beneficiarios_proyecto = 0;
				if ( is_array( $contratos_p ) ) {
					foreach ( $contratos_p as $c ) {
						$c_muns = $c['municipiosEjecContractual'] ?? [];
						if ( is_array( $c_muns ) ) {
							foreach ( $c_muns as $m ) {
								if ( is_array( $m ) && isset( $m['poblacion_beneficiada'] ) ) {
									$beneficiarios_proyecto += absint( $m['poblacion_beneficiada'] );
								}
							}
						}
					}
				}

				$first_municipio = ! empty( $mun_names ) ? $mun_names[0] : '';
			?>
				<div
					class="bpid-grid-card"
					data-index="<?php echo esc_attr( (string) $index ); ?>"
					data-dependencia="<?php echo esc_attr( $dep_attr ); ?>"
					data-municipios="<?php echo esc_attr( implode( '|', $mun_names ) ); ?>"
					data-odss="<?php echo esc_attr( implode( '|', $ods_names ) ); ?>"
					data-search="<?php echo esc_attr( $search_text ); ?>"
				>
					<div class="bpid-grid-card-image">
						<?php if ( $card_image ) : ?>
							<img
								src="<?php echo esc_url( $card_image ); ?>"
								alt="<?php echo esc_attr( $card_title ); ?>"
								loading="lazy"
							/>
						<?php else : ?>
							<svg class="bpid-grid-card-placeholder" viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
								<rect width="400" height="300" fill="#e0e0e0"/>
								<text x="200" y="150" text-anchor="middle" dominant-baseline="central" fill="#9e9e9e" font-size="18"><?php esc_html_e( 'Sin imagen', 'bpid-suite' ); ?></text>
							</svg>
						<?php endif; ?>

						<?php if ( $first_municipio ) : ?>
							<span class="bpid-grid-card-badge"><?php echo esc_html( $first_municipio ); ?></span>
						<?php endif; ?>
					</div>

					<div class="bpid-grid-card-body">
						<h3 class="bpid-grid-card-title" style="font-size:<?php echo esc_attr( $title_font_size ); ?>px;color:<?php echo esc_attr( $title_color ); ?>;">
							<?php echo esc_html( $card_title ?: __( 'Sin nombre', 'bpid-suite' ) ); ?>
						</h3>

						<p class="bpid-grid-card-desc" style="font-size:<?php echo esc_attr( $desc_font_size ); ?>px;color:<?php echo esc_attr( $desc_color ); ?>;">
							<?php echo esc_html( $card_desc ); ?>
						</p>

						<?php if ( ! empty( $card_extra_fields ) ) : ?>
							<div class="bpid-grid-card-extras">
								<?php foreach ( $card_extra_fields as $ef ) :
									$ef_field = $ef['field'] ?? '';
									$ef_label = $ef['label'] ?? '';
									if ( ! $ef_field ) continue;
									$ef_value = bpid_get_project_field( $proyecto, $ef_field, $field_map );
									$ef_display_label = $ef_label ?: $ef_field;
								?>
									<p class="bpid-grid-card-extra" style="color:<?php echo esc_attr( $desc_color ); ?>;">
										<strong><?php echo esc_html( $ef_display_label ); ?>:</strong>
										<span style="color:<?php echo esc_attr( $secondary_color ); ?>;"><?php echo esc_html( $ef_value ); ?></span>
									</p>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>

					<div class="bpid-grid-card-footer">
						<span class="bpid-grid-card-actividades">
							<?php
							echo esc_html( sprintf(
								_n( '%s actividad', '%s actividades', $num_actividades, 'bpid-suite' ),
								number_format( $num_actividades, 0, ',', '.' )
							) );
							?>
						</span>
						<button
							type="button"
							class="bpid-grid-card-details-btn"
							onclick="bpidGridOpenModal(<?php echo esc_attr( (string) $index ); ?>)"
						>
							<?php esc_html_e( 'Ver detalles', 'bpid-suite' ); ?> &rarr;
						</button>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>

	<div id="bpid-grid-no-results-message" class="bpid-grid-no-results" style="display:none;">
		<p><?php esc_html_e( 'No se encontraron proyectos que coincidan con los criterios de búsqueda.', 'bpid-suite' ); ?></p>
	</div>

	<div id="bpid-grid-modal" class="bpid-grid-modal" style="display:none;" data-ocultar-ops="<?php echo esc_attr( $ocultar_ops ? '1' : '0' ); ?>">
		<div class="bpid-grid-modal-overlay"></div>
		<div class="bpid-grid-modal-content">
			<span class="bpid-grid-modal-close" role="button" tabindex="0" aria-label="<?php esc_attr_e( 'Cerrar', 'bpid-suite' ); ?>">&times;</span>
			<div id="bpid-grid-modal-body" class="bpid-grid-modal-body"></div>
		</div>
	</div>

</div>

<script type="application/json" id="bpid-grid-data">
<?php echo wp_json_encode( is_array( $proyectos ) ? $proyectos : [], JSON_HEX_TAG | JSON_HEX_AMP ); ?>
</script>

<script type="application/json" id="bpid-grid-config">
<?php echo wp_json_encode( [
	'ajaxUrl'                => admin_url( 'admin-ajax.php' ),
	'nonce'                  => wp_create_nonce( 'bpid_suite_export_nonce' ),
	'accordionShowMetas'     => $accordion_show_metas,
	'accordionShowOds'       => $accordion_show_ods,
	'accordionShowContratos' => $accordion_show_contratos,
	'accordionContratoFields' => $accordion_contrato_fields,
	'cardTitleField'         => $card_title_field,
	'cardDescField'          => $card_desc_field,
], JSON_HEX_TAG | JSON_HEX_AMP ); ?>
</script>
