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

        add_action( '_core_updated_successfully', [ $this, 'update_core' ] );
    }

    public function update_core( $version ) {

        dt_activity_insert(
            [
                'action' => 'update',
                'object_type' => 'core',
                'object_name' => $version,
            ]
        );
    }
}
DT_Advanced_Security_Hooks::instance();
