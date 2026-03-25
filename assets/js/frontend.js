(function() {
    'use strict';

    // Check D3plus loaded
    if (typeof d3plus === 'undefined' || typeof d3plus.BarChart !== 'function') {
        console.error('[BPID Suite] D3plus no cargo correctamente');
        return;
    }

    function getD3PlusClass(chartType) {
        var map = {
            'bar':         function() { return new d3plus.BarChart(); },
            'line':        function() { return new d3plus.LinePlot(); },
            'area':        function() { return new d3plus.AreaPlot(); },
            'pie':         function() { return new d3plus.Pie(); },
            'donut':       function() { return new d3plus.Donut(); },
            'treemap':     function() { return new d3plus.Treemap(); },
            'stacked_bar': function() { return new d3plus.BarChart().stacked(true); },
            'grouped_bar': function() { return new d3plus.BarChart().stacked(false); },
            'tree':        function() { return new d3plus.Tree(); },
            'pack':        function() { return new d3plus.Pack(); },
            'network':     function() { return new d3plus.Network(); },
            'scatter':     function() { return new d3plus.Plot(); },
            'box_whisker': function() { return new d3plus.BoxWhisker(); },
            'matrix':      function() { return new d3plus.Matrix(); },
            'bump':        function() { return new d3plus.BumpChart(); }
        };
        var factory = map[chartType];
        if (!factory) {
            console.warn('[BPID Suite] Tipo de grafico no reconocido:', chartType);
            return new d3plus.BarChart();
        }
        try {
            return factory();
        } catch (e) {
            console.error('[BPID Suite] Error instanciando grafico:', chartType, e);
            return new d3plus.BarChart();
        }
    }

    // Initialize all charts on the page
    document.querySelectorAll('.bpid-chart-container').forEach(function(container) {
        var chartId = container.dataset.chartId;
        var chartType = container.dataset.chartType;
        var dataEl = document.getElementById('bpid-chart-data-' + chartId);

        if (!dataEl) {
            console.error('[BPID Suite] No data found for chart', chartId);
            return;
        }

        var chartData;
        try {
            chartData = JSON.parse(dataEl.textContent);
        } catch (e) {
            console.error('[BPID Suite] Error parsing chart data:', e);
            return;
        }

        if (!chartData || !chartData.data || !chartData.data.length) {
            container.innerHTML = '<p style="text-align:center;padding:40px;color:#666;">Sin datos disponibles para esta grafica</p>';
            return;
        }

        try {
            var chart = getD3PlusClass(chartType);
            chart
                .select('#bpid-chart-' + chartId)
                .data(chartData.data);

            if (chartData.groupBy) chart.groupBy(chartData.groupBy);
            if (chartData.x) chart.x(chartData.x);
            if (chartData.y) chart.y(chartData.y);
            if (chartData.size) chart.size(chartData.size);
            if (chartData.color) chart.color(chartData.color);
            if (chartData.time) chart.time(chartData.time);

            // Tooltip
            chart.tooltipConfig({
                body: function(d) {
                    var val = d.valor ? '$' + Number(d.valor).toLocaleString('es-CO') : '';
                    var av = d.avance_fisico ? d.avance_fisico + '%' : '';
                    return [val, av].filter(Boolean).join(' | ');
                }
            });

            chart.render();

        } catch (e) {
            console.error('[BPID Suite] Error rendering chart:', chartType, e);
            container.innerHTML = '<p style="text-align:center;padding:40px;color:#c00;">Error al renderizar la grafica</p>';
        }
    });

})();
