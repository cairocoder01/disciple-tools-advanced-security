<?php

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}

/**
 * Class DT_Advanced_Security_Settings
 */
class DT_Advanced_Security_Settings {

    public $token = 'dt_advanced_security';

    private static $_instance = null;

    /**
     * DT_Advanced_Security_Settings Instance
     *
     * Ensures only one instance of DT_Advanced_Security_Settings is loaded or can be loaded.
     *
     * @return DT_Advanced_Security_Settings instance
     * @since 0.1.0
     * @static
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()


    /**
     * Constructor function.
     * @access  public
     * @since   0.1.0
     */
    public function __construct() {
        require_once( plugin_dir_path( __FILE__ ) . '../logger/file-logger.php' );
    } // End __construct()


    public function styles() {
        ?>
        <style>
            .table-settings {
                margin: 1rem 0;
            }

            /** switch **/
            [type="checkbox"] {
                position: absolute;
                left: -9999px;
            }

            .switch {
                position: relative;
            }
            .switch label {
                display: flex;
                align-items: center;
                justify-content: flex-start;
            }
            .switch label span:last-child {
                position: relative;
                width: 50px;
                height: 26px;
                margin-left: 0.5rem;
                border-radius: 15px;
                box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.4);
                background: #eee;
                transition: all 0.3s;
            }
            .switch label span:last-child::before,
            .switch label span:last-child::after {
                content: "";
                position: absolute;
            }
            .switch label span:last-child::before {
                left: 1px;
                top: 1px;
                width: 24px;
                height: 24px;
                background: #fff;
                border-radius: 50%;
                z-index: 1;
                transition: transform 0.3s;
            }
            .switch [type="checkbox"]:checked + label span:last-child {
                background: #46b450;
            }
            .switch [type="checkbox"]:checked + label span:last-child::before {
                transform: translateX(24px);
            }
        </style>
        <?php
    }

    /**
     * Builds page contents
     * @since 0.1
     */
    public function content() {

        if ( !current_user_can( 'manage_dt' ) ) { // manage dt is a permission that is specific to Disciple Tools and allows admins, strategists and dispatchers into the wp-admin
            wp_die( esc_attr__( 'You do not have sufficient permissions to access this page.' ) );
        }

        $this->save_settings();

        $this->styles();
        ?>
        <div class="wrap">
            <h2><?php esc_attr_e( 'Advanced Security - Settings', 'dt_advanced_security' ) ?></h2>

            <div class="wrap">
                <div id="poststuff">
                    <div id="post-body" class="metabox-holder columns-2">
                        <div id="post-body-content">
                            <!-- Main Column -->

                            <?php $this->main_column() ?>

                            <!-- End Main Column -->
                        </div><!-- end post-body-content -->
                        <div id="postbox-container-1" class="postbox-container">
                            <!-- Right Column -->

                            <?php $this->right_column() ?>

                            <!-- End Right Column -->
                        </div><!-- postbox-container 1 -->
                        <div id="postbox-container-2" class="postbox-container">
                        </div><!-- postbox-container 2 -->
                    </div><!-- post-body meta box container -->
                </div><!--poststuff end -->
            </div><!-- wrap end -->

        </div><!-- End wrap -->

        <?php
    }

    public function main_column() {
        $log_path = DT_Advanced_Security_File_Logger::instance()->get_log_path();
        $log_path = substr( $log_path, strpos( $log_path, "/wp-content" ) );

        $file_logger = json_decode( get_option( "dt_advanced_security_file_logger" ), true );
        $elastic = json_decode( get_option( "dt_advanced_security_elastic_logger" ), true );
        ?>
        <form method="POST" action="">
            <?php wp_nonce_field( 'security_headers', 'security_headers_nonce' ); ?>

            <button type="submit" class="button">Save</button>

            <!-- Log to file -->
            <table class="widefat striped table-settings">
                <thead>
                <tr>
                    <th>Log to File</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>
                        <p>Save filtered activity log entries to file in directory: <br><code><?php echo esc_html( $log_path ) ?></code></p>
                        <hr>
                        <span class="switch">
                            <input type="checkbox" id="file-enable" name="file_logger[enabled]" value="1" <?php echo isset( $file_logger['enabled'] ) && $file_logger['enabled'] ? 'checked' : '' ?> />
                            <label for="file-enable">
                                <span>Enabled:</span>
                                <span></span>
                            </label>
                        </span>
                    </td>
                </tr>
                </tbody>
            </table>

            <!-- Log to file -->
            <table class="widefat striped table-settings">
                <thead>
                <tr>
                    <th>Log to Elasticsearch</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>
                        <p>
                            Elasticsearch provides a <a href="https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-index_.html" target="_blank">convenient REST API</a>
                            to receive external data. To send data directly to your Elasticsearch system, enter the URL for that API endpoint below (URL should end with <code>/_dev/</code>)
                            as well as the authorization credentials that have the proper access.
                        </p>
                        <table class="form-table">
                            <tr>
                                <th><label for="elastic-url">Endpoint URL</label></th>
                                <td>
                                    <input type="url"
                                           id="elastic-url"
                                           name="elastic[url]"
                                           class="regular-text"
                                           value="<?php echo esc_attr( isset( $elastic['url'] ) ? $elastic['url'] : "" ) ?>"
                                           />
                                </td>
                            </tr>
                            <tr>
                                <th><label for="elastic-username">Username</label></th>
                                <td>
                                    <input type="text"
                                           id="elastic-username"
                                           name="elastic[username]"
                                           class="regular-text"
                                           value="<?php echo esc_attr( isset( $elastic['username'] ) ? $elastic['username'] : "" ) ?>"
                                           />
                                </td>
                            </tr>
                            <tr>
                                <th><label for="elastic-password">Password</label></th>
                                <td>
                                    <input type="password"
                                           id="elastic-password"
                                           name="elastic[password]"
                                           class="regular-text"
                                           value="<?php echo esc_attr( isset( $elastic['password'] ) ? $elastic['password'] : "" ) ?>"
                                           />
                                </td>
                            </tr>
                        </table>
                        <hr>
                        <span class="switch">
                            <input type="checkbox" id="elastic-enable" name="elastic[enabled]" value="1" <?php echo isset( $elastic['enabled'] ) && $elastic['enabled'] ? 'checked' : '' ?> />
                            <label for="elastic-enable">
                                <span>Enabled:</span>
                                <span></span>
                            </label>
                        </span>
                    </td>
                </tr>
                </tbody>
            </table>

            <button type="submit" class="button">Save</button>
        </form>
        <!-- End Box -->
        <?php
    }

    public function right_column() {
        ?>
        <!-- Box -->
        <table class="widefat striped hidden">
            <thead>
            <tr>
                <th>Information</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    Content
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }

    public function save_settings() {
        if ( !empty( $_POST ) ){
            if ( isset( $_POST['security_headers_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['security_headers_nonce'] ), 'security_headers' ) ) {

                // Save file logger settings as json
                if ( isset( $_POST['file_logger'] ) ) {
                    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                    $file_logger = $this->sanitize_text_or_array_field( wp_unslash( $_POST['file_logger'] ) );
                    $file_logger['enabled'] = isset( $file_logger['enabled'] ) ? boolval( $file_logger['enabled'] ) : false;
                    update_option( "dt_advanced_security_file_logger", json_encode( $file_logger ) );
                } else {
                    update_option( "dt_advanced_security_file_logger", json_encode( [ 'enabled' => false ] ) );
                }

                // Save Elastic settings as json
                if ( isset( $_POST['elastic'] ) ) {
                    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                    $elastic = $this->sanitize_text_or_array_field( wp_unslash( $_POST['elastic'] ) );
                    $elastic['enabled'] = isset( $elastic['enabled'] ) ? boolval( $elastic['enabled'] ) : false;
                    update_option( "dt_advanced_security_elastic_logger", json_encode( $elastic ) );
                }
            }

            echo '<div class="notice notice-success"><p>Settings saved</p></div>';
        }
    }

    private function sanitize_text_or_array_field( $array_or_string) {
        if (is_string( $array_or_string )) {
            $array_or_string = sanitize_text_field( $array_or_string );
        } elseif (is_array( $array_or_string )) {
            foreach ($array_or_string as $key => &$value) {
                if (is_array( $value )) {
                    $value = $this->sanitize_text_or_array_field( $value );
                } else {
                    $value = sanitize_text_field( $value );
                }
            }
        }

        return $array_or_string;
    }
}
