Análisis Completo del Proyecto Yeison BTX Widget - WooCommerce Integration
Resumen General del Proyecto
Este es un plugin de WordPress que permite la integración automática entre Bitrix24 y WooCommerce. Su función principal es crear widgets que se muestran dentro de Bitrix24 y permiten a los usuarios acceder automáticamente a sus cuentas de WooCommerce sin necesidad de introducir credenciales.

1. ARCHIVO: yeison_btx_widget.php (Archivo Principal del Plugin)
Propósito
Es el archivo principal del plugin que define la estructura, constantes, verificaciones de requisitos y la clase principal de control.
Constantes Definidas

YEISON_BTX_WIDGET_VERSION: Versión del plugin (2.0.0)
YEISON_BTX_WIDGET_PLUGIN_FILE: Ruta al archivo principal
YEISON_BTX_WIDGET_PLUGIN_DIR: Directorio del plugin
YEISON_BTX_WIDGET_PLUGIN_URL: URL del plugin
YEISON_BTX_WIDGET_PLUGIN_BASENAME: Nombre base del plugin

Clase Principal: YeisonBTX_Widget_Main
Patrón Singleton - Asegura una sola instancia del plugin
Funciones Principales:

get_instance(): Obtiene la instancia única del plugin
define_constants(): Define constantes adicionales
init_hooks(): Inicializa todos los hooks de WordPress
load_dependencies(): Carga archivos necesarios (functions.php y clases)
init_components(): Inicializa las clases API, Widget Manager y Autologin Handler
load_textdomain(): Carga archivos de idioma
init(): Inicialización principal, verifica WooCommerce
is_woocommerce_active(): Verifica si WooCommerce está activo
add_admin_menu(): Crea menú de administración con submenús
admin_scripts(): Carga scripts JavaScript para admin
admin_page(): Muestra página principal de configuración
logs_page(): Muestra página de logs
diagnostic_page(): Muestra página de diagnóstico
handle_oauth_callback(): Maneja el callback de autorización OAuth2
activation(): Ejecuta tareas de activación del plugin
deactivation(): Ejecuta tareas de desactivación
create_logs_table(): Crea tabla de logs en la base de datos
get_client_ip(): Obtiene IP real del cliente
get_plugin_info(): Retorna información del plugin

Funciones de Verificación:

yeison_btx_widget_check_requirements(): Verifica requisitos mínimos (WordPress 5.0+, PHP 7.4+, extensiones necesarias)
yeison_btx_widget_requirements_notice(): Muestra avisos de requisitos no cumplidos

Funciones de Utilidad Globales:

yeison_btx_widget_debug(): Función de debugging
yeison_btx_widget_is_ready(): Verifica si el plugin está listo
yeison_btx_widget_get_widget_url(): Genera URL del widget


2. ARCHIVO: functions.php (Funciones Principales del Sistema)
Propósito
Contiene todas las funciones críticas del sistema, configuración de headers para iframe, logging, handlers de widget y endpoints AJAX.
Secciones Principales:
SECCIÓN 1: Configuración de Headers para Iframe Bitrix24
Funciones para Headers:

yeison_force_iframe_headers_critical(): Fuerza headers de iframe con máxima prioridad
yeison_override_wp_headers_critical(): Sobreescribe headers de WordPress
yeison_start_header_buffer(): Inicia buffer de output para modificar headers
yeison_modify_headers_in_buffer(): Modifica headers en el buffer
yeison_btx_widget_force_iframe_headers(): Configura headers para widgets
yeison_btx_widget_start_output_buffer(): Inicia buffer de output
yeison_btx_widget_modify_output_headers(): Modifica headers en output
yeison_btx_widget_filter_wp_headers(): Filtra headers de WordPress
yeison_btx_widget_prevent_frame_options_override(): Previene override de frame options

SECCIÓN 2: Funciones Básicas del Sistema

yeison_btx_widget_log(): Sistema de logging principal, registra eventos en BD
yeison_btx_widget_get_ip(): Obtiene IP real considerando proxies y CDNs
yeison_btx_widget_get_option(): Obtiene configuración del plugin
yeison_btx_widget_update_option(): Actualiza configuración
yeison_btx_widget_is_configured(): Verifica configuración básica
yeison_btx_widget_has_valid_tokens(): Verifica si hay tokens válidos
yeison_btx_widget_ensure_widget_secret(): Genera widget secret automáticamente

