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
        include( 'activity-hooks-theme.php' );

        new DT_Advanced_Security_Hooks_User();
        new DT_Advanced_Security_Hooks_Plugin();
        new DT_Advanced_Security_Hooks_Theme();

        add_action( 'wp_upgrade', [ $this, 'update_core' ], 10, 2 );
    }

    public function update_core( $new_version, $old_version ) {

        dt_activity_insert(
            [
                'action' => 'update',
                'object_type' => 'core',
                'object_name' => $new_version,
                'object_note' => "$old_version -> $new_version",
            ]
        );
    }
}
DT_Advanced_Security_Hooks::instance();
