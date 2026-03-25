<?php
/**
 * Post module visualizer template.
 *
 * Renders the BPID grid/card view of projects.
 *
 * @package BPID_Suite
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

// Pre-compute aggregate data from the grouped project structure.
$total_proyectos     = is_array( $proyectos ) ? count( $proyectos ) : 0;
$total_actividades   = 0;
$total_beneficiarios = 0;
$dependencias_unicas = [];
$municipios_unicos   = [];
$ods_unicos          = [];
$metas_unicas        = [];

if ( is_array( $proyectos ) ) {
	foreach ( $proyectos as $proyecto ) {
		// Count actividades (contratos).
		$contratos = $proyecto['contratosProyecto'] ?? [];
		if ( is_array( $contratos ) ) {
			$total_actividades += count( $contratos );

			// Collect municipios and beneficiarios from contracts.
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

		// Unique dependencias.
		$dep_name = is_string( $proyecto['dependenciaProyecto'] ?? null )
			? $proyecto['dependenciaProyecto']
			: '';
		if ( $dep_name ) {
			$dependencias_unicas[ $dep_name ] = $dep_name;
		}

		// Unique ODS from odssProyecto.
		$odss = $proyecto['odssProyecto'] ?? [];
		if ( is_array( $odss ) ) {
			foreach ( $odss as $ods_item ) {
				$ods_name = is_string( $ods_item ) ? $ods_item : '';
				if ( $ods_name ) {
					$ods_unicos[ $ods_name ] = $ods_name;
				}
			}
		}

		// Unique metas from metasProyecto.
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
?>

<style>
:root {
	--bpid-color-primario: <?php echo esc_attr( $color_primario ); ?>;
	--bpid-color-fondo: <?php echo esc_attr( $color_fondo ); ?>;
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
			/>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $texto_intro ) ) : ?>
		<p class="bpid-grid-intro"><?php echo esc_html( $texto_intro ); ?></p>
	<?php endif; ?>

	<?php if ( $mostrar_stats ) : ?>
		<div class="bpid-grid-stats">
			<div class="bpid-grid-stat-card">
				<span class="bpid-grid-stat-value"><?php echo esc_html( number_format( $total_proyectos, 0, ',', '.' ) ); ?></span>
				<span class="bpid-grid-stat-label"><?php esc_html_e( 'Total Proyectos', 'bpid-suite' ); ?></span>
			</div>
			<div class="bpid-grid-stat-card">
				<span class="bpid-grid-stat-value"><?php echo esc_html( number_format( $total_actividades, 0, ',', '.' ) ); ?></span>
				<span class="bpid-grid-stat-label"><?php esc_html_e( 'Total Actividades', 'bpid-suite' ); ?></span>
			</div>
			<div class="bpid-grid-stat-card">
				<span class="bpid-grid-stat-value"><?php echo esc_html( number_format( $total_beneficiarios, 0, ',', '.' ) ); ?></span>
				<span class="bpid-grid-stat-label"><?php esc_html_e( 'Beneficiarios', 'bpid-suite' ); ?></span>
			</div>
			<div class="bpid-grid-stat-card">
				<span class="bpid-grid-stat-value"><?php echo esc_html( (string) count( $dependencias_unicas ) ); ?></span>
				<span class="bpid-grid-stat-label"><?php esc_html_e( 'Dependencias', 'bpid-suite' ); ?></span>
			</div>
			<div class="bpid-grid-stat-card">
				<span class="bpid-grid-stat-value"><?php echo esc_html( (string) count( $metas_unicas ) ); ?></span>
				<span class="bpid-grid-stat-label"><?php esc_html_e( 'Metas Totales', 'bpid-suite' ); ?></span>
			</div>
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

	<div id="bpid-grid-proyectos" class="bpid-grid-proyectos" style="display:grid;grid-template-columns:repeat(<?php echo esc_attr( (string) $cols ); ?>, 1fr);gap:20px;">
		<?php if ( is_array( $proyectos ) ) : ?>
			<?php foreach ( $proyectos as $index => $proyecto ) :
				// Build data attributes from the agrupar_por_proyecto structure.
				$dep_attr = is_string( $proyecto['dependenciaProyecto'] ?? null )
					? $proyecto['dependenciaProyecto']
					: '';

				// Collect municipio names from all contracts.
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

				// ODS names from odssProyecto.
				$ods_names = [];
				$odss_p = $proyecto['odssProyecto'] ?? [];
				if ( is_array( $odss_p ) ) {
					foreach ( $odss_p as $o ) {
						if ( is_string( $o ) && $o ) {
							$ods_names[] = $o;
						}
					}
				}

				// Find first image from any contract.
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

				// Search text for JS filtering.
				$search_text = mb_strtolower(
					( $proyecto['numeroProyecto'] ?? '' ) . ' ' .
					( $proyecto['nombreProyecto'] ?? '' ) . ' ' .
					$dep_attr . ' ' .
					implode( ' ', $mun_names ) . ' ' .
					implode( ' ', $ods_names )
				);

				// Count actividades.
				$num_actividades = is_array( $contratos_p ) ? count( $contratos_p ) : 0;

				// Count beneficiarios for this project from contracts.
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
								alt="<?php echo esc_attr( $proyecto['nombreProyecto'] ?? '' ); ?>"
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
						<h3 class="bpid-grid-card-title">
							<?php echo esc_html( $proyecto['nombreProyecto'] ?? __( 'Sin nombre', 'bpid-suite' ) ); ?>
						</h3>

						<p class="bpid-grid-card-bpin">
							<?php esc_html_e( 'BPIN:', 'bpid-suite' ); ?>
							<strong><?php echo esc_html( $proyecto['numeroProyecto'] ?? '—' ); ?></strong>
						</p>

						<p class="bpid-grid-card-beneficiarios">
							<?php esc_html_e( 'Beneficiarios:', 'bpid-suite' ); ?>
							<strong><?php echo esc_html( number_format( $beneficiarios_proyecto, 0, ',', '.' ) ); ?></strong>
						</p>
					</div>

					<div class="bpid-grid-card-footer">
						<span class="bpid-grid-card-actividades">
							<?php
							echo esc_html( sprintf(
								/* translators: %s: number of activities */
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