SECCIÓN 3: Handlers de Widget Directo

yeison_btx_widget_handle_widget_direct_v2(): Handler principal para URL /?yeison_widget=contact
yeison_btx_widget_extract_email_from_request(): Extrae email de múltiples fuentes
yeison_btx_widget_extract_contact_id_from_request(): Extrae contact_id de la petición
yeison_btx_widget_generate_error_widget_simple(): Genera widget de error simple

SECCIÓN 4: Simulación de Cookies para Autologin

yeison_btx_widget_simulate_auth_cookie_in_request(): Simula cookies AUTH en $_COOKIE
yeison_btx_widget_simulate_logged_in_cookie_in_request(): Simula cookies LOGGED_IN
yeison_btx_widget_debug_login_success(): Debug cuando wp_login es exitoso

SECCIÓN 5: Debugging y Scripts de Iframe

yeison_btx_widget_add_iframe_debug_script(): Añade JavaScript para debugging en iframe
yeison_btx_widget_check_conflicting_plugins(): Verifica plugins que pueden interferir

SECCIÓN 6: Soporte para Cookies en Iframe

yeison_btx_widget_setup_iframe_cookies(): Configura cookies para iframe
yeison_btx_widget_setcookie_iframe(): Función personalizada para establecer cookies con SameSite=None

SECCIÓN 7: Funciones Helper para Instancias

yeison_btx_widget_api(): Obtiene instancia de API Bitrix24
yeison_btx_widget_widgets(): Obtiene instancia del Widget Manager
yeison_btx_widget_autologin(): Obtiene instancia del Autologin Handler

SECCIÓN 8: Endpoints AJAX para Admin
Funciones AJAX para Administración:

yeison_btx_widget_ajax_test_connection(): Test de conexión con Bitrix24
yeison_btx_widget_ajax_view_logs(): Ver logs del sistema
yeison_btx_widget_ajax_clear_logs(): Limpiar logs
yeison_btx_widget_ajax_test_widget(): Test del widget
yeison_btx_widget_ajax_register_widget(): Registrar widget en Bitrix24
yeison_btx_widget_ajax_flush_permalinks(): Actualizar permalinks
yeison_btx_widget_ajax_clear_tokens(): Limpiar tokens
yeison_btx_widget_ajax_register_widget_fixed(): Versión corregida del registro
yeison_btx_widget_ajax_check_widgets_fixed(): Verificar widgets existentes
yeison_btx_widget_ajax_full_diagnostic(): Diagnóstico completo del sistema
yeison_btx_widget_ajax_clear_cache(): Limpiar cache
yeison_btx_widget_ajax_regenerate_secret(): Regenerar widget secret
yeison_btx_widget_ajax_cleanup_widgets_json_safe(): Eliminar widgets de forma segura
yeison_btx_widget_ajax_get_widgets_info(): Obtener información de widgets

Funciones de Diagnóstico:

yeison_btx_widget_generate_full_diagnostic(): Genera datos completos de diagnóstico
yeison_btx_widget_generate_diagnostic_tables(): Genera HTML de tablas de diagnóstico
yeison_btx_widget_check_database_tables(): Verifica tablas de base de datos
yeison_btx_widget_ensure_database_tables(): Asegura que existan las tablas

Sistema de Auto-Configuración:

yeison_auto_setup_complete_system(): Auto-configura todo al activar plugin
yeison_auto_clean_old_tokens(): Limpia tokens antiguos automáticamente
yeison_auto_ensure_mu_plugin(): Asegura que exista el MU-Plugin
yeison_auto_verify_htaccess(): Verifica configuración htaccess
yeison_auto_clear_all_caches(): Limpia todos los caches
yeison_auto_create_test_files(): Crea archivos de test automáticamente
yeison_auto_verify_system_health(): Verifica estado del sistema
yeison_detect_plugin_activation(): Detecta activación del plugin


3. ARCHIVO: admin-page.php (Página de Administración)
Propósito
Interfaz de administración completa con panel de control, herramientas de gestión y diagnóstico.
Secciones de la Interfaz:
Panel de Estado:

Muestra estado de configuración, dominio, credenciales, autorización
Información del widget (URL principal, Widget Secret)

Formulario de Configuración:

Campos para Dominio Bitrix24, Client ID, Client Secret
Botón de autorización OAuth2 cuando es necesario

