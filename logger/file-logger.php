<?php

class DT_Advanced_Security_File_Logger
{
    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct() {
        add_action( 'dt_insert_activity', [ $this, 'insert_activity' ] );
    }

    public function insert_activity( $args ) {
        dt_write_log("we're hooked! " . json_encode( $args ) );
    }
}
DT_Advanced_Security_File_Logger::instance();
