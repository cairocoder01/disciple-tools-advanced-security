<?php
/**
 * Plugin Name: Disciple Tools - Advanced Security
 * Plugin URI: https://github.com/cairocoder01/disciple-tools-advanced-security
 * Description: Disciple Tools - Advanced Security The Disciple Tools Advanced Security Plugin is intended to facilitate advanced security audits
 * of activity on your Disciple.Tools site.
 * Version:  0.1.0
 * Author URI: https://github.com/cairocoder01
 * GitHub Plugin URI: https://github.com/cairocoder01/disciple-tools-advanced-security
 * Requires at least: 4.7.0
 * (Requires 4.7+ because of the integration of the REST API at 4.7 and the security requirements of this milestone version.)
 * Tested up to: 5.4
 *
 * @package Disciple_Tools
 * @link    https://github.com/DiscipleTools
 * @license GPL-2.0 or later
 *          https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
$dt_advanced_security_required_dt_theme_version = '0.28.0';

/**
 * Gets the instance of the `DT_Advanced_Security` class.
 *
 * @since  0.1
 * @access public
 * @return object|bool
 */
function dt_advanced_security() {
    global $dt_advanced_security_required_dt_theme_version;
    $wp_theme = wp_get_theme();
    $version = $wp_theme->version;

    /*
     * Check if the Disciple.Tools theme is loaded and is the latest required version
     */
    $is_theme_dt = strpos( $wp_theme->get_template(), "disciple-tools-theme" ) !== false || $wp_theme->name === "Disciple Tools";
    if ( $is_theme_dt && version_compare( $version, $dt_advanced_security_required_dt_theme_version, "<" ) ) {
        add_action( 'admin_notices', 'dt_advanced_security_hook_admin_notice' );
        add_action( 'wp_ajax_dismissed_notice_handler', 'dt_hook_ajax_notice_handler' );
        return false;
    }
    if ( !$is_theme_dt ){
        return false;
    }
    /**
     * Load useful function from the theme
     */
    if ( !defined( 'DT_FUNCTIONS_READY' ) ){
        require_once get_template_directory() . '/dt-core/global-functions.php';
    }

    return DT_Advanced_Security::get_instance();
}
add_action( 'after_setup_theme', 'dt_advanced_security' );

/**
 * Singleton class for setting up the plugin.
 *
 * @since  0.1
 * @access public
 */
class DT_Advanced_Security {

    public $token;
    public $version;
    public $dir_path = '';
    public $dir_uri = '';
    public static function get_instance() {

        static $instance = null;

        if ( is_null( $instance ) ) {
            $instance = new dt_advanced_security();
            $instance->setup();
            $instance->plugin_updater_setup();
            $instance->includes();
            $instance->admin_setup();
        }

        return $instance;
    }
    private function __construct() {}

    /**
     * Sets up variables and language translation
     */
    private function setup() {

        // Admin and settings variables
        $this->token             = 'dt_advanced_security';
        $this->version             = '0.1';

        // Main plugin directory path and URI.
        $this->dir_path     = trailingslashit( plugin_dir_path( __FILE__ ) );
        $this->dir_uri      = trailingslashit( plugin_dir_url( __FILE__ ) );

        // Internationalize the text strings used.
        add_action( 'init', array( $this, 'i18n' ), 2 );
    }

    /**
     * Sets up plugin updater features
     */
    private function plugin_updater_setup(){

        if ( is_admin() ){

            // Check for plugin updates
            if ( ! class_exists( 'Puc_v4_Factory' ) ) {
                require( get_template_directory() . '/dt-core/libraries/plugin-update-checker/plugin-update-checker.php' );
            }

            $hosted_json = "https://raw.githubusercontent.com/cairocoder01/disciple-tools-advanced-security/master/admin/version-control.json"; // change this url
            Puc_v4_Factory::buildUpdateChecker(
                $hosted_json,
                __FILE__,
                'disciple-tools-advanced-security' // change this token
            );

        }
    }

    /**
     * Sets up all file dependencies
     */
    private function includes() {

        require_once( 'logger/file-logger.php' );
        require_once('logger/activity-hooks.php');

    }

    /**
     * Sets up admin area
     */
    private function admin_setup() {
        if ( is_admin() ) {

            // adds starter admin page and section for plugin
            require_once( 'admin/admin-menu-and-tabs.php' );

            // adds links to the plugin description area in the plugin admin list.
            add_filter( 'plugin_row_meta', [ $this, 'plugin_description_links' ], 10, 4 );
        }
    }