Herramientas de Gestión:

Pruebas: Test conexión, probar widget, ver logs
Widgets: Registrar, verificar, eliminar widgets
Diagnóstico: Test completo, limpiar cache, regenerar secret

JavaScript del Admin:
Funciones JavaScript:

checkAuthStatus(): Verifica estado de autorización al cargar
testConnection(): Ejecuta test de conexión
registerWidget(): Registra widget en Bitrix24
checkWidgets(): Verifica widgets existentes
deleteAllWidgets(): Elimina todos los widgets
runFullDiagnostic(): Ejecuta diagnóstico completo
clearCache(): Limpia cache del sistema
regenerateSecret(): Regenera widget secret
copyToClipboard(): Copia URL al portapapeles
makeAjaxRequest(): Función helper para peticiones AJAX


4. ARCHIVO: class-bitrix-api.php (API de Bitrix24)
Propósito
Maneja toda la comunicación con la API de Bitrix24, incluyendo OAuth2, renovación de tokens y llamadas a la API.
Clase: YeisonBTX_Widget_Bitrix_API
Funciones de Configuración:

load_config(): Carga configuración desde opciones de WordPress
is_configured(): Verifica si está configurado básicamente
is_authorized(): Verifica si está autorizado completamente

Funciones OAuth2:

get_auth_url(): Genera URL de autorización OAuth2
exchange_code_for_tokens(): Intercambia código por tokens de acceso
refresh_access_token(): Renueva access token usando refresh token

Funciones de API:

api_call(): Realiza llamadas a la API de Bitrix24
make_request(): Función HTTP base (WordPress HTTP API o cURL)
test_connection(): Prueba conectividad completa con manejo de errores

Funciones de Gestión de Tokens:

clear_tokens(): Limpia todos los tokens
is_token_invalid_for_domain(): Detecta tokens inválidos por cambio de cuenta
auto_clean_invalid_tokens(): Auto-limpia tokens inválidos


5. ARCHIVO: class-autologin-handler.php (Manejador de Autologin)
Propósito
Gestiona el sistema de autenticación automática, especialmente diseñado para funcionar en iframes de Bitrix24.
Clase: YeisonBTX_Widget_Autologin_Handler
Funciones de Configuración de Iframe:

setup_iframe_cookie_handling(): Configura manejo de cookies para iframe
is_bitrix24_iframe(): Detecta si está en iframe de Bitrix24
force_iframe_cookie_settings(): Fuerza configuración de cookies para iframe
set_iframe_cookie(): Establece cookie con configuración específica para iframe
force_iframe_cookie_clearing(): Limpia cookies para iframe

Funciones de Autologin:

handle_autologin_request(): Handler principal para peticiones de autologin
force_logout_current_session(): Cierra sesión existente antes del autologin
perform_autologin(): Ejecuta el autologin completo
set_auth_cookies_for_iframe(): Establece cookies específicamente para iframe
redirect_to_my_account(): Redirige a Mi Cuenta después del login
redirect_to_my_account_simple(): Redirección simple sin query args

Funciones de Tokens:

validate_autologin_token(): Valida tokens de autologin con verificación HMAC
create_temp_login_token(): Crea tokens temporales para autologin
handle_autologin_error(): Maneja errores de autologin
show_autologin_error_page(): Muestra página de error amigable


6. ARCHIVO: class-widget-manager.php (Gestor de Widgets)
Propósito
Gestiona los endpoints REST, generación de HTML del widget y registro en Bitrix24.
Clase: YeisonBTX_Widget_Manager
Funciones de Endpoints:

register_widget_endpoints(): Registra endpoints REST API
register_fallback_endpoints(): Registra endpoints alternativos AJAX
handle_ajax_auth(): Manejador AJAX alternativo
verify_widget_access(): Verifica acceso a widgets
serve_contact_widget(): Sirve el widget principal
handle_widget_auth(): Maneja autenticación del widget

Funciones de Generación de HTML:

generate_bitrix24_widget_html(): Genera HTML completo del widget con JavaScript
generate_error_widget_html(): Genera HTML de widget de error

Funciones de Extracción de Datos:

extract_contact_id(): Extrae contact_id de parámetros
extract_email_from_params(): Extrae email de múltiples fuentes
get_contact_email_from_bitrix(): Obtiene email desde API de Bitrix24

