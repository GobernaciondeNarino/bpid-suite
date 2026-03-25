(function($) {
    'use strict';

    var $columnsContainer = $('#bpid-filter-columns');
    var $shortcodePreview = $('#bpid-filter-shortcode-preview');

    /**
     * Toggle the field type selector visibility when a column checkbox is checked/unchecked.
     */
    function bindColumnToggles() {
        $columnsContainer.on('change', '.bpid-column-checkbox', function() {
            var $checkbox = $(this);
            var $row = $checkbox.closest('.bpid-column-row');
            var $typeSelector = $row.find('.bpid-column-type-selector');

            if ($checkbox.is(':checked')) {
                $typeSelector.slideDown(150);
            } else {
                $typeSelector.slideUp(150);
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

            if ($checkbox.is(':checked')) {
                $typeSelector.show();
            } else {
                $typeSelector.hide();
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
            $columnsContainer.find('.bpid-column-checkbox').prop('checked', true).trigger('change');
        });

        $('#bpid-filter-deselect-all').on('click', function(e) {
            e.preventDefault();
            $columnsContainer.find('.bpid-column-checkbox').prop('checked', false).trigger('change');
        });
    }

    // Initialize
    $(document).ready(function() {
        initColumnStates();
        bindColumnToggles();
        bindSelectAll();
        updateShortcodePreview();

        // Update preview when title changes
        $('#title').on('input', updateShortcodePreview);
    });

})(jQuery);
