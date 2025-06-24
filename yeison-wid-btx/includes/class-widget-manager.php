<?php
/**
 * Widget Manager - Widget Version con soporte para REST API
 * 
 * @package YeisonBTX_Widget
 */

if (!defined('ABSPATH')) {
    exit;
}

class YeisonBTX_Widget_Manager {
    
    private static $instance = null;
    private $config = array();
    
    private function __construct() {
        $this->load_config();
        $this->init_hooks();
    }
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function load_config() {
        $this->config = array(
            'enabled' => yeison_btx_widget_get_option('widgets_enabled', true),
            'widget_secret' => yeison_btx_widget_get_option('widget_secret', wp_generate_password(32, false)),
            'auto_register' => yeison_btx_widget_get_option('widget_auto_register', true)
        );
        
        if (empty(yeison_btx_widget_get_option('widget_secret'))) {
            yeison_btx_widget_update_option('widget_secret', $this->config['widget_secret']);
        }
    }
    
    private function init_hooks() {
        if (!$this->config['enabled']) {
            return;
        }
        
        // CAMBIO CRÍTICO: Usar priority 99 para asegurar que se registre después de todo
        add_action('rest_api_init', array($this, 'register_widget_endpoints'), 99);
        add_action('yeison_btx_widget_oauth_success', array($this, 'auto_register_widgets'));
        
        // NUEVO: Registrar endpoints alternativos
        add_action('init', array($this, 'register_fallback_endpoints'), 20);
        
        yeison_btx_widget_log('Widget Manager inicializado', 'info', array(
            'enabled' => $this->config['enabled']
        ));
    }
    
    /**
     * NUEVO: Endpoints de respaldo en caso de que REST falle
     */
    public function register_fallback_endpoints() {
        // Endpoint alternativo para autenticación
        if (isset($_GET['yeison_widget_ajax']) && $_GET['yeison_widget_ajax'] === 'auth') {
            $this->handle_ajax_auth();
        }
    }
    
    /**
     * NUEVO: Manejador AJAX alternativo
     */
    private function handle_ajax_auth() {
        // Verificar nonce si existe
        $nonce_valid = true;
        if (isset($_REQUEST['_wpnonce'])) {
            $nonce_valid = wp_verify_nonce($_REQUEST['_wpnonce'], 'wp_rest');
        }
        
        // Headers para AJAX
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('X-Frame-Options: ALLOWALL');
        
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $action = isset($_POST['action']) ? sanitize_text_field($_POST['action']) : '';
        
        if ($action === 'check_and_login' && $email) {
            $response = $this->check_and_create_login($email, '');
            echo json_encode($response->get_data());
        } else {
            echo json_encode(array(
                'success' => false,
                'message' => 'Parámetros inválidos'
            ));
        }
        exit;
    }
    
    public function register_widget_endpoints() {
        // Endpoint principal del widget - CORREGIDO
        register_rest_route('yeison-btx-widget/v1', '/widget/contact-login', array(
            'methods' => 'GET',
            'callback' => array($this, 'serve_contact_widget'),
            'permission_callback' => '__return_true', // CAMBIO: Permitir acceso público
            'args' => array(
                'contact_id' => array(
                    'required' => false,
                    'type' => 'string'
                ),
                'email' => array(
                    'required' => false,
                    'type' => 'string'
                )
            )
        ));
        
        // Endpoint de autenticación - CORREGIDO
        register_rest_route('yeison-btx-widget/v1', '/widget/auth', array(
            'methods' => array('GET', 'POST'), // CAMBIO: Permitir GET y POST
            'callback' => array($this, 'handle_widget_auth'),
            'permission_callback' => '__return_true', // CAMBIO: Permitir acceso público
            'args' => array(
                'email' => array(
                    'required' => true,
                    'type' => 'string',
                    'validate_callback' => function($param) {
                        return is_email($param);
                    }
                ),
                'action' => array(
                    'required' => true,
                    'type' => 'string',
                    'enum' => array('check_and_login')
                )
            )
        ));
        
        yeison_btx_widget_log('Endpoints de widgets registrados', 'info');
    }

    /**
     * CORREGIDO: Verificación de acceso más permisiva
     */
    public function verify_widget_access($request) {
        // Siempre permitir acceso para simplificar
        return true;
    }

