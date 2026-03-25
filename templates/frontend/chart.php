<?php
/**
 * Chart rendering template.
 *
 * @package BPID_Suite
 *
 * Expected variables:
 * @var int    $chart_id    Unique chart identifier.
 * @var string $chart_type  Chart type (bar, line, pie, etc.).
 * @var array  $chart_data  Data array to be rendered by JS.
 * @var int    $height      Minimum height in pixels (default 400).
 * @var string $extra_class Additional CSS class(es).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$height      = isset( $height ) ? absint( $height ) : 400;
$extra_class = isset( $extra_class ) ? $extra_class : '';
?>
<div
	class="bpid-chart-container <?php echo esc_attr( $extra_class ); ?>"
	id="bpid-chart-<?php echo esc_attr( $chart_id ); ?>"
	style="min-height:<?php echo esc_attr( $height ); ?>px"
	data-chart-type="<?php echo esc_attr( $chart_type ); ?>"
	data-chart-id="<?php echo esc_attr( $chart_id ); ?>"
>
	<div class="bpid-chart-loading">
		<span class="bpid-spinner" aria-hidden="true"></span>
		<span class="screen-reader-text"><?php esc_html_e( 'Cargando gráfico...', 'bpid-suite' ); ?></span>
	</div>
</div>
<script type="application/json" id="bpid-chart-data-<?php echo esc_attr( $chart_id ); ?>">
<?php echo wp_json_encode( $chart_data, JSON_HEX_TAG | JSON_HEX_AMP ); ?>
</script>
