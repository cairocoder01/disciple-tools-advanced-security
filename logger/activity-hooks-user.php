<?php

class DT_Advanced_Security_Hooks_User
{
    private $current_ip_address = null;

    public function __construct() {
        /*
        invite_user
        https://developer.wordpress.org/reference/hooks/deleted_user/
        https://developer.wordpress.org/reference/hooks/add_user_role/
        https://developer.wordpress.org/reference/hooks/user_register/
        https://developer.wordpress.org/reference/hooks/wpmu_new_user/
        https://developer.wordpress.org/reference/hooks/update_usermeta/
        https://developer.wordpress.org/reference/hooks/add_user_to_blog/ (multi-site)
        https://developer.wordpress.org/reference/hooks/remove_user_role/
        https://developer.wordpress.org/reference/hooks/updated_usermeta/
        https://developer.wordpress.org/reference/hooks/register_new_user/
        https://developer.wordpress.org/reference/hooks/wpmu_activate_user/
        https://developer.wordpress.org/reference/hooks/added_existing_user/
        https://developer.wordpress.org/reference/hooks/remove_user_from_blog/
        https://developer.wordpress.org/reference/hooks/edit_user_created_user/
        https://developer.wordpress.org/reference/hooks/network_site_new_created_user/
        https://developer.wordpress.org/reference/hooks/network_user_new_created_user/
        https://developer.wordpress.org/reference/hooks/network_site_users_created_user/
        https://developer.wordpress.org/reference/hooks/wp_logout/
        https://developer.wordpress.org/reference/hooks/password_reset/
        https://developer.wordpress.org/reference/hooks/profile_update/
        https://developer.wordpress.org/reference/hooks/granted_super_admin/
        https://developer.wordpress.org/reference/hooks/revoked_super_admin/
        https://developer.wordpress.org/reference/hooks/after_password_reset/
        */

        add_action( 'wp_login_failed', [ $this, 'login_failed' ], 10, 2 );

        // Set user IP address
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $this->current_ip_address = $_SERVER['HTTP_CLIENT_IP'];
        } else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $this->current_ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $this->current_ip_address = $_SERVER['REMOTE_ADDR'];
        }
    }

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
}