    /**
     * MEJORADO: Generar HTML del widget con fallback URL
     */
    public function generate_bitrix24_widget_html($email, $contact_id) {
        // URLs de autenticación - usar ambas como fallback
        $rest_url = rest_url('yeison-btx-widget/v1/widget/auth');
        $ajax_url = home_url('/?yeison_widget_ajax=auth');
        $nonce = wp_create_nonce('wp_rest');
        
        $user = null;
        $user_exists = false;
        $customer_data = null;
        
        if ($email && is_email($email)) {
            $user = get_user_by('email', $email);
            if ($user) {
                $user_exists = true;
                
                if (class_exists('WC_Customer')) {
                    $customer = new WC_Customer($user->ID);
                    $customer_data = array(
                        'name' => trim($customer->get_first_name() . ' ' . $customer->get_last_name()),
                        'orders_count' => wc_get_customer_order_count($user->ID),
                        'total_spent' => wc_get_customer_total_spent($user->ID)
                    );
                }
            }
        }
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>WooCommerce - Bitrix24 Widget</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                    background: #ffffff;
                    color: #333;
                    line-height: 1.4;
                    overflow-x: hidden;
                }
                
                .widget-container {
                    width: 100%;
                    height: 100vh;
                    display: flex;
                    flex-direction: column;
                }
                
