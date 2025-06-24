<?php
/**
 * YEISON BTX WIDGET - FUNCTIONS.PHP PRINCIPAL
 * Sistema completo de autenticación automática para WooCommerce desde Bitrix24
 * 
 * @package YeisonBTX_Widget
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}


/**
 * FORZAR HEADERS IFRAME - MÁXIMA PRIORIDAD
 * Agregar AL INICIO de functions.php
 */

// CRÍTICO: Interceptar headers ANTES que cualquier cosa
add_action('send_headers', 'yeison_force_iframe_headers_critical', -9999);
add_action('wp', 'yeison_force_iframe_headers_critical', -9999);

function yeison_force_iframe_headers_critical() {
    // Detectar widget más agresivamente
    $is_widget = (
        isset($_GET['yeison_widget']) ||
        isset($_GET['yeison_widget_autologin']) ||
        strpos($_SERVER['REQUEST_URI'] ?? '', 'yeison') !== false ||
        strpos($_SERVER['HTTP_REFERER'] ?? '', 'bitrix24') !== false ||
        strpos($_SERVER['HTTP_USER_AGENT'] ?? '', 'Bitrix') !== false ||
        isset($_SERVER['HTTP_X_BITRIX_FRAME']) ||
        isset($_REQUEST['DOMAIN']) && strpos($_REQUEST['DOMAIN'], 'bitrix24') !== false
    );
    
    if (!$is_widget) {
        return;
    }
    
    // FORZAR headers SIN verificar si ya se enviaron
    if (!headers_sent()) {
        // Limpiar headers conflictivos
        if (function_exists('header_remove')) {
            header_remove('X-Frame-Options');
            header_remove('Content-Security-Policy');
            header_remove('X-Content-Type-Options');
        }
        
        // Establecer headers para iframe
        header('X-Frame-Options: ALLOWALL', true);
        header('Content-Security-Policy: frame-ancestors *', true);
        header('Access-Control-Allow-Origin: *', true);
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS', true);
        header('Access-Control-Allow-Headers: *', true);
        header('Cache-Control: no-cache, no-store, must-revalidate', true);
        header('Pragma: no-cache', true);
        header('Expires: 0', true);
        
        // Log crítico
        error_log('[Yeison Widget] Headers forzados para iframe: ' . ($_SERVER['REQUEST_URI'] ?? ''));
    }
}

// CRÍTICO: Filtrar headers de WordPress más agresivamente
add_filter('wp_headers', 'yeison_override_wp_headers_critical', 99999);
function yeison_override_wp_headers_critical($headers) {
    // Detectar widget
    $is_widget = (
        isset($_GET['yeison_widget']) ||
        isset($_GET['yeison_widget_autologin']) ||
        strpos($_SERVER['REQUEST_URI'] ?? '', 'yeison') !== false ||
        strpos($_SERVER['HTTP_REFERER'] ?? '', 'bitrix24') !== false
    );
    
    if ($is_widget) {
        // SOBREESCRIBIR headers de WordPress
        $headers['X-Frame-Options'] = 'ALLOWALL';
        $headers['Content-Security-Policy'] = 'frame-ancestors *';
        $headers['Access-Control-Allow-Origin'] = '*';
        $headers['Cache-Control'] = 'no-cache, no-store, must-revalidate';
        
        // Eliminar headers problemáticos
        unset($headers['X-Content-Type-Options']);
        
        error_log('[Yeison Widget] Headers de WordPress sobreescritos');
    }
    
    return $headers;
}

// CRÍTICO: Interceptar output buffer para modificar headers
add_action('init', 'yeison_start_header_buffer', -9999);
function yeison_start_header_buffer() {
    if (isset($_GET['yeison_widget']) || isset($_GET['yeison_widget_autologin'])) {
        ob_start('yeison_modify_headers_in_buffer');
    }
}

function yeison_modify_headers_in_buffer($buffer) {
    // Asegurar headers antes del output
    if (!headers_sent()) {
        header_remove('X-Frame-Options');
        header('X-Frame-Options: ALLOWALL', true);
        header('Content-Security-Policy: frame-ancestors *', true);
        header('Access-Control-Allow-Origin: *', true);
    }
    return $buffer;
}









// ============================================================================
// SECCIÓN 1: CONFIGURACIÓN DE HEADERS PARA IFRAME BITRIX24
// ============================================================================

/**
 * Detectar y configurar headers para widgets Bitrix24 de forma agresiva
 * Se ejecuta en múltiples hooks para garantizar funcionamiento
 */
add_action('plugins_loaded', 'yeison_btx_widget_force_iframe_headers', -999);
add_action('init', 'yeison_btx_widget_force_iframe_headers', -999);
add_action('wp_loaded', 'yeison_btx_widget_force_iframe_headers', -999);
add_action('template_redirect', 'yeison_btx_widget_force_iframe_headers', -999);
add_action('send_headers', 'yeison_btx_widget_force_iframe_headers', -999);

function yeison_btx_widget_force_iframe_headers() {
    // Detectar si es un widget de Bitrix24
    $is_widget = (
        isset($_GET['yeison_widget']) ||
        isset($_GET['yeison_widget_autologin']) ||
        strpos($_SERVER['REQUEST_URI'] ?? '', 'yeison-btx-widget') !== false ||
        strpos($_SERVER['HTTP_REFERER'] ?? '', 'bitrix24') !== false ||
        strpos($_SERVER['HTTP_USER_AGENT'] ?? '', 'Bitrix') !== false
    );
    
    if (!$is_widget) {
        return;
    }
    
    // FORZAR headers para iframe sin verificar si ya fueron enviados
    if (!headers_sent()) {
        // Remover headers conflictivos
        header_remove('X-Frame-Options');
        header_remove('Content-Security-Policy');
        header_remove('X-Content-Type-Options');
        
        // Establecer headers para iframe
        header('X-Frame-Options: ALLOWALL', true);
        header('X-Frame-Options: SAMEORIGIN', false); // Remover explícitamente
        header('Content-Security-Policy: frame-ancestors *', true);
        header('Access-Control-Allow-Origin: *', true);
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS', true);
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With', true);
        header('Cache-Control: no-cache, no-store, must-revalidate', true);
        header('Pragma: no-cache', true);
        header('Expires: 0', true);
    }
}

/**
 * Output buffering para capturar y modificar headers
 */
add_action('init', 'yeison_btx_widget_start_output_buffer', -1000);
function yeison_btx_widget_start_output_buffer() {
    if (isset($_GET['yeison_widget']) || isset($_GET['yeison_widget_autologin'])) {
        ob_start('yeison_btx_widget_modify_output_headers');
    }
}

function yeison_btx_widget_modify_output_headers($buffer) {
    // Forzar headers antes del output
    if (!headers_sent()) {
        header_remove('X-Frame-Options');
        header('X-Frame-Options: ALLOWALL', true);
        header('Content-Security-Policy: frame-ancestors *', true);
        header('Access-Control-Allow-Origin: *', true);
    }
    return $buffer;
}

/**
 * Filtrar headers de WordPress para widgets
 */
add_filter('wp_headers', 'yeison_btx_widget_filter_wp_headers', 9999);
function yeison_btx_widget_filter_wp_headers($headers) {
    if (isset($_GET['yeison_widget']) || isset($_GET['yeison_widget_autologin']) || 
        strpos($_SERVER['REQUEST_URI'] ?? '', 'yeison-btx-widget') !== false) {
        
        // Remover headers problemáticos
        unset($headers['X-Frame-Options']);
        unset($headers['Content-Security-Policy']);
        
        // Establecer headers correctos
        $headers['X-Frame-Options'] = 'ALLOWALL';
        $headers['Content-Security-Policy'] = 'frame-ancestors *';
        $headers['Access-Control-Allow-Origin'] = '*';
        $headers['Access-Control-Allow-Methods'] = 'GET, POST, OPTIONS';
        $headers['Access-Control-Allow-Headers'] = 'Content-Type, Authorization, X-Requested-With';
        $headers['Cache-Control'] = 'no-cache, no-store, must-revalidate';
        $headers['Pragma'] = 'no-cache';
        $headers['Expires'] = '0';
        
        yeison_btx_widget_log('🔧 Headers filtrados para widget', 'info', array(
            'headers_set' => array_keys($headers),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? ''
        ));
    }
    
    return $headers;
}

/**
 * Última línea de defensa para headers de iframe
 */
add_action('wp_headers', 'yeison_btx_widget_prevent_frame_options_override', 99999);
function yeison_btx_widget_prevent_frame_options_override() {
    if (isset($_GET['yeison_widget']) || isset($_GET['yeison_widget_autologin'])) {
        // Última oportunidad para establecer headers correctos
        if (!headers_sent()) {
            header_remove('X-Frame-Options');
            header('X-Frame-Options: ALLOWALL', true);
            header('Content-Security-Policy: frame-ancestors *', true);
        }
    }
}

// ============================================================================
// SECCIÓN 2: FUNCIONES BÁSICAS DEL SISTEMA
// ============================================================================

