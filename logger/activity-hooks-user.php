<?php

class DT_Advanced_Security_Hooks_User
{
    private $current_ip_address = null;

    public function __construct() {

        add_action( 'wp_login_failed', [ $this, 'login_failed' ], 10, 2 );
        add_action( 'lostpassword_post', [ $this, 'lostpassword_post' ], 10, 2 );
        add_action( 'after_password_reset', [ $this, 'after_password_reset' ], 10, 2 );

        add_action( 'user_register', [ $this, 'user_register' ] );
        add_action( 'deleted_user', [ $this, 'deleted_user' ], 10, 3 );

        add_action( 'add_user_role', [ $this, 'add_user_role' ], 10, 2 );
        add_action( 'remove_user_role', [ $this, 'remove_user_role' ], 10, 2 );
        add_action( 'add_user_to_blog', [ $this, 'add_user_to_blog' ], 10, 3 );
        add_action( 'remove_user_from_blog', [ $this, 'remove_user_from_blog' ], 10, 3 );

        add_action( 'granted_super_admin', [ $this, 'granted_super_admin' ] );
        add_action( 'revoked_super_admin', [ $this, 'revoked_super_admin' ] );

        // Set user IP address
        if ( !empty( $_SERVER['HTTP_CLIENT_IP'] )) {
            $this->current_ip_address = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
        } else if ( !empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $this->current_ip_address = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
        } else if ( !empty( $_SERVER['REMOTE_ADDR'] ) ) {
            $this->current_ip_address = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
        }
    }

    /**
     * https://developer.wordpress.org/reference/hooks/wp_login_failed/
     * @param $username
     * @param $error
     */
    public function login_failed( $username, $error ) {

        dt_activity_insert(
            [
                'action' => 'login_failed',
                'object_type' => 'User',
                'object_name' => $username,
                'object_note' => $error->get_error_code(),
                'hist_ip' => $this->current_ip_address,
            ]
        );
    }

    /**
     * https://developer.wordpress.org/reference/hooks/lostpassword_post/
     * Triggered when requesting password reset, before errors are validated
     * and before reset email is sent.
     * @param $errors
     * @param $user
     */
    public function lostpassword_post( $errors, $user ) {

        // only log this if no errors and it will continue to send reset email
        if ( !is_wp_error( $errors ) ) {
            dt_activity_insert(
                [
                    'action' => 'password_reset_request',
                    'object_type' => 'User',
                    'object_id' => $user->ID,
                    'object_name' => $user->user_nicename,
                    'hist_ip' => $this->current_ip_address,
                ]
            );
        }
    }
    /**
     * https://developer.wordpress.org/reference/hooks/after_password_reset/
     * @param $user
     * @param $new_pass
     */
    public function after_password_reset( $user, $new_pass ) {

        dt_activity_insert(
            [
                'action' => 'password_reset',
                'object_type' => 'User',
                'object_id' => $user->ID,
                'object_name' => $user->user_nicename,
                'hist_ip' => $this->current_ip_address,
            ]
        );
    }

    /**
     * https://developer.wordpress.org/reference/hooks/user_register/
     * @param $user_id
     */
    public function user_register( $user_id ) {
        dt_activity_insert(
            [
                'action' => 'created',
                'object_type' => 'User',
                'object_id' => $user_id,
            ]
        );
    }

    /**
     * https://developer.wordpress.org/reference/hooks/add_user_role/
     * @param $user_id
     * @param $role
     */
    public function add_user_role( $user_id, $role ) {
        dt_activity_insert(
            [
                'action' => 'add_role',
                'object_type' => 'User',
                'object_id' => $user_id,
                'meta_key' => 'role',
                'meta_value' => $role,
            ]
        );
    }
    /**
     * https://developer.wordpress.org/reference/hooks/remove_user_role/
     * @param $user_id
     * @param $role
     */
    public function remove_user_role( $user_id, $role ) {
        dt_activity_insert(
            [
                'action' => 'remove_role',
                'object_type' => 'User',
                'object_id' => $user_id,
                'meta_key' => 'role',
                'meta_value' => $role,
            ]
        );
    }

    /**
     * https://developer.wordpress.org/reference/hooks/add_user_to_blog/
     * @param $user_id
     * @param $role
     * @param $blog_id
     */
    public function add_user_to_blog( $user_id, $role, $blog_id ) {
        dt_activity_insert(
            [
                'action' => 'add_to_site',
                'object_type' => 'User',
                'object_id' => $user_id,
                'meta_key' => 'site',
                'meta_value' => $blog_id,
                'object_note' => "site:$blog_id, role:$role"
            ]
        );
    }
    /**
     * https://developer.wordpress.org/reference/hooks/remove_user_from_blog/
     * @param $user_id
     * @param $blog_id
     * @param $reassign
     */
    public function remove_user_from_blog( $user_id, $blog_id, $reassign ) {
        // $blog_id seems to be 0 if you remove from network/site-users.php?id={siteId}
        // but it's correct when removing from within the given site
        dt_activity_insert(
            [
                'action' => 'remove_from_site',
                'object_type' => 'User',
                'object_id' => $user_id,
                'meta_key' => 'site',
                'meta_value' => $blog_id,
            ]
        );
    }

    /**
     * https://developer.wordpress.org/reference/hooks/granted_super_admin/
     * @param $user_id
     */
    public function granted_super_admin( $user_id ) {
        dt_activity_insert(
            [
                'action' => 'granted_super_admin',
                'object_type' => 'User',
                'object_id' => $user_id,
            ]
        );
    }
    /**
     * https://developer.wordpress.org/reference/hooks/revoked_super_admin/
     * @param $user_id
     */
    public function revoked_super_admin( $user_id ) {
        dt_activity_insert(
            [
                'action' => 'revoked_super_admin',
                'object_type' => 'User',
                'object_id' => $user_id,
            ]
        );
    }

    /**
     * https://developer.wordpress.org/reference/hooks/deleted_user/
     * @param $id
     * @param $reassign
     * @param $user
     */
    public function deleted_user( $id, $reassign, $user ) {
        dt_activity_insert(
            [
                'action' => 'deleted',
                'object_type' => 'User',
                'object_id' => $id,
                'object_name' => $user->user_nicename,
            ]
        );
    }
}
