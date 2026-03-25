(function($) {
    'use strict';

    var config = window.bpidSuitePost || {};

    /**
     * Initialize WordPress color pickers on all designated fields.
     */
    function initColorPickers() {
        $('.bpid-color-picker').wpColorPicker({
            change: function() {
                // Allow time for the value to update before refreshing preview
                setTimeout(updateShortcodePreview, 50);
            },
            clear: function() {
                updateShortcodePreview();
            }
        });
    }

    /**
     * Handle "Limpiar cache" (clear cache) button.
     */
    function bindClearCache() {
        $('#bpid-post-clear-cache').on('click', function(e) {
            e.preventDefault();

            var $btn = $(this);
            var $status = $('#bpid-post-cache-status');
            var originalText = $btn.text();

            $btn.prop('disabled', true).text('Limpiando...');
            $status.empty();

            $.post(config.ajaxUrl, {
                action: 'bpid_suite_clear_post_cache',
                nonce: config.nonce,
                post_id: $('#post_ID').val() || 0
            }).done(function(response) {
                if (response.success) {
                    $status.html('<span class="bpid-cache-success">' + (response.data?.message || 'Cache limpiado correctamente') + '</span>');
                } else {
                    $status.html('<span class="bpid-cache-error">' + (response.data?.message || 'Error al limpiar cache') + '</span>');
                }
            }).fail(function() {
                $status.html('<span class="bpid-cache-error">Error de conexion</span>');
            }).always(function() {
                $btn.prop('disabled', false).text(originalText);
                // Auto-hide status message after 5 seconds
                setTimeout(function() { $status.fadeOut(300, function() { $(this).empty().show(); }); }, 5000);
            });
        });
    }

    /**
     * Update the shortcode preview text.
     */
    function updateShortcodePreview() {
        var $preview = $('#bpid-post-shortcode-preview');
        if (!$preview.length) return;

        var postId = $('#post_ID').val() || '0';
        var shortcode = '[bpid_post id="' + postId + '"]';
        $preview.text(shortcode);
    }

    // Initialize on DOM ready
    $(document).ready(function() {
        initColorPickers();
        bindClearCache();
        updateShortcodePreview();

        // Update preview when title changes
        $('#title').on('input', updateShortcodePreview);
    });

})(jQuery);