/**
 * Función principal de logging del sistema
 * Registra todos los eventos importantes en la base de datos
 */
function yeison_btx_widget_log($message, $type = 'info', $data = array()) {
    global $wpdb;
    
    $valid_types = array('info', 'error', 'warning', 'success', 'debug');
    if (!in_array($type, $valid_types)) {
        $type = 'info';
    }
    
    $log_data = array(
        'type' => $type,
        'action' => current_action() ?: 'manual',
        'message' => $message,
        'data' => !empty($data) ? wp_json_encode($data) : null,
        'user_id' => get_current_user_id(),
        'ip_address' => yeison_btx_widget_get_ip(),
        'created_at' => current_time('mysql')
    );
    
    $result = $wpdb->insert(
        $wpdb->prefix . 'yeison_btx_widget_logs',
        $log_data,
        array('%s', '%s', '%s', '%s', '%d', '%s', '%s')
    );
    
    // Log crítico también en error_log de PHP
    if ($type === 'error') {
        error_log(sprintf('[Yeison BTX Widget] %s: %s', $message, wp_json_encode($data)));
    }
    
    return $result ? $wpdb->insert_id : false;
}

/**
 * Obtener IP real del cliente (considerando proxies y CDNs)
 */
function yeison_btx_widget_get_ip() {
    $ip_keys = array('HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
    
    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = filter_var($_SERVER[$key], FILTER_VALIDATE_IP);
            if ($ip !== false) {
                return $ip;
            }
        }
    }
    
    return '0.0.0.0';
}

/**
 * Obtener configuración del plugin
 */
function yeison_btx_widget_get_option($option, $default = null) {
    $options = get_option('yeison_btx_widget_settings', array());
    return isset($options[$option]) ? $options[$option] : $default;
}

/**
 * Actualizar configuración del plugin
 */
function yeison_btx_widget_update_option($option, $value) {
    $options = get_option('yeison_btx_widget_settings', array());
    $options[$option] = $value;
    return update_option('yeison_btx_widget_settings', $options);
}

/**
 * Verificar si el plugin está configurado básicamente
 */
function yeison_btx_widget_is_configured() {
    $domain = yeison_btx_widget_get_option('bitrix_domain');
    $client_id = yeison_btx_widget_get_option('client_id');
    $client_secret = yeison_btx_widget_get_option('client_secret');
    
    return !empty($domain) && !empty($client_id) && !empty($client_secret);
}

/**
 * Verificar si hay tokens válidos almacenados
 */
function yeison_btx_widget_has_valid_tokens() {
    $access_token = yeison_btx_widget_get_option('access_token');
    $refresh_token = yeison_btx_widget_get_option('refresh_token');
    
    return !empty($access_token) && !empty($refresh_token);
}

/**
 * Generar widget secret automáticamente si no existe
 */
add_action('admin_init', 'yeison_btx_widget_ensure_widget_secret', 1);
function yeison_btx_widget_ensure_widget_secret() {
    $widget_secret = yeison_btx_widget_get_option('widget_secret');
    
    if (empty($widget_secret)) {
        $new_secret = wp_generate_password(64, false);
        yeison_btx_widget_update_option('widget_secret', $new_secret);
        
        yeison_btx_widget_log('🔐 Widget secret generado automáticamente', 'success', array(
            'secret_length' => strlen($new_secret)
        ));
    }
}

// ============================================================================
// SECCIÓN 3: HANDLERS DE WIDGET DIRECTO
// ============================================================================

/**
 * Handler principal para widget directo (URL: /?yeison_widget=contact)
 * Este es el endpoint principal que usa Bitrix24
 */
add_action('init', 'yeison_btx_widget_handle_widget_direct_fixed', 1);
function yeison_btx_widget_handle_widget_direct_fixed() {
    if (!isset($_GET['yeison_widget']) || $_GET['yeison_widget'] !== 'contact') {
        return;
    }
    
    // CRÍTICO: Verificar headers antes de hacer cualquier cosa
    if (headers_sent($file, $line)) {
        yeison_btx_widget_log('❌ Headers ya enviados', 'error', array(
            'file' => $file,
            'line' => $line
        ));
        return;
    }
    
    // Headers para iframe ANTES de cualquier output
    header_remove();
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Frame-Options: ALLOWALL');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: *');
    header('Content-Security-Policy: frame-ancestors *;');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    try {
        yeison_btx_widget_log('🔗 Widget directo - headers configurados', 'info', array(
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'no-agent',
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'no-referer',
            'params' => $_GET,
            'current_user_initial' => get_current_user_id()
        ));
        
        // Verificar clases necesarias
        if (!class_exists('YeisonBTX_Widget_Manager')) {
            $widget_manager_file = YEISON_BTX_WIDGET_PLUGIN_DIR . 'includes/class-widget-manager.php';
            if (file_exists($widget_manager_file)) {
                require_once $widget_manager_file;
            } else {
                throw new Exception('Widget Manager no disponible');
            }
        }
        
        $widgets = yeison_btx_widget_widgets();
        if (!$widgets) {
            throw new Exception('No se pudo instanciar Widget Manager');
        }
        
        // Extraer parámetros
        $email = yeison_btx_widget_extract_email_from_request();
        $contact_id = yeison_btx_widget_extract_contact_id_from_request();
        
        // Generar y mostrar widget
        $widget_html = $widgets->generate_bitrix24_widget_html($email, $contact_id);
        
        if (empty($widget_html)) {
            throw new Exception('Widget HTML vacío');
        }
        
        echo $widget_html;
        
        yeison_btx_widget_log('✅ Widget directo servido', 'success', array(
            'email' => $email,
            'contact_id' => $contact_id,
            'html_length' => strlen($widget_html)
        ));
        
        exit;
        
    } catch (Exception $e) {
        yeison_btx_widget_log('❌ Error en widget directo', 'error', array(
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ));
        
        // Error amigable sin comprometer headers
        echo yeison_btx_widget_generate_error_widget_simple($e->getMessage());
        exit;
    }
}

/**
 * Extraer email de la petición (múltiples fuentes)
 */
function yeison_btx_widget_extract_email_from_request() {
    // Buscar en GET
    if (isset($_GET['email']) && is_email($_GET['email'])) {
        return sanitize_email($_GET['email']);
    }
    
    // Buscar en PLACEMENT_OPTIONS (parámetros de Bitrix24)
    if (isset($_GET['PLACEMENT_OPTIONS'])) {
        $placement_options = json_decode($_GET['PLACEMENT_OPTIONS'], true);
        if (is_array($placement_options) && isset($placement_options['email']) && is_email($placement_options['email'])) {
            return sanitize_email($placement_options['email']);
        }
    }
    
    // Intentar obtener de contact_id si está disponible
    $contact_id = yeison_btx_widget_extract_contact_id_from_request();
    if ($contact_id) {
        $widgets = yeison_btx_widget_widgets();
        $email = $widgets->get_contact_email_from_bitrix($contact_id);
        if ($email && is_email($email)) {
            return $email;
        }
    }
    
    // Fallback para testing
    return 'test@example.com';
}

/**
 * Extraer contact_id de la petición (múltiples fuentes)
 */
