(function($) {
    'use strict';

    const config = window.bpidSuiteImport || {};

    // Start Import button
    $('#bpid-start-import').on('click', function() {
        if (!confirm(config.i18n?.confirm || '¿Iniciar importación?')) return;

        const $btn = $(this);
        const $progress = $('#bpid-import-progress');
        const $progressBar = $('#bpid-import-progress-bar');
        const $progressText = $('#bpid-import-progress-text');
        const $cancel = $('#bpid-cancel-import');
        const $results = $('#bpid-import-results');

        $btn.prop('disabled', true);
        $progress.show();
        $cancel.show();
        $results.empty();

        // Start import AJAX
        $.post(config.ajaxUrl, {
            action: 'bpid_suite_start_import',
            nonce: config.nonce
        }).done(function(response) {
            if (response.success) {
                $results.html('<div class="notice notice-success"><p>' +
                    'Insertados: ' + response.data.inserted +
                    ' | Actualizados: ' + response.data.updated +
                    ' | Errores: ' + response.data.errors + '</p></div>');
            } else {
                $results.html('<div class="notice notice-error"><p>' + (response.data?.message || 'Error') + '</p></div>');
            }
        }).fail(function() {
            $results.html('<div class="notice notice-error"><p>Error de conexión</p></div>');
        }).always(function() {
            $btn.prop('disabled', false);
            $progress.hide();
            $cancel.hide();
        });

        // Poll status
        const pollInterval = setInterval(function() {
            $.get(config.ajaxUrl, {
                action: 'bpid_suite_import_status',
                nonce: config.nonce
            }).done(function(response) {
                if (response.success && response.data) {
                    const d = response.data;
                    const pct = d.total > 0 ? Math.round((d.processed / d.total) * 100) : 0;
                    $progressBar.css('width', pct + '%').text(pct + '%');
                    $progressText.text('Procesando ' + d.processed + ' de ' + d.total + ' contratos...');
                    if (d.status === 'completed' || d.status === 'cancelled' || d.status === 'error') {
                        clearInterval(pollInterval);
                    }
                }
            });
        }, 2000);
    });

    // Cancel Import
    $('#bpid-cancel-import').on('click', function() {
        $.post(config.ajaxUrl, {
            action: 'bpid_suite_cancel_import',
            nonce: config.nonce
        });
        $(this).prop('disabled', true).text('Cancelando...');
    });

})(jQuery);
