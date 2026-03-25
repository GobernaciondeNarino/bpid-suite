(function($) {
    'use strict';

    var $chartType     = $('#bpid_chart_type');
    var $groupByRow    = $('.bpid-field-groupby').closest('tr');
    var $xAxisRow      = $('.bpid-field-x').closest('tr');
    var $yAxisRow      = $('.bpid-field-y').closest('tr');
    var $sizeRow       = $('.bpid-field-size').closest('tr');
    var $colorRow      = $('.bpid-field-color').closest('tr');
    var $timeRow       = $('.bpid-field-time').closest('tr');

    // Chart types that use specific axis configurations
    var typesWithXY     = ['bar', 'line', 'area', 'stacked_bar', 'grouped_bar', 'scatter', 'box_whisker', 'bump'];
    var typesWithSize   = ['treemap', 'pack', 'network', 'scatter'];
    var typesWithTime   = ['line', 'area', 'bump'];
    var typesWithColor  = ['bar', 'line', 'area', 'pie', 'donut', 'treemap', 'stacked_bar', 'grouped_bar',
                           'tree', 'pack', 'network', 'scatter', 'box_whisker', 'matrix', 'bump'];

    function toggleFields() {
        var type = $chartType.val();

        $xAxisRow.toggle(typesWithXY.indexOf(type) !== -1);
        $yAxisRow.toggle(typesWithXY.indexOf(type) !== -1);
        $sizeRow.toggle(typesWithSize.indexOf(type) !== -1);
        $timeRow.toggle(typesWithTime.indexOf(type) !== -1);
        $colorRow.toggle(typesWithColor.indexOf(type) !== -1);
        $groupByRow.toggle(type !== 'scatter' && type !== 'box_whisker');

        updateShortcodePreview();
    }

    function updateShortcodePreview() {
        var $preview = $('#bpid-chart-shortcode-preview');
        if (!$preview.length) return;

        var postId = $('#post_ID').val() || '0';
        var shortcode = '[bpid_chart id="' + postId + '"]';
        $preview.text(shortcode);
    }

    // Bind events
    $chartType.on('change', toggleFields);

    // Update preview when title changes
    $('#title').on('input', updateShortcodePreview);

    // Initialize on load
    $(document).ready(function() {
        toggleFields();
    });

})(jQuery);
