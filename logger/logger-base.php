<?php

abstract class DT_Advanced_Security_Base_Logger
{
    abstract public static function instance();

    public function __construct() {

        if ( $this->enabled() ) {
            add_action( 'dt_insert_activity', [ $this, 'log_activity' ] );
        }
        add_filter( 'dt_advanced_security_activity_included', [ $this, 'hook_activity_included' ], 10, 2 );
    }

    abstract protected function enabled();
    abstract public function log_activity( $args );

    /**
     * Test if a given activity_log should be written to the log file
     * @param $args
     * @return bool
     */
    protected function should_write_log( $args ) {
        $include = apply_filters( 'dt_advanced_security_activity_included', false, $args );
        return $include;
    }

    public function hook_activity_included( $include, $args ) {
        // Export of any types
        $include = $include || $args['action'] == 'export';

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

        return $include;
    }
}