function yeison_btx_widget_extract_contact_id_from_request() {
    $possible_keys = array('contact_id', 'CONTACT_ID', 'contactId', 'id');
    
    foreach ($possible_keys as $key) {
        if (isset($_GET[$key]) && !empty($_GET[$key])) {
            return sanitize_text_field($_GET[$key]);
        }
    }
    
    if (isset($_GET['PLACEMENT_OPTIONS'])) {
        $placement_data = json_decode($_GET['PLACEMENT_OPTIONS'], true);
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

/**
 * Widget de error simple para mostrar cuando hay problemas
 */
function yeison_btx_widget_generate_error_widget_simple($error_message) {
    return '<!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Error Widget</title>
                <style>
                    body {
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
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
                        border-radius: 12px;
                        padding: 30px;
                        text-align: center;
                        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
                        max-width: 400px;
                        border-left: 4px solid #dc3545;
                    }
                    .error-icon { font-size: 48px; margin-bottom: 20px; }
                    .error-title { color: #dc3545; margin-bottom: 15px; font-size: 20px; }
                    .error-message { color: #6c757d; line-height: 1.5; margin-bottom: 20px; }
                    .retry-btn {
                        background: #007cba;
                        color: white;
                        padding: 10px 20px;
                        border: none;
                        border-radius: 5px;
                        cursor: pointer;
                        text-decoration: none;
                        display: inline-block;
                    }
                    .debug-info {
                        font-size: 12px;
                        color: #999;
                        margin-top: 20px;
                        padding: 10px;
                        background: #f8f9fa;
                        border-radius: 5px;
                        font-family: monospace;
                    }
                </style>
            </head>
            <body>
                <div class="error-container">
                    <div class="error-icon">⚠️</div>
                    <h3 class="error-title">Error Temporal</h3>
                    <p class="error-message">' . esc_html($error_message) . '</p>
                    <button onclick="location.reload()" class="retry-btn">🔄 Reintentar</button>
                    <div class="debug-info">
                        Error: ' . esc_html($error_message) . '<br>
                        Tiempo: ' . date('Y-m-d H:i:s') . '<br>
                        UA: ' . esc_html($_SERVER['HTTP_USER_AGENT'] ?? 'unknown') . '
                    </div>
                </div>
            </body>
            </html>';
}

// ============================================================================
// SECCIÓN 4: SIMULACIÓN DE COOKIES PARA AUTOLOGIN
// ============================================================================

/**
 * Simular cookies en $_COOKIE para que WordPress las detecte inmediatamente
 * Esto soluciona el problema de que las cookies no se leen hasta la siguiente petición
 */
add_action('set_auth_cookie', 'yeison_btx_widget_simulate_auth_cookie_in_request', 10, 6);
function yeison_btx_widget_simulate_auth_cookie_in_request($auth_cookie, $expire, $expiration, $user_id, $scheme, $token) {
    // Solo aplicar durante autologin
    if (!isset($_GET['yeison_widget_autologin'])) {
        return;
    }
    
    $cookie_name = is_ssl() ? SECURE_AUTH_COOKIE : AUTH_COOKIE;
    $_COOKIE[$cookie_name] = $auth_cookie;
    
    yeison_btx_widget_log('🍪 Cookie AUTH simulada en $_COOKIE', 'debug', array(
        'cookie_name' => $cookie_name,
        'user_id' => $user_id,
        'scheme' => $scheme
    ));
}

add_action('set_logged_in_cookie', 'yeison_btx_widget_simulate_logged_in_cookie_in_request', 10, 6);
function yeison_btx_widget_simulate_logged_in_cookie_in_request($logged_in_cookie, $expire, $expiration, $user_id, $scheme, $token) {
    // Solo aplicar durante autologin
    if (!isset($_GET['yeison_widget_autologin'])) {
        return;
    }
    
    $_COOKIE[LOGGED_IN_COOKIE] = $logged_in_cookie;
    
    yeison_btx_widget_log('🍪 Cookie LOGGED_IN simulada en $_COOKIE', 'debug', array(
        'user_id' => $user_id
    ));
}

/**
 * Debug cuando se ejecuta el hook wp_login exitosamente
 */
add_action('wp_login', 'yeison_btx_widget_debug_login_success', 10, 2);
function yeison_btx_widget_debug_login_success($user_login, $user) {
    if (!isset($_GET['yeison_widget_autologin'])) {
        return;
    }
    
    yeison_btx_widget_log('🎉 Hook wp_login ejecutado exitosamente', 'success', array(
        'user_login' => $user_login,
        'user_id' => $user->ID,
        'current_user_after_login' => get_current_user_id(),
        'is_user_logged_in' => is_user_logged_in(),
        'cookies_set' => array_keys($_COOKIE)
    ));
}

// ============================================================================
// SECCIÓN 5: DEBUGGING Y SCRIPTS DE IFRAME
// ============================================================================

/**
 * JavaScript para debugging en iframe
 */
add_action('wp_footer', 'yeison_btx_widget_add_iframe_debug_script');
add_action('wp_head', 'yeison_btx_widget_add_iframe_debug_script');
function yeison_btx_widget_add_iframe_debug_script() {
    if (isset($_GET['yeison_widget']) || isset($_GET['yeison_widget_autologin'])) {
        ?>
        <script>
        // Debug para verificar si estamos en iframe
        if (window.self !== window.top) {
            console.log('✅ Widget cargado en iframe correctamente');
            
            // Notificar al parent frame que cargamos
            try {
                window.parent.postMessage({
                    action: 'yeison_widget_loaded',
                    url: window.location.href,
                    timestamp: new Date().toISOString()
                }, '*');
            } catch(e) {
                console.log('No se pudo comunicar con parent frame:', e);
            }
        } else {
            console.log('⚠️ Widget NO está en iframe');
        }
        
        // Log de headers para debug
        console.log('🔍 Headers de debug:', {
            url: window.location.href,
            referrer: document.referrer,
            userAgent: navigator.userAgent
        });
        </script>
        <?php
    }
}

/**
 * Verificar plugins que pueden interferir con iframes
 */
add_action('plugins_loaded', 'yeison_btx_widget_check_conflicting_plugins', 999);
function yeison_btx_widget_check_conflicting_plugins() {
    // Lista de plugins que pueden causar problemas con iframes
    $problematic_plugins = array(
        'wordfence/wordfence.php',
        'really-simple-ssl/rlrsssl-really-simple-ssl.php',
        'wp-security-audit-log/wp-security-audit-log.php',
        'all-in-one-wp-security-and-firewall/wp-security.php'
    );
    
    $active_plugins = get_option('active_plugins', array());
    $conflicting = array_intersect($problematic_plugins, $active_plugins);
    
    if (!empty($conflicting)) {
        yeison_btx_widget_log('⚠️ Plugins que pueden interferir con iframes detectados', 'warning', array(
            'plugins' => $conflicting
        ));
    }
}

// ============================================================================
// SECCIÓN 6: SOPORTE PARA COOKIES EN IFRAME
// ============================================================================

// CRÍTICO: Forzar SameSite=None para cookies de WordPress en iframe
add_action('init', 'yeison_btx_widget_setup_iframe_cookies', 1);
function yeison_btx_widget_setup_iframe_cookies() {
    if (!isset($_GET['yeison_widget_autologin']) && !isset($_GET['yeison_widget'])) {
        return;
    }
    
    // Detectar iframe de Bitrix24
    $is_bitrix_iframe = (
        isset($_GET['DOMAIN']) && strpos($_GET['DOMAIN'], 'bitrix24') !== false ||
        isset($_GET['APP_SID']) ||
        strpos($_SERVER['HTTP_REFERER'] ?? '', 'bitrix24') !== false
    );
    
    if (!$is_bitrix_iframe) {
        return;
    }
    
    yeison_btx_widget_log('🖼️ Configurando cookies para iframe Bitrix24', 'info');
}

// NUEVA: Función personalizada para establecer cookies con SameSite=None
if (!function_exists('yeison_btx_widget_setcookie_iframe')) {
    function yeison_btx_widget_setcookie_iframe($name, $value, $expire = 0, $path = '', $domain = '', $secure = false, $httponly = false) {
        if (headers_sent()) {
            return false;
        }
        
        $cookie_string = $name . '=' . rawurlencode($value);
        
        if ($expire > 0) {
            $cookie_string .= '; expires=' . gmdate('D, d M Y H:i:s T', $expire);
            $cookie_string .= '; Max-Age=' . ($expire - time());
        }
        
        if (!empty($path)) {
            $cookie_string .= '; path=' . $path;
        }
        
        if (!empty($domain)) {
            $cookie_string .= '; domain=' . $domain;
        }
        
        if ($secure) {
            $cookie_string .= '; Secure';
        }
        
        if ($httponly) {
            $cookie_string .= '; HttpOnly';
        }
        
        // CRÍTICO: SameSite=None para iframe de terceros
        $cookie_string .= '; SameSite=None';
        
        header('Set-Cookie: ' . $cookie_string, false);
        
        yeison_btx_widget_log('🍪 Cookie iframe establecida', 'debug', array(
            'cookie_string' => $cookie_string
        ));
        
        return true;
    }
}

// ============================================================================
// SECCIÓN 7: FUNCIONES HELPER PARA INSTANCIAS DE CLASES
// ============================================================================

/**
 * Obtener instancia de la API de Bitrix24
 */
if (!function_exists('yeison_btx_widget_api')) {
    function yeison_btx_widget_api() {
        return YeisonBTX_Widget_Bitrix_API::get_instance();
    }
}

/**
 * Obtener instancia del Widget Manager
 */
if (!function_exists('yeison_btx_widget_widgets')) {
    function yeison_btx_widget_widgets() {
        return YeisonBTX_Widget_Manager::get_instance();
    }
}

/**
 * Obtener instancia del Autologin Handler
 */
if (!function_exists('yeison_btx_widget_autologin')) {
    function yeison_btx_widget_autologin() {
        return YeisonBTX_Widget_Autologin_Handler::get_instance();
    }
}

// ============================================================================
// SECCIÓN 8: ENDPOINTS AJAX PARA ADMIN
// ============================================================================

// Test de conexión
add_action('wp_ajax_yeison_btx_widget_test_connection', 'yeison_btx_widget_ajax_test_connection');
function yeison_btx_widget_ajax_test_connection() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Sin permisos');
        return;
    }
    
    // Verificar nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'yeison_btx_widget_test')) {
        wp_send_json_error('Nonce inválido');
        return;
    }
    
    $api = yeison_btx_widget_api();
    if (!$api) {
        wp_send_json_error('API no disponible');
        return;
    }
    
    $result = $api->test_connection();
    
    if ($result['success']) {
        wp_send_json_success($result['message']);
    } else {
        wp_send_json_error($result['message']);
    }
}

// Ver logs
add_action('wp_ajax_yeison_btx_widget_view_logs', 'yeison_btx_widget_ajax_view_logs');
function yeison_btx_widget_ajax_view_logs() {
    if (!current_user_can('manage_options')) {
        wp_die('Sin permisos');
    }
    
    global $wpdb;
    $logs = $wpdb->get_results(
        "SELECT * FROM {$wpdb->prefix}yeison_btx_widget_logs 
         ORDER BY created_at DESC LIMIT 100"
    );
    
    header('Content-Type: text/html; charset=UTF-8');
    ?>
    <!DOCTYPE html>
    <html><head><title>Logs Yeison BTX Widget</title>
    <style>body{font-family:monospace;margin:20px;} .log{margin:10px 0;padding:10px;border-radius:5px;} .error{background:#ffe6e6;} .success{background:#e6ffe6;} .warning{background:#fff3cd;} .info{background:#e6f3ff;}</style>
    </head><body>
    <h1>📝 Logs del Sistema Widget</h1>
    <?php if (empty($logs)): ?>
        <p>No hay logs registrados.</p>
    <?php else: ?>
        <?php foreach ($logs as $log): ?>
            <div class="log <?php echo esc_attr($log->type); ?>">
                <strong><?php echo esc_html($log->created_at); ?></strong> 
                [<?php echo esc_html(strtoupper($log->type)); ?>] 
                <?php echo esc_html($log->message); ?>
                <?php if ($log->data): ?>
                    <details><summary>Ver datos</summary>
                    <pre><?php echo esc_html($log->data); ?></pre>
                    </details>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    </body></html>
    <?php
    exit;
}







// Limpiar logs
add_action('wp_ajax_yeison_btx_widget_clear_logs', 'yeison_btx_widget_ajax_clear_logs');
function yeison_btx_widget_ajax_clear_logs() {
    if (!current_user_can('manage_options')) {
        wp_die('Sin permisos');
    }
    
    global $wpdb;
    $deleted = $wpdb->query("DELETE FROM {$wpdb->prefix}yeison_btx_widget_logs");
    
    yeison_btx_widget_log('Logs limpiados manualmente', 'info', array('deleted_count' => $deleted));
    
    header('Content-Type: text/html; charset=UTF-8');
    echo "✅ Se eliminaron {$deleted} registros de log.";
    exit;
}

// Test del widget
add_action('wp_ajax_yeison_btx_widget_test_widget', 'yeison_btx_widget_ajax_test_widget');
function yeison_btx_widget_ajax_test_widget() {
    if (!current_user_can('manage_options')) {
        wp_die('Sin permisos');
    }
    
    $test_email = get_option('admin_email');
    $widget_url = home_url('/?yeison_widget=contact&email=' . urlencode($test_email));
    
    header('Content-Type: text/html; charset=UTF-8');
    ?>
    <!DOCTYPE html>
    <html><head><title>Test Widget</title>
    <style>body{font-family:Arial,sans-serif;margin:20px;} iframe{width:100%;height:600px;border:2px solid #007cba;border-radius:8px;}</style>
    </head><body>
    <h1>🧪 Test del Widget</h1>
    <p><strong>URL:</strong> <code><?php echo esc_html($widget_url); ?></code></p>
    <p><strong>Email de prueba:</strong> <?php echo esc_html($test_email); ?></p>
    <iframe src="<?php echo esc_url($widget_url); ?>"></iframe>
    </body></html>
    <?php
    exit;
}

// Registrar widget en Bitrix24
add_action('wp_ajax_yeison_btx_widget_register_widget', 'yeison_btx_widget_ajax_register_widget');
function yeison_btx_widget_ajax_register_widget() {
    if (!current_user_can('manage_options')) {
        wp_die('Sin permisos');
    }
    
    $widgets = yeison_btx_widget_widgets();
    $result = $widgets->register_widget_in_bitrix24();
    
    header('Content-Type: text/html; charset=UTF-8');
    if ($result) {
        echo "✅ Widget registrado exitosamente en Bitrix24";
        yeison_btx_widget_log('Widget registrado desde admin', 'success');
    } else {
        echo "❌ Error registrando widget. Revisa que la API esté autorizada.";
        yeison_btx_widget_log('Error registrando widget desde admin', 'error');
    }
    exit;
}







// Actualizar permalinks
add_action('wp_ajax_yeison_btx_widget_flush_permalinks', 'yeison_btx_widget_ajax_flush_permalinks');
function yeison_btx_widget_ajax_flush_permalinks() {
    if (!current_user_can('manage_options')) {
        wp_die('Sin permisos');
    }
    
    flush_rewrite_rules();
    yeison_btx_widget_log('Permalinks actualizados desde admin', 'info');
    
    header('Content-Type: text/html; charset=UTF-8');
    echo "✅ Permalinks actualizados correctamente";
    exit;
}

// ============================================================================
// FIN DEL ARCHIVO
// ============================================================================




// ============================================================================
// SECCIÓN 8.1: FUNCIONES AJAX ADICIONALES FALTANTES
// ============================================================================

// Limpiar tokens (automático - sin botón)
add_action('wp_ajax_yeison_btx_widget_clear_tokens', 'yeison_btx_widget_ajax_clear_tokens');
function yeison_btx_widget_ajax_clear_tokens() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Sin permisos');
        return;
    }
    
    // Verificar nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'yeison_btx_widget_clear_tokens')) {
        wp_send_json_error('Nonce inválido');
        return;
    }
    
    $api = yeison_btx_widget_api();
    if (!$api) {
        wp_send_json_error('API no disponible');
        return;
    }
    
    $result = $api->clear_tokens();
    
    if ($result) {
        yeison_btx_widget_log('🧹 Tokens limpiados manualmente', 'success');
        wp_send_json_success(array(
            'message' => 'Tokens limpiados exitosamente',
            'needs_reauth' => true
        ));
    } else {
        wp_send_json_error('Error limpiando tokens');
    }
}


