<?php
/**
 * Plugin Name: Yeison BTX Widget - WooCommerce Integration
 * Plugin URI: https://www.yeisonprogramador.com
 * Description: Sistema de autenticación automática para WooCommerce desde Bitrix24 - Versión Widget
 * Version: 2.0.0
 * Author: Yeison Programador
 * Author URI: https://www.yeisonprogramador.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: yeison-btx-widget
 * Domain Path: /languages
 * 
 * @package YeisonBTX_Widget
 * @version 2.0.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// ============================================================================
// CONSTANTES DEL PLUGIN
// ============================================================================

define('YEISON_BTX_WIDGET_VERSION', '2.0.0');
define('YEISON_BTX_WIDGET_PLUGIN_FILE', __FILE__);
define('YEISON_BTX_WIDGET_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('YEISON_BTX_WIDGET_PLUGIN_URL', plugin_dir_url(__FILE__));
define('YEISON_BTX_WIDGET_PLUGIN_BASENAME', plugin_basename(__FILE__));

// ============================================================================
// VERIFICACIÓN DE REQUISITOS
// ============================================================================

/**
 * Verificar que WordPress y PHP cumplan los requisitos mínimos
 */
function yeison_btx_widget_check_requirements() {
    global $wp_version;
    
    $requirements = array(
        'wp_version' => '5.0',
        'php_version' => '7.4'
    );
    
    $errors = array();
    
    // Verificar versión de WordPress
    if (version_compare($wp_version, $requirements['wp_version'], '<')) {
        $errors[] = sprintf(
            'WordPress %s o superior es requerido. Tienes %s.',
            $requirements['wp_version'],
            $wp_version
        );
    }
    
    // Verificar versión de PHP
    if (version_compare(PHP_VERSION, $requirements['php_version'], '<')) {
        $errors[] = sprintf(
            'PHP %s o superior es requerido. Tienes %s.',
            $requirements['php_version'],
            PHP_VERSION
        );
    }
    
    // Verificar extensiones PHP requeridas
    $required_extensions = array('curl', 'json', 'mbstring');
    foreach ($required_extensions as $extension) {
        if (!extension_loaded($extension)) {
            $errors[] = sprintf('La extensión PHP "%s" es requerida.', $extension);
        }
    }
    
    return $errors;
}

/**
 * Mostrar notice de requisitos no cumplidos
 */
function yeison_btx_widget_requirements_notice() {
    $errors = yeison_btx_widget_check_requirements();
    
    if (!empty($errors)) {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>Yeison BTX Widget:</strong> No se puede activar debido a los siguientes problemas:<br>';
        foreach ($errors as $error) {
            echo '• ' . esc_html($error) . '<br>';
        }
        echo '</p></div>';
        
        // Desactivar el plugin
        deactivate_plugins(YEISON_BTX_WIDGET_PLUGIN_BASENAME);
        return false;
    }
    
    return true;
}

/**
 * Función para verificar requisitos en activación
 */
function yeison_btx_widget_activation_check() {
    if (!yeison_btx_widget_requirements_notice()) {
        return;
    }
}

// Verificar requisitos en activación
register_activation_hook(__FILE__, 'yeison_btx_widget_activation_check');

// Verificar requisitos en admin_notices
add_action('admin_notices', 'yeison_btx_widget_admin_notices_check');
function yeison_btx_widget_admin_notices_check() {
    $errors = yeison_btx_widget_check_requirements();
    if (!empty($errors)) {
        yeison_btx_widget_requirements_notice();
    }
}

// ============================================================================
// CLASE PRINCIPAL DEL PLUGIN
// ============================================================================

