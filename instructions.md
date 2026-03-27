You are an expert WordPress Security & Quality Engineer with deep specialization in PHP 8.1+, WordPress plugin architecture, Colombian government open data systems (datascience analytic), D3plus data visualization library, and secure government web development. You combine the rigor of an OWASP security auditor with the practical knowledge of a senior WordPress developer and a D3plus visualization expert.

# Update

## Example del modulo Visualizaciones (Post GRID)


// Evitar ejecución directa
if (!defined('ABSPATH')) exit;

// =============================================================================
// CONFIGURACIÓN
// =============================================================================


// =============================================================================
// SHORTCODE PRINCIPAL
// =============================================================================
if (!function_exists('bpid_cs_visualizador_shortcode')) {
    add_shortcode('visor_bpid_gestion', 'bpid_cs_visualizador_shortcode'); 

    /**
     * Función principal del shortcode para renderizar el visor.
     */
    function bpid_cs_visualizador_shortcode($atts) {
        // 1. Consultar API y obtener datos (con caché)
        $apiResult = bpid_cs_consultar_api();
        $hasError = !$apiResult['success'];
        $errorMessage = $hasError ? $apiResult['error'] : '';
        $datos = $hasError ? null : $apiResult['data'];
        
        // 2. Organizar datos por dependencia para el selector
        $datosPorDependencia = [];
        if (!$hasError) {
            $datosPorDependencia = bpid_cs_organizar_datos_por_dependencia($datos);
        }
        
        // 3. Inicio del buffer de salida y carga de estilos
        ob_start();
        bpid_cs_incluir_estilos();
        
        // 4. Estructura HTML
        ?>
        <div class="bpid-cs-container">
            <?php if ($hasError): ?>
                <div class="bpid-cs-error">
                    <strong>⚠️ Error de Conexión</strong><br>
                    <?php echo esc_html($errorMessage); ?>
                </div>
            <?php else: ?>
                
                <div class="bpid-cs-stats">
                    <div class="bpid-cs-stat">
                        <div class="bpid-cs-stat-num"><?php echo $datos['totalProyectos'] ?? 0; ?></div>
                        <div class="bpid-cs-stat-label">Total Proyectos</div>
                    </div>
                    <div class="bpid-cs-stat">
                        <div class="bpid-cs-stat-num"><?php echo $datos['totalContratos'] ?? 0; ?></div>
                        <div class="bpid-cs-stat-label">Total Contratos</div>
                    </div>
                    <div class="bpid-cs-stat">
                        <div class="bpid-cs-stat-num"><?php echo count($datosPorDependencia); ?></div>
                        <div class="bpid-cs-stat-label">Dependencias</div>
                    </div>
                </div>
                
                <div class="bpid-cs-controls">
                    <h3>📊 Exportar Informes de Gestión</h3>
                    <div class="bpid-cs-export-btns">
                        <button class="bpid-cs-btn bpid-cs-btn-word" onclick="bpidCSExportar('word')">
                            <span class="dashicons dashicons-media-document"></span> Exportar a Word
                        </button>
                        <button class="bpid-cs-btn bpid-cs-btn-excel" onclick="bpidCSExportar('excel')">
                            <span class="dashicons dashicons-media-spreadsheet"></span> Exportar a Excel
                        </button>
                    </div>
                    <div id="bpid-cs-status" class="bpid-cs-status"></div>
                </div>
                
                <div class="bpid-cs-selector">
                    <label for="bpid-cs-dep">Seleccionar Dependencia:</label>
                    <select id="bpid-cs-dep" onchange="bpidCSCambiarDep(this.value)">
                        <option value="">-- Seleccione una dependencia --</option>
                        <?php foreach (array_keys($datosPorDependencia) as $dep): ?>
                            <option value="<?php echo esc_attr($dep); ?>"><?php echo esc_html($dep); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="bpid-cs-tablas">
                    <div class="bpid-cs-placeholder">
                        <p>👆 Seleccione una dependencia para ver su informe de gestión</p>
                    </div>
                </div>
                
                <script type="application/json" id="bpid-cs-datos">
                    <?php echo json_encode($datosPorDependencia, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>
                </script>
                
            <?php endif; ?>
        </div>
        
        <?php
        // 5. Carga de scripts y retorno del contenido
        bpid_cs_incluir_scripts();
        return ob_get_clean();
    }
}

// =============================================================================
// UTILIDADES Y DATA
// =============================================================================

if (!function_exists('bpid_cs_normalizar_texto')) {
    /**
     * Normaliza una cadena forzando minúsculas y eliminando todos los caracteres especiales/espacios.
     */
    function bpid_cs_normalizar_texto($texto) {
        if (!is_string($texto) || empty($texto)) return '';
        
        $texto = trim($texto); // 1. Elimina espacios al inicio y al final
        $texto = strtolower($texto); // 2. Convierte a minúsculas
        
        // 3. Usa la función nativa de WordPress para eliminar acentos (si existe)
        if (function_exists('remove_accents')) {
            $texto = remove_accents($texto);
        }

        // 4. ELIMINACIÓN AGRESIVA: Elimina todos los caracteres que no sean letras (a-z) o números (0-9)
        $texto = preg_replace('/[^a-z0-9]/', '', $texto); 
        
        return $texto;
    }
}

if (!function_exists('bpid_cs_organizar_datos_por_dependencia')) {
    /**
     * Organiza los datos de la API por dependencia de proyecto o filtra si se especifica una.
     */
    function bpid_cs_organizar_datos_por_dependencia($datos, $dependenciaFiltro = null) {
        if (!isset($datos['proyectos'])) {
            return [];
        }

        $proyectos = $datos['proyectos'];
        $nombreDefault = 'Sin dependencia';

        // 1. Si hay filtro, devolver solo los proyectos de esa dependencia
        if (!empty($dependenciaFiltro)) {
            $proyectosFiltrados = [];
            // Aplicamos normalización agresiva al filtro (eliminar todo excepto letras y números)
            $filtroNormalizado = preg_replace('/[^a-z0-9]/', '', strtolower(remove_accents(trim($dependenciaFiltro))));

            foreach ($proyectos as $proyecto) {
                $depAPI = $proyecto['dependenciaProyecto'] ?? $nombreDefault;
                
                // Aplicamos normalización agresiva al valor de la API para la comparación
                $depAPINormalizada = preg_replace('/[^a-z0-9]/', '', strtolower(remove_accents(trim($depAPI))));

                // Comparación usando valores normalizados
                if ($depAPINormalizada === $filtroNormalizado) {
                    $proyectosFiltrados[] = $proyecto;
                }
            }
            return $proyectosFiltrados;
        }

        // 2. Si no hay filtro, organizar todos por dependencia (para el shortcode)
        $datosPorDependencia = [];
        foreach ($proyectos as $proyecto) {
            $dep = $proyecto['dependenciaProyecto'] ?? $nombreDefault;
            if (!isset($datosPorDependencia[$dep])) {
                $datosPorDependencia[$dep] = [];
            }
            $datosPorDependencia[$dep][] = $proyecto;
        }
        ksort($datosPorDependencia);
        return $datosPorDependencia;
    }
}

if (!function_exists('bpid_cs_consultar_api')) {
    /**
     * Consulta la API y cachea los resultados por una hora.
     */
    function bpid_cs_consultar_api() {
        $transient_key = 'bpid_cs_api_data';
        $cached = get_transient($transient_key);

        if ($cached !== false) {
            return ['success' => true, 'data' => $cached];
        }
        
        $response = wp_remote_get(BPID_API_URL, [
            'headers' => ['apikey' => BPID_API_KEY],
            'timeout' => 30,
            'sslverify' => false
        ]);

        if (is_wp_error($response)) {
            return ['success' => false, 'error' => 'Error WP Remote: ' . $response->get_error_message()];
        }
        
        $http_code = wp_remote_retrieve_response_code($response);
        if ($http_code !== 200) {
            return ['success' => false, 'error' => 'Error HTTP ' . $http_code];
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'error' => 'Error JSON: ' . json_last_error_msg()];
        }
        
        set_transient($transient_key, $data, HOUR_IN_SECONDS);
        return ['success' => true, 'data' => $data];
    }
}

