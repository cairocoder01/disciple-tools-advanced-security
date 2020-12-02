<?php

class DT_Advanced_Security_Hooks_Plugin
{
    private $object_type = 'plugin';

    public function __construct() {

        add_action( 'deleted_plugin', [ $this, 'deleted_plugin' ], 10, 2 );
        add_action( 'activated_plugin', [ $this, 'activated_plugin' ], 10, 2 );
        add_action( 'deactivated_plugin', [ $this, 'deactivated_plugin' ], 10, 2 );

    }

    /**
     * https://developer.wordpress.org/reference/hooks/deleted_plugin/
     * @param $plugin_file
     * @param $deleted
     */
    public function deleted_plugin( $plugin_file, $deleted ) {

        dt_activity_insert(
            [
                'action' => $deleted ? 'delete' : 'delete_fail',
                'object_type' => $this->object_type,
                'object_name' => $plugin_file,
            ]
        );
    }

    /**
     * https://developer.wordpress.org/reference/hooks/activated_plugin/
     * @param $plugin
     * @param $network_wide
     */
    public function activated_plugin( $plugin, $network_wide ) {

        dt_activity_insert(
            [
                'action' => $network_wide ? 'activate_network' : 'activate',
                'object_type' => $this->object_type,
                'object_name' => $plugin,
            ]
        );
    }

    /**
     * https://developer.wordpress.org/reference/hooks/deactivated_plugin/
     * @param $plugin
     * @param $network_deactivating
     */
    public function deactivated_plugin( $plugin, $network_deactivating ) {

        dt_activity_insert(
            [
                'action' => $network_deactivating ? 'deactivate_network' : 'deactivate',
                'object_type' => $this->object_type,
                'object_name' => $plugin,
            ]
        );
    }
}
