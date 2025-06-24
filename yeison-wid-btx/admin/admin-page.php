<?php
/**
 * Página de administración MEJORADA - Yeison BTX Widget
 * Sistema completo de herramientas para gestión de widgets
 * 
 * @package YeisonBTX_Widget
 */

if (!defined('ABSPATH')) {
    exit;
}

// Procesar formulario de configuración
if (isset($_POST['submit']) && current_user_can('manage_options')) {
    check_admin_referer('yeison_btx_widget_config');
    
    $domain = sanitize_text_field($_POST['bitrix_domain'] ?? '');
    $client_id = sanitize_text_field($_POST['client_id'] ?? '');
    $client_secret = sanitize_text_field($_POST['client_secret'] ?? '');
    
    yeison_btx_widget_update_option('bitrix_domain', $domain);
    yeison_btx_widget_update_option('client_id', $client_id);
    yeison_btx_widget_update_option('client_secret', $client_secret);
    
    echo '<div class="notice notice-success"><p>✅ Configuración guardada correctamente</p></div>';
    
    yeison_btx_widget_log('Configuración actualizada desde admin', 'info', array(
        'domain' => $domain,
        'client_id_set' => !empty($client_id)
    ));
}

// Obtener configuración actual
$domain = yeison_btx_widget_get_option('bitrix_domain', '');
$client_id = yeison_btx_widget_get_option('client_id', '');
$client_secret = yeison_btx_widget_get_option('client_secret', '');
$access_token = yeison_btx_widget_get_option('access_token', '');
$widget_secret = yeison_btx_widget_get_option('widget_secret', '');

// Estado de la configuración
$is_configured = yeison_btx_widget_is_configured();
$has_tokens = yeison_btx_widget_has_valid_tokens();

// URL de autorización
$auth_url = '';
$show_auth_button = false;

if ($is_configured) {
    $api = yeison_btx_widget_api();
    if ($api) {
        $auth_url = $api->get_auth_url();
        $show_auth_button = !$has_tokens || (!empty($access_token) && !$api->test_connection()['success']);
    }
}

// URLs del widget
$widget_url = home_url('/?yeison_widget=contact');
$test_widget_url = admin_url('admin-ajax.php?action=yeison_btx_widget_test_widget');
?>

