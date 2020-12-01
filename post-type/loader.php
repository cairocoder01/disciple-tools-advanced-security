<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly.


/**
 * Test that DT_Module_Base has loaded
 */
if ( ! class_exists( 'DT_Module_Base' ) ) {
    dt_write_log('Disciple Tools System not loaded. Cannot load custom post type.');
    return;
}

/**
 * Add any modules required or added for the post type
 */
add_filter( 'dt_post_type_modules', function( $modules ){
    $modules["starter_base"] = [
        "name" => "Starter",
        "enabled" => true,
        "locked" => true,
        "prerequisites" => [ "contacts_base" ],
        "post_type" => "starter_post_type",
        "description" => "Default starter functionality"
    ];
    return $modules;
}, 20, 1 );

require_once 'base-setup.php';
DT_Starter_Base::instance();
