<?php
/**
 * Manejador de Autologin - Widget Version con Soporte para Iframe
 * 
 * @package YeisonBTX_Widget
 */

if (!defined('ABSPATH')) {
    exit;
}

class YeisonBTX_Widget_Autologin_Handler {
    
    private static $instance = null;
    
    private function __construct() {
        $this->init_hooks();
    }
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function init_hooks() {
        // Hook temprano para manejar autologin
        add_action('wp_loaded', array($this, 'handle_autologin_request'), 1);
        
        // Hook para forzar cookies SameSite=None en iframe
        add_action('init', array($this, 'setup_iframe_cookie_handling'), 1);
    }
    
    /**
     * NUEVO: Configurar manejo de cookies para iframe
     */
    public function setup_iframe_cookie_handling() {
        if (!isset($_GET['yeison_widget_autologin'])) {
            return;
        }
        
        // Detectar si estamos en iframe de Bitrix24
        $is_iframe = $this->is_bitrix24_iframe();
        
        if ($is_iframe) {
            yeison_btx_widget_log('🖼️ Detected iframe context - adjusting cookie settings', 'info', array(
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'referer' => $_SERVER['HTTP_REFERER'] ?? ''
            ));
            
            // Forzar configuración de cookies para iframe
            add_action('wp_set_auth_cookie', array($this, 'force_iframe_cookie_settings'), 10, 5);
            add_action('wp_clear_auth_cookie', array($this, 'force_iframe_cookie_clearing'), 10);
        }
    }
    
    /**
     * NUEVO: Detectar si estamos en iframe de Bitrix24
     */
    private function is_bitrix24_iframe() {
        $indicators = array(
            isset($_GET['DOMAIN']) && strpos($_GET['DOMAIN'], 'bitrix24') !== false,
            isset($_GET['APP_SID']),
            isset($_GET['PROTOCOL']),
            strpos($_SERVER['HTTP_REFERER'] ?? '', 'bitrix24') !== false,
            strpos($_SERVER['HTTP_USER_AGENT'] ?? '', 'Bitrix') !== false
        );
        
        return in_array(true, $indicators);
    }
    
    /**
     * NUEVO: Forzar configuración de cookies para iframe
     */
    public function force_iframe_cookie_settings($auth_cookie, $expire, $expiration, $user_id, $scheme) {
        if (!$this->is_bitrix24_iframe()) {
            return;
        }
        
        $secure = is_ssl();
        $httponly = true;
        $samesite = 'None'; // CRÍTICO para iframe de terceros
        
        // Nombres de cookies
        $auth_cookie_name = $secure ? SECURE_AUTH_COOKIE : AUTH_COOKIE;
        $logged_in_cookie_name = LOGGED_IN_COOKIE;
        
        // Generar cookies manualmente con SameSite=None
        $logged_in_cookie = wp_generate_auth_cookie($user_id, $expiration, 'logged_in');
        
        // Establecer cookies con configuración de iframe
        $this->set_iframe_cookie($auth_cookie_name, $auth_cookie, $expire, $secure, $httponly, $samesite);
        $this->set_iframe_cookie($logged_in_cookie_name, $logged_in_cookie, $expire, $secure, $httponly, $samesite);
        
        // También establecer en $_COOKIE para lectura inmediata
        $_COOKIE[$auth_cookie_name] = $auth_cookie;
        $_COOKIE[$logged_in_cookie_name] = $logged_in_cookie;
        
        yeison_btx_widget_log('🍪 Cookies establecidas para iframe', 'success', array(
            'auth_cookie_name' => $auth_cookie_name,
            'logged_in_cookie_name' => $logged_in_cookie_name,
            'user_id' => $user_id,
            'secure' => $secure,
            'samesite' => $samesite
        ));
    }
    
    /**
     * NUEVO: Establecer cookie con configuración específica para iframe
     */
    private function set_iframe_cookie($name, $value, $expire, $secure, $httponly, $samesite) {
        $cookie_header = sprintf(
            '%s=%s; expires=%s; path=%s; domain=%s',
            $name,
            $value,
            gmdate('D, d M Y H:i:s T', $expire),
            COOKIEPATH,
            COOKIE_DOMAIN
        );
        
        if ($secure) {
            $cookie_header .= '; Secure';
        }
        
        if ($httponly) {
            $cookie_header .= '; HttpOnly';
        }
        
        if ($samesite) {
            $cookie_header .= '; SameSite=' . $samesite;
        }
        
        // Enviar cookie header
        if (!headers_sent()) {
            header('Set-Cookie: ' . $cookie_header, false);
        }
        
        yeison_btx_widget_log('🍪 Cookie header enviado', 'debug', array(
            'cookie_header' => $cookie_header,
            'headers_sent' => headers_sent()
        ));
    }
    
