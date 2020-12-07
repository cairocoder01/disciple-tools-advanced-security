<?php

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}


/**
 * Class DT_Advanced_Security_Logs
 */
class DT_Advanced_Security_Logs {

    public $token = 'dt_advanced_security_logs';
    public $page_size = 10;
    private $logs;
    private $_pagination_args;


    private static $_instance = null;

    /**
     * DT_Advanced_Security_Logs Instance
     *
     * Ensures only one instance of DT_Advanced_Security_Logs is loaded or can be loaded.
     *
     * @return DT_Advanced_Security_Logs instance
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


    } // End __construct()

    public function styles() {
        ?>
        <style type="text/css">
            code { display: block; }
            .actions {
                display: flex;
                align-items: flex-end;
            }
            .action-group {
                display: flex;
                flex-direction: column;
            }
            .action-group > label {
                font-weight: 500;
                font-size: 0.7rem;
                line-height: 1rem;
                margin: 0 3px;
            }
        </style>
        <?php
    }
    public function scripts() {
        ?>
        <script type="text/javascript">
            jQuery(function($) {
                $('[type=reset]').on( 'click', function (evt) {
                    if (evt) {
                        evt.preventDefault();
                    }
                    $('.dtsec-filter').val('');
                });

                $('.view-details').click(function(e) {
                    e.preventDefault();
                    var histid = $(this).data('id');
                    var dialogId = '#dialog-' + histid;
                    var dialog = $(dialogId);
                    if (dialog && dialog.length) {
                        dialog.dialog('open');
                    } else {
                        var content = $(this).next();

                        if (content.length) {
                            dialog = $('<div id="dialog-' + histid + '"></div>').dialog({
                                title: 'Log Details',
                                dialogClass: 'wp-dialog',
                                autoOpen: false,
                                draggable: false,
                                width: 'auto',
                                modal: true,
                                resizable: false,
                                closeOnEscape: true,
                                position: {
                                    my: "center",
                                    at: "center",
                                    of: window
                                },
                                open: function () {
                                    // close dialog by clicking the overlay behind it
                                    $(this).html(content[0].innerHTML);
                                    $('.ui-widget-overlay').bind('click', function () {
                                        $('#dialog-' + histid).dialog('close');
                                    })
                                },
                                create: function () {
                                    // style fix for WordPress admin
                                    $('.ui-dialog-titlebar-close').addClass('ui-button');
                                },
                            });

                            dialog.dialog('open');
                        }
                    }
                });
            });
        </script>
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

        wp_enqueue_script( 'jquery-ui-dialog' );
        wp_enqueue_style( 'wp-jquery-ui-dialog' );

        $this->get_logs();
        $this->styles();

        ?>
        <div class="wrap">
            <h2><?php esc_attr_e( 'Activity Logs', 'dt_advanced_security' ) ?></h2>

            <div class="wrap">
                <div id="poststuff">
                    <div id="post-body" class="metabox-holder">
                        <div id="post-body-content">
                            <!-- Main Column -->

                            <?php $this->main_column() ?>

                            <!-- End Main Column -->
                        </div><!-- end post-body-content -->
                    </div><!-- post-body meta box container -->
                </div><!--poststuff end -->
            </div><!-- wrap end -->

        </div><!-- End wrap -->

        <?php
        $this->scripts();
    }

    public function filters_actions() {
        ?>
        <div class="tablenav top">

            <div class="alignleft actions">
                <div class="action-group">
                    <label for="dtsec-date-start-filter">Start Date</label>
                    <input type="date"
                           name="start_date"
                           id="dtsec-date-start-filter"
                           class="dtsec-filter filter-date"
                           value="<?php echo isset( $_REQUEST['start_date'] ) ? esc_html( sanitize_text_field( wp_unslash( $_REQUEST['start_date'] ) ) ) : '' ?>"
                    />
                </div>
                <div class="action-group">
                    <label for="dtsec-date-end-filter">End Date</label>
                    <input type="date"
                           name="end_date"
                           id="dtsec-date-end-filter"
                           class="dtsec-filter filter-date"
                           value="<?php echo isset( $_REQUEST['end_date'] ) ? esc_html( sanitize_text_field( wp_unslash( $_REQUEST['end_date'] ) ) ) : '' ?>"
                    />
                </div>

                <input type="hidden" name="page" value="<?php echo esc_attr( $this->token ) ?>" />
                <input type="submit" id="dtsec-logs-query-submit" class="button button-primary" />
                <input type="reset" id="dtsec-logs-query-reset" class="button" />
            </div>
            <?php $this->pagination( "top" ) ?>
            <br class="clear">
        </div>
        <?php
    }
    public function main_column() {
        ?>
        <form method="GET" action="">
            <?php $this->filters_actions() ?>

            <table class="wp-list-table widefat striped itsec-log-entries itsec-logs-color">
                <thead>
                <tr>
                    <th scope="col" class="column-histid hidden">ID</th>
                    <th scope="col" class="column-hist_time column-primary">Timestamp</th>
                    <th scope="col" class="column-user_id">User ID</th>
                    <th scope="col" class="column-action">Action</th>
                    <th scope="col" class="column-object_type">Object Type</th>
                    <th scope="col" class="column-object_name">Object Name</th>
                    <th scope="col" class="column-object_id">Object ID</th>
                    <th scope="col" class="column-object_subtype">Subtype</th>
                    <th scope="col" class="column-details">Details</th>
                </tr>
                </thead>

                <tbody id="the-list" data-wp-lists="list:itsec-log-entry">
                <?php foreach ( $this->logs as $log ): ?>
                    <tr class="itsec-log-type-notice">
                        <td class="id column-id hidden" data-colname="ID"><?php echo esc_html( $log['histid'] ) ?></td>
                        <td class="column-histid"><?php echo nl2br( esc_html( str_replace( 'T', PHP_EOL, gmdate( DATE_ATOM, $log['hist_time'] ) ) ) ) ?></td>
                        <td class="column-user_id">
                        <?php
                            if ( !empty( $log['user_nicename'] ) ) {
                                esc_html_e( $log['user_nicename'] . ' (ID:' . $log['user_id'] . ')' );
                            } else {
                                esc_html_e( $log['user_id'] );
                            }
                        ?>
                        </td>
                        <td class="column-action"><?php echo esc_html( $log['action'] ) ?></td>
                        <td class="column-object_type"><?php echo esc_html( $log['object_type'] ) ?></td>
                        <td class="column-object_name"><?php echo esc_html( $log['object_name'] ) ?></td>
                        <td class="column-object_id"><?php echo esc_html( $log['object_id'] ) ?></td>
                        <td class="column-object_subtype"><?php echo esc_html( $log['object_subtype'] ) ?></td>
                        <td class="column-details">
                            <a class="view-details" href="javascript:;" data-id="<?php esc_attr_e( $log['histid'] ) ?>"><span class="dashicons dashicons-info"></span></a>
                            <div class="details-content" style="display:none;">
                                <table class="form-table" role="presentation">
                                <?php foreach( $log as $key => $value ): ?>
                                    <tr class="form-field">
                                        <th scope="row"><?php esc_html_e( $key ) ?></th>
                                        <td><?php esc_html_e( $key == 'hist_time' ? gmdate( DATE_ATOM, $log['hist_time'] ) : $value ) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </table>
                                <pre style="display: none;"><code><?php echo json_encode($log, JSON_PRETTY_PRINT) ?></code></pre>
                            </div>
                        </td>
                    </tr>

                <?php endforeach; ?>
                </tbody>

            </table>
        </form>
        <?php
    }

    public function right_column() {
        ?>
        <!-- Box -->
        <table class="widefat striped">
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

    /**
     * Stealing much of this direct from /wp-admin/includes/class-wp-list-table.php
     * since it is private and they recommend to copy/paste
     * @param $which top|bottom
     */
    public function pagination( $which ) {
        // Stealing much of the below from /wp-admin/includes/class-wp-list-table.php
        $total_items     = $this->_pagination_args['total_items'];
        $total_pages     = $this->_pagination_args['total_pages'];

        $total_item_display = sprintf(
            _n( '%s item', '%s items', $total_items ),
            number_format_i18n( $total_items )
        );

        if ( $total_pages ) {
            $page_class = $total_pages < 2 ? ' one-page' : '';
        } else {
            $page_class = ' no-pages';
        }

        echo "<div class=\"tablenav-pages " . esc_attr( $page_class ) . "\">";
        echo "<span class=\"displaying-num\">" . esc_html( $total_item_display ) . "</span>";
        echo "<span class=\"pagination-links\">";

        $current = $this->_pagination_args['current_page'];
        $removable_query_args = wp_removable_query_args();

        $host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
        $current_url = set_url_scheme( "http://$host$request_uri" );

        $current_url = remove_query_arg( $removable_query_args, $current_url );


        $disable_first = false;
        $disable_last  = false;
        $disable_prev  = false;
        $disable_next  = false;

        if ( 1 == $current ) {
            $disable_first = true;
            $disable_prev  = true;
        }
        if ( 2 == $current ) {
            $disable_first = true;
        }
        if ( $total_pages == $current ) {
            $disable_last = true;
            $disable_next = true;
        }
        if ( $total_pages - 1 == $current ) {
            $disable_last = true;
        }

        if ( $disable_first ) {
            echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
        } else {
            echo sprintf(
                "<a class='first-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
                esc_url( remove_query_arg( 'paged', $current_url ) ),
                esc_html__( 'First page' ),
                '&laquo;'
            );
        }

        if ( $disable_prev ) {
            echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
        } else {
            echo sprintf(
                "<a class='prev-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
                esc_url( add_query_arg( 'paged', max( 1, $current - 1 ), $current_url ) ),
                esc_html__( 'Previous page' ),
                '&lsaquo;'
            );
        }

        if ( 'bottom' === $which ) {

            echo '<span class="screen-reader-text">' . esc_html__( 'Current Page' ) . '</span><span id="table-paging" class="paging-input"><span class="tablenav-paging-text">';
            echo sprintf(
                esc_html_x( '%1$s of %2$s', 'paging' ),
                esc_html( $current ),
                sprintf( "<span class='total-pages'>%s</span>", esc_html( number_format_i18n( $total_pages ) ) )
            );
            echo '</span></span>';
        } else {
            echo '<span class="paging-input">';
            echo sprintf(
                esc_html_x( '%1$s of %2$s', 'paging' ),
                sprintf(
                    "%s<input class='current-page' id='current-page-selector' type='text' name='paged' value='%s' size='%d' aria-describedby='table-paging' /><span class='tablenav-paging-text'>",
                    '<label for="current-page-selector" class="screen-reader-text">' . esc_html__( 'Current Page' ) . '</label>',
                    esc_html( $current ),
                    esc_html( strlen( $total_pages ) )
                ),
                sprintf( "<span class='total-pages'>%s</span>", esc_html( number_format_i18n( $total_pages ) ) )
            );
            echo '</span></span>';
        }

        if ( $disable_next ) {
            echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
        } else {
            echo sprintf(
                "<a class='next-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
                esc_url( add_query_arg( 'paged', min( $total_pages, $current + 1 ), $current_url ) ),
                esc_html( __( 'Next page' ) ),
                '&rsaquo;'
            );
        }

        if ( $disable_last ) {
            echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
        } else {
            echo sprintf(
                "<a class='last-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
                esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ),
                esc_html( __( 'Last page' ) ),
                '&raquo;'
            );
        }