// Función para verificar tablas de base de datos
function yeison_btx_widget_check_database_tables() {
    global $wpdb;
    
    $tables_to_check = array(
        'yeison_btx_widget_logs' => $wpdb->prefix . 'yeison_btx_widget_logs'
    );
    
    $results = array();
    
    foreach ($tables_to_check as $logical_name => $table_name) {
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        )) === $table_name;
        
        $results[$logical_name] = $table_exists;
    }
    
    return $results;
}


// Auto-verificar tabla de logs si no existe (versión simplificada)
add_action('admin_init', 'yeison_btx_widget_ensure_database_tables');
function yeison_btx_widget_ensure_database_tables() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'yeison_btx_widget_logs';
    
    // Verificar si la tabla existe
    $table_exists = $wpdb->get_var($wpdb->prepare(
        "SHOW TABLES LIKE %s",
        $table_name
    )) === $table_name;
    
    if (!$table_exists) {
        // Intentar recrear la tabla
        if (class_exists('YeisonBTX_Widget_Main')) {
            $plugin_instance = YeisonBTX_Widget_Main::get_instance();
            // Forzar reactivación para crear tablas
            $plugin_instance->activation();
        }
    }
}



// ============================================================================
// FIN DEL ARCHIVO
// ============================================================================







// ============================================================================
// SISTEMA DE AUTO-CONFIGURACIÓN - ACTIVACIÓN DEL PLUGIN
// ============================================================================

/**
 * Auto-configurar todo al activar el plugin
 */
add_action('yeison_btx_widget_activated', 'yeison_auto_setup_complete_system');
function yeison_auto_setup_complete_system() {
    yeison_btx_widget_log('🚀 Iniciando auto-configuración completa', 'info');
    
    // 1. Limpiar tokens antiguos automáticamente
    yeison_auto_clean_old_tokens();
    
    // 2. Asegurar MU-Plugin
    yeison_auto_ensure_mu_plugin();
    
    // 3. Verificar configuración htaccess
    yeison_auto_verify_htaccess();
    
    // 4. Limpiar todos los caches
    yeison_auto_clear_all_caches();
    
    // 5. Crear archivos de test
    yeison_auto_create_test_files();
    
    yeison_btx_widget_log('✅ Auto-configuración completada', 'success');
}

/**
 * 1. Limpiar tokens antiguos automáticamente
 */
function yeison_auto_clean_old_tokens() {
    $api = yeison_btx_widget_api();
    if ($api) {
        // Verificar si los tokens están funcionando
        $test = $api->test_connection();
        if (!$test['success'] && $test['needs_reauth']) {
            // Limpiar tokens automáticamente
            $api->clear_tokens();
            yeison_btx_widget_log('🧹 Tokens antiguos limpiados automáticamente', 'info');
        }
    }
}

/**
 * 2. Asegurar que el MU-Plugin exista
 */
