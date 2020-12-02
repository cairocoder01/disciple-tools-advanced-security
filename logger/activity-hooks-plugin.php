<?php

class DT_Advanced_Security_Hooks_Plugin
{
    private $object_type = 'plugin';

    public function __construct() {

        add_action( 'deleted_plugin', [ $this, 'deleted_plugin' ], 10, 2 );
        add_action( 'activated_plugin', [ $this, 'activated_plugin' ], 10, 2 );
        add_action( 'deactivated_plugin', [ $this, 'deactivated_plugin' ], 10, 2 );
        add_action( 'upgrader_process_complete', [ $this, 'upgrade_plugin' ], 10, 2 );

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

    public function upgrade_plugin( $upgrader, $hook_extra ) {
        if ( !is_array( $hook_extra ) || !array_key_exists( 'action', $hook_extra ) || !array_key_exists( 'type', $hook_extra ) ) {
            return;
        }

        if ( $hook_extra['action'] != 'update' || $hook_extra['type'] != 'plugin' || !array_key_exists( 'plugins', $hook_extra ) ) {
            return;
        }

        // log upgrade of each plugin
        if (is_array( $hook_extra['plugins'] ) && !empty( $hook_extra['plugins'] )) {
            foreach ($hook_extra['plugins'] as $plugin) {
                $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
                $version = $plugin_data && is_array( $plugin_data ) ? $plugin_data['Version'] : '';
                dt_activity_insert(
                    [
                        'action' => 'update',
                        'object_type' => $this->object_type,
                        'object_name' => $plugin,
                        'object_note' => $version,
                    ]
                );
            }
        }
    }
}
