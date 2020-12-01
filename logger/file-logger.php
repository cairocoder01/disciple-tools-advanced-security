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

            $dirpath = DT_Advanced_Security::get_instance()->dir_path . "logs/";
            $filename = $dirpath . "activity.log";

            // Create logs directory if it doesn't exist
            if ( !file_exists( $dirpath ) ) {
                mkdir( $dirpath );
            }

            // Write activity to log file
            file_put_contents( $filename, json_encode( $args ) . PHP_EOL, FILE_APPEND );

            // Rename file if it exceeds 100 MB
            $filesize = filesize( $filename );
            if ( $filesize > 1024 * 1024 * 100 ) { // 100 MB
                $files = scandir( $dirpath );
                $next = 1;
                // get the last file number and increment
                if ( $files && count( $files ) > 1 ) {
                    $last_file = $files[count( $files ) - 2]; // last file is activity.log, so get second to last
                    preg_match( '/activity\.(\d*)\.log/m', $last_file, $match );
                    if ( $match ) {
                        $next = intval( $match[1] ) + 1;
                    }
                }

                rename( $filename, $dirpath . "activity.$next.log" );
            }
        } catch ( Exception $ex ) {
            dt_write_log( json_encode( $ex ) );
        }
    }
}
DT_Advanced_Security_File_Logger::instance();