if (!function_exists('bpid_cs_formatear_valor')) {
    /**
     * Formatea un valor numérico a formato monetario colombiano ($0.000.000).
     */
    function bpid_cs_formatear_valor($valor) {
        if (is_numeric($valor)) {
            return '$' . number_format($valor, 0, ',', '.');
        }
        return $valor;
    }
}

// =============================================================================
// HANDLERS AJAX PARA EXPORTACIÓN CON DIAGNÓSTICO
// =============================================================================

if (!function_exists('bpid_cs_exportar_word_ajax')) {
    add_action('wp_ajax_bpid_cs_exportar_word', 'bpid_cs_exportar_word_ajax');
    add_action('wp_ajax_nopriv_bpid_cs_exportar_word', 'bpid_cs_exportar_word_ajax');

    /**
     * Función AJAX para exportar informe a Word. Incluye diagnóstico 400.
     */
    function bpid_cs_exportar_word_ajax() {
        // Seguridad y validación
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bpid_cs_nonce')) {
            wp_send_json_error(['message' => 'Seguridad fallida'], 403);
        }
        
        if (!isset($_POST['dependencia']) || empty($_POST['dependencia'])) {
            wp_send_json_error(['message' => 'Dependencia no especificada'], 400);
        }
        
        $dependencia = sanitize_text_field($_POST['dependencia']);
        
        // Consulta la API
        $apiResult = bpid_cs_consultar_api();
        if (!$apiResult['success']) {
            wp_send_json_error(['message' => 'Error API: ' . $apiResult['error']], 500);
        }
        
        // FILTRADO
        $datos = $apiResult['data'];
        $proyectos = bpid_cs_organizar_datos_por_dependencia($datos, $dependencia);

        if (empty($proyectos)) {
            // ERROR DE DIAGNÓSTICO: Devolvemos la dependencia usada y el conteo
            wp_send_json_error([
                'message' => 'Error de Filtro 400. Dependencia enviada: "' . esc_html($dependencia) . '". Proyectos encontrados: 0. Revise la coincidencia de nombre.',
                'dependencia_enviada' => $dependencia
            ], 400); 
        }
        
        // Generar documento HTML compatible con Word
        $html = bpid_cs_generar_html_word($dependencia, $proyectos);

        // Headers para forzar la descarga como .doc
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/msword; charset=utf-8');
        header('Content-Disposition: attachment; filename="Informe_' . sanitize_file_name($dependencia) . '_' . date('Y-m-d') . '.doc"');
        header('Cache-Control: max-age=0');
        
        echo "\xEF\xBB\xBF"; // BOM UTF-8
        echo $html;
        exit;
    }
}

