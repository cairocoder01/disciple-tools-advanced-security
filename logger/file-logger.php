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
        try {
            $filename = DT_Advanced_Security::get_instance()->dir_path . "/activity.log";
            file_put_contents( $filename, json_encode( $args ) . PHP_EOL, FILE_APPEND );
        } catch ( Exception $ex ) {
            dt_write_log( json_encode( $ex ) );
        }

        //todo: handle file size by either truncating or creating new files (delete after a given period?)
    }
}
DT_Advanced_Security_File_Logger::instance();
