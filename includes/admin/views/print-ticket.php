<?php defined('WPINC') or exit; ?>

<div class="wpas-print-ticket-container">

    <table>
        <tr>
            <td colspan="5">
                <h3><?php echo $ticket->post_title; ?></h3>
            </td>
        </tr>
        <tr>
            <th>
                <?php _e( 'ID', 'awesome-support' ); ?>
            </th>
            <th>
                <?php _e( 'Status', 'awesome-support' ); ?>
            </th>
            <th>
                <?php _e( 'Created by', 'awesome-support' ); ?>
            </th>
            <th>
                <?php _e( 'Agent', 'awesome-support' ); ?>
            </th>
            <th>
                <?php _e( 'Date', 'awesome-support' ); ?>
            </th>
        </tr>
        <tr>
            <td>
                #<?php echo $ticket->ID; ?>
            </td>
            <td>
                <?php wpas_cf_display_status( 'status', $ticket->ID ); ?>
            </td>
            <td>
                <?php $user = get_user_by( 'id', $ticket->post_author )->display_name; ?>
                <?php echo $user; ?>
            </td>
            <td>
                <?php

                    $agent_id = wpas_get_cf_value( 'assignee', $ticket->ID );
                    echo get_user_by( 'id', $agent_id )->display_name;

                ?>
            </td>
            <td>
                <?php echo date( get_option( 'date_format' ), strtotime( $ticket->post_date ) ) . ' ' . date( get_option( 'time_format' ), strtotime( $ticket->post_date ) ); ?>
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <td>
                <strong><?php echo $user; ?></strong>, 
                <?php echo date( get_option( 'date_format' ), strtotime( $ticket->post_date ) ) . ' ' . date( get_option( 'time_format' ), strtotime( $ticket->post_date ) ); ?>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo $ticket->post_content; ?>
            </td>
        </tr>
    </table>


    <?php if ( $replies ): ?>

        <?php foreach ($replies as $reply): ?>

            <?php 
            
            // Set the author data (if author is known)
            if ( $reply->post_author != 0 ) {
                $user_data = get_userdata( $reply->post_author );
                $user_id   = $user_data->data->ID;
                $user_name = $user_data->data->display_name;
            }

            // In case the post author is unknown, we set this as an anonymous post
            else {
                $user_name = __( 'Anonymous', 'awesome-support' );
                $user_id   = 0;
            }

            ?>

            <?php if ( $reply->post_type == 'ticket_history' ): ?>

                <table class="wpas-print-ticket-history" style="display:none;">
                    <tr>
                        <td>
                            <strong><?php echo $user_name; ?></strong>, 
                            <?php echo date( get_option( 'date_format' ), strtotime( $reply->post_date ) ) . ' ' . date( get_option( 'time_format' ), strtotime( $reply->post_date ) ); ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?php echo $reply->post_content; ?>
                        </td>
                    </tr>
                </table>

            <?php else: ?>

                <table class="<?php echo ( $reply->post_type == 'ticket_note' ) ? 'wpas-print-ticket-notes' : 'wpas-print-ticket-reply'; ?>">
                    <tr>
                        <td>
                            <strong><?php echo $user_name; ?></strong>, 
                            <?php echo date( get_option( 'date_format' ), strtotime( $reply->post_date ) ) . ' ' . date( get_option( 'time_format' ), strtotime( $reply->post_date ) ); ?>
                            <?php if ( $reply->post_type == 'ticket_note' ) printf( ' - <strong>%s</strong>', __( 'Private note', 'awesome-support' ) ); ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?php

                                $content = apply_filters( 'the_content', $reply->post_content );

                                do_action( 'wpas_backend_reply_content_before', $reply->ID );

                                echo wp_kses( $content, wp_kses_allowed_html( 'post' ) );

                                do_action( 'wpas_backend_reply_content_after', $reply->ID );

                            ?>
                        </td>
                    </tr>
                </table>

                
            <?php endif; ?>

        <?php endforeach; ?>

    <?php endif; ?>

</div>