    /**
     * NUEVO: Limpiar cookies para iframe
     */
    public function force_iframe_cookie_clearing() {
        if (!$this->is_bitrix24_iframe()) {
            return;
        }
        
        $secure = is_ssl();
        $auth_cookie_name = $secure ? SECURE_AUTH_COOKIE : AUTH_COOKIE;
        $logged_in_cookie_name = LOGGED_IN_COOKIE;
        
        // Limpiar cookies con configuración de iframe
        $this->set_iframe_cookie($auth_cookie_name, '', time() - 3600, $secure, true, 'None');
        $this->set_iframe_cookie($logged_in_cookie_name, '', time() - 3600, $secure, true, 'None');
        
        // Limpiar de $_COOKIE
        unset($_COOKIE[$auth_cookie_name]);
        unset($_COOKIE[$logged_in_cookie_name]);
    }
    
    public function handle_autologin_request() {
        if (!isset($_GET['yeison_widget_autologin'])) {
            return;
        }
        
        if (headers_sent()) {
            yeison_btx_widget_log('❌ Headers ya enviados - no se puede hacer autologin', 'error');
            return;
        }
        
        $token = sanitize_text_field($_GET['yeison_widget_autologin']);
        $source = sanitize_text_field($_GET['source'] ?? 'unknown');
        
        yeison_btx_widget_log('🔐 Procesando autologin en iframe', 'info', array(
            'source' => $source,
            'token_length' => strlen($token),
            'current_user_before' => get_current_user_id(),
            'ip' => yeison_btx_widget_get_ip(),
            'is_iframe' => $this->is_bitrix24_iframe(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ));
        
        $user_data = $this->validate_autologin_token($token);
        
        if (!$user_data) {
            $this->handle_autologin_error('Token inválido o expirado');
            return;
        }
        
        // Verificar si ya estamos logueados como el usuario correcto
        $current_user_id = get_current_user_id();
        if ($current_user_id == $user_data['user_id']) {
            yeison_btx_widget_log('✅ Usuario ya logueado correctamente', 'success', array(
                'user_id' => $current_user_id
            ));
            $this->redirect_to_my_account_simple($user_data);
            return;
        }
        
        $this->force_logout_current_session();
        $this->perform_autologin($user_data);
    }

    private function force_logout_current_session() {
        $current_user_id = get_current_user_id();
        
        if ($current_user_id > 0) {
            yeison_btx_widget_log('🚫 Cerrando sesión existente', 'info', array(
                'current_user_id' => $current_user_id
            ));
        }
        
        // Limpiar sesión de WordPress
        wp_destroy_current_session();
        wp_clear_auth_cookie();
        wp_set_current_user(0);
        
        // Limpiar sesión PHP si existe
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    /**
     * MEJORADO: Autologin con manejo especial para iframe
     */
    private function perform_autologin($user_data) {
        $user_id = intval($user_data['user_id']);
        $user = get_user_by('id', $user_id);
        
        if (!$user) {
            $this->handle_autologin_error('Usuario no encontrado (ID: ' . $user_id . ')');
            return;
        }
        
        if ($user->user_email !== $user_data['email']) {
            yeison_btx_widget_log('❌ Email no coincide', 'error', array(
                'token_email' => $user_data['email'],
                'user_email' => $user->user_email,
                'user_id' => $user_id
            ));
            $this->handle_autologin_error('Datos de usuario no coinciden');
            return;
        }
        
        try {
            // Establecer usuario actual
            wp_set_current_user($user_id);
            
            // Configurar cookies con configuración específica para iframe
            if ($this->is_bitrix24_iframe()) {
                // Para iframe, usar configuración especial
                $this->set_auth_cookies_for_iframe($user_id);
            } else {
                // Para uso normal
                wp_set_auth_cookie($user_id, true, is_ssl());
            }
            
            // Verificar que el login funcionó
            $logged_user_id = get_current_user_id();
            if ($logged_user_id !== $user_id) {
                throw new Exception("Login falló - Expected: {$user_id}, Got: {$logged_user_id}");
            }
            
            // Actualizar metadatos
            update_user_meta($user_id, 'last_login', current_time('mysql'));
            update_user_meta($user_id, 'login_source', 'bitrix24_widget');
            update_user_meta($user_id, 'last_login_ip', yeison_btx_widget_get_ip());
            
            // Disparar hooks
            do_action('wp_login', $user->user_login, $user);
            do_action('yeison_btx_widget_user_autologin', $user, $user_data);
            
            yeison_btx_widget_log('🎉 Autologin exitoso en iframe', 'success', array(
                'user_id' => $user_id,
                'user_login' => $user->user_login,
                'email' => $user->user_email,
                'source' => $user_data['source'] ?? 'unknown',
                'verified_current_user' => get_current_user_id(),
                'is_iframe' => $this->is_bitrix24_iframe()
            ));
            
            $this->redirect_to_my_account($user, $user_data);
            
        } catch (Exception $e) {
            yeison_btx_widget_log('❌ Error en autologin', 'error', array(
                'error' => $e->getMessage(),
                'user_id' => $user_id
            ));
            $this->handle_autologin_error('Error interno en autologin: ' . $e->getMessage());
        }
    }
    
    /**
     * NUEVO: Establecer cookies específicamente para iframe
     */
    private function set_auth_cookies_for_iframe($user_id) {
        $expiration = time() + (14 * DAY_IN_SECONDS); // 14 días
        $expire = $expiration + (12 * HOUR_IN_SECONDS);
        
        // Crear tokens de sesión
        $manager = WP_Session_Tokens::get_instance($user_id);
        $token = $manager->create($expiration);
        
        // Generar cookies
        $secure = is_ssl();
        $auth_cookie = wp_generate_auth_cookie($user_id, $expiration, $secure ? 'secure_auth' : 'auth', $token);
        $logged_in_cookie = wp_generate_auth_cookie($user_id, $expiration, 'logged_in', $token);
        
        // Nombres de cookies
        $auth_cookie_name = $secure ? SECURE_AUTH_COOKIE : AUTH_COOKIE;
        $logged_in_cookie_name = LOGGED_IN_COOKIE;
        
        // Establecer cookies con SameSite=None para iframe
        $this->set_iframe_cookie($auth_cookie_name, $auth_cookie, $expire, $secure, true, 'None');
        $this->set_iframe_cookie($logged_in_cookie_name, $logged_in_cookie, $expire, $secure, false, 'None');
        
        // Establecer en $_COOKIE para lectura inmediata
        $_COOKIE[$auth_cookie_name] = $auth_cookie;
        $_COOKIE[$logged_in_cookie_name] = $logged_in_cookie;
        
        yeison_btx_widget_log('🍪 Cookies de iframe establecidas manualmente', 'success', array(
            'user_id' => $user_id,
            'token' => substr($token, 0, 10) . '...',
            'expiration' => date('Y-m-d H:i:s', $expiration)
        ));
    }
    
    /**
     * NUEVO: Redirección simple sin query args
     */
    private function redirect_to_my_account_simple($user_data) {
        $myaccount_url = '';
        
        if (function_exists('wc_get_page_permalink')) {
            $myaccount_url = wc_get_page_permalink('myaccount');
        }
        
        if (empty($myaccount_url) || $myaccount_url === home_url()) {
            $myaccount_url = admin_url();
        }
        
        yeison_btx_widget_log('🔄 Redirigiendo usuario ya logueado', 'info', array(
            'user_id' => $user_data['user_id'],
            'redirect_url' => $myaccount_url
        ));
        
        wp_safe_redirect($myaccount_url);
        exit;
    }

    private function redirect_to_my_account($user, $user_data) {
        $myaccount_url = '';
        
        if (function_exists('wc_get_page_permalink')) {
            $myaccount_url = wc_get_page_permalink('myaccount');
        }
        
        if (empty($myaccount_url) || $myaccount_url === home_url()) {
            $myaccount_url = admin_url();
        }
        
        // Para iframe, no agregar query parameters que puedan causar problemas
        if ($this->is_bitrix24_iframe()) {
            $redirect_url = $myaccount_url;
        } else {
            $redirect_url = add_query_arg(array(
                'login_success' => '1',
                'source' => 'bitrix24_widget',
                'user_id' => $user->ID,
                'timestamp' => time()
            ), $myaccount_url);
        }
        
        yeison_btx_widget_log('🔄 Redirigiendo a Mi Cuenta', 'info', array(
            'user_id' => $user->ID,
            'redirect_url' => $redirect_url,
            'current_user_verified' => get_current_user_id(),
            'is_iframe' => $this->is_bitrix24_iframe()
        ));
        
        wp_safe_redirect($redirect_url);
        exit;
    }

    private function validate_autologin_token($token) {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 2) {
                yeison_btx_widget_log('❌ Formato de token inválido', 'error');
                return false;
            }
            
            list($token_data_encoded, $received_hash) = $parts;
            
            $widget_secret = yeison_btx_widget_get_option('widget_secret');
            if (empty($widget_secret)) {
                yeison_btx_widget_log('❌ Widget secret no configurado', 'error');
                return false;
            }
            
            $expected_hash = hash_hmac('sha256', $token_data_encoded, $widget_secret);
            
            if (!hash_equals($expected_hash, $received_hash)) {
                yeison_btx_widget_log('❌ Hash de token inválido', 'error');
                return false;
            }
            
            $token_data = json_decode(base64_decode($token_data_encoded), true);
            
            if (!$token_data || !isset($token_data['user_id'], $token_data['email'], $token_data['expires'])) {
                yeison_btx_widget_log('❌ Datos de token inválidos', 'error');
                return false;
            }
            
            if (time() > $token_data['expires']) {
                yeison_btx_widget_log('❌ Token expirado', 'warning', array(
                    'expired_at' => date('Y-m-d H:i:s', $token_data['expires'])
                ));
                return false;
            }
            
            // Verificar que el token esté en el almacén temporal
            $token_key = 'yeison_widget_autologin_' . md5($token_data_encoded);
            $stored_data = get_transient($token_key);
            
            if (!$stored_data) {
                yeison_btx_widget_log('❌ Token no encontrado en almacén temporal', 'error');
                return false;
            }
            
            // Eliminar token después de usarlo (uso único)
            delete_transient($token_key);
            
            yeison_btx_widget_log('✅ Token validado correctamente', 'success', array(
                'user_id' => $token_data['user_id'],
                'email' => $token_data['email']
            ));
            
            return $token_data;
            
        } catch (Exception $e) {
            yeison_btx_widget_log('❌ Excepción validando token', 'error', array(
                'error' => $e->getMessage()
            ));
            return false;
        }
    }