if (!function_exists('bpid_cs_exportar_excel_ajax')) {
    add_action('wp_ajax_bpid_cs_exportar_excel', 'bpid_cs_exportar_excel_ajax');
    add_action('wp_ajax_nopriv_bpid_cs_exportar_excel', 'bpid_cs_exportar_excel_ajax');

    /**
     * Función AJAX para exportar informe a Excel. Incluye diagnóstico 400.
     */
    function bpid_cs_exportar_excel_ajax() {
        // Seguridad y validación
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bpid_cs_nonce')) {
            wp_send_json_error(['message' => 'Seguridad fallida'], 403);
        }
        
        if (!isset($_POST['dependencia']) || empty($_POST['dependencia'])) {
            wp_send_json_error(['message' => 'Dependencia no especificada'], 400);
        }
        
        $dependencia = sanitize_text_field($_POST['dependencia']);
        
        // Consulta la API
        $apiResult = bpid_cs_consultar_api();
        if (!$apiResult['success']) {
            wp_send_json_error(['message' => 'Error API: ' . $apiResult['error']], 500);
        }
        
        // FILTRADO
        $datos = $apiResult['data'];
        $proyectos = bpid_cs_organizar_datos_por_dependencia($datos, $dependencia);

        if (empty($proyectos)) {
            // ERROR DE DIAGNÓSTICO: Devolvemos la dependencia usada y el conteo
            wp_send_json_error([
                'message' => 'Error de Filtro 400. Dependencia enviada: "' . esc_html($dependencia) . '". Proyectos encontrados: 0. Revise la coincidencia de nombre.',
                'dependencia_enviada' => $dependencia
            ], 400); 
        }
        
        // Generar HTML compatible con Excel
        $html = bpid_cs_generar_html_excel($dependencia, $proyectos);

        // Headers para forzar la descarga como .xls
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="Informe_' . sanitize_file_name($dependencia) . '_' . date('Y-m-d') . '.xls"');
        header('Cache-Control: max-age=0');
        
        echo "\xEF\xBB\xBF"; // BOM UTF-8
        echo $html;
        exit;
    }
}

// =============================================================================
// GENERADORES DE DOCUMENTOS
// =============================================================================