function yeison_auto_ensure_mu_plugin() {
    $mu_dir = WP_CONTENT_DIR . '/mu-plugins';
    $mu_file = $mu_dir . '/yeison-widget-headers.php';
    
    // Crear directorio si no existe
    if (!is_dir($mu_dir)) {
        wp_mkdir_p($mu_dir);
    }
    
    // Solo crear si no existe
    if (!file_exists($mu_file)) {
        $mu_content = '<?php
/**
 * Auto-generado por Yeison BTX Widget
 * Fuerza headers para iframe
 */
if (!defined("ABSPATH")) exit;

add_action("send_headers", "yeison_force_headers_critical", -9999);
function yeison_force_headers_critical() {
    $is_widget = (
        isset($_GET["yeison_widget"]) ||
        isset($_GET["yeison_widget_autologin"]) ||
        strpos($_SERVER["REQUEST_URI"] ?? "", "yeison") !== false ||
        strpos($_SERVER["HTTP_REFERER"] ?? "", "bitrix24") !== false
    );
    
    if ($is_widget && !headers_sent()) {
        header_remove("X-Frame-Options");
        header("X-Frame-Options: ALLOWALL", true);
        header("Content-Security-Policy: frame-ancestors *", true);
        header("Access-Control-Allow-Origin: *", true);
        header("Cache-Control: no-cache, no-store, must-revalidate", true);
    }
}';
        
        file_put_contents($mu_file, $mu_content);
        yeison_btx_widget_log('📁 MU-Plugin creado automáticamente', 'success');
    }
}

/**
 * 3. Verificar htaccess (solo advertencia)
 */
function yeison_auto_verify_htaccess() {
    $htaccess_file = ABSPATH . '.htaccess';
    if (file_exists($htaccess_file)) {
        $content = file_get_contents($htaccess_file);
        if (strpos($content, 'YEISON BTX WIDGET') === false) {
            yeison_btx_widget_log('⚠️ .htaccess sin configuración Yeison - revisar manualmente', 'warning');
        }
    }
}

/**
 * 4. Limpiar todos los caches
 */
function yeison_auto_clear_all_caches() {
    // WordPress cache
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
    
    // LiteSpeed cache
    if (function_exists('litespeed_purge_all')) {
        litespeed_purge_all();
    }
    
    // Transients del plugin
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_yeison_widget_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_yeison_widget_%'");
    
    yeison_btx_widget_log('🧹 Caches limpiados automáticamente', 'info');
}

/**
 * 5. Crear archivos de test automáticamente
 */
function yeison_auto_create_test_files() {
    $root_dir = ABSPATH;
    
    // Test básico del widget
    $test_content = '<?php
// Test rápido del widget - Auto-generado
header("X-Frame-Options: ALLOWALL");
header("Content-Security-Policy: frame-ancestors *");
echo "<!DOCTYPE html><html><head><title>Widget Test</title></head><body>";
echo "<h1>🧪 Test Widget Yeison</h1>";
echo "<p>URL: " . htmlspecialchars($_SERVER["REQUEST_URI"]) . "</p>";
echo "<p>Tiempo: " . date("Y-m-d H:i:s") . "</p>";
if (isset($_GET["yeison_widget"])) {
    echo "<p style=\"color: green;\">✅ Parámetro widget detectado</p>";
} else {
    echo "<p style=\"color: orange;\">⚠️ Sin parámetro widget</p>";
}
echo "<p><a href=\"/?yeison_widget=contact&email=test@test.com\">🎯 Test Widget Completo</a></p>";
echo "</body></html>";
?>';
    
    file_put_contents($root_dir . 'widget-test.php', $test_content);
    yeison_btx_widget_log('📄 Archivo widget-test.php creado', 'info');
}

// ============================================================================
// DETECTAR PROBLEMAS COMUNES Y AUTO-CORREGIR
// ============================================================================

/**
 * Verificar estado del sistema cada vez que se carga el plugin
 */
add_action('plugins_loaded', 'yeison_auto_verify_system_health', 999);
function yeison_auto_verify_system_health() {
    // Solo verificar si el plugin está activo
    if (!function_exists('yeison_btx_widget_get_option')) {
        return;
    }
    
    static $verified = false;
    if ($verified) return;
    $verified = true;
    
    // Verificar configuración básica
    $issues = array();
    
    // 1. Widget secret
    if (empty(yeison_btx_widget_get_option('widget_secret'))) {
        $new_secret = wp_generate_password(64, false);
        yeison_btx_widget_update_option('widget_secret', $new_secret);
        $issues[] = 'Widget secret regenerado';
    }
    
    // 2. MU-Plugin
    $mu_file = WP_CONTENT_DIR . '/mu-plugins/yeison-widget-headers.php';
    if (!file_exists($mu_file)) {
        yeison_auto_ensure_mu_plugin();
        $issues[] = 'MU-Plugin recreado';
    }
    
    // 3. Tokens inválidos
    $api = yeison_btx_widget_api();
    if ($api && $api->is_token_invalid_for_domain()) {
        $api->clear_tokens();
        $issues[] = 'Tokens inválidos limpiados';
    }
    
    // Log solo si hay issues
    if (!empty($issues)) {
        yeison_btx_widget_log('🔧 Auto-correcciones aplicadas', 'info', array('issues' => $issues));
    }
}

// ============================================================================
// HANDLER MEJORADO PARA WIDGET DIRECTO
// ============================================================================

/**
 * REEMPLAZAR el handler actual con uno más robusto
 */
remove_action('init', 'yeison_btx_widget_handle_widget_direct_fixed', 1);
add_action('init', 'yeison_btx_widget_handle_widget_direct_v2', 1);

function yeison_btx_widget_handle_widget_direct_v2() {
    if (!isset($_GET['yeison_widget']) || $_GET['yeison_widget'] !== 'contact') {
        return;
    }
    
    // CRÍTICO: Headers antes que cualquier cosa
    if (headers_sent($file, $line)) {
        yeison_btx_widget_log('❌ Headers ya enviados', 'error', array('file' => $file, 'line' => $line));
        return;
    }
    
    // Forzar headers para iframe
    header_remove();
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Frame-Options: ALLOWALL');
    header('Content-Security-Policy: frame-ancestors *');
    header('Access-Control-Allow-Origin: *');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    try {
        yeison_btx_widget_log('🎯 Widget directo v2 - headers establecidos', 'info', array(
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 100),
            'referer' => $_SERVER['HTTP_REFERER'] ?? '',
            'params' => $_GET
        ));
        
        // Verificar que el plugin esté completamente cargado
        if (!class_exists('YeisonBTX_Widget_Manager')) {
            // Cargar clases manualmente si es necesario
            $plugin_dir = dirname(__FILE__);
            $class_files = array(
                'includes/class-bitrix-api.php',
                'includes/class-widget-manager.php',
                'includes/class-autologin-handler.php'
            );
            
            foreach ($class_files as $class_file) {
                $full_path = $plugin_dir . '/' . $class_file;
                if (file_exists($full_path)) {
                    require_once $full_path;
                }
            }
        }
        
        $widgets = yeison_btx_widget_widgets();
        if (!$widgets) {
            throw new Exception('Widget Manager no disponible - plugin no cargado correctamente');
        }
        
        // Extraer parámetros
        $email = yeison_btx_widget_extract_email_from_request();
        $contact_id = yeison_btx_widget_extract_contact_id_from_request();
        
        // Generar widget
        $widget_html = $widgets->generate_bitrix24_widget_html($email, $contact_id);
        
        if (empty($widget_html)) {
            throw new Exception('Widget HTML vacío');
        }
        
        echo $widget_html;
        
        yeison_btx_widget_log('✅ Widget directo v2 servido exitosamente', 'success', array(
            'email' => $email,
            'contact_id' => $contact_id,
            'html_size' => strlen($widget_html)
        ));
        
        exit;
        
    } catch (Exception $e) {
        yeison_btx_widget_log('❌ Error en widget directo v2', 'error', array(
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ));
        
        // Widget de error simple
        echo '<!DOCTYPE html><html><head><title>Error</title></head><body>';
        echo '<div style="padding: 20px; text-align: center; font-family: Arial;">';
        echo '<h3 style="color: #dc3545;">Error Temporal</h3>';
        echo '<p>Error: ' . esc_html($e->getMessage()) . '</p>';
        echo '<p><a href="javascript:location.reload()">🔄 Reintentar</a></p>';
        echo '<p style="font-size: 12px; color: #666;">Tiempo: ' . date('H:i:s') . '</p>';
        echo '</div></body></html>';
        exit;
    }
}

// ============================================================================
// TRIGGER PARA AUTO-CONFIGURACIÓN
// ============================================================================

/**
 * Trigger automático cuando se active el plugin
 */
add_action('activated_plugin', 'yeison_detect_plugin_activation', 10, 2);
function yeison_detect_plugin_activation($plugin, $network_wide) {
    // Detectar si es nuestro plugin (cualquier nombre de carpeta)
    if (strpos($plugin, 'yeison_btx_widget.php') !== false) {
        // Trigger de auto-configuración
        do_action('yeison_btx_widget_activated');
        
        // Redirect al admin para mostrar estado
        if (!wp_doing_ajax() && !wp_doing_cron()) {
            wp_safe_redirect(admin_url('admin.php?page=yeison-btx-widget&setup=auto'));
            exit;
        }
    }
}

