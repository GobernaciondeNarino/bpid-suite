<?php
/**
 * Chart rendering template (v2.0).
 *
 * @package BPID_Suite
 * @since   2.0.0
 *
 * Expected variables from shortcode_render():
 * @var int    $chart_id     Unique chart post ID.
 * @var string $chart_type   Chart type (bar, line, pie, etc.).
 * @var array  $chart_data   Data array to be rendered by JS.
 * @var array  $chart_config Full configuration array.
 * @var int    $height       Chart height in pixels.
 * @var string $extra_class  Additional CSS class(es).
 * @var string $width        Optional width override.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$height      = isset( $height ) ? absint( $height ) : 400;
$extra_class = isset( $extra_class ) ? $extra_class : '';
$width       = isset( $width ) ? esc_attr( $width ) : '100%';
$json_flags  = JSON_HEX_TAG | JSON_HEX_AMP;
?>
<div
	class="bpid-chart-container <?php echo esc_attr( $extra_class ); ?>"
	id="bpid-chart-<?php echo esc_attr( $chart_id ); ?>"
	style="min-height:<?php echo esc_attr( $height ); ?>px;width:<?php echo $width; ?>"
	data-chart-type="<?php echo esc_attr( $chart_type ); ?>"
	data-chart-id="<?php echo esc_attr( $chart_id ); ?>"
>
	<div class="bpid-chart-loading">
		<span class="bpid-spinner" aria-hidden="true"></span>
		<span class="screen-reader-text"><?php esc_html_e( 'Cargando gráfico...', 'bpid-suite' ); ?></span>
	</div>
</div>
<?php if ( ! empty( $chart_config ) ) : ?>
<script type="application/json" id="bpid-chart-config-<?php echo esc_attr( $chart_id ); ?>">
<?php
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo wp_json_encode( $chart_config, $json_flags );
?>
</script>
<?php endif; ?>
<script type="application/json" id="bpid-chart-data-<?php echo esc_attr( $chart_id ); ?>">
<?php
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo wp_json_encode( $chart_data, $json_flags );
?>
</script>
