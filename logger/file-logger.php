<?php
require_once "logger-base.php";

class DT_Advanced_Security_File_Logger extends DT_Advanced_Security_Base_Logger
{
    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    protected function enabled() {
        return boolval( get_option( "dt_advanced_security_enable_file_logger" ) );
    }

    public function log_activity( $args ) {
        try {

            if ( !$this->should_write_log( $args ) ) {
                return;
            }

            $dirpath = $this->get_log_path();
            $filename = $dirpath . "activity.log";

            // Create logs directory if it doesn't exist
            if ( !file_exists( $dirpath ) ) {
                mkdir( $dirpath, 0777, true );
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

    public function get_log_path() {
        $path = DT_Advanced_Security::get_instance()->dir_path . "logs/";

        if ( is_multisite() ) {
            $site = get_blog_details();
            dt_write_log( json_encode( $site ) );
            if ( $site ) {
                $path .= $site->domain;
                // if subdirectory multisite, we'll need the path
                if ( !empty( trim( $site->path, "\/" ) ) ) {
                    $path .= '-' . trim( $site->path, "\/" );
                }
                $path .= "/";
            }
        }

        return $path;
    }
}
DT_Advanced_Security_File_Logger::instance();