Funciones de Autenticación:

check_and_create_login(): Verifica usuario y crea token de login
generate_autologin_url(): Genera URL de autologin

Funciones de Gestión en Bitrix24:

register_widget_in_bitrix24(): Registra widget en Bitrix24
unregister_widget_from_bitrix24(): Desregistra widget
cleanup_all_widgets(): Limpia todos los widgets del sitio
auto_register_widgets(): Registro automático después de OAuth

Funciones de Utilidad:

set_bitrix24_headers(): Establece headers específicos para Bitrix24
get_client_ip(): Obtiene IP del cliente
get_widget_status(): Obtiene estado del sistema de widgets


PROCESOS PRINCIPALES Y FLUJOS DE TRABAJO
1. PROCESO DE INSTALACIÓN Y CONFIGURACIÓN
Funciones involucradas:

yeison_btx_widget_check_requirements() - Verificar requisitos
activation() - Ejecutar activación
create_logs_table() - Crear tabla de logs
yeison_auto_setup_complete_system() - Auto-configuración
yeison_auto_ensure_mu_plugin() - Crear MU-Plugin
yeison_btx_widget_ensure_widget_secret() - Generar widget secret

2. PROCESO DE AUTORIZACIÓN OAUTH2
Funciones involucradas:

get_auth_url() - Generar URL de autorización
handle_oauth_callback() - Manejar callback
exchange_code_for_tokens() - Intercambiar código por tokens
auto_register_widgets() - Registro automático de widgets

3. PROCESO DE SERVIR WIDGET EN IFRAME
Funciones involucradas:

yeison_force_iframe_headers_critical() - Forzar headers de iframe
yeison_btx_widget_handle_widget_direct_v2() - Handler directo
extract_email_from_request() - Extraer email
extract_contact_id_from_request() - Extraer contact_id
generate_bitrix24_widget_html() - Generar HTML del widget
set_bitrix24_headers() - Establecer headers específicos

4. PROCESO DE AUTENTICACIÓN AUTOMÁTICA
Funciones involucradas:

handle_widget_auth() - Manejar petición de auth
check_and_create_login() - Verificar usuario y crear token
create_temp_login_token() - Crear token temporal
handle_autologin_request() - Procesar autologin
validate_autologin_token() - Validar token
perform_autologin() - Ejecutar login
setup_iframe_cookie_handling() - Configurar cookies para iframe
set_auth_cookies_for_iframe() - Establecer cookies específicas
redirect_to_my_account() - Redirigir a cuenta

5. PROCESO DE GESTIÓN DE WIDGETS EN BITRIX24
Funciones involucradas:

register_widget_in_bitrix24() - Registrar widget
api_call() - Llamadas a API de Bitrix24
cleanup_all_widgets() - Limpiar widgets
unregister_widget_from_bitrix24() - Desregistrar

6. PROCESO DE DIAGNÓSTICO Y DEBUGGING
Funciones involucradas:

yeison_btx_widget_generate_full_diagnostic() - Generar diagnóstico
test_connection() - Test de conexión
yeison_btx_widget_log() - Sistema de logging
yeison_btx_widget_check_database_tables() - Verificar BD
yeison_auto_verify_system_health() - Verificar salud del sistema

7. PROCESO DE ADMINISTRACIÓN VÍA AJAX
Funciones involucradas:

makeAjaxRequest() - Peticiones AJAX del frontend
yeison_btx_widget_ajax_test_connection() - Test vía AJAX
yeison_btx_widget_ajax_register_widget_fixed() - Registro vía AJAX
yeison_btx_widget_ajax_full_diagnostic() - Diagnóstico vía AJAX
yeison_btx_widget_ajax_cleanup_widgets_json_safe() - Limpieza vía AJAX

8. PROCESO DE MANEJO DE ERRORES Y RECUPERACIÓN
Funciones involucradas:

refresh_access_token() - Renovar tokens expirados
auto_clean_invalid_tokens() - Limpiar tokens inválidos
handle_autologin_error() - Manejar errores de autologin
show_autologin_error_page() - Mostrar página de error
generate_error_widget_simple() - Widget de error

El proyecto implementa un sistema complejo pero bien estructurado que permite una integración fluida entre Bitrix24 y WooCommerce, con especial atención al funcionamiento en iframes y manejo robusto de errores.