if (!class_exists('YeisonBTX_Widget_Main')) {
    
    class YeisonBTX_Widget_Main {
        
        /**
         * Instancia única del plugin
         */
        private static $instance = null;
        
        /**
         * Versión del plugin
         */
        public $version = YEISON_BTX_WIDGET_VERSION;
        
        /**
         * API de Bitrix24
         */
        public $api = null;
        
        /**
         * Widget Manager
         */
        public $widgets = null;
        
        /**
         * Autologin Handler
         */
        public $autologin = null;
        
        /**
         * Notices para OAuth
         */
        private $oauth_success_notice = false;
        private $oauth_error_notice = false;
        
        /**
         * Constructor privado (Singleton)
         */
        private function __construct() {
            $this->define_constants();
            $this->init_hooks();
            $this->load_dependencies();
            $this->init_components();
        }
        
        /**
         * Obtener instancia única del plugin
         */
        public static function get_instance() {
            if (null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        
        /**
         * Definir constantes adicionales
         */
        private function define_constants() {
            if (!defined('YEISON_BTX_WIDGET_ABSPATH')) {
                define('YEISON_BTX_WIDGET_ABSPATH', dirname(YEISON_BTX_WIDGET_PLUGIN_FILE) . '/');
            }
        }
        
        /**
         * Inicializar hooks de WordPress
         */
        private function init_hooks() {
            add_action('plugins_loaded', array($this, 'load_textdomain'));
            add_action('init', array($this, 'init'), 0);
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
            add_action('admin_notices', array($this, 'show_admin_notices'));
            
            // Hooks de activación y desactivación
            register_activation_hook(YEISON_BTX_WIDGET_PLUGIN_FILE, array($this, 'activation'));
            register_deactivation_hook(YEISON_BTX_WIDGET_PLUGIN_FILE, array($this, 'deactivation'));
        }
        
        /**
         * Mostrar notices de administración
         */
        public function show_admin_notices() {
            if ($this->oauth_success_notice) {
                $this->show_oauth_success_notice();
            }
            if ($this->oauth_error_notice) {
                $this->show_oauth_error_notice();
            }
        }
        
        /**
         * Cargar dependencias del plugin
         */
        private function load_dependencies() {
            // Cargar functions.php principal
            $functions_file = YEISON_BTX_WIDGET_PLUGIN_DIR . 'functions.php';
            if (file_exists($functions_file)) {
                require_once $functions_file;
            }
            
            // Cargar clases principales
            $classes = array(
                'class-bitrix-api.php',
                'class-autologin-handler.php',
                'class-widget-manager.php'
            );
            
            foreach ($classes as $class_file) {
                $file_path = YEISON_BTX_WIDGET_PLUGIN_DIR . 'includes/' . $class_file;
                if (file_exists($file_path)) {
                    require_once $file_path;
                }
            }
        }
        
        /**
         * Inicializar componentes del plugin
         */
        private function init_components() {
            // Solo inicializar si las clases existen
            if (class_exists('YeisonBTX_Widget_Bitrix_API')) {
                $this->api = YeisonBTX_Widget_Bitrix_API::get_instance();
            }
            
            if (class_exists('YeisonBTX_Widget_Manager')) {
                $this->widgets = YeisonBTX_Widget_Manager::get_instance();
            }
            
            if (class_exists('YeisonBTX_Widget_Autologin_Handler')) {
                $this->autologin = YeisonBTX_Widget_Autologin_Handler::get_instance();
            }
        }
        
        /**
         * Cargar archivos de idioma
         */
        public function load_textdomain() {
            load_plugin_textdomain(
                'yeison-btx-widget',
                false,
                dirname(YEISON_BTX_WIDGET_PLUGIN_BASENAME) . '/languages/'
            );
        }
        
        /**
         * Inicialización principal del plugin
         */
        public function init() {
            // Verificar que WooCommerce esté activo
            if (!$this->is_woocommerce_active()) {
                add_action('admin_notices', array($this, 'woocommerce_required_notice'));
                return;
            }
            
            // Log de inicialización
            if (function_exists('yeison_btx_widget_log')) {
                yeison_btx_widget_log('Plugin Yeison BTX Widget inicializado', 'info', array(
                    'version' => $this->version,
                    'wp_version' => get_bloginfo('version'),
                    'php_version' => PHP_VERSION
                ));
            }
        }
        
        /**
         * Verificar si WooCommerce está activo
         */
        private function is_woocommerce_active() {
            return class_exists('WooCommerce');
        }
        
        /**
         * Notice cuando WooCommerce no está activo
         */
        public function woocommerce_required_notice() {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>Yeison BTX Widget:</strong> ';
            echo 'WooCommerce es requerido para que este plugin funcione correctamente.';
            echo '</p></div>';
        }
        
        /**
         * Agregar menú de administración
         */
        public function add_admin_menu() {
            add_menu_page(
                'Yeison BTX Widget',
                'BTX Widget',
                'manage_options',
                'yeison-btx-widget',
                array($this, 'admin_page'),
                'dashicons-store',
                30
            );
            
            // Submenús
            add_submenu_page(
                'yeison-btx-widget',
                'Configuración - BTX Widget',
                'Configuración',
                'manage_options',
                'yeison-btx-widget',
                array($this, 'admin_page')
            );
            
            add_submenu_page(
                'yeison-btx-widget',
                'Logs - BTX Widget',
                'Logs',
                'manage_options',
                'yeison-btx-widget-logs',
                array($this, 'logs_page')
            );
            
            add_submenu_page(
                'yeison-btx-widget',
                'Diagnóstico - BTX Widget',
                'Diagnóstico',
                'manage_options',
                'yeison-btx-widget-diagnostic',
                array($this, 'diagnostic_page')
            );
        }
        
        /**
         * Cargar scripts de administración
         */
        public function admin_scripts($hook) {
            // Solo cargar en nuestras páginas
            if (strpos($hook, 'yeison-btx-widget') === false) {
                return;
            }
            
            wp_enqueue_script('jquery');
            
            // Script inline para AJAX
            $ajax_nonce = wp_create_nonce('yeison_btx_widget_test');
            ?>
            <script>
            jQuery(document).ready(function($) {
                window.yeisonBtxWidget = {
                    ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
                    nonce: '<?php echo $ajax_nonce; ?>'
                };
            });
            </script>
            <?php
        }
        
        /**
         * Página principal de administración
         */
        public function admin_page() {
            // Manejar OAuth callback
            if (isset($_GET['action']) && $_GET['action'] === 'oauth' && isset($_GET['code'])) {
                $this->handle_oauth_callback();
            }
            
            // Mostrar página de configuración
            include YEISON_BTX_WIDGET_PLUGIN_DIR . 'admin/admin-page.php';
        }
        
        /**
         * Página de logs
         */
        public function logs_page() {
            include YEISON_BTX_WIDGET_PLUGIN_DIR . 'admin/logs-page.php';
        }
        
        /**
         * Página de diagnóstico
         */
        public function diagnostic_page() {
            include YEISON_BTX_WIDGET_PLUGIN_DIR . 'admin/diagnostic-page.php';
        }
        
        /**
         * Manejar callback de OAuth
         */
        private function handle_oauth_callback() {
            if (!current_user_can('manage_options')) {
                wp_die('Sin permisos');
            }
            
            $code = sanitize_text_field($_GET['code']);
            $state = sanitize_text_field($_GET['state'] ?? '');
            
            if ($this->api && $this->api->exchange_code_for_tokens($code, $state)) {
                $this->oauth_success_notice = true;
                
                // Trigger hook para auto-registro de widgets
                do_action('yeison_btx_widget_oauth_success');
            } else {
                $this->oauth_error_notice = true;
            }
        }
        
        /**
         * Mostrar notice de OAuth exitoso
         */
        public function show_oauth_success_notice() {
            echo '<div class="notice notice-success"><p>✅ Autorización exitosa con Bitrix24</p></div>';
        }
        
        /**
         * Mostrar notice de error en OAuth
         */
        public function show_oauth_error_notice() {
            echo '<div class="notice notice-error"><p>❌ Error en la autorización con Bitrix24</p></div>';
        }
        
        /**
         * Activación del plugin
         */
        


        /**
         * Activación del plugin - VERSIÓN AUTO-SETUP
         */
        public function activation() {
            // Verificar requisitos
            $errors = yeison_btx_widget_check_requirements();
            if (!empty($errors)) {
                wp_die('No se puede activar el plugin: ' . implode(', ', $errors));
            }
            
            // Crear tabla de logs PRIMERO
            $this->create_logs_table();
            
            // Log de inicio de activación
            if (function_exists('yeison_btx_widget_log')) {
                yeison_btx_widget_log('🚀 Iniciando activación del plugin', 'info', array(
                    'version' => YEISON_BTX_WIDGET_VERSION,
                    'php_version' => PHP_VERSION,
                    'wp_version' => get_bloginfo('version')
                ));
            }
            
            // Establecer opciones por defecto
            $default_options = array(
                'plugin_version' => YEISON_BTX_WIDGET_VERSION,
                'widgets_enabled' => true,
                'widget_auto_register' => true,
                'debug_mode' => false,
                'widget_secret' => wp_generate_password(64, false), // Auto-generar siempre
                'setup_completed' => false
            );
            
            $current_options = get_option('yeison_btx_widget_settings', array());
            $new_options = array_merge($default_options, $current_options);
            
            // Forzar regenerar widget_secret si está vacío
            if (empty($new_options['widget_secret'])) {
                $new_options['widget_secret'] = wp_generate_password(64, false);
            }
            
            update_option('yeison_btx_widget_settings', $new_options);
            
            // Flush rewrite rules
            flush_rewrite_rules();
            
            // TRIGGER para auto-configuración
            if (function_exists('yeison_btx_widget_log')) {
                yeison_btx_widget_log('✅ Plugin activado - triggering auto-setup', 'success', array(
                    'widget_secret_generated' => !empty($new_options['widget_secret']),
                    'setup_completed' => $new_options['setup_completed']
                ));
                
                // Trigger inmediato de auto-configuración
                do_action('yeison_btx_widget_activated');
                
                // Marcar setup como completado
                yeison_btx_widget_update_option('setup_completed', true);
                yeison_btx_widget_update_option('last_activation', current_time('mysql'));
            }
        }






         





        /**
         * Desactivación del plugin
         */
        public function deactivation() {
            // Flush rewrite rules
            flush_rewrite_rules();
            
            // Log de desactivación
            if (function_exists('yeison_btx_widget_log')) {
                yeison_btx_widget_log('Plugin desactivado', 'info', array(
                    'version' => YEISON_BTX_WIDGET_VERSION
                ));
            }
        }
        
        /**
         * Crear tabla de logs
         */
        /**
         * Crear tabla de logs - VERSIÓN MEJORADA
         */
        private function create_logs_table() {
            global $wpdb;
            
            $table_name = $wpdb->prefix . 'yeison_btx_widget_logs';
            
            // Verificar si ya existe
            $table_exists = $wpdb->get_var($wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $table_name
            )) === $table_name;
            
            if ($table_exists) {
                error_log('[Yeison BTX Widget] Tabla de logs ya existe: ' . $table_name);
                return;
            }
            
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                type varchar(20) NOT NULL DEFAULT 'info',
                action varchar(100) DEFAULT NULL,
                message text NOT NULL,
                data longtext DEFAULT NULL,
                user_id bigint(20) DEFAULT NULL,
                ip_address varchar(45) DEFAULT NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY (id),
                KEY type (type),
                KEY created_at (created_at),
                KEY user_id (user_id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $result = dbDelta($sql);
            
            // Verificar creación exitosa
            $table_created = $wpdb->get_var($wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $table_name
            )) === $table_name;
            
            if ($table_created) {
                error_log('[Yeison BTX Widget] Tabla de logs creada exitosamente: ' . $table_name);
                
                // Insertar log inicial de activación
                $wpdb->insert(
                    $table_name,
                    array(
                        'type' => 'success',
                        'action' => 'plugin_activation',
                        'message' => 'Plugin Yeison BTX Widget activado correctamente',
                        'data' => wp_json_encode(array(
                            'version' => YEISON_BTX_WIDGET_VERSION,
                            'wp_version' => get_bloginfo('version'),
                            'php_version' => PHP_VERSION
                        )),
                        'user_id' => get_current_user_id(),
                        'ip_address' => $this->get_client_ip(),
                        'created_at' => current_time('mysql')
                    ),
                    array('%s', '%s', '%s', '%s', '%d', '%s', '%s')
                );
            } else {
                error_log('[Yeison BTX Widget] ERROR: No se pudo crear tabla de logs: ' . $table_name);
            }
        }



        private function get_client_ip() {
            $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
            
            foreach ($ip_keys as $key) {
                if (!empty($_SERVER[$key])) {
                    $ip = $_SERVER[$key];
                    if (strpos($ip, ',') !== false) {
                        $ip = trim(explode(',', $ip)[0]);
                    }
                    return filter_var($ip, FILTER_VALIDATE_IP) ?: '127.0.0.1';
                }
            }
            return '127.0.0.1';
        }
        












        /**
         * Obtener información del plugin
         */
        public function get_plugin_info() {
            return array(
                'name' => 'Yeison BTX Widget',
                'version' => $this->version,
                'file' => YEISON_BTX_WIDGET_PLUGIN_FILE,
                'dir' => YEISON_BTX_WIDGET_PLUGIN_DIR,
                'url' => YEISON_BTX_WIDGET_PLUGIN_URL,
                'basename' => YEISON_BTX_WIDGET_PLUGIN_BASENAME
            );
        }
    }
}

// ============================================================================
// INICIALIZACIÓN DEL PLUGIN
// ============================================================================

/**
 * Función principal para obtener la instancia del plugin
 */
function yeison_btx_widget() {
    return YeisonBTX_Widget_Main::get_instance();
}

/**
 * Inicializar el plugin después de verificar requisitos
 */
function yeison_btx_widget_init() {
    // Verificar requisitos antes de inicializar
    if (empty(yeison_btx_widget_check_requirements())) {
        yeison_btx_widget();
    }
}

// Inicializar el plugin
add_action('plugins_loaded', 'yeison_btx_widget_init', 10);

// ============================================================================
// HOOKS DE DESINSTALACIÓN
// ============================================================================

/**
 * Limpiar datos al desinstalar el plugin
 */
function yeison_btx_widget_uninstall() {
    global $wpdb;
    
    // Eliminar opciones del plugin
    delete_option('yeison_btx_widget_settings');
    
    // Eliminar tabla de logs (opcional - comentar si quieres conservar logs)
    // $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}yeison_btx_widget_logs");
    
    // Limpiar transients
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_yeison_widget_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_yeison_widget_%'");
}

register_uninstall_hook(__FILE__, 'yeison_btx_widget_uninstall');

// ============================================================================
// FUNCIONES DE UTILIDAD GLOBALES
// ============================================================================

/**
 * Función de debug rápida
 */
if (!function_exists('yeison_btx_widget_debug')) {
    function yeison_btx_widget_debug($data, $label = 'Debug') {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log($label . ': ' . print_r($data, true));
        }
    }
}

/**
 * Verificar si el plugin está activo y configurado
 */
if (!function_exists('yeison_btx_widget_is_ready')) {
    function yeison_btx_widget_is_ready() {
        if (!function_exists('yeison_btx_widget_is_configured')) {
            return false;
        }
        
        return yeison_btx_widget_is_configured();
    }
}

/**
 * Obtener URL del widget para Bitrix24
 */
if (!function_exists('yeison_btx_widget_get_widget_url')) {
    function yeison_btx_widget_get_widget_url($email = '', $contact_id = '') {
        $params = array('yeison_widget' => 'contact');
        
        if (!empty($email)) {
            $params['email'] = $email;
        }
        
        if (!empty($contact_id)) {
            $params['contact_id'] = $contact_id;
        }
        
        return add_query_arg($params, home_url('/'));
    }
}

// ============================================================================
// FIN DEL ARCHIVO
// ============================================================================