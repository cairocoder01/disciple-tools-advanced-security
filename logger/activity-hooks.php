<?php

class DT_Advanced_Security_Hooks
{
    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct() {

        include( 'activity-hooks-user.php' );
        include( 'activity-hooks-plugin.php' );

        new DT_Advanced_Security_Hooks_User();
        new DT_Advanced_Security_Hooks_Plugin();

    }
}
DT_Advanced_Security_Hooks::instance();