    /**
     * Filters the array of row meta for each/specific plugin in the Plugins list table.
     * Appends additional links below each/specific plugin on the plugins page.
     *
     * @access  public
     * @param   array       $links_array            An array of the plugin's metadata
     * @param   string      $plugin_file_name       Path to the plugin file
     * @param   array       $plugin_data            An array of plugin data
     * @param   string      $status                 Status of the plugin
     * @return  array       $links_array
     */
    public function plugin_description_links( $links_array, $plugin_file_name, $plugin_data, $status ) {
        /*if ( strpos( $plugin_file_name, basename( __FILE__ ) ) ) {
            // You can still use `array_unshift()` to add links at the beginning.

            // $links_array[] = '<a href="https://disciple.tools">Disciple.Tools Community</a>'; // @todo replace with your links.
            // add other links here
        }*/

        return $links_array;
    }

    /**
     * Method that runs only when the plugin is activated.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public static function activation() {
        // add elements here that need to fire on activation
    }

    /**
     * Method that runs only when the plugin is deactivated.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public static function deactivation() {
        // add functions here that need to happen on deactivation

        delete_option( 'dismissed-dt-advanced-security' );
    }

    /**
     * Loads the translation files.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function i18n() {
        load_plugin_textdomain( 'dt_advanced_security', false, trailingslashit( dirname( plugin_basename( __FILE__ ) ) ). 'languages' );
    }

    /**
     * Magic method to output a string if trying to use the object as a string.
     *
     * @since  0.1
     * @access public
     * @return string
     */
    public function __toString() {
        return 'dt_advanced_security';
    }

    /**
     * Magic method to keep the object from being cloned.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, 'Whoah, partner!', '0.1' );
    }

    /**
     * Magic method to keep the object from being unserialized.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, 'Whoah, partner!', '0.1' );
    }

    /**
     * Magic method to prevent a fatal error when calling a method that doesn't exist.
     *
     * @param string $method
     * @param array $args
     * @return null
     * @since  0.1
     * @access public
     */
    public function __call( $method = '', $args = array() ) {
        _doing_it_wrong( "dt_advanced_security::" . esc_html( $method ), 'Method does not exist.', '0.1' );
        unset( $method, $args );
        return null;
    }
}
// end main plugin class

// Register activation hook.
register_activation_hook( __FILE__, [ 'DT_Advanced_Security', 'activation' ] );
register_deactivation_hook( __FILE__, [ 'DT_Advanced_Security', 'deactivation' ] );

if ( ! function_exists( 'dt_advanced_security_hook_admin_notice' ) ) {
    function dt_advanced_security_hook_admin_notice() {
        global $dt_advanced_security_required_dt_theme_version;
        $wp_theme = wp_get_theme();
        $current_version = $wp_theme->version;
        $message = __( "'Disciple Tools - Advanced Security' plugin requires 'Disciple Tools' theme to work. Please activate 'Disciple Tools' theme or make sure it is latest version.", "dt_advanced_security" );
        if ( $wp_theme->get_template() === "disciple-tools-theme" ){
            $message .= sprintf( esc_html__( 'Current Disciple Tools version: %1$s, required version: %2$s', 'dt_advanced_security' ), esc_html( $current_version ), esc_html( $dt_advanced_security_required_dt_theme_version ) );
        }
        // Check if it's been dismissed...
        if ( ! get_option( 'dismissed-dt-advanced-security', false ) ) { ?>
            <div class="notice notice-error notice-dt-advanced-security is-dismissible" data-notice="dt-advanced-security">
                <p><?php echo esc_html( $message );?></p>
            </div>
            <script>
                jQuery(function($) {
                    $( document ).on( 'click', '.notice-dt-advanced-security .notice-dismiss', function () {
                        $.ajax( ajaxurl, {
                            type: 'POST',
                            data: {
                                action: 'dismissed_notice_handler',
                                type: 'dt-advanced-security',
                                security: '<?php echo esc_html( wp_create_nonce( 'wp_rest_dismiss' ) ) ?>'
                            }
                        })
                    });
                });
            </script>
        <?php }
    }
}

/**
 * AJAX handler to store the state of dismissible notices.
 */
if ( ! function_exists( "dt_hook_ajax_notice_handler" )){
    function dt_hook_ajax_notice_handler(){
        check_ajax_referer( 'wp_rest_dismiss', 'security' );
        if ( isset( $_POST["type"] ) ){
            $type = sanitize_text_field( wp_unslash( $_POST["type"] ) );
            update_option( 'dismissed-' . $type, true );
        }
    }
}