// ============================================================================
// FIN AUTO-SETUP
// ============================================================================




// ============================================================================
// FIN DEL ARCHIVO
// ============================================================================













// Registrar widget AJAX mejorado
add_action('wp_ajax_yeison_btx_widget_register_widget_ajax', 'yeison_btx_widget_ajax_register_widget_improved');
function yeison_btx_widget_ajax_register_widget_improved() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Sin permisos');
        return;
    }
    
    // Verificar nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'yeison_btx_widget_register')) {
        wp_send_json_error('Nonce inválido');
        return;
    }
    
    try {
        $api = yeison_btx_widget_api();
        $widgets = yeison_btx_widget_widgets();
        
        if (!$api) {
            wp_send_json_error('API no disponible');
            return;
        }
        
        if (!$widgets) {
            wp_send_json_error('Widget Manager no disponible');
            return;
        }
        
        // Verificar autorización
        if (!$api->is_authorized()) {
            wp_send_json_error('API no autorizada. Debes autorizar primero con Bitrix24.');
            return;
        }
        
        // Test de conexión rápido
        $connection_test = $api->test_connection();
        if (!$connection_test['success']) {
            wp_send_json_error('Error de conexión: ' . $connection_test['message']);
            return;
        }
        
        // Intentar registrar el widget
        $result = $widgets->register_widget_in_bitrix24();
        
        if ($result) {
            $widget_url = home_url('/?yeison_widget=contact');
            
            yeison_btx_widget_log('Widget registrado desde admin AJAX', 'success', array(
                'widget_url' => $widget_url,
                'placement' => 'CRM_CONTACT_DETAIL_TAB'
            ));
            
            wp_send_json_success(array(
                'message' => 'Widget registrado exitosamente en Bitrix24',
                'details' => array(
                    'handler' => $widget_url,
                    'placement' => 'CRM_CONTACT_DETAIL_TAB',
                    'status' => 'active'
                )
            ));
        } else {
            // Obtener más información del error
            $domain = yeison_btx_widget_get_option('bitrix_domain');
            
            yeison_btx_widget_log('Error registrando widget desde admin AJAX', 'error', array(
                'domain' => $domain,
                'api_authorized' => $api->is_authorized()
            ));
            
            wp_send_json_error('Error registrando el widget. Verifica los logs para más detalles.');
        }
        
    } catch (Exception $e) {
        yeison_btx_widget_log('Excepción en registro de widget AJAX', 'error', array(
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ));
        
        wp_send_json_error('Error interno: ' . $e->getMessage());
    }
}






// ============================================================================
// FIN DEL ARCHIVO
// ============================================================================




// Verificar widgets existentes AJAX
add_action('wp_ajax_yeison_btx_widget_check_widgets_ajax', 'yeison_btx_widget_ajax_check_widgets');
function yeison_btx_widget_ajax_check_widgets() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Sin permisos');
        return;
    }
    
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'yeison_btx_widget_check')) {
        wp_send_json_error('Nonce inválido');
        return;
    }
    
    try {
        $api = yeison_btx_widget_api();
        
        if (!$api || !$api->is_authorized()) {
            wp_send_json_error('API no autorizada');
            return;
        }
        
        // Obtener lista de placements
        $response = $api->api_call('placement.get');
        
        if (!$response || !isset($response['result'])) {
            wp_send_json_error('Error consultando placements: ' . ($response['error'] ?? 'respuesta vacía'));
            return;
        }
        
        $our_domain = parse_url(home_url(), PHP_URL_HOST);
        $our_widgets = array();
        
        foreach ($response['result'] as $placement) {
            if (isset($placement['handler']) && 
                (strpos($placement['handler'], $our_domain) !== false || 
                 strpos($placement['handler'], 'yeison') !== false)) {
                $our_widgets[] = array(
                    'placement' => $placement['placement'],
                    'handler' => $placement['handler'],
                    'title' => $placement['title'] ?? 'Sin título'
                );
            }
        }
        
        yeison_btx_widget_log('Widgets verificados desde admin', 'info', array(
            'total_placements' => count($response['result']),
            'our_widgets' => count($our_widgets)
        ));
        
        wp_send_json_success(array(
            'total' => count($our_widgets),
            'widgets' => $our_widgets,
            'message' => count($our_widgets) > 0 ? 
                'Encontrados ' . count($our_widgets) . ' widgets de este sitio' : 
                'No hay widgets registrados de este sitio'
        ));
        
    } catch (Exception $e) {
        wp_send_json_error('Error: ' . $e->getMessage());
    }
}






// ============================================================================
// FIN DEL ARCHIVO
// ============================================================================

/**
 * FUNCIONES AJAX MEJORADAS - Yeison BTX Widget
 * Sistema completo de endpoints AJAX para el panel de administración
 * 
 */

// ============================================================================
// FUNCIONES AJAX CORREGIDAS Y NUEVAS
// ============================================================================

/**
 * Test de conexión - CORREGIDO
 */
add_action('wp_ajax_yeison_btx_widget_test_connection', 'yeison_btx_widget_ajax_test_connection_fixed');
function yeison_btx_widget_ajax_test_connection_fixed() {
    // Verificar permisos
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Sin permisos administrativos');
        return;
    }
    
    // Verificar nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'yeison_btx_widget_test')) {
        wp_send_json_error('Nonce inválido');
        return;
    }
    
    try {
        $api = yeison_btx_widget_api();
        if (!$api) {
            wp_send_json_error('API no disponible - plugin no inicializado');
            return;
        }
        
        $result = $api->test_connection();
        
        if ($result['success']) {
            $message = $result['message'];
            if (isset($result['data']['domain'])) {
                $message .= ' (Dominio: ' . $result['data']['domain'] . ')';
            }
            wp_send_json_success($message);
        } else {
            $error_msg = $result['message'];
            if ($result['needs_reauth']) {
                $error_msg .= ' - Necesita reautorización';
            }
            wp_send_json_error($error_msg);
        }
    } catch (Exception $e) {
        yeison_btx_widget_log('Error en test_connection AJAX', 'error', array(
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ));
        wp_send_json_error('Error interno: ' . $e->getMessage());
    }
}

/**
 * Registrar widget - CORREGIDO
 */
add_action('wp_ajax_yeison_btx_widget_register_widget_ajax', 'yeison_btx_widget_ajax_register_widget_fixed');
function yeison_btx_widget_ajax_register_widget_fixed() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Sin permisos administrativos');
        return;
    }
    
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'yeison_btx_widget_register')) {
        wp_send_json_error('Nonce inválido');
        return;
    }
    
    try {
        $api = yeison_btx_widget_api();
        $widgets = yeison_btx_widget_widgets();
        
        if (!$api) {
            wp_send_json_error('API no disponible');
            return;
        }
        
        if (!$widgets) {
            wp_send_json_error('Widget Manager no disponible');
            return;
        }
        
        // Verificar configuración básica
        if (!yeison_btx_widget_is_configured()) {
            wp_send_json_error('Plugin no configurado. Configura dominio, Client ID y Client Secret primero.');
            return;
        }
        
        // Verificar autorización
        if (!$api->is_authorized()) {
            wp_send_json_error('API no autorizada. Autoriza el plugin con Bitrix24 primero.');
            return;
        }
        
        // Test rápido de conexión
        $connection_test = $api->test_connection();
        if (!$connection_test['success']) {
            wp_send_json_error('Error de conexión: ' . $connection_test['message']);
            return;
        }
        
        // Intentar registrar el widget
        $result = $widgets->register_widget_in_bitrix24();
        
        if ($result) {
            $widget_url = home_url('/?yeison_widget=contact');
            
            yeison_btx_widget_log('Widget registrado desde admin AJAX', 'success', array(
                'widget_url' => $widget_url,
                'placement' => 'CRM_CONTACT_DETAIL_TAB'
            ));
            
            wp_send_json_success(array(
                'message' => 'Widget registrado exitosamente en Bitrix24',
                'details' => array(
                    'handler' => $widget_url,
                    'placement' => 'CRM_CONTACT_DETAIL_TAB',
                    'status' => 'active'
                )
            ));
        } else {
            yeison_btx_widget_log('Error registrando widget desde admin AJAX', 'error', array(
                'domain' => yeison_btx_widget_get_option('bitrix_domain'),
                'api_authorized' => $api->is_authorized()
            ));
            
            wp_send_json_error('Error registrando el widget. Verifica los logs para más detalles.');
        }
        
    } catch (Exception $e) {
        yeison_btx_widget_log('Excepción en registro de widget AJAX', 'error', array(
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ));
        
        wp_send_json_error('Error interno: ' . $e->getMessage());
    }
}

/**
 * Verificar widgets existentes - CORREGIDO
 */
