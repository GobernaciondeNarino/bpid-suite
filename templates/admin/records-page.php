<?php
/**
 * Admin template: Records viewer.
 *
 * @package BPID_Suite
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$db       = BPID_Suite_Database::get_instance();
$page     = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
$per_page = isset($_GET['per_page']) ? absint($_GET['per_page']) : 20;

if ($page < 1) {
    $page = 1;
}
if ($per_page < 1 || $per_page > 100) {
    $per_page = 20;
}

$result = $db->get_contratos([
    'page'     => $page,
    'per_page' => $per_page,
    'orderby'  => 'id',
    'order'    => 'DESC',
]);

$records     = $result['data'];
$total       = $result['total'];
$total_pages = $result['pages'];
$base_url    = admin_url('admin.php?page=bpid-suite-records');
?>

<div class="bpid-admin-wrap">

    <div class="bpid-page-header">
        <div class="bpid-page-header-content">
            <div>
                <h1 class="bpid-page-title"><?php echo esc_html__('Registros', 'bpid-suite'); ?></h1>
                <p class="bpid-page-subtitle">
                    <?php
                    echo esc_html(sprintf(
                        /* translators: 1: total records, 2: current page, 3: total pages */
                        __('Mostrando página %2$d de %3$d (%1$s registros en total)', 'bpid-suite'),
                        number_format_i18n($total),
                        $page,
                        $total_pages > 0 ? $total_pages : 1
                    ));
                    ?>
                </p>
            </div>
        </div>
    </div>

    <div class="bpid-card">
        <div class="bpid-card-body" style="padding:0;">
            <?php if (!empty($records)) : ?>
                <table class="bpid-records-table">
                    <thead>
                        <tr>
                            <th style="width:60px;"><?php echo esc_html__('ID', 'bpid-suite'); ?></th>
                            <th><?php echo esc_html__('Dependencia', 'bpid-suite'); ?></th>
                            <th style="width:130px;"><?php echo esc_html__('Num. Proyecto', 'bpid-suite'); ?></th>
                            <th><?php echo esc_html__('Nombre Proyecto', 'bpid-suite'); ?></th>
                            <th style="width:140px;"><?php echo esc_html__('Valor', 'bpid-suite'); ?></th>
                            <th style="width:90px;"><?php echo esc_html__('Avance %', 'bpid-suite'); ?></th>
                            <th style="width:70px;"><?php echo esc_html__('Es OPS', 'bpid-suite'); ?></th>
                            <th style="width:140px;"><?php echo esc_html__('Fecha Import.', 'bpid-suite'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $row) : ?>
                            <tr>
                                <td><?php echo esc_html((string) ($row['id'] ?? '')); ?></td>
                                <td><?php echo esc_html((string) ($row['dependencia'] ?? '')); ?></td>
                                <td><?php echo esc_html((string) ($row['numero_proyecto'] ?? '')); ?></td>
                                <td><?php echo esc_html((string) ($row['nombre_proyecto'] ?? '')); ?></td>
                                <td style="text-align:right;">
                                    <?php
                                    $valor = isset($row['valor']) ? (float) $row['valor'] : 0;
                                    echo esc_html('$ ' . number_format_i18n($valor, 2));
                                    ?>
                                </td>
                                <td style="text-align:center;">
                                    <?php echo esc_html((string) ($row['avance_fisico'] ?? '0')); ?>%
                                </td>
                                <td style="text-align:center;">
                                    <?php echo esc_html(((int) ($row['es_ops'] ?? 0)) === 1 ? __('Sí', 'bpid-suite') : __('No', 'bpid-suite')); ?>
                                </td>
                                <td><?php echo esc_html((string) ($row['fecha_importacion'] ?? '')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="bpid-empty-message">
                    <?php echo esc_html__('No se encontraron registros.', 'bpid-suite'); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($total_pages > 1) : ?>
        <div class="bpid-pagination">
            <div class="bpid-pagination-info">
                <?php
                echo esc_html(sprintf(
                    /* translators: %s: number of items */
                    _n('%s elemento', '%s elementos', $total, 'bpid-suite'),
                    number_format_i18n($total)
                ));
                ?>
            </div>
            <div class="bpid-pagination-controls">
                <?php if ($page > 1) : ?>
                    <a class="button" href="<?php echo esc_url(add_query_arg('paged', 1, $base_url)); ?>">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                    <a class="button" href="<?php echo esc_url(add_query_arg('paged', $page - 1, $base_url)); ?>">
                        <span aria-hidden="true">&lsaquo;</span>
                    </a>
                <?php else : ?>
                    <span class="button disabled" aria-hidden="true">&laquo;</span>
                    <span class="button disabled" aria-hidden="true">&lsaquo;</span>
                <?php endif; ?>

                <span class="bpid-page-info">
                    <?php echo esc_html($page); ?>
                    <?php echo esc_html__('de', 'bpid-suite'); ?>
                    <?php echo esc_html((string) $total_pages); ?>
                </span>

                <?php if ($page < $total_pages) : ?>
                    <a class="button" href="<?php echo esc_url(add_query_arg('paged', $page + 1, $base_url)); ?>">
                        <span aria-hidden="true">&rsaquo;</span>
                    </a>
                    <a class="button" href="<?php echo esc_url(add_query_arg('paged', $total_pages, $base_url)); ?>">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                <?php else : ?>
                    <span class="button disabled" aria-hidden="true">&rsaquo;</span>
                    <span class="button disabled" aria-hidden="true">&raquo;</span>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
