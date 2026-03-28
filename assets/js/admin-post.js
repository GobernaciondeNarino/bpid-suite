(function($) {
    'use strict';

    // Parse JSON config blocks from the template.
    function parseJson(id) {
        try {
            var el = document.getElementById(id);
            return el ? JSON.parse(el.textContent) : null;
        } catch (e) {
            return null;
        }
    }

    var savedExtraFields    = parseJson('bpid-postgrid-saved-extra-fields') || [];
    var savedStatsFields    = parseJson('bpid-postgrid-saved-stats-fields') || [];
    var savedAccordionFields = parseJson('bpid-postgrid-saved-accordion-fields') || [];
    var availableFields     = parseJson('bpid-postgrid-available-fields') || {};
    var aggregationOptions  = parseJson('bpid-postgrid-aggregation-options') || {};
    var adminConfig         = parseJson('bpid-postgrid-admin-config') || {};

    // ── Build select options HTML ──
    function buildFieldOptions(selectedValue) {
        var html = '';
        for (var key in availableFields) {
            if (availableFields.hasOwnProperty(key)) {
                var sel = key === selectedValue ? ' selected' : '';
                html += '<option value="' + key + '"' + sel + '>' + availableFields[key] + '</option>';
            }
        }
        return html;
    }

    function buildAggregationOptions(selectedValue) {
        var html = '';
        for (var key in aggregationOptions) {
            if (aggregationOptions.hasOwnProperty(key)) {
                var sel = key === selectedValue ? ' selected' : '';
                html += '<option value="' + key + '"' + sel + '>' + aggregationOptions[key] + '</option>';
            }
        }
        return html;
    }

    // ── Card Extra Fields ──
    var $cardExtraRows = $('#bpid-post-card-extra-rows');
    var cardExtraIndex = 0;

    function addCardExtraRow(data) {
        data = data || { field: '', label: '', aggregation: 'none' };
        cardExtraIndex++;
        var html = '<div class="y-axis-row">' +
            '<span class="y-axis-badge">' + cardExtraIndex + '</span>' +
            '<select name="bpid_post_card_extra_fields[field][]" class="y-column-select">' +
            buildFieldOptions(data.field) + '</select>' +
            '<input type="text" name="bpid_post_card_extra_fields[label][]" value="' +
            (data.label || '').replace(/"/g, '&quot;') +
            '" placeholder="Etiqueta personalizada" class="bpid-chart-input-sm" style="max-width:180px;" />' +
            '<select name="bpid_post_card_extra_fields[aggregation][]" class="y-column-select" style="max-width:150px;">' +
            buildAggregationOptions(data.aggregation) + '</select>' +
            '<button type="button" class="button y-axis-remove bpid-remove-row">' +
            '<span class="dashicons dashicons-trash" style="margin-top:4px;"></span></button>' +
            '</div>';
        $cardExtraRows.append(html);
    }

    // ── Stats Fields ──
    var $statsRows = $('#bpid-post-stats-rows');
    var statsIndex = 0;

    function addStatsRow(data) {
        data = data || { field: '', label: '', aggregation: 'COUNT' };
        statsIndex++;
        var html = '<div class="y-axis-row">' +
            '<span class="y-axis-badge">' + statsIndex + '</span>' +
            '<select name="bpid_post_stats_fields[field][]" class="y-column-select">' +
            buildFieldOptions(data.field) + '</select>' +
            '<input type="text" name="bpid_post_stats_fields[label][]" value="' +
            (data.label || '').replace(/"/g, '&quot;') +
            '" placeholder="Texto personalizado" class="bpid-chart-input-sm" style="max-width:180px;" />' +
            '<select name="bpid_post_stats_fields[aggregation][]" class="y-column-select" style="max-width:150px;">' +
            buildAggregationOptions(data.aggregation) + '</select>' +
            '<button type="button" class="button y-axis-remove bpid-remove-row">' +
            '<span class="dashicons dashicons-trash" style="margin-top:4px;"></span></button>' +
            '</div>';
        $statsRows.append(html);
    }

    // ── Accordion Contrato Fields ──
    var $accordionRows = $('#bpid-post-accordion-fields');
    var accordionIndex = 0;

    function addAccordionRow(data) {
        data = data || { field: '', label: '', aggregation: 'none' };
        accordionIndex++;
        var html = '<div class="y-axis-row">' +
            '<span class="y-axis-badge">' + accordionIndex + '</span>' +
            '<select name="bpid_post_accordion_contrato_fields[field][]" class="y-column-select">' +
            buildFieldOptions(data.field) + '</select>' +
            '<input type="text" name="bpid_post_accordion_contrato_fields[label][]" value="' +
            (data.label || '').replace(/"/g, '&quot;') +
            '" placeholder="Etiqueta personalizada" class="bpid-chart-input-sm" style="max-width:180px;" />' +
            '<button type="button" class="button y-axis-remove bpid-remove-row">' +
            '<span class="dashicons dashicons-trash" style="margin-top:4px;"></span></button>' +
            '</div>';
        $accordionRows.append(html);
    }

    // ── Remove row handler (delegated) ──
    $(document).on('click', '.bpid-remove-row', function() {
        $(this).closest('.y-axis-row').fadeOut(200, function() {
            $(this).remove();
            renumberRows($cardExtraRows);
            renumberRows($statsRows);
            renumberRows($accordionRows);
        });
    });

    function renumberRows($container) {
        $container.find('.y-axis-row').each(function(i) {
            $(this).find('.y-axis-badge').text(i + 1);
        });
    }

    // ── Add buttons ──
    $('#bpid-post-add-card-field').on('click', function() { addCardExtraRow(); });
    $('#bpid-post-add-stat').on('click', function() { addStatsRow(); });
    $('#bpid-post-add-accordion-field').on('click', function() { addAccordionRow(); });

    // ── Populate saved rows ──
    savedExtraFields.forEach(function(item) { addCardExtraRow(item); });
    savedStatsFields.forEach(function(item) { addStatsRow(item); });
    savedAccordionFields.forEach(function(item) { addAccordionRow(item); });

    // ── Clear Cache ──
    $('#bpid-post-clear-cache').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var $spinner = $('#bpid-post-clear-cache-spinner');
        var $result = $('#bpid-post-clear-cache-result');

        $btn.prop('disabled', true);
        $spinner.addClass('is-active');
        $result.empty();

        $.post(adminConfig.ajaxUrl || ajaxurl, {
            action: 'bpid_post_clear_cache',
            nonce: adminConfig.nonce || '',
            post_id: adminConfig.postId || $('#post_ID').val() || 0
        }).done(function(response) {
            if (response.success) {
                $result.html('<span style="color:#00a32a;">' + (response.data.message || 'OK') + '</span>');
            } else {
                $result.html('<span style="color:#d63638;">' + (response.data.message || 'Error') + '</span>');
            }
        }).fail(function() {
            $result.html('<span style="color:#d63638;">Error de conexi&oacute;n</span>');
        }).always(function() {
            $btn.prop('disabled', false);
            $spinner.removeClass('is-active');
            setTimeout(function() { $result.fadeOut(300, function() { $(this).empty().show(); }); }, 5000);
        });
    });

    // ── Copy Shortcode ──
    $(document).on('click', '.bpid-copy-shortcode', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var targetId = $btn.data('target');
        var $target = $('#' + targetId);
        if ($target.length && navigator.clipboard) {
            navigator.clipboard.writeText($target.text());
            $btn.text('\u00a1Copiado!');
            setTimeout(function() { $btn.text('Copiar'); }, 1500);
        }
    });

})(jQuery);
