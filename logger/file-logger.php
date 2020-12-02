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

            if ( !$this->should_write_log( $args ) ) {
                return;
            }

            $dirpath = $this->get_log_path();
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

    private function get_log_path() {
        $path = DT_Advanced_Security::get_instance()->dir_path . "logs/";

        if ( is_multisite() ) {
            $site = get_blog_details();
            if ( $site ) {
                $path .= $site->domain . "/";
            }
        }

        return $path;
    }

    /**
     * Test if a given activity_log should be written to the log file
     * @param $args
     * @return bool
     */
    private function should_write_log( $args ) {
        $include = false;

        // All core actions
        $include = $include || $args['object_type'] == 'core';

        // All plugin actions
        $include = $include || $args['object_type'] == 'plugin';

        // All theme actions
        $include = $include || $args['object_type'] == 'theme';

        // All site link actions
        $include = $include || $args['object_type'] == 'site_link_system';

        // All user actions (logged_in, invalid_login, etc.)
        $include = $include || $args['object_type'] == 'User' || $args['object_type'] == 'user';

        $include = apply_filters( 'dt_advanced_security_activity_included', $include, $args );
        dt_write_log( json_encode( $include ) );
        dt_write_log( json_encode( $args ) );
        return $include;
    }
}
DT_Advanced_Security_File_Logger::instance();
