<?php
/**
 * DT_Advanced_Security_Menu class for the admin page
 *
 * @class       DT_Advanced_Security_Menu
 * @version     0.1.0
 * @since       0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}

/**
 * Initialize menu class
 */
DT_Advanced_Security_Menu::instance();

/**
 * Class DT_Advanced_Security_Menu
 */
class DT_Advanced_Security_Menu {

    public $token = 'dt_advanced_security';

    private static $_instance = null;

    /**
     * DT_Advanced_Security_Menu Instance
     *
     * Ensures only one instance of DT_Advanced_Security_Menu is loaded or can be loaded.
     *
     * @return DT_Advanced_Security_Settings instance
     * @since 0.1.0
     * @static
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()


    /**
     * Constructor function.
     * @access  public
     * @since   0.1.0
     */
    public function __construct() {

        add_action( "admin_menu", array( $this, "register_menu" ) );

    } // End __construct()


    /**
     * Loads the subnav page
     * @since 0.1
     */
    public function register_menu() {
        require_once( 'admin-settings.php' );
        require_once( 'admin-logs.php' );

        $settings = DT_Advanced_Security_Settings::instance();
        add_menu_page(
            __( 'Settings', 'disciple_tools' ),
            __( 'Security (DT)', 'disciple_tools' ),
            'manage_dt',
            'dt_advanced_security',
        [ $settings, 'content' ], 'dashicons-lock', 60 );

        add_submenu_page( 'dt_advanced_security',
            __( 'Settings', 'dt_advanced_security' ),
            __( 'Settings', 'dt_advanced_security' ),
            'manage_dt',
            $settings->token,
        [ $settings, 'content' ] );

        $logs = DT_Advanced_Security_Logs::instance();
        add_submenu_page( 'dt_advanced_security',
            __( 'Activity Logs', 'dt_advanced_security' ),
            __( 'Activity Logs', 'dt_advanced_security' ),
            'manage_dt',
            $logs->token,
        [ $logs, 'content' ] );
        ;
    }

    /**
     * Menu stub. Replaced when Disciple Tools Theme fully loads.
     */
    public function extensions_menu() {}

}
