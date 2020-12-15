<?php

class DT_Advanced_Security_Hooks_Theme
{
    private $object_type = 'theme';

    public function __construct() {

        add_action( 'upgrader_process_complete', [ $this, 'upgrade_theme' ], 10, 2 );

    }

    public function upgrade_theme( $upgrader, $hook_extra ) {
        if ( !is_array( $hook_extra ) || !array_key_exists( 'action', $hook_extra ) || !array_key_exists( 'type', $hook_extra ) ) {
            return;
        }

        if ( $hook_extra['action'] != 'update' || $hook_extra['type'] != 'theme' || !array_key_exists( 'themes', $hook_extra ) ) {
            return;
        }

        // log upgrade of each plugin
        if (is_array( $hook_extra['themes'] ) && !empty( $hook_extra['themes'] )) {
            foreach ($hook_extra['themes'] as $theme) {
                // $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $theme );
                // $version = $plugin_data && is_array( $plugin_data ) ? $plugin_data['Version'] : '';

                $wp_theme = wp_get_theme( $theme );
                $version = $wp_theme->version;
                dt_activity_insert(
                    [
                        'action' => 'update',
                        'object_type' => $this->object_type,
                        'object_name' => $theme,
                        'object_note' => $version,
                    ]
                );
            }
        }
    }
}