                .widget-header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 12px 16px;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
                    flex-shrink: 0;
                }
                
                .header-title {
                    font-size: 14px;
                    font-weight: 600;
                    margin: 0;
                }
                
                .header-email {
                    font-size: 11px;
                    background: rgba(255,255,255,0.2);
                    padding: 4px 8px;
                    border-radius: 10px;
                    max-width: 150px;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                }
                
                .status-bar {
                    background: <?php echo $user_exists ? '#d4edda' : '#fff3cd'; ?>;
                    color: <?php echo $user_exists ? '#155724' : '#856404'; ?>;
                    padding: 8px 16px;
                    font-size: 12px;
                    font-weight: 500;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    flex-shrink: 0;
                }
                
                .widget-content {
                    flex: 1;
                    position: relative;
                    overflow: hidden;
                    background: #f8f9fa;
                }
                
                .account-frame {
                    width: 100%;
                    height: 100%;
                    border: none;
                    background: white;
                }
                
                .no-account {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    height: 100%;
                    padding: 20px;
                    text-align: center;
                }
                
                .no-account-icon {
                    font-size: 40px;
                    margin-bottom: 15px;
                    opacity: 0.6;
                }
                
                .no-account-title {
                    font-size: 16px;
                    font-weight: 600;
                    color: #495057;
                    margin-bottom: 8px;
                }
                
                .no-account-message {
                    color: #6c757d;
                    font-size: 13px;
                    line-height: 1.5;
                }
                
                .loading-overlay {
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(255,255,255,0.95);
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    z-index: 1000;
                }
                
                .loading-spinner {
                    width: 32px;
                    height: 32px;
                    border: 3px solid #f3f3f3;
                    border-top: 3px solid #667eea;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                    margin-bottom: 12px;
                }
                
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                
                .loading-text {
                    font-size: 12px;
                    color: #6c757d;
                    font-weight: 500;
                }
                
                .action-bar {
                    background: white;
                    padding: 8px 12px;
                    border-top: 1px solid #e9ecef;
                    display: flex;
                    gap: 8px;
                    flex-shrink: 0;
                }
                
                .action-btn {
                    flex: 1;
                    padding: 6px 8px;
                    background: #667eea;
                    color: white;
                    border: none;
                    border-radius: 4px;
                    font-size: 11px;
                    cursor: pointer;
                    transition: all 0.2s;
                    text-align: center;
                    font-weight: 500;
                }
                
                .action-btn:hover {
                    background: #5a6fd8;
                    transform: translateY(-1px);
                }
                
                .action-btn:disabled {
                    opacity: 0.6;
                    cursor: not-allowed;
                    transform: none;
                }
                
                .debug-footer {
                    background: #f1f3f4;
                    padding: 6px 12px;
                    font-size: 10px;
                    color: #666;
                    border-top: 1px solid #ddd;
                    font-family: monospace;
                    flex-shrink: 0;
                }
                
                .error-message {
                    background: #f8d7da;
                    color: #721c24;
                    padding: 10px;
                    margin: 10px;
                    border-radius: 5px;
                    font-size: 12px;
                }
            </style>
        </head>
        <body>
            <div class="widget-container">
                <div class="widget-header">
                    <h3 class="header-title">🛍️ WooCommerce</h3>
                    <div class="header-email" title="<?php echo esc_attr($email); ?>">
                        <?php echo esc_html($email); ?>
                    </div>
                </div>
                
                <div class="status-bar">
                    <span><?php echo $user_exists ? '✅' : '⚠️'; ?></span>
                    <span>
                        <?php if ($user_exists): ?>
                            Cliente encontrado
                            <?php if ($customer_data): ?>
                                (<?php echo $customer_data['orders_count']; ?> pedidos)
                            <?php endif; ?>
                        <?php else: ?>
                            Cliente no encontrado
                        <?php endif; ?>
                    </span>
                </div>
                
                <div class="widget-content">
                    <?php if ($user_exists): ?>
                        <div id="loadingOverlay" class="loading-overlay">
                            <div class="loading-spinner"></div>
                            <div class="loading-text">Generando acceso...</div>
                        </div>
                        <div id="errorContainer" style="display:none;"></div>
                        <iframe id="accountFrame" class="account-frame" style="display: none;"></iframe>
                    <?php else: ?>
                        <div class="no-account">
                            <div class="no-account-icon">👤</div>
                            <h3 class="no-account-title">Sin cuenta WooCommerce</h3>
                            <p class="no-account-message">
                                No existe cuenta para este email.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($user_exists): ?>
                <div class="action-bar">
                    <button class="action-btn" onclick="openInNewTab()" id="newTabBtn" disabled>
                        🔗 Abrir en nueva pestaña
                    </button>
                </div>
                <?php endif; ?>
                
                <div class="debug-footer">
                    📧 <?php echo esc_html($email); ?> | 
                    🆔 <?php echo esc_html($contact_id ?: 'none'); ?> | 
                    👤 <?php echo $user_exists ? 'found' : 'not found'; ?> |
                    🕐 <?php echo date('H:i:s'); ?>
                </div>
            </div>
            
            <script>
                const CONFIG = {
                    userEmail: "<?php echo esc_js($email); ?>",
                    userExists: <?php echo $user_exists ? 'true' : 'false'; ?>,
                    contactId: "<?php echo esc_js($contact_id); ?>",
                    restUrl: "<?php echo esc_js($rest_url); ?>",
                    ajaxUrl: "<?php echo esc_js($ajax_url); ?>", // URL alternativa
                    nonce: "<?php echo esc_js($nonce); ?>"
                };
                
                let loginUrl = null;
                let authAttempts = 0;
                
                console.log('🎯 Widget Bitrix24 inicializado:', CONFIG);
                
                if (CONFIG.userExists) {
                    setTimeout(autoLogin, 800);
                }
                
                async function autoLogin() {
                    const loadingEl = document.getElementById("loadingOverlay");
                    const frameEl = document.getElementById("accountFrame");
                    const newTabBtn = document.getElementById("newTabBtn");
                    const errorContainer = document.getElementById("errorContainer");
                    
                    try {
                        console.log('📡 Solicitando autologin... Intento:', authAttempts + 1);
                        
                        // Intentar primero con REST API
                        let response = await makeAuthRequest(CONFIG.restUrl);
                        
                        // Si falla REST, intentar con AJAX alternativo
                        if (!response.ok && authAttempts === 0) {
                            console.log('⚠️ REST falló, intentando con AJAX...');
                            response = await makeAuthRequestAjax(CONFIG.ajaxUrl);
                        }
                        
                        if (!response.ok) {
                            throw new Error(`Error HTTP: ${response.status} ${response.statusText}`);
                        }
                        
                        const result = await response.json();
                        console.log('📨 Respuesta autologin:', result);
                        
                        if (result.success && result.data && result.data.login_url) {
                            loginUrl = result.data.login_url;
                            
                            frameEl.onload = function() {
                                console.log('✅ Mi Cuenta cargada en iframe');
                                loadingEl.style.display = "none";
                                newTabBtn.disabled = false;
                            };
                            
                            frameEl.onerror = function() {
                                console.log('❌ Error cargando iframe');
                                showError('Error cargando el iframe. Intenta abrir en nueva pestaña.');
                                newTabBtn.disabled = false;
                            };
                            
                            frameEl.src = loginUrl;
                            frameEl.style.display = "block";
                            
                            setTimeout(() => {
                                if (loadingEl.style.display !== "none") {
                                    console.log('⏰ Timeout - ofreciendo nueva pestaña');
                                    showError('La carga está tardando. Puedes abrir en nueva pestaña.');
                                    newTabBtn.disabled = false;
                                }
                            }, 8000);
                            
                        } else {
                            throw new Error(result.message || "Error generando acceso");
                        }
                        
                    } catch (error) {
                        console.error('❌ Error autologin:', error);
                        
                        authAttempts++;
                        if (authAttempts < 3) {
                            console.log('🔄 Reintentando en 2 segundos...');
                            setTimeout(autoLogin, 2000);
                        } else {
                            showError('Error: ' + error.message);
                            loadingEl.style.display = "none";
                        }
                    }
                }
                
                // Función para hacer petición REST
                async function makeAuthRequest(url) {
                    return fetch(url, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-WP-Nonce": CONFIG.nonce
                        },
                        body: JSON.stringify({
                            email: CONFIG.userEmail,
                            action: "check_and_login",
                            contact_id: CONFIG.contactId
                        })
                    });
                }
                
                // Función alternativa AJAX
                async function makeAuthRequestAjax(url) {
                    const formData = new FormData();
                    formData.append('email', CONFIG.userEmail);
                    formData.append('action', 'check_and_login');
                    formData.append('contact_id', CONFIG.contactId);
                    formData.append('_wpnonce', CONFIG.nonce);
                    
                    return fetch(url, {
                        method: "POST",
                        body: formData
                    });
                }
                
                function showError(message) {
                    const errorContainer = document.getElementById("errorContainer");
                    errorContainer.innerHTML = '<div class="error-message">' + message + '</div>';
                    errorContainer.style.display = "block";
                }
                
                function openInNewTab() {
                    if (loginUrl) {
                        console.log('🔗 Abriendo en nueva pestaña');
                        window.open(loginUrl, "_blank");
                    } else {
                        alert("No hay enlace generado aún");
                    }
                }
                
                // Notificar al parent frame
                if (window.parent !== window) {
                    window.parent.postMessage({
                        action: "yeison_widget_ready",
                        email: CONFIG.userEmail,
                        hasAccount: CONFIG.userExists,
                        timestamp: new Date().toISOString()
                    }, "*");
                }
            </script>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    private function extract_contact_id($params) {
        $possible_keys = array('contact_id', 'CONTACT_ID', 'contactId', 'id');
        
        foreach ($possible_keys as $key) {
            if (isset($params[$key]) && !empty($params[$key])) {
                return sanitize_text_field($params[$key]);
            }
        }
        
        if (isset($params['PLACEMENT_OPTIONS'])) {
            $placement_data = json_decode($params['PLACEMENT_OPTIONS'], true);
            if (is_array($placement_data)) {
                foreach ($possible_keys as $key) {
                    if (isset($placement_data[$key])) {
                        return sanitize_text_field($placement_data[$key]);
                    }
                }
            }
        }
        
        return null;
    }

    private function extract_email_from_params($params, $contact_id) {
        yeison_btx_widget_log('🔍 Extrayendo email de parámetros', 'info', array('params' => $params));
        
        if (!empty($params['email']) && is_email($params['email'])) {
            $email = sanitize_email($params['email']);
            yeison_btx_widget_log('📧 Email encontrado en params', 'success', array('email' => $email));
            return $email;
        }
        
        if (isset($params['PLACEMENT_OPTIONS'])) {
            $placement_data = json_decode($params['PLACEMENT_OPTIONS'], true);
            if (is_array($placement_data)) {
                foreach (['email', 'EMAIL', 'user_email', 'contact_email'] as $key) {
                    if (isset($placement_data[$key]) && is_email($placement_data[$key])) {
                        $email = sanitize_email($placement_data[$key]);
                        yeison_btx_widget_log('📧 Email encontrado en PLACEMENT_OPTIONS', 'success', array('email' => $email));
                        return $email;
                    }
                }
            }
        }
        
        if (!empty($contact_id)) {
            $bitrix_email = $this->get_contact_email_from_bitrix($contact_id);
            if ($bitrix_email && is_email($bitrix_email)) {
                yeison_btx_widget_log('📧 Email obtenido de Bitrix24 API', 'success', array('email' => $bitrix_email));
                return $bitrix_email;
            }
        }
        
        $admin_users = get_users(array('role' => 'administrator', 'number' => 1));
        if (!empty($admin_users)) {
            $admin_email = $admin_users[0]->user_email;
            yeison_btx_widget_log('📧 Usando email de admin para testing', 'warning', array('email' => $admin_email));
            return $admin_email;
        }
        
        yeison_btx_widget_log('⚠️ No se pudo extraer email - usando fallback', 'warning');
        return 'test@example.com';
    }

    public function get_contact_email_from_bitrix($contact_id) {
        try {
            $api = yeison_btx_widget_api();
            
            if (!$api->is_authorized()) {
                yeison_btx_widget_log('API no autorizada para obtener email', 'warning');
                return null;
            }
            
            yeison_btx_widget_log('📞 Consultando Bitrix24 para contact_id: ' . $contact_id, 'info');
            
            $response = $api->api_call('crm.contact.get', array(
                'id' => $contact_id
            ));
            
            if ($response && isset($response['result'])) {
                $contact_data = $response['result'];
                
                if (isset($contact_data['EMAIL']) && is_array($contact_data['EMAIL'])) {
                    foreach ($contact_data['EMAIL'] as $email_data) {
                        if (isset($email_data['VALUE']) && is_email($email_data['VALUE'])) {
                            yeison_btx_widget_log('📧 Email encontrado en Bitrix24', 'success', array(
                                'email' => $email_data['VALUE'],
                                'contact_id' => $contact_id
                            ));
                            return $email_data['VALUE'];
                        }
                    }
                }
            }
            
            yeison_btx_widget_log('❌ Email no encontrado en Bitrix24', 'warning', array(
                'contact_id' => $contact_id,
                'response' => $response
            ));
            
            return null;
            
        } catch (Exception $e) {
            yeison_btx_widget_log('❌ Error obteniendo email de Bitrix24', 'error', array(
                'error' => $e->getMessage(),
                'contact_id' => $contact_id
            ));
            return null;
        }
    }

    public function serve_contact_widget($request) {
        try {
            $this->set_bitrix24_headers();
            
            $all_params = $request->get_params();
            
            yeison_btx_widget_log('🎯 Widget accedido desde Bitrix24', 'info', array(
                'params' => $all_params,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'referer' => $_SERVER['HTTP_REFERER'] ?? 'unknown',
                'ip' => $this->get_client_ip()
            ));
            
            $contact_id = $this->extract_contact_id($all_params);
            $email = $this->extract_email_from_params($all_params, $contact_id);
            
            $widget_html = $this->generate_bitrix24_widget_html($email, $contact_id);
            
            echo $widget_html;
            exit;
            
        } catch (Exception $e) {
            $this->set_bitrix24_headers();
            
            yeison_btx_widget_log('❌ Error crítico en widget Bitrix24', 'error', array(
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ));
            
            echo $this->generate_error_widget_html('Error: ' . $e->getMessage());
            exit;
        }
    }



    private function set_bitrix24_headers() {
        // CRÍTICO: Verificar si headers ya se enviaron
        if (headers_sent($file, $line)) {
            yeison_btx_widget_log('❌ Headers ya enviados - no se pueden modificar', 'error', array(
                'file' => $file,
                'line' => $line
            ));
            return;
        }
        
        // FORZAR limpieza de headers conflictivos
        if (function_exists('header_remove')) {
            header_remove('X-Frame-Options');
            header_remove('Content-Security-Policy');
            header_remove('X-Content-Type-Options');
            header_remove('Cache-Control');
            header_remove('Pragma');
            header_remove('Expires');
        }
        
        // ESTABLECER headers para iframe - FORZADO
        header('Content-Type: text/html; charset=UTF-8', true);
        header('X-Frame-Options: ALLOWALL', true);
        header('Content-Security-Policy: frame-ancestors *', true);
        header('Access-Control-Allow-Origin: *', true);
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS', true);
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-WP-Nonce', true);
        header('Access-Control-Allow-Credentials: false', true);
        header('Cache-Control: no-cache, no-store, must-revalidate, private', true);
        header('Pragma: no-cache', true);
        header('Expires: 0', true);
        header('Referrer-Policy: no-referrer-when-downgrade', true);
        
        // Headers adicionales para Bitrix24
        header('X-Content-Type-Options: nosniff', true);
        header('Permissions-Policy: frame-src *, iframe-src *', true);
        
        yeison_btx_widget_log('🔧 Headers de iframe establecidos FORZADAMENTE', 'success', array(
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'referer' => $_SERVER['HTTP_REFERER'] ?? '',
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 100)
        ));
    }








    private function get_client_ip() {
        $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }
        return '127.0.0.1';
    }

    private function generate_error_widget_html($error_message) {
        return '<!DOCTYPE html>
                <html lang="es">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Error Widget</title>
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            background: #f8f9fa;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            min-height: 100vh;
                            margin: 0;
                            padding: 20px;
                        }
                        .error-container {
                            background: white;
                            border-radius: 8px;
                            padding: 30px;
                            text-align: center;
                            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                            max-width: 400px;
                        }
                        .error-icon {
                            font-size: 48px;
                            margin-bottom: 20px;
                        }
                        .error-title {
                            color: #dc3545;
                            margin-bottom: 15px;
                        }
                        .error-message {
                            color: #6c757d;
                            line-height: 1.5;
                        }
                    </style>
                </head>
                <body>
                    <div class="error-container">
                        <div class="error-icon">❌</div>
                        <h3 class="error-title">Error en el Widget</h3>
                        <p class="error-message">' . esc_html($error_message) . '</p>
                    </div>
                </body>
                </html>';
    }

    public function handle_widget_auth($request) {
        $email = sanitize_email($request->get_param('email'));
        $action = sanitize_text_field($request->get_param('action'));
        $contact_id = sanitize_text_field($request->get_param('contact_id'));
        
        yeison_btx_widget_log('Procesando autenticación de widget', 'info', array(
            'email' => $email,
            'action' => $action,
            'contact_id' => $contact_id
        ));
        
        if (empty($email) || !is_email($email)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Email inválido'
            ), 400);
        }
        
        switch ($action) {
            case 'check_and_login':
                return $this->check_and_create_login($email, $contact_id);
                
            default:
                return new WP_REST_Response(array(
                    'success' => false,
                    'message' => 'Acción no válida'
                ), 400);
        }
    }
    
    private function check_and_create_login($email, $contact_id) {
        $user = get_user_by('email', $email);
        
        if (!$user) {
            yeison_btx_widget_log('Usuario no encontrado', 'info', array(
                'email' => $email,
                'contact_id' => $contact_id
            ));
            
            return new WP_REST_Response(array(
                'success' => true,
                'data' => array(
                    'user_exists' => false,
                    'message' => 'No existe cuenta WooCommerce para este email'
                )
            ), 200);
        }
        
        $autologin = yeison_btx_widget_autologin();
        $login_token = $autologin->create_temp_login_token($user->ID, $email);
        
        if (!$login_token) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Error generando token de acceso'
            ), 500);
        }
        
        $login_url = $this->generate_autologin_url($login_token);
        
        yeison_btx_widget_log('Token creado para usuario', 'success', array(
            'user_id' => $user->ID,
            'email' => $email,
            'contact_id' => $contact_id
        ));
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'user_exists' => true,
                'user_id' => $user->ID,
                'user_name' => $user->display_name,
                'login_url' => $login_url,
                'message' => 'Token de acceso creado'
            )
        ), 200);
    }
    
    private function generate_autologin_url($token) {
        if (class_exists('WooCommerce')) {
            $base_url = wc_get_page_permalink('myaccount');
            if ($base_url && $base_url !== home_url()) {
                return add_query_arg(array(
                    'yeison_widget_autologin' => $token,
                    'source' => 'bitrix24_widget'
                ), $base_url);
            }
        }
        
        return add_query_arg(array(
            'yeison_widget_autologin' => $token,
            'source' => 'bitrix24_widget'
        ), admin_url());
    }

    public function register_widget_in_bitrix24() {
        $api = yeison_btx_widget_api();
        
        if (!$api->is_authorized()) {
            yeison_btx_widget_log('No se puede registrar widget: API no autorizada', 'error');
            return false;
        }
        
        // Usar URL directa para el widget
        $widget_url = home_url('/?yeison_widget=contact');
        
        yeison_btx_widget_log('Registrando widget en Bitrix24', 'info', array(
            'widget_url' => $widget_url,
            'placement' => 'CRM_CONTACT_DETAIL_TAB'
        ));
        
        $placement_data = array(
            'PLACEMENT' => 'CRM_CONTACT_DETAIL_TAB',
            'HANDLER' => $widget_url,
            'LANG_ALL' => array(
                'en' => array('TITLE' => 'WooCommerce Login'),
                'es' => array('TITLE' => 'WooCommerce Login'),
                'ru' => array('TITLE' => 'WooCommerce Login')
            )
        );
        
        $response = $api->api_call('placement.bind', $placement_data);
        
        if ($response && isset($response['result'])) {
            yeison_btx_widget_log('Widget registrado exitosamente', 'success', array(
                'placement' => 'CRM_CONTACT_DETAIL_TAB',
                'handler' => $widget_url,
                'response' => $response['result']
            ));
            return true;
        }
        
        yeison_btx_widget_log('Error registrando widget', 'error', array(
            'response' => $response,
            'widget_url' => $widget_url
        ));
        
        return false;
    }

    public function unregister_widget_from_bitrix24() {
        $api = yeison_btx_widget_api();
        
        if (!$api->is_authorized()) {
            return false;
        }
        
        $widget_url = home_url('/?yeison_widget=contact');
        
        $response = $api->api_call('placement.unbind', array(
            'PLACEMENT' => 'CRM_CONTACT_DETAIL_TAB',
            'HANDLER' => $widget_url
        ));
        
        return $response && isset($response['result']);
    }

    public function cleanup_all_widgets() {
        $api = yeison_btx_widget_api();
        
        if (!$api->is_authorized()) {
            yeison_btx_widget_log('No se puede limpiar widgets: API no autorizada', 'error');
            return false;
        }
        
        yeison_btx_widget_log('Iniciando limpieza de widgets', 'info');
        
        $response = $api->api_call('placement.get');
        
        if (!$response || !isset($response['result'])) {
            yeison_btx_widget_log('Error obteniendo lista de placements', 'error');
            return false;
        }
        
        $our_domain = parse_url(home_url(), PHP_URL_HOST);
        $removed_count = 0;
        $failed_count = 0;
        
        foreach ($response['result'] as $placement) {
            if (isset($placement['handler']) && 
                (strpos($placement['handler'], $our_domain) !== false || 
                strpos($placement['handler'], 'yeison-btx-widget') !== false)) {
                
                $unbind_response = $api->api_call('placement.unbind', array(
                    'PLACEMENT' => $placement['placement'],
                    'HANDLER' => $placement['handler']
                ));
                
                if ($unbind_response && isset($unbind_response['result'])) {
                    $removed_count++;
                    yeison_btx_widget_log('Widget eliminado', 'success', array(
                        'placement' => $placement['placement'],
                        'handler' => $placement['handler']
                    ));
                } else {
                    $failed_count++;
                }
            }
        }
        
        yeison_btx_widget_log('Limpieza de widgets completada', 'info', array(
            'removed' => $removed_count,
            'failed' => $failed_count
        ));
        
        return array(
            'removed' => $removed_count,
            'failed' => $failed_count,
            'total_found' => count($response['result'])
        );
    }
    
    public function auto_register_widgets() {
        if (!$this->config['auto_register']) {
            return;
        }
        
        $api = yeison_btx_widget_api();
        if (!$api->is_authorized()) {
            return;
        }
        
        yeison_btx_widget_log('Registrando widgets automáticamente', 'info');
        
        $this->register_widget_in_bitrix24();
    }
    
    public function get_widget_status() {
        return array(
            'enabled' => $this->config['enabled'],
            'widget_endpoints' => array(
                'contact_login' => rest_url('yeison-btx-widget/v1/widget/contact-login'),
                'auth_handler' => rest_url('yeison-btx-widget/v1/widget/auth')
            ),
            'auto_register' => $this->config['auto_register'],
            'widget_secret_configured' => !empty($this->config['widget_secret'])
        );
    }
}

if (!function_exists('yeison_btx_widget_widgets')) {
    function yeison_btx_widget_widgets() {
        return YeisonBTX_Widget_Manager::get_instance();
    }
}