add_action('wp_ajax_yeison_btx_widget_check_widgets_ajax', 'yeison_btx_widget_ajax_check_widgets_fixed');
function yeison_btx_widget_ajax_check_widgets_fixed() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Sin permisos administrativos');
        return;
    }
    
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'yeison_btx_widget_check')) {
        wp_send_json_error('Nonce inválido');
        return;
    }
    
    try {
        $api = yeison_btx_widget_api();
        
        if (!$api || !$api->is_authorized()) {
            wp_send_json_error('API no autorizada. Autoriza el plugin primero.');
            return;
        }
        
        // Obtener lista de placements
        $response = $api->api_call('placement.get');
        
        if (!$response || !isset($response['result'])) {
            $error_msg = 'Error consultando placements';
            if (isset($response['error'])) {
                $error_msg .= ': ' . $response['error'];
                if (isset($response['error_description'])) {
                    $error_msg .= ' - ' . $response['error_description'];
                }
            }
            wp_send_json_error($error_msg);
            return;
        }
        
        $our_domain = parse_url(home_url(), PHP_URL_HOST);
        $our_widgets = array();
        
        foreach ($response['result'] as $placement) {
            if (isset($placement['handler']) && 
                (strpos($placement['handler'], $our_domain) !== false || 
                 strpos($placement['handler'], 'yeison') !== false)) {
                $our_widgets[] = array(
                    'placement' => $placement['placement'],
                    'handler' => $placement['handler'],
                    'title' => $placement['title'] ?? 'Sin título'
                );
            }
        }
        
        yeison_btx_widget_log('Widgets verificados desde admin', 'info', array(
            'total_placements' => count($response['result']),
            'our_widgets' => count($our_widgets)
        ));
        
        wp_send_json_success(array(
            'total' => count($our_widgets),
            'widgets' => $our_widgets,
            'message' => count($our_widgets) > 0 ? 
                'Encontrados ' . count($our_widgets) . ' widgets de este sitio' : 
                'No hay widgets registrados de este sitio'
        ));
        
    } catch (Exception $e) {
        yeison_btx_widget_log('Error verificando widgets', 'error', array(
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ));
        wp_send_json_error('Error interno: ' . $e->getMessage());
    }
}








/**
 * NUEVO: Diagnóstico completo del sistema
 */
add_action('wp_ajax_yeison_btx_widget_full_diagnostic', 'yeison_btx_widget_ajax_full_diagnostic');
function yeison_btx_widget_ajax_full_diagnostic() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Sin permisos administrativos');
        return;
    }
    
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'yeison_btx_widget_diagnostic')) {
        wp_send_json_error('Nonce inválido');
        return;
    }
    
    try {
        $diagnostic_data = yeison_btx_widget_generate_full_diagnostic();
        $tables_html = yeison_btx_widget_generate_diagnostic_tables($diagnostic_data);
        
        wp_send_json_success(array(
            'message' => 'Diagnóstico completado exitosamente',
            'tables_html' => $tables_html,
            'data' => $diagnostic_data
        ));
        
    } catch (Exception $e) {
        yeison_btx_widget_log('Error en diagnóstico completo', 'error', array(
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ));
        wp_send_json_error('Error generando diagnóstico: ' . $e->getMessage());
    }
}

/**
 * NUEVO: Limpiar cache
 */
add_action('wp_ajax_yeison_btx_widget_clear_cache', 'yeison_btx_widget_ajax_clear_cache');
function yeison_btx_widget_ajax_clear_cache() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Sin permisos administrativos');
        return;
    }
    
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'yeison_btx_widget_diagnostic')) {
        wp_send_json_error('Nonce inválido');
        return;
    }
    
    try {
        $cleared_items = array();
        
        // WordPress cache
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
            $cleared_items[] = 'WordPress Cache';
        }
        
        // LiteSpeed cache
        if (function_exists('litespeed_purge_all')) {
            litespeed_purge_all();
            $cleared_items[] = 'LiteSpeed Cache';
        }
        
        // Transients del plugin
        global $wpdb;
        $deleted_transients = $wpdb->query(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_yeison_widget_%' 
             OR option_name LIKE '_transient_timeout_yeison_widget_%'"
        );
        
        if ($deleted_transients > 0) {
            $cleared_items[] = "{$deleted_transients} transients del plugin";
        }
        
        // Object cache si existe
        if (function_exists('wp_cache_delete_group')) {
            wp_cache_delete_group('yeison_btx_widget');
            $cleared_items[] = 'Object Cache del plugin';
        }
        
        yeison_btx_widget_log('Cache limpiado desde admin', 'info', array(
            'items_cleared' => $cleared_items
        ));
        
        $message = 'Cache limpiado exitosamente';
        if (!empty($cleared_items)) {
            $message .= ': ' . implode(', ', $cleared_items);
        }
        
        wp_send_json_success($message);
        
    } catch (Exception $e) {
        wp_send_json_error('Error limpiando cache: ' . $e->getMessage());
    }
}

/**
 * NUEVO: Regenerar widget secret
 */
add_action('wp_ajax_yeison_btx_widget_regenerate_secret', 'yeison_btx_widget_ajax_regenerate_secret');
function yeison_btx_widget_ajax_regenerate_secret() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Sin permisos administrativos');
        return;
    }
    
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'yeison_btx_widget_diagnostic')) {
        wp_send_json_error('Nonce inválido');
        return;
    }
    
    try {
        $old_secret = yeison_btx_widget_get_option('widget_secret');
        $new_secret = wp_generate_password(64, false);
        
        yeison_btx_widget_update_option('widget_secret', $new_secret);
        
        yeison_btx_widget_log('Widget secret regenerado', 'success', array(
            'old_secret_length' => strlen($old_secret),
            'new_secret_length' => strlen($new_secret)
        ));
        
        wp_send_json_success('Widget secret regenerado exitosamente. La página se recargará en 2 segundos.');
        
    } catch (Exception $e) {
        wp_send_json_error('Error regenerando secret: ' . $e->getMessage());
    }
}

/**
 * Generar datos de diagnóstico completo
 */
function yeison_btx_widget_generate_full_diagnostic() {
    global $wpdb;
    
    $data = array();
    
    // 1. Información del sistema
    $data['system'] = array(
        'WordPress Version' => get_bloginfo('version'),
        'PHP Version' => PHP_VERSION,
        'Plugin Version' => YEISON_BTX_WIDGET_VERSION,
        'WooCommerce Active' => class_exists('WooCommerce') ? '✅ Sí' : '❌ No',
        'WooCommerce Version' => class_exists('WooCommerce') ? WC()->version : 'N/A',
        'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'Memory Limit' => ini_get('memory_limit'),
        'Max Execution Time' => ini_get('max_execution_time') . 's',
        'SSL Active' => is_ssl() ? '✅ Sí' : '❌ No'
    );
    
    // 2. Configuración del plugin
    $data['config'] = array(
        'Bitrix24 Domain' => yeison_btx_widget_get_option('bitrix_domain') ?: '❌ No configurado',
        'Client ID' => yeison_btx_widget_get_option('client_id') ? '✅ Configurado' : '❌ No configurado',
        'Client Secret' => yeison_btx_widget_get_option('client_secret') ? '✅ Configurado' : '❌ No configurado',
        'Access Token' => yeison_btx_widget_get_option('access_token') ? '✅ Presente' : '❌ Ausente',
        'Refresh Token' => yeison_btx_widget_get_option('refresh_token') ? '✅ Presente' : '❌ Ausente',
        'Widget Secret' => yeison_btx_widget_get_option('widget_secret') ? '✅ Generado' : '❌ Faltante',
        'Plugin Configured' => yeison_btx_widget_is_configured() ? '✅ Sí' : '❌ No',
        'Has Valid Tokens' => yeison_btx_widget_has_valid_tokens() ? '✅ Sí' : '❌ No'
    );
    
    // 3. URLs importantes
    $data['urls'] = array(
        'Site URL' => home_url(),
        'Admin URL' => admin_url(),
        'Widget URL' => home_url('/?yeison_widget=contact'),
        'WooCommerce My Account' => function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : 'N/A',
        'OAuth Redirect URI' => admin_url('admin.php?page=yeison-btx-widget&action=oauth'),
        'REST API Base' => rest_url('yeison-btx-widget/v1/'),
        'Test Widget URL' => admin_url('admin-ajax.php?action=yeison_btx_widget_test_widget')
    );
    
    // 4. Estado de la API
    $api = yeison_btx_widget_api();
    $api_status = 'API no disponible';
    $api_details = array();
    
    if ($api) {
        if (!$api->is_configured()) {
            $api_status = '⚠️ No configurada';
        } elseif (!$api->is_authorized()) {
            $api_status = '⚠️ No autorizada';
        } else {
            $test = $api->test_connection();
            if ($test['success']) {
                $api_status = '✅ Conectada';
                $api_details = $test['data'] ?? array();
            } else {
                $api_status = '❌ Error: ' . $test['message'];
            }
        }
    }
    
    $data['api'] = array(
        'Estado' => $api_status,
        'Dominio' => yeison_btx_widget_get_option('bitrix_domain') ?: 'No configurado'
    );
    
    if (!empty($api_details)) {
        $data['api'] = array_merge($data['api'], $api_details);
    }
    
    // 5. Base de datos
    $logs_table = $wpdb->prefix . 'yeison_btx_widget_logs';
    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $logs_table)) === $logs_table;
    
    $data['database'] = array(
        'Logs Table Exists' => $table_exists ? '✅ Sí' : '❌ No',
        'Total Logs' => $table_exists ? $wpdb->get_var("SELECT COUNT(*) FROM {$logs_table}") : 0,
        'Recent Errors' => $table_exists ? $wpdb->get_var("SELECT COUNT(*) FROM {$logs_table} WHERE type = 'error' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)") : 0,
        'DB Charset' => $wpdb->get_charset_collate()
    );
    
    // 6. Archivos importantes
    $files_to_check = array(
        '.htaccess' => ABSPATH . '.htaccess',
        'wp-config.php' => ABSPATH . 'wp-config.php',
        'MU-Plugin Headers' => WP_CONTENT_DIR . '/mu-plugins/yeison-widget-headers.php',
        'Plugin Main File' => YEISON_BTX_WIDGET_PLUGIN_FILE
    );
    
    foreach ($files_to_check as $name => $path) {
        $data['files'][$name] = file_exists($path) ? '✅ Existe' : '❌ No existe';
    }
    
    // 7. Plugins activos que pueden interferir
    $problematic_plugins = array(
        'wordfence/wordfence.php' => 'Wordfence Security',
        'really-simple-ssl/rlrsssl-really-simple-ssl.php' => 'Really Simple SSL',
        'wp-security-audit-log/wp-security-audit-log.php' => 'WP Security Audit Log',
        'all-in-one-wp-security-and-firewall/wp-security.php' => 'All In One WP Security'
    );
    
    $active_plugins = get_option('active_plugins', array());
    $conflicting_plugins = array();
    
    foreach ($problematic_plugins as $plugin_file => $plugin_name) {
        if (in_array($plugin_file, $active_plugins)) {
            $conflicting_plugins[] = $plugin_name;
        }
    }
    
    $data['plugins'] = array(
        'Total Active Plugins' => count($active_plugins),
        'Potentially Conflicting' => empty($conflicting_plugins) ? '✅ Ninguno' : '⚠️ ' . implode(', ', $conflicting_plugins)
    );
    
    return $data;
}