if (!function_exists('bpid_cs_generar_html_word')) {
    /**
     * Genera el contenido HTML con estilos para exportación a Word.
     */
    function bpid_cs_generar_html_word($dependencia, $proyectos) {
        $html = '<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word">
<head>
    <meta charset="UTF-8">
    <title>Informe de Gestión - ' . esc_html($dependencia) . '</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11pt; }
        h1 { color: #334155; text-align: center; font-size: 18pt; }
        h2 { color: #348afb; font-size: 14pt; margin-top: 20pt; }
        table { border-collapse: collapse; width: 100%; margin: 10pt 0; }
        th, td { border: 1px solid #348afb; padding: 8pt; text-align: left; }
        th { background-color: #348afb; color: white; font-weight: bold; }
        .info { font-weight: bold; color: #334155; }
        p { margin: 5pt 0; }
    </style>
</head>
<body>
    <h1>INFORME DE GESTIÓN</h1>
    <h1>' . strtoupper(esc_html($dependencia)) . '</h1>
    <p style="text-align: center;"><strong>Vigencia 2025</strong></p>
    <p><strong>Fecha:</strong> ' . date('d/m/Y H:i:s') . '</p>
    <p><strong>Total proyectos:</strong> ' . count($proyectos) . '</p>
    <hr>
    
    <h2>CUMPLIMIENTO DE METAS Y PRINCIPALES LOGROS</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 50px;">No.</th>
                <th style="width: 200px;">Programa / Proyecto</th>
                <th style="width: 150px;">Meta</th>
                <th style="width: 80px;">% Cumplimiento</th>
                <th style="width: 200px;">Logro Destacado</th>
            </tr>
        </thead>
        <tbody>';

        $contador = 1;
        foreach ($proyectos as $proyecto) {
            // Calcular promedio de avance
            $totalAvance = 0;
            $numContratos = 0;
            if (!empty($proyecto['contratosProyecto'])) {
                foreach ($proyecto['contratosProyecto'] as $contrato) {
                    $totalAvance += (float)($contrato['procentajeAvanceFisico'] ?? 0);
                    $numContratos++;
                }
            }
            $promedioAvance = $numContratos > 0 ? round($totalAvance / $numContratos, 1) : 0;
            
            // Metas
            $metasTexto = 'N/A';
            if (!empty($proyecto['metasProyecto'])) {
                $metasTexto = implode('; ', array_slice($proyecto['metasProyecto'], 0, 2));
            }
            
            // Logro
            $logroTexto = count($proyecto['contratosProyecto'] ?? []) . ' contratos ejecutados. Valor: ' . bpid_cs_formatear_valor($proyecto['valorProyecto'] ?? 0);
            
            $html .= '<tr>
                <td style="text-align: center;">' . $contador . '</td>
                <td>' . esc_html($proyecto['nombreProyecto'] ?? 'N/A') . '</td>
                <td>' . esc_html($metasTexto) . '</td>
                <td style="text-align: center;">' . $promedioAvance . '%</td>
                <td>' . esc_html($logroTexto) . '</td>
            </tr>';
            $contador++;
        }
        
        $html .= '</tbody></table>
        
        <h2>INVERSIÓN Y EJECUCIÓN PRESUPUESTAL</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">No.</th>
                    <th style="width: 250px;">Proyecto</th>
                    <th style="width: 120px;">Presupuesto Asignado</th>
                    <th style="width: 120px;">Presupuesto Ejecutado</th>
                    <th style="width: 80px;">% Ejecución</th>
                </tr>
            </thead>
            <tbody>';

        $contador = 1;
        $totalAsignado = 0;
        $totalEjecutado = 0;
        
        foreach ($proyectos as $proyecto) {
            $valorProyecto = (float)($proyecto['valorProyecto'] ?? 0);
            $totalAsignado += $valorProyecto;
            
            // Calcular ejecutado (Valor Contrato * % Avance / 100)
            $valorEjecutado = 0;
            if (!empty($proyecto['contratosProyecto'])) {
                foreach ($proyecto['contratosProyecto'] as $contrato) {
                    $valorContrato = (float)($contrato['valorContrato'] ?? 0);
                    $avance = (float)($contrato['procentajeAvanceFisico'] ?? 0);
                    $valorEjecutado += ($valorContrato * $avance / 100);
                }
            }
            $totalEjecutado += $valorEjecutado;
            $porcentajeEjecucion = $valorProyecto > 0 ? round(($valorEjecutado / $valorProyecto) * 100, 1) : 0;

            $html .= '<tr>
                <td style="text-align: center;">' . $contador . '</td>
                <td>' . esc_html($proyecto['nombreProyecto'] ?? 'N/A') . '</td>
                <td style="text-align: right;">' . bpid_cs_formatear_valor($valorProyecto) . '</td>
                <td style="text-align: right;">' . bpid_cs_formatear_valor($valorEjecutado) . '</td>
                <td style="text-align: center;">' . $porcentajeEjecucion . '%</td>
            </tr>';
            $contador++;
        }
        
        // Fila de totales
        $porcentajeTotal = $totalAsignado > 0 ? round(($totalEjecutado / $totalAsignado) * 100, 1) : 0;
        
        $html .= '<tr style="font-weight: bold; background-color: #f0f9ff;">
                <td colspan="2" style="text-align: right;">TOTAL</td>
                <td style="text-align: right;">' . bpid_cs_formatear_valor($totalAsignado) . '</td>
                <td style="text-align: right;">' . bpid_cs_formatear_valor($totalEjecutado) . '</td>
                <td style="text-align: center;">' . $porcentajeTotal . '%</td>
            </tr>';
        
        $html .= '</tbody></table>
        
        <hr>
        <p style="text-align: center; color: #666; font-size: 9pt;">
            Documento generado por el Sistema BPID - Gobernación de Nariño<br>
            Fecha de generación: ' . date('d/m/Y H:i:s') . '
        </p>
    </body>
    </html>';
        
        return $html;
    }
}

if (!function_exists('bpid_cs_generar_html_excel')) {
    /**
     * Genera el contenido HTML con estilos para exportación a Excel.
     */
    function bpid_cs_generar_html_excel($dependencia, $proyectos) {
        $html = '<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">
<head>
    <meta charset="UTF-8">
    <title>Informe - ' . esc_html($dependencia) . '</title>
    <style>
        table { border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 5px; }
        th { background-color: #348afb; color: white; font-weight: bold; }
        .header { font-size: 16pt; font-weight: bold; text-align: center; }
        .section { font-size: 12pt; font-weight: bold; margin-top: 20px; }
        .number { text-align: right; }
        .center { text-align: center; }
    </style>
</head>
<body>
    <div class="header">INFORME DE GESTIÓN - ' . strtoupper(esc_html($dependencia)) . '</div>
    <div class="header">Vigencia 2025</div>
    <br>
    <div>Fecha: ' . date('d/m/Y H:i:s') . '</div>
    <div>Total de proyectos: ' . count($proyectos) . '</div>
    <br><br>
    
    <div class="section">CUMPLIMIENTO DE METAS Y PRINCIPALES LOGROS</div>
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Programa / Proyecto</th>
                <th>Meta</th>
                <th>% Cumplimiento</th>
                <th>Logro Destacado</th>
            </tr>
        </thead>
        <tbody>';

        $contador = 1;
        foreach ($proyectos as $proyecto) {
            $totalAvance = 0;
            $numContratos = 0;
            if (!empty($proyecto['contratosProyecto'])) {
                foreach ($proyecto['contratosProyecto'] as $contrato) {
                    $totalAvance += (float)($contrato['procentajeAvanceFisico'] ?? 0);
                    $numContratos++;
                }
            }
            $promedioAvance = $numContratos > 0 ? round($totalAvance / $numContratos, 1) : 0;
            
            $metasTexto = 'N/A';
            if (!empty($proyecto['metasProyecto'])) {
                $metasTexto = implode('; ', array_slice($proyecto['metasProyecto'], 0, 2));
            }
            
            $logroTexto = count($proyecto['contratosProyecto'] ?? []) . ' contratos. Valor: ' . bpid_cs_formatear_valor($proyecto['valorProyecto'] ?? 0);
            
            $html .= '<tr>
                <td class="center">' . $contador . '</td>
                <td>' . esc_html($proyecto['nombreProyecto'] ?? 'N/A') . '</td>
                <td>' . esc_html($metasTexto) . '</td>
                <td class="center">' . $promedioAvance . '%</td>
                <td>' . esc_html($logroTexto) . '</td>
            </tr>';
            $contador++;
        }
        
        $html .= '</tbody></table><br><br>
        
        <div class="section">INVERSIÓN Y EJECUCIÓN PRESUPUESTAL</div>
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Proyecto</th>
                    <th>Presupuesto Asignado</th>
                    <th>Presupuesto Ejecutado</th>
                    <th>% Ejecución</th>
                </tr>
            </thead>
            <tbody>';
        
        $contador = 1;
        $totalAsignado = 0;
        $totalEjecutado = 0;
        
        foreach ($proyectos as $proyecto) {
            $valorProyecto = (float)($proyecto['valorProyecto'] ?? 0);
            $totalAsignado += $valorProyecto;
            
            $valorEjecutado = 0;
            if (!empty($proyecto['contratosProyecto'])) {
                foreach ($proyecto['contratosProyecto'] as $contrato) {
                    $valorContrato = (float)($contrato['valorContrato'] ?? 0);
                    $avance = (float)($contrato['procentajeAvanceFisico'] ?? 0);
                    $valorEjecutado += ($valorContrato * $avance / 100);
                }
            }
            $totalEjecutado += $valorEjecutado;
            $porcentajeEjecucion = $valorProyecto > 0 ? round(($valorEjecutado / $valorProyecto) * 100, 1) : 0;

            // Formateado sin el signo '$' para Excel
            $html .= '<tr>
                <td class="center">' . $contador . '</td>
                <td>' . esc_html($proyecto['nombreProyecto'] ?? 'N/A') . '</td>
                <td class="number">' . number_format($valorProyecto, 0, ',', '.') . '</td>
                <td class="number">' . number_format($valorEjecutado, 0, ',', '.') . '</td>
                <td class="center">' . $porcentajeEjecucion . '%</td>
            </tr>';
            $contador++;
        }
        
        $porcentajeTotal = $totalAsignado > 0 ? round(($totalEjecutado / $totalAsignado) * 100, 1) : 0;
        
        $html .= '<tr style="font-weight: bold; background-color: #f0f9ff;">
                <td colspan="2" class="number">TOTAL</td>
                <td class="number">' . number_format($totalAsignado, 0, ',', '.') . '</td>
                <td class="number">' . number_format($totalEjecutado, 0, ',', '.') . '</td>
                <td class="center">' . $porcentajeTotal . '%</td>
            </tr>';
        
        $html .= '</tbody></table>
    </body>
    </html>';
        
        return $html;
    }
}

// =============================================================================
// ESTILOS CSS
// =============================================================================

if (!function_exists('bpid_cs_incluir_estilos')) {
    /**
     * Incluye los estilos CSS necesarios.
     */
    function bpid_cs_incluir_estilos() {
        ?>
        <style>
            .bpid-cs-container {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                color: #444;
                line-height: 1.6;
                margin: 20px 0;
            }
            
            .bpid-cs-error {
                background-color: #fee;
                border: 2px solid #c00;
                color: #c00;
                padding: 20px;
                border-radius: 8px;
                margin: 20px 0;
            }
            
            .bpid-cs-stats {
                background: linear-gradient(135deg, #348afb 0%, #2563eb 100%);
                padding: 30px;
                border-radius: 12px;
                margin-bottom: 30px;
                display: flex;
                justify-content: space-around;
                flex-wrap: wrap;
                gap: 20px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            }
            
            .bpid-cs-stat {
                text-align: center;
                color: white;
            }
            
            .bpid-cs-stat-num {
                font-size: 42px;
                font-weight: bold;
            }
            
            .bpid-cs-stat-label {
                font-size: 16px;
                margin-top: 5px;
                opacity: 0.9;
            }
            
            .bpid-cs-controls {
                background-color: white;
                border: 2px solid #348afb;
                padding: 25px;
                border-radius: 12px;
                margin-bottom: 25px;
            }
            
            .bpid-cs-controls h3 {
                margin-top: 0;
                color: #334155;
            }
            
            .bpid-cs-export-btns {
                display: flex;
                gap: 15px;
                flex-wrap: wrap;
                margin-top: 15px;
            }
            
            .bpid-cs-btn {
                padding: 12px 24px;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                color: white;
            }
            
            .bpid-cs-btn-word {
                background-color: #2b579a;
            }
            
            .bpid-cs-btn-word:hover {
                background-color: #1e3a6b;
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            }
            
            .bpid-cs-btn-excel {
                background-color: #217346;
            }
            
            .bpid-cs-btn-excel:hover {
                background-color: #185c37;
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            }
            
            .bpid-cs-btn:disabled {
                opacity: 0.6;
                cursor: not-allowed;
            }
            
            .bpid-cs-status {
                margin-top: 15px;
                padding: 12px;
                border-radius: 6px;
                display: none;
            }
            
            .bpid-cs-status.success {
                background-color: #d1fae5;
                color: #065f46;
                border: 1px solid #10b981;
                display: block;
            }
            
            .bpid-cs-status.error {
                background-color: #fee2e2;
                color: #991b1b;
                border: 1px solid #ef4444;
                display: block;
            }
            
            .bpid-cs-status.loading {
                background-color: #dbeafe;
                color: #1e40af;
                border: 1px solid #3b82f6;
                display: block;
            }
            
            .bpid-cs-selector {
                background-color: white;
                border: 2px solid #348afb;
                padding: 20px;
                border-radius: 12px;
                margin-bottom: 25px;
            }
            
            .bpid-cs-selector label {
                display: block;
                color: #334155;
                font-weight: 600;
                margin-bottom: 10px;
                font-size: 16px;
            }
            
            .bpid-cs-selector select {
                width: 100%;
                padding: 12px;
                border: 2px solid #348afb;
                border-radius: 8px;
                font-size: 16px;
                background-color: #fffcf3;
                color: #444;
                cursor: pointer;
            }
            
            #bpid-cs-tablas {
                background-color: white;
                border: 2px solid #348afb;
                border-radius: 12px;
                padding: 25px;
                min-height: 200px;
            }
            
            .bpid-cs-placeholder {
                text-align: center;
                padding: 60px 20px;
                color: #64748b;
            }
            
            .bpid-cs-placeholder p {
                font-size: 18px;
                margin: 0;
            }
            
            .bpid-cs-tabla-title {
                color: #334155;
                font-size: 20px;
                font-weight: bold;
                margin-bottom: 20px;
                padding-bottom: 10px;
                border-bottom: 3px solid #348afb;
            }
            
            .bpid-cs-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 30px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            
            .bpid-cs-table thead th {
                background-color: #348afb;
                color: white;
                padding: 12px;
                text-align: left;
                font-weight: 600;
                border: 1px solid #2563eb;
            }
            
            .bpid-cs-table tbody td {
                padding: 12px;
                border: 1px solid #ddd;
                background-color: #fffcf3;
            }
            
            .bpid-cs-table tbody tr:hover {
                background-color: #f0f9ff;
            }
            
            .bpid-cs-table .num {
                width: 60px;
                text-align: center;
            }
            
            .bpid-cs-table .pct {
                width: 120px;
                text-align: center;
            }
            
            .bpid-cs-table .val {
                width: 150px;
                text-align: right;
            }
            
            .bpid-cs-resumen {
                background-color: #f0f9ff;
                border: 2px solid #348afb;
                padding: 20px;
                border-radius: 8px;
                margin-top: 20px;
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
            }
            
            .bpid-cs-resumen-item {
                text-align: center;
            }
            
            .bpid-cs-resumen-label {
                font-size: 14px;
                color: #64748b;
                margin-bottom: 5px;
            }
            
            .bpid-cs-resumen-value {
                font-size: 24px;
                font-weight: bold;
                color: #348afb;
            }
            
            @media (max-width: 768px) {
                .bpid-cs-stats {
                    flex-direction: column;
                }
                .bpid-cs-export-btns {
                    flex-direction: column;
                }
                .bpid-cs-btn {
                    width: 100%;
                    justify-content: center;
                }
                .bpid-cs-table {
                    font-size: 14px;
                }
            }
        </style>
        <?php
    }
}

// =============================================================================
// SCRIPTS JAVASCRIPT CON MENSAJES DE CONSOLA
// =============================================================================

if (!function_exists('bpid_cs_incluir_scripts')) {
    /**
     * Incluye los scripts JavaScript necesarios con mensajes de diagnóstico en consola.
     */
    function bpid_cs_incluir_scripts() {
        $nonce = wp_create_nonce('bpid_cs_nonce');
        $ajax_url = admin_url('admin-ajax.php');
        ?>
        <script>
            const bpidCSDatos = JSON.parse(document.getElementById('bpid-cs-datos').textContent);
            let bpidCSDepActual = '';
            
            console.log("DIAGNÓSTICO JS: Datos cargados por dependencia:", bpidCSDatos);

            // Formato monetario (COL)
            function bpidCSFormatVal(val) {
                if (isNaN(val)) return val;
                return '$' + Number(val).toLocaleString('es-CO', {minimumFractionDigits: 0});
            }
            
            // Escape HTML
            function bpidCSEsc(txt) {
                const div = document.createElement('div');
                div.textContent = txt;
                return div.innerHTML;
            }

            // Renderiza las tablas al seleccionar dependencia
            function bpidCSCambiarDep(dep) {
                bpidCSDepActual = dep;
                const container = document.getElementById('bpid-cs-tablas');
                
                console.log("DIAGNÓSTICO JS: Dependencia seleccionada:", dep); 

                if (!dep) {
                    container.innerHTML = '<div class="bpid-cs-placeholder"><p>👆 Seleccione una dependencia para ver su informe de gestión</p></div>';
                    return;
                }
                
                const proyectos = bpidCSDatos[dep] || [];
                
                if (proyectos.length === 0) {
                    container.innerHTML = '<div class="bpid-cs-placeholder"><p>No hay proyectos para esta dependencia.</p></div>';
                    return;
                }
                
                // Lógica de renderizado HTML (Cumplimiento de Metas)
                let html = '<div><h3 class="bpid-cs-tabla-title">📊 Cumplimiento de Metas</h3>';
                html += '<div style="overflow-x: auto;"><table class="bpid-cs-table"><thead><tr>';
                html += '<th class="num">No.</th><th>Programa / Proyecto</th><th>Meta</th>';
                html += '<th class="pct">% Cumplimiento</th><th>Logro Destacado</th>';
                html += '</tr></thead><tbody>';
                
                proyectos.forEach((proy, idx) => {
                    let totalAv = 0, numC = 0;
                    if (proy.contratosProyecto) {
                        proy.contratosProyecto.forEach(c => {
                            totalAv += parseFloat(c.procentajeAvanceFisico || 0);
                            numC++;
                        });
                    }
                    const promAv = numC > 0 ? (totalAv / numC).toFixed(1) : 0;
                    
                    const metas = proy.metasProyecto && proy.metasProyecto.length > 0 
                        ? proy.metasProyecto.slice(0, 2).join('; ') : 'N/A';
                    
                    const logro = `${proy.contratosProyecto ? proy.contratosProyecto.length : 0} contratos. Valor: ${bpidCSFormatVal(proy.valorProyecto || 0)}`;
                    
                    html += `<tr><td class="num">${idx + 1}</td><td>${bpidCSEsc(proy.nombreProyecto || 'N/A')}</td>`;
                    html += `<td>${bpidCSEsc(metas)}</td><td class="pct">${promAv}%</td><td>${bpidCSEsc(logro)}</td></tr>`;
                });
                
                html += '</tbody></table></div></div>';
                
                // Lógica de renderizado HTML (Ejecución Presupuestal)
                html += '<div><h3 class="bpid-cs-tabla-title">💰 Ejecución Presupuestal</h3>';
                html += '<div style="overflow-x: auto;"><table class="bpid-cs-table"><thead><tr>';
                html += '<th class="num">No.</th><th>Proyecto</th><th class="val">Asignado</th>';
                html += '<th class="val">Ejecutado</th><th class="pct">% Ejecución</th>';
                html += '</tr></thead><tbody>';
                let totAsig = 0, totEjec = 0;
                
                proyectos.forEach((proy, idx) => {
                    const valProy = parseFloat(proy.valorProyecto || 0);
                    totAsig += valProy;
                    
                    let valEjec = 0;
                    
                    if (proy.contratosProyecto) {
                        proy.contratosProyecto.forEach(c => {
                            const valC = parseFloat(c.valorContrato || 0);
                            const av = parseFloat(c.procentajeAvanceFisico || 0);
                            valEjec += (valC * av / 100);
                        });
                    }
                    totEjec += valEjec;
                    
                    const pctEjec = valProy > 0 ? ((valEjec / valProy) * 100).toFixed(1) : 0;
                    
                    html += `<tr><td class="num">${idx + 1}</td><td>${bpidCSEsc(proy.nombreProyecto || 'N/A')}</td>`;
                    html += `<td class="val">${bpidCSFormatVal(valProy)}</td><td class="val">${bpidCSFormatVal(valEjec)}</td>`;
                    html += `<td class="pct">${pctEjec}%</td></tr>`;
                });
                
                html += '</tbody></table></div>';
                
                // Resumen total
                const pctTot = totAsig > 0 ? ((totEjec / totAsig) * 100).toFixed(1) : 0;
                html += '<div class="bpid-cs-resumen">';
                html += '<div class="bpid-cs-resumen-item"><div class="bpid-cs-resumen-label">Total Proyectos</div>';
                html += `<div class="bpid-cs-resumen-value">${proyectos.length}</div></div>`;
                html += '<div class="bpid-cs-resumen-item"><div class="bpid-cs-resumen-label">Asignado Total</div>';
                html += `<div class="bpid-cs-resumen-value">${bpidCSFormatVal(totAsig)}</div></div>`;
                html += '<div class="bpid-cs-resumen-item"><div class="bpid-cs-resumen-label">Ejecutado Total</div>';
                html += `<div class="bpid-cs-resumen-value">${bpidCSFormatVal(totEjec)}</div></div>`;
                html += '<div class="bpid-cs-resumen-item"><div class="bpid-cs-resumen-label">% Ejecución Total</div>';
                html += `<div class="bpid-cs-resumen-value">${pctTot}%</div></div>`;
                html += '</div></div>';
                
                container.innerHTML = html;
            }
            
            // Lógica de exportación AJAX
            function bpidCSExportar(fmt) {
                if (!bpidCSDepActual) {
                    alert('Seleccione una dependencia primero');
                    return;
                }
                
                console.log(`DIAGNÓSTICO JS: Iniciando exportación a ${fmt}. Dependencia a enviar: "${bpidCSDepActual}"`); 
                
                const status = document.getElementById('bpid-cs-status');
                const btnW = document.querySelector('.bpid-cs-btn-word');
                const btnE = document.querySelector('.bpid-cs-btn-excel');
                
                btnW.disabled = true;
                btnE.disabled = false;
                
                status.className = 'bpid-cs-status loading';
                status.textContent = '⏳ Generando documento...';
                
                const formData = new FormData();
                formData.append('nonce', '<?php echo $nonce; ?>');
                formData.append('dependencia', bpidCSDepActual);
                formData.append('action', fmt === 'word' ? 'bpid_cs_exportar_word' : 'bpid_cs_exportar_excel');
                
                fetch('<?php echo $ajax_url; ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        console.error("DIAGNÓSTICO JS: Fallo HTTP:", response.status); 
                        return response.json().then(errorData => {
                            // Captura el mensaje de diagnóstico del servidor
                            throw new Error(errorData.data.message || 'Error desconocido en el servidor');
                        }).catch(e => {
                            throw new Error('Error ' + response.status + ' al generar el archivo');
                        });
                    }
                    return response.blob();
                })
                .then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    const ext = fmt === 'word' ? 'doc' : 'xls';
                    const filename = `Informe_${bpidCSDepActual.replace(/ /g, '_')}_${new Date().toISOString().split('T')[0]}.${ext}`;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    
                    status.className = 'bpid-cs-status success';
                    status.textContent = '✅ Documento generado exitosamente';
                    setTimeout(() => { status.style.display = 'none'; }, 5000);
                })
                .catch(error => {
                    console.error('DIAGNÓSTICO JS: Error final capturado:', error); 
                    status.className = 'bpid-cs-status error';
                    status.textContent = '❌ Error: ' + error.message;
                })
                .finally(() => {
                    btnW.disabled = false;
                    btnE.disabled = false;
                });
            }
        </script>
        <?php
    }
}


// =============================================================================
// OPCIONAL: Limpieza de Caché
// =============================================================================

if (!function_exists('bpid_cs_limpiar_cache')) {
    /**
     * Limpia la caché (transient) de la API.
     */
    function bpid_cs_limpiar_cache() {
        delete_transient('bpid_cs_api_data');
    }
}
