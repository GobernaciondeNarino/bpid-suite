<?php
/**
 * Filter rendering template.
 *
 * @package BPID_Suite
 *
 * Expected variables:
 * @var int    $filter_id   Unique filter identifier.
 * @var array  $columns     Array of column definitions (key => label).
 * @var array  $types       Array of column types keyed by column key.
 * @var int    $per_page    Results per page.
 * @var bool   $show_export Whether to show the CSV export button.
 * @var string $extra_class Additional CSS class(es).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$extra_class = isset( $extra_class ) ? $extra_class : '';
$per_page    = isset( $per_page ) ? absint( $per_page ) : 25;
$show_export = isset( $show_export ) ? (bool) $show_export : false;
?>
<div
	class="bpid-filter-container <?php echo esc_attr( $extra_class ); ?>"
	id="bpid-filter-<?php echo esc_attr( $filter_id ); ?>"
>
	<form class="bpid-filter-form" data-filter-id="<?php echo esc_attr( $filter_id ); ?>" onsubmit="return false;">
		<div class="bpid-filter-fields">
			<?php foreach ( $columns as $col_key => $col_label ) :
				$type = isset( $types[ $col_key ] ) ? $types[ $col_key ] : 'text';
				$field_id = 'bpid-filter-' . esc_attr( $filter_id ) . '-' . esc_attr( $col_key );
			?>
				<div class="bpid-filter-field bpid-filter-field--<?php echo esc_attr( $type ); ?>">
					<label for="<?php echo esc_attr( $field_id ); ?>">
						<?php echo esc_html( $col_label ); ?>
					</label>

					<?php if ( 'text' === $type ) : ?>
						<input
							type="text"
							id="<?php echo esc_attr( $field_id ); ?>"
							name="<?php echo esc_attr( $col_key ); ?>"
							class="bpid-filter-input"
							placeholder="<?php echo esc_attr( sprintf( __( 'Filtrar por %s', 'bpid-suite' ), $col_label ) ); ?>"
						/>

					<?php elseif ( 'select' === $type ) : ?>
						<select
							id="<?php echo esc_attr( $field_id ); ?>"
							name="<?php echo esc_attr( $col_key ); ?>"
							class="bpid-filter-select"
						>
							<option value=""><?php echo esc_html( sprintf( __( 'Todos (%s)', 'bpid-suite' ), $col_label ) ); ?></option>
							<?php
							/**
							 * Options are loaded dynamically from the JSON config data by JS.
							 */
							?>
						</select>

					<?php elseif ( 'range_number' === $type ) : ?>
						<div class="bpid-filter-range">
							<input
								type="number"
								id="<?php echo esc_attr( $field_id ); ?>-min"
								name="<?php echo esc_attr( $col_key ); ?>_min"
								class="bpid-filter-input bpid-filter-range-min"
								placeholder="<?php esc_attr_e( 'Mín', 'bpid-suite' ); ?>"
							/>
							<span class="bpid-filter-range-separator">&ndash;</span>
							<input
								type="number"
								id="<?php echo esc_attr( $field_id ); ?>-max"
								name="<?php echo esc_attr( $col_key ); ?>_max"
								class="bpid-filter-input bpid-filter-range-max"
								placeholder="<?php esc_attr_e( 'Máx', 'bpid-suite' ); ?>"
							/>
						</div>

					<?php elseif ( 'range_date' === $type ) : ?>
						<div class="bpid-filter-range">
							<input
								type="date"
								id="<?php echo esc_attr( $field_id ); ?>-min"
								name="<?php echo esc_attr( $col_key ); ?>_min"
								class="bpid-filter-input bpid-filter-date-min"
							/>
							<span class="bpid-filter-range-separator">&ndash;</span>
							<input
								type="date"
								id="<?php echo esc_attr( $field_id ); ?>-max"
								name="<?php echo esc_attr( $col_key ); ?>_max"
								class="bpid-filter-input bpid-filter-date-max"
							/>
						</div>

					<?php elseif ( 'checkbox' === $type ) : ?>
						<div
							class="bpid-filter-checkboxes"
							id="<?php echo esc_attr( $field_id ); ?>"
							data-column="<?php echo esc_attr( $col_key ); ?>"
						>
							<?php
							/**
							 * Checkbox options are loaded dynamically from the JSON config data by JS.
							 */
							?>
						</div>

					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="bpid-filter-actions">
			<button type="submit" class="bpid-filter-btn bpid-filter-btn--search">
				<?php esc_html_e( 'Buscar', 'bpid-suite' ); ?>
			</button>
			<button type="button" class="bpid-filter-btn bpid-filter-btn--clear">
				<?php esc_html_e( 'Limpiar', 'bpid-suite' ); ?>
			</button>
		</div>
	</form>

	<div class="bpid-filter-results" id="bpid-filter-results-<?php echo esc_attr( $filter_id ); ?>">
		<table class="bpid-filter-table">
			<thead></thead>
			<tbody></tbody>
		</table>
	</div>

	<div class="bpid-filter-pagination" id="bpid-filter-pagination-<?php echo esc_attr( $filter_id ); ?>"></div>

	<?php if ( $show_export ) : ?>
		<div class="bpid-filter-export">
			<button type="button" class="bpid-filter-btn bpid-filter-btn--export" data-filter-id="<?php echo esc_attr( $filter_id ); ?>">
				<?php esc_html_e( 'Exportar CSV', 'bpid-suite' ); ?>
			</button>
		</div>
	<?php endif; ?>
</div>

<script type="application/json" id="bpid-filter-config-<?php echo esc_attr( $filter_id ); ?>">
<?php
echo wp_json_encode(
	array(
		'filter_id'  => $filter_id,
		'columns'    => $columns,
		'types'      => $types,
		'per_page'   => $per_page,
		'show_export' => $show_export,
	),
	JSON_HEX_TAG | JSON_HEX_AMP
);
?>
</script>