/**
 * Generar HTML de tablas de diagnóstico
 */
function yeison_btx_widget_generate_diagnostic_tables($data) {
    $html = '';
    
    foreach ($data as $section_name => $section_data) {
        $section_title = ucwords(str_replace('_', ' ', $section_name));
        
        $html .= '<h3>📋 ' . esc_html($section_title) . '</h3>';
        $html .= '<table class="diagnostic-table">';
        $html .= '<thead><tr><th>Elemento</th><th>Estado/Valor</th></tr></thead>';
        $html .= '<tbody>';
        
        foreach ($section_data as $key => $value) {
            $status_class = '';
            if (strpos($value, '✅') !== false) {
                $status_class = 'status-ok';
            } elseif (strpos($value, '❌') !== false) {
                $status_class = 'status-error';
            } elseif (strpos($value, '⚠️') !== false) {
                $status_class = 'status-warning';
            }
            
            $html .= '<tr>';
            $html .= '<td><strong>' . esc_html($key) . '</strong></td>';
            $html .= '<td class="' . $status_class . '">' . esc_html($value) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
    }
    
    return $html;
}

// ============================================================================
// FIN FUNCIONES AJAX MEJORADAS
// ============================================================================







/**
 * FUNCIÓN AJAX CORREGIDA PARA ELIMINAR WIDGETS
 * Reemplazar la función existente en functions.php
 */

add_action('wp_ajax_yeison_btx_widget_cleanup_widgets', 'yeison_btx_widget_ajax_cleanup_widgets_json_safe');
function yeison_btx_widget_ajax_cleanup_widgets_json_safe() {
    // Headers para JSON limpio
    header('Content-Type: application/json; charset=UTF-8');
    
    // Verificar permisos
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Sin permisos administrativos');
        return;
    }
    
    // Verificar nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'yeison_btx_widget_cleanup')) {
        wp_send_json_error('Nonce invalido');
        return;
    }
    
    try {
        $api = yeison_btx_widget_api();
        if (!$api || !$api->is_authorized()) {
            wp_send_json_error('API no autorizada. Autoriza el plugin primero.');
            return;
        }
        
        yeison_btx_widget_log('Iniciando limpieza de widgets desde admin', 'info');
        
        // Obtener lista de placements
        $response = $api->api_call('placement.get');
        
        if (!$response || !isset($response['result'])) {
            $error_msg = 'Error consultando placements de Bitrix24';
            if (isset($response['error'])) {
                $error_msg .= ': ' . $response['error'];
            }
            wp_send_json_error($error_msg);
            return;
        }
        
        $our_domain = parse_url(home_url(), PHP_URL_HOST);
        $removed_count = 0;
        $failed_count = 0;
        $total_found = count($response['result']);
        $widgets_to_remove = array();
        
        // Identificar widgets de nuestro sitio
        foreach ($response['result'] as $placement) {
            if (isset($placement['handler']) && 
                (strpos($placement['handler'], $our_domain) !== false || 
                 strpos($placement['handler'], 'yeison') !== false)) {
                $widgets_to_remove[] = $placement;
            }
        }
        
        // Eliminar cada widget encontrado
        foreach ($widgets_to_remove as $widget) {
            $unbind_response = $api->api_call('placement.unbind', array(
                'PLACEMENT' => $widget['placement'],
                'HANDLER' => $widget['handler']
            ));
            
            if ($unbind_response && isset($unbind_response['result'])) {
                $removed_count++;
                yeison_btx_widget_log('Widget eliminado', 'success', array(
                    'placement' => $widget['placement'],
                    'handler' => $widget['handler']
                ));
            } else {
                $failed_count++;
                yeison_btx_widget_log('Error eliminando widget', 'error', array(
                    'placement' => $widget['placement'],
                    'handler' => $widget['handler'],
                    'response' => $unbind_response
                ));
            }
        }
        
        yeison_btx_widget_log('Limpieza completada', 'info', array(
            'removed' => $removed_count,
            'failed' => $failed_count,
            'total_checked' => $total_found
        ));
        
        // Respuesta JSON segura sin emojis problemáticos
        wp_send_json_success(array(
            'message' => 'Limpieza completada exitosamente',
            'removed' => $removed_count,
            'failed' => $failed_count,
            'total_found' => count($widgets_to_remove),
            'total_checked' => $total_found,
            'summary' => sprintf('Eliminados: %d, Errores: %d, Total revisados: %d', 
                               $removed_count, $failed_count, $total_found)
        ));
        
    } catch (Exception $e) {
        yeison_btx_widget_log('Error en limpieza de widgets', 'error', array(
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ));
        
        wp_send_json_error('Error interno: ' . $e->getMessage());
    }
}

/**
 * FUNCIÓN AUXILIAR PARA OBTENER INFORMACIÓN DE WIDGETS
 */
add_action('wp_ajax_yeison_btx_widget_get_widgets_info', 'yeison_btx_widget_ajax_get_widgets_info');
function yeison_btx_widget_ajax_get_widgets_info() {
    header('Content-Type: application/json; charset=UTF-8');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Sin permisos');
        return;
    }
    
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'yeison_btx_widget_cleanup')) {
        wp_send_json_error('Nonce invalido');
        return;
    }
    
    try {
        $api = yeison_btx_widget_api();
        if (!$api || !$api->is_authorized()) {
            wp_send_json_error('API no autorizada');
            return;
        }
        
        $response = $api->api_call('placement.get');
        
        if (!$response || !isset($response['result'])) {
            wp_send_json_error('Error consultando Bitrix24');
            return;
        }
        
        $our_domain = parse_url(home_url(), PHP_URL_HOST);
        $our_widgets = array();
        
        foreach ($response['result'] as $placement) {
            if (isset($placement['handler']) && 
                (strpos($placement['handler'], $our_domain) !== false || 
                 strpos($placement['handler'], 'yeison') !== false)) {
                $our_widgets[] = array(
                    'placement' => $placement['placement'],
                    'handler' => $placement['handler'],
                    'title' => $placement['title'] ?? 'Sin titulo'
                );
            }
        }
        
        wp_send_json_success(array(
            'widgets' => $our_widgets,
            'count' => count($our_widgets),
            'total_placements' => count($response['result'])
        ));
        
    } catch (Exception $e) {
        wp_send_json_error('Error: ' . $e->getMessage());
    }
}


// ============================================================================
// FIN FUNCIONES AJAX MEJORADAS
// ============================================================================