<div class="wrap">
    <h1>🔗 Yeison BTX Widget - Panel de Control</h1>
    
    <!-- STATUS GENERAL -->
    <div class="status-dashboard">
        <h2>📊 Estado del Sistema</h2>
        
        <div class="status-cards">
            <div class="status-card">
                <h3>🔧 Configuración</h3>
                <div class="status-items">
                    <div class="status-item">
                        <span class="label">Plugin:</span>
                        <span class="value">v<?php echo YEISON_BTX_WIDGET_VERSION; ?></span>
                        <span class="status-icon">✅</span>
                    </div>
                    <div class="status-item">
                        <span class="label">Dominio:</span>
                        <span class="value"><?php echo $domain ?: 'No configurado'; ?></span>
                        <span class="status-icon"><?php echo $domain ? '✅' : '❌'; ?></span>
                    </div>
                    <div class="status-item">
                        <span class="label">Credenciales:</span>
                        <span class="value"><?php echo ($client_id && $client_secret) ? 'Configuradas' : 'Faltantes'; ?></span>
                        <span class="status-icon"><?php echo ($client_id && $client_secret) ? '✅' : '❌'; ?></span>
                    </div>
                    <div class="status-item">
                        <span class="label">Autorización:</span>
                        <span class="value" id="auth-status">Verificando...</span>
                        <span class="status-icon" id="auth-icon">⏳</span>
                    </div>
                </div>
            </div>
            
            <div class="status-card">
                <h3>🔗 Widget Info</h3>
                <div class="status-items">
                    <div class="status-item">
                        <span class="label">URL Principal:</span>
                        <span class="value widget-url"><?php echo esc_html($widget_url); ?></span>
                        <button class="copy-btn" onclick="copyToClipboard('<?php echo esc_js($widget_url); ?>')">📋</button>
                    </div>
                    <div class="status-item">
                        <span class="label">Widget Secret:</span>
                        <span class="value"><?php echo $widget_secret ? 'Generado' : 'Faltante'; ?></span>
                        <span class="status-icon"><?php echo $widget_secret ? '✅' : '❌'; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CONFIGURACIÓN -->
    <div class="config-section">
        <h2>⚙️ Configuración de Bitrix24</h2>
        
        <form method="post" class="config-form">
            <?php wp_nonce_field('yeison_btx_widget_config'); ?>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="bitrix_domain">Dominio de Bitrix24</label>
                    <input type="text" 
                           id="bitrix_domain" 
                           name="bitrix_domain" 
                           value="<?php echo esc_attr($domain); ?>" 
                           placeholder="mi-empresa.bitrix24.com"
                           class="form-control" />
                    <small>Tu dominio de Bitrix24 (sin https://)</small>
                </div>
                
                <div class="form-group">
                    <label for="client_id">Client ID</label>
                    <input type="text" 
                           id="client_id" 
                           name="client_id" 
                           value="<?php echo esc_attr($client_id); ?>" 
                           placeholder="local.674d8a8c123456.12345678"
                           class="form-control" />
                </div>
                
                <div class="form-group">
                    <label for="client_secret">Client Secret</label>
                    <input type="password" 
                           id="client_secret" 
                           name="client_secret" 
                           value="<?php echo esc_attr($client_secret); ?>" 
                           placeholder="abcd1234efgh5678ijkl9012"
                           class="form-control" />
                </div>
            </div>
            
            <button type="submit" name="submit" class="btn btn-primary">💾 Guardar Configuración</button>
        </form>
        
        <?php if ($show_auth_button && $auth_url): ?>
        <div class="auth-section">
            <h3>🔐 Autorización Requerida</h3>
            <p>Para conectar con Bitrix24, necesitas autorizar el plugin:</p>
            <a href="<?php echo esc_url($auth_url); ?>" class="btn btn-success btn-lg">
                🚀 Autorizar con Bitrix24
            </a>
            <div class="redirect-info">
                <small><strong>Redirect URI:</strong> <code><?php echo admin_url('admin.php?page=yeison-btx-widget&action=oauth'); ?></code></small>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- HERRAMIENTAS PRINCIPALES -->
    <div class="tools-section">
        <h2>🛠️ Herramientas de Gestión</h2>
        
        <div class="tools-grid">
            <!-- PRUEBAS -->
            <div class="tool-card">
                <h3>🧪 Pruebas</h3>
                <div class="tool-actions">
                    <button onclick="testConnection()" class="btn btn-info">
                        🔍 Test Conexión
                    </button>
                    <a href="<?php echo esc_url($test_widget_url); ?>" target="_blank" class="btn btn-info">
                        🧪 Probar Widget
                    </a>
                    <a href="<?php echo admin_url('admin-ajax.php?action=yeison_btx_widget_view_logs'); ?>" target="_blank" class="btn btn-info">
                        📝 Ver Logs
                    </a>
                </div>
                <div id="test-results" class="result-area"></div>
            </div>

            <!-- WIDGETS -->
            <div class="tool-card">
                <h3>📝 Gestión de Widgets</h3>
                <div class="tool-actions">
                    <button onclick="registerWidget()" class="btn btn-success">
                        ➕ Registrar Widget
                    </button>
                    <button onclick="checkWidgets()" class="btn btn-primary">
                        🔍 Verificar Widgets
                    </button>
                    <button onclick="deleteAllWidgets()" class="btn btn-danger">
                        🗑️ Eliminar Todos
                    </button>
                </div>
                <div id="widget-results" class="result-area"></div>
            </div>

            <!-- DIAGNÓSTICO -->
            <div class="tool-card">
                <h3>🔬 Diagnóstico General</h3>
                <div class="tool-actions">
                    <button onclick="runFullDiagnostic()" class="btn btn-warning">
                        🔬 Test Completo
                    </button>
                    <button onclick="clearCache()" class="btn btn-secondary">
                        🧹 Limpiar Cache
                    </button>
                    <button onclick="regenerateSecret()" class="btn btn-secondary">
                        🔐 Regenerar Secret
                    </button>
                </div>
                <div id="diagnostic-results" class="result-area"></div>
            </div>
        </div>
    </div>

    <!-- TABLA DE DIAGNÓSTICO COMPLETO -->
    <div id="full-diagnostic-section" class="diagnostic-section" style="display: none;">
        <h2>🔬 Diagnóstico Completo del Sistema</h2>
        <div id="diagnostic-tables"></div>
    </div>
</div>

<!-- ESTILOS -->
<style>
.status-dashboard {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.status-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.status-card {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    padding: 15px;
}

.status-card h3 {
    margin: 0 0 15px 0;
    color: #495057;
}

.status-items {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.status-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #e9ecef;
}

.status-item:last-child {
    border-bottom: none;
}

.status-item .label {
    font-weight: 600;
    color: #495057;
    min-width: 100px;
}

.status-item .value {
    flex: 1;
    margin: 0 10px;
    font-family: monospace;
    font-size: 13px;
    color: #6c757d;
}

.widget-url {
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.copy-btn {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 14px;
    padding: 2px 5px;
    border-radius: 3px;
}

.copy-btn:hover {
    background: #e9ecef;
}

.config-section, .tools-section, .diagnostic-section {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 600;
    margin-bottom: 5px;
    color: #495057;
}

.form-control {
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 14px;
}

.form-group small {
    margin-top: 5px;
    color: #6c757d;
    font-size: 12px;
}

.tools-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.tool-card {
    border: 1px solid #e9ecef;
    border-radius: 6px;
    padding: 15px;
    background: #f8f9fa;
}

.tool-card h3 {
    margin: 0 0 15px 0;
    color: #495057;
}

.tool-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 15px;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    text-decoration: none;
    text-align: center;
    transition: all 0.2s;
    display: inline-block;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.btn-primary { background: #007cba; color: white; }
.btn-success { background: #28a745; color: white; }
.btn-danger { background: #dc3545; color: white; }
.btn-warning { background: #ffc107; color: #212529; }
.btn-info { background: #17a2b8; color: white; }
.btn-secondary { background: #6c757d; color: white; }
.btn-lg { padding: 12px 24px; font-size: 16px; }

.result-area {
    min-height: 40px;
    padding: 10px;
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    font-family: monospace;
    font-size: 12px;
    white-space: pre-wrap;
    overflow-x: auto;
}

.auth-section {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    padding: 20px;
    border-radius: 6px;
    margin-top: 20px;
    text-align: center;
}

.redirect-info {
    margin-top: 15px;
}

.redirect-info code {
    background: #f1f1f1;
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 11px;
}

.diagnostic-table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
    background: white;
}

.diagnostic-table th,
.diagnostic-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.diagnostic-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #495057;
}

.diagnostic-table tr:hover {
    background: #f8f9fa;
}

.status-ok { color: #28a745; font-weight: 600; }
.status-warning { color: #ffc107; font-weight: 600; }
.status-error { color: #dc3545; font-weight: 600; }

.loading {
    opacity: 0.6;
    pointer-events: none;
}

.loading::after {
    content: "⏳ Cargando...";
    margin-left: 10px;
}
</style>

<!-- JAVASCRIPT -->
<script>
const AJAX_URL = '<?php echo admin_url('admin-ajax.php'); ?>';
const NONCES = {
    test: '<?php echo wp_create_nonce('yeison_btx_widget_test'); ?>',
    register: '<?php echo wp_create_nonce('yeison_btx_widget_register'); ?>',
    check: '<?php echo wp_create_nonce('yeison_btx_widget_check'); ?>',
    cleanup: '<?php echo wp_create_nonce('yeison_btx_widget_cleanup'); ?>',
    diagnostic: '<?php echo wp_create_nonce('yeison_btx_widget_diagnostic'); ?>'
};

// Verificar autorización al cargar
document.addEventListener('DOMContentLoaded', function() {
    checkAuthStatus();
});

async function checkAuthStatus() {
    try {
        const response = await makeAjaxRequest('yeison_btx_widget_test_connection', {
            nonce: NONCES.test
        });
        
        const statusEl = document.getElementById('auth-status');
        const iconEl = document.getElementById('auth-icon');
        
        if (response.success) {
            statusEl.textContent = 'Conectado';
            statusEl.className = 'status-ok';
            iconEl.textContent = '✅';
        } else {
            statusEl.textContent = 'Error: ' + response.data;
            statusEl.className = 'status-error';
            iconEl.textContent = '❌';
        }
    } catch (error) {
        document.getElementById('auth-status').textContent = 'Error verificando';
        document.getElementById('auth-icon').textContent = '❌';
    }
}

async function testConnection() {
    const resultEl = document.getElementById('test-results');
    resultEl.textContent = 'Probando conexión...';
    
    try {
        const response = await makeAjaxRequest('yeison_btx_widget_test_connection', {
            nonce: NONCES.test
        });
        
        if (response.success) {
            resultEl.innerHTML = '<span class="status-ok">✅ ' + response.data + '</span>';
        } else {
            resultEl.innerHTML = '<span class="status-error">❌ ' + response.data + '</span>';
        }
    } catch (error) {
        resultEl.innerHTML = '<span class="status-error">❌ Error: ' + error.message + '</span>';
    }
}

async function registerWidget() {
    const resultEl = document.getElementById('widget-results');
    resultEl.textContent = 'Registrando widget en Bitrix24...';
    
    try {
        const response = await makeAjaxRequest('yeison_btx_widget_register_widget_ajax', {
            nonce: NONCES.register
        });
        
        if (response.success) {
            resultEl.innerHTML = '<span class="status-ok">✅ ' + response.data.message + '</span>';
            if (response.data.details) {
                resultEl.innerHTML += '\n\nDetalles:\nHandler: ' + response.data.details.handler + '\nPlacement: ' + response.data.details.placement;
            }
        } else {
            resultEl.innerHTML = '<span class="status-error">❌ ' + response.data + '</span>';
        }
    } catch (error) {
        resultEl.innerHTML = '<span class="status-error">❌ Error: ' + error.message + '</span>';
    }
}

async function checkWidgets() {
    const resultEl = document.getElementById('widget-results');
    resultEl.textContent = 'Verificando widgets registrados...';
    
    try {
        const response = await makeAjaxRequest('yeison_btx_widget_check_widgets_ajax', {
            nonce: NONCES.check
        });
        
        if (response.success) {
            let output = '<span class="status-ok">✅ ' + response.data.message + '</span>\n';
            
            if (response.data.widgets && response.data.widgets.length > 0) {
                output += '\nWidgets encontrados:\n';
                response.data.widgets.forEach(widget => {
                    output += `- ${widget.placement}: ${widget.handler}\n`;
                });
            }
            
            resultEl.innerHTML = output;
        } else {
            resultEl.innerHTML = '<span class="status-error">❌ ' + response.data + '</span>';
        }
    } catch (error) {
        resultEl.innerHTML = '<span class="status-error">❌ Error: ' + error.message + '</span>';
    }
}

async function deleteAllWidgets() {
    if (!confirm('¿Estás seguro de que quieres eliminar TODOS los widgets de este sitio en Bitrix24?')) {
        return;
    }
    
    const resultEl = document.getElementById('widget-results');
    resultEl.textContent = 'Eliminando todos los widgets...';
    
    try {
        const response = await makeAjaxRequest('yeison_btx_widget_cleanup_widgets', {
            nonce: NONCES.cleanup
        });
        
        if (response.success) {
            resultEl.innerHTML = '<span class="status-ok">✅ Limpieza completada\n' + 
                                'Eliminados: ' + response.data.removed + '\n' +
                                'Errores: ' + response.data.failed + '</span>';
        } else {
            resultEl.innerHTML = '<span class="status-error">❌ ' + response.data + '</span>';
        }
    } catch (error) {
        resultEl.innerHTML = '<span class="status-error">❌ Error: ' + error.message + '</span>';
    }
}

async function runFullDiagnostic() {
    const resultEl = document.getElementById('diagnostic-results');
    const fullSection = document.getElementById('full-diagnostic-section');
    const tablesEl = document.getElementById('diagnostic-tables');
    
    resultEl.textContent = 'Ejecutando diagnóstico completo...';
    
    try {
        const response = await makeAjaxRequest('yeison_btx_widget_full_diagnostic', {
            nonce: NONCES.diagnostic
        });
        
        if (response.success) {
            resultEl.innerHTML = '<span class="status-ok">✅ Diagnóstico completado</span>';
            
            // Mostrar tablas
            tablesEl.innerHTML = response.data.tables_html;
            fullSection.style.display = 'block';
            
            // Scroll hacia las tablas
            fullSection.scrollIntoView({ behavior: 'smooth' });
        } else {
            resultEl.innerHTML = '<span class="status-error">❌ ' + response.data + '</span>';
        }
    } catch (error) {
        resultEl.innerHTML = '<span class="status-error">❌ Error: ' + error.message + '</span>';
    }
}

async function clearCache() {
    const resultEl = document.getElementById('diagnostic-results');
    resultEl.textContent = 'Limpiando cache...';
    
    try {
        const response = await makeAjaxRequest('yeison_btx_widget_clear_cache', {
            nonce: NONCES.diagnostic
        });
        
        if (response.success) {
            resultEl.innerHTML = '<span class="status-ok">✅ ' + response.data + '</span>';
        } else {
            resultEl.innerHTML = '<span class="status-error">❌ ' + response.data + '</span>';
        }
    } catch (error) {
        resultEl.innerHTML = '<span class="status-error">❌ Error: ' + error.message + '</span>';
    }
}

async function regenerateSecret() {
    if (!confirm('¿Regenerar el Widget Secret? Esto requerirá reconfigurar los widgets en Bitrix24.')) {
        return;
    }
    
    const resultEl = document.getElementById('diagnostic-results');
    resultEl.textContent = 'Regenerando widget secret...';
    
    try {
        const response = await makeAjaxRequest('yeison_btx_widget_regenerate_secret', {
            nonce: NONCES.diagnostic
        });
        
        if (response.success) {
            resultEl.innerHTML = '<span class="status-ok">✅ ' + response.data + '</span>';
            // Recargar la página para actualizar la interfaz
            setTimeout(() => location.reload(), 2000);
        } else {
            resultEl.innerHTML = '<span class="status-error">❌ ' + response.data + '</span>';
        }
    } catch (error) {
        resultEl.innerHTML = '<span class="status-error">❌ Error: ' + error.message + '</span>';
    }
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('URL copiada al portapapeles');
    });
}

async function makeAjaxRequest(action, data) {
    const formData = new FormData();
    formData.append('action', action);
    
    for (const [key, value] of Object.entries(data)) {
        formData.append(key, value);
    }
    
    const response = await fetch(AJAX_URL, {
        method: 'POST',
        body: formData
    });
    
    if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }
    
    return await response.json();
}
</script>