    private function handle_autologin_error($message) {
        yeison_btx_widget_log('❌ Error en autologin: ' . $message, 'error');
        $this->show_autologin_error_page($message);
    }

    private function show_autologin_error_page($message) {
        http_response_code(400);
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Error de Acceso - Yeison BTX Widget</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                }
                .error-container {
                    background: white;
                    border-radius: 16px;
                    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                    padding: 40px;
                    max-width: 500px;
                    width: 100%;
                    text-align: center;
                }
                .error-icon { font-size: 64px; margin-bottom: 20px; }
                .error-title { color: #dc3545; margin-bottom: 15px; }
                .error-message { color: #4a5568; line-height: 1.5; }
                .error-details {
                    background: #fed7d7;
                    border: 1px solid #fc8181;
                    border-radius: 8px;
                    padding: 15px;
                    margin: 20px 0;
                    color: #742a2a;
                    font-size: 14px;
                }
                .back-button {
                    background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    border: none;
                    border-radius: 8px;
                    padding: 12px 24px;
                    font-size: 16px;
                    font-weight: 600;
                    cursor: pointer;
                    text-decoration: none;
                    display: inline-block;
                    margin-top: 20px;
                    transition: transform 0.2s;
                }
                .back-button:hover { transform: translateY(-2px); }
            </style>
        </head>
        <body>
            <div class="error-container">
                <div class="error-icon">🔒</div>
                <h1 class="error-title">Acceso Denegado</h1>
                <p class="error-message">No se pudo completar el inicio de sesión automático.</p>
                <div class="error-details">
                    <strong>Motivo:</strong> <?php echo esc_html($message); ?>
                </div>
                <a href="<?php echo home_url(); ?>" class="back-button">🏠 Volver al Inicio</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    public function create_temp_login_token($user_id, $email) {
        $widget_secret = yeison_btx_widget_get_option('widget_secret');
        
        if (empty($widget_secret)) {
            yeison_btx_widget_log('❌ No se puede crear token: widget_secret no configurado', 'error');
            return false;
        }
        
        $token_data = array(
            'user_id' => intval($user_id),
            'email' => sanitize_email($email),
            'timestamp' => time(),
            'expires' => time() + (10 * 60), // 10 minutos
            'source' => 'bitrix24_widget',
            'random' => wp_generate_password(32, false),
            'created_by' => get_current_user_id(),
            'ip' => yeison_btx_widget_get_ip()
        );
        
        $token_encoded = base64_encode(wp_json_encode($token_data));
        $token_hash = hash_hmac('sha256', $token_encoded, $widget_secret);
        $full_token = $token_encoded . '.' . $token_hash;
        
        // Almacenar temporalmente
        $token_key = 'yeison_widget_autologin_' . md5($token_encoded);
        set_transient($token_key, $token_data, 600); // 10 minutos
        
        yeison_btx_widget_log('🎫 Token de autologin creado', 'success', array(
            'user_id' => $user_id,
            'email' => $email,
            'expires_in' => '10 minutos'
        ));
        
        return $full_token;
    }
}