        echo "</span>";
        echo "</div>";
    }

    private function get_logs() {
        global $wpdb;
        $limit = $this->page_size;
        $offset = 0;
        $where = 'WHERE 1=%d ';
        $page = 1;
        $params = [ 1 ];
        if ( !empty( $_REQUEST ) ) {
            // if (isset($_POST['security_headers_nonce']) && wp_verify_nonce(sanitize_key($_POST['security_headers_nonce']), 'security_headers')) {
            if ( isset( $_REQUEST['start_date'] ) && !empty( $_REQUEST['start_date'] ) ) {
                $start_date = sanitize_text_field( wp_unslash( $_REQUEST['start_date'] ) );
                $where .= ' AND hist_time >= %d ';
                array_push( $params, strtotime( $start_date ) );
            }

            if ( isset( $_REQUEST['end_date'] ) && !empty( $_REQUEST['end_date'] ) ) {
                $end_date = sanitize_text_field( wp_unslash( $_REQUEST['end_date'] ) );
                $where .= ' AND hist_time <= %d ';
                array_push( $params, strtotime( $end_date ) );
            }

            if ( isset( $_REQUEST['paged'] ) && !empty( $_REQUEST['paged'] ) ) {
                $page = intval( sanitize_text_field( wp_unslash( $_REQUEST['paged'] ) ) );
                if ( $page > 1 ) {
                    $offset = ( $page - 1 ) * $this->page_size;
                }
            }
        }

        // Get total result count before pagination
        $total_count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $wpdb->dt_activity_log $where",
            $params
        ) ); // WPCS: unprepared SQL OK.

        // Add pagination params and get this page of data
        array_push( $params, $offset );
        array_push( $params, $limit );
        $sql = "
            SELECT l.*, u.user_nicename, u.user_login FROM $wpdb->dt_activity_log l
            LEFT JOIN $wpdb->users u on l.user_id = u.ID
            $where
            ORDER BY `hist_time` DESC, `histid` DESC
            LIMIT %d, %d
            ";
        $this->logs = $wpdb->get_results( $wpdb->prepare(
            $sql,
            $params
        ), ARRAY_A ); // WPCS: unprepared SQL OK.
        $this->_pagination_args = [
            'onepage' => $total_count == count( $this->logs ),
            'total_items' => $total_count,
            'total_pages' => ceil( $total_count / $this->page_size ),
            'current_page' => $page,
        ];
    }
}

