(function($) {
    'use strict';

    var $columnsContainer = $('#bpid-filter-columns');
    var $shortcodePreview = $('#bpid-filter-shortcode-preview');

    /**
     * Toggle the field type/operator selector visibility and row state.
     */
    function bindColumnToggles() {
        $columnsContainer.on('change', '.bpid-column-checkbox', function() {
            var $checkbox = $(this);
            var $row = $checkbox.closest('.bpid-column-row');
            var $typeSelector = $row.find('.bpid-column-type-selector');
            var $shortcode = $row.find('.bpid-column-shortcode');

            if ($checkbox.is(':checked')) {
                $row.addClass('bpid-column-row--active');
                $typeSelector.slideDown(200);
                $shortcode.slideDown(200);
                // Load select options via AJAX if type is 'select'
                var $typeSelect = $row.find('.bpid-column-type-select');
                if ($typeSelect.val() === 'select') {
                    loadColumnValues($row);
                }
            } else {
                $row.removeClass('bpid-column-row--active');
                $typeSelector.slideUp(200);
                $shortcode.slideUp(200);
            }
        });
    }

    /**
     * Load column distinct values via AJAX for select-type fields.
     */
    function loadColumnValues($row) {
        var column = $row.data('column');
        if (!column || typeof bpidFiltersAdmin === 'undefined') return;

        // Visual loading indicator
        var $typeSelect = $row.find('.bpid-column-type-select');
        $typeSelect.css('opacity', '0.5');

        $.ajax({
            url: bpidFiltersAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'bpid_filter_column_values',
                _ajax_nonce: bpidFiltersAdmin.nonce,
                column: column
            },
            success: function(response) {
                $typeSelect.css('opacity', '1');
                if (response.success && response.data) {
                    // Store values as data attribute for reference
                    $row.data('column-values', response.data);
                }
            },
            error: function() {
                $typeSelect.css('opacity', '1');
            }
        });
    }

    /**
     * Initialize visibility state for already-checked columns.
     */
    function initColumnStates() {
        $columnsContainer.find('.bpid-column-checkbox').each(function() {
            var $checkbox = $(this);
            var $row = $checkbox.closest('.bpid-column-row');
            var $typeSelector = $row.find('.bpid-column-type-selector');
            var $shortcode = $row.find('.bpid-column-shortcode');

            if ($checkbox.is(':checked')) {
                $row.addClass('bpid-column-row--active');
                $typeSelector.show();
                $shortcode.show();
            } else {
                $row.removeClass('bpid-column-row--active');
                $typeSelector.hide();
                $shortcode.hide();
            }
        });
    }

    /**
     * Update the shortcode preview text.
     */
    function updateShortcodePreview() {
        if (!$shortcodePreview.length) return;
        var postId = $('#post_ID').val() || '0';
        var shortcode = '[bpid_filter id="' + postId + '"]';
        $shortcodePreview.text(shortcode);
    }

    /**
     * Handle "Select All" / "Deselect All" buttons.
     */
    function bindSelectAll() {
        $('#bpid-filter-select-all').on('click', function(e) {
            e.preventDefault();
            $columnsContainer.find('.bpid-column-checkbox').not(':checked').prop('checked', true).trigger('change');
        });

        $('#bpid-filter-deselect-all').on('click', function(e) {
            e.preventDefault();
            $columnsContainer.find('.bpid-column-checkbox:checked').prop('checked', false).trigger('change');
        });
    }

    /**
     * Handle type selector change — reload values via AJAX when switching to select.
     */
    function bindTypeChange() {
        $columnsContainer.on('change', '.bpid-column-type-select', function() {
            var $select = $(this);
            var $row = $select.closest('.bpid-column-row');
            if ($select.val() === 'select') {
                loadColumnValues($row);
            }
        });
    }

    /**
     * Copy shortcode functionality.
     */
    function bindCopyShortcode() {
        $(document).on('click', '.bpid-copy-shortcode', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var targetId = $btn.data('target');
            var $target = $('#' + targetId);
            if ($target.length) {
                var text = $target.text();
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text);
                }
                $btn.text('\u00a1Copiado!');
                setTimeout(function() { $btn.text('Copiar'); }, 1500);
            }
        });
    }

    // Initialize
    $(document).ready(function() {
        if (!$columnsContainer.length) return;

        initColumnStates();
        bindColumnToggles();
        bindSelectAll();
        bindTypeChange();
        bindCopyShortcode();
        updateShortcodePreview();

        // Update preview when title changes
        $('#title').on('input', updateShortcodePreview);
    });

})(jQuery);
