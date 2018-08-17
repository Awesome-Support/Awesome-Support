(function ($) {
	"use strict";


	$(function () {
        $(".wpas-show-reply-history").click(function(e) {
            if( ! $('.wpas-show-history-popup').length ) {
              $('#wpcontent').append('<div class="wpas-show-history-popup"></div>');
            }

            $('.wpas-show-history-popup').addClass('is-visible');

            e.preventDefault();
            $.ajax({
              url : WPAS_Reply_History.ajax_url,
              type : 'post',
              data : {
                action : 'wpas_load_reply_history',
                reply_id : $(this).data( 'replyid' )
              },
              success : function( response ) {
                if( response.code === 200 ) {
                  $( '.wpas-reply-notification' ).addClass( 'success' ).html( '<p>' + response.message + '</p>' );
                  if( response.data ) {
                    var responseTable = '<table class="wp-list-table widefat fixed striped">';
                    $.each( response.data, function(i, item) {                      
                      responseTable += '<tr><td><div class="wpas-reply-history-log-table"><div class="row"><div class="title">' + WPAS_Reply_History.date_label + ' : ' + item.post_date + '</div></div><div class="row"><div class="title">' + item.post_title + '</div></div><div class="row"><div class="content">' + item.post_content + '</div></div></div></td></tr>';
                    });
                    responseTable += '</table>';
                    $( '.wpas-reply-history-table' ).html( responseTable );
                  }
                }else{
                  $( '.wpas-reply-notification' ).addClass( 'failed' ).html( '<p>' + response.message + '</p>' );
                  $( '.wpas-reply-history-table' ).html( '' );
                }
                $(".pop").fadeIn("slow");
              }
            });
          });

          /**
           * Ticket content history
           */
          $("#wpas-view-edit-main-ticket-message").click(function(e) {
            e.preventDefault();
            $.ajax({
              url : WPAS_Reply_History.ajax_url,
              type : 'post',
              data : {
                action : 'wpas_load_reply_history',
                reply_id : $(this).data( 'ticketid' )
              },
              success : function( response ) {
                if( response.code === 200 ) {
                  $( '.wpas-reply-notification' ).addClass( 'success' ).html( '<p>' + response.message + '</p>' );
                  if( response.data ) {
                    var responseTable = '<table class="wp-list-table widefat fixed striped">';
                    $.each( response.data, function(i, item) {                      
                      responseTable += '<tr><td><div class="wpas-reply-history-log-table"><div class="row"><div class="title">' + WPAS_Reply_History.date_label + ' : ' + item.post_date + '</div></div><div class="row"><div class="title">' + item.post_title + '</div></div><div class="row"><div class="content">' + item.post_content + '</div></div></div></td></tr>';
                    });
                    responseTable += '</table>';
                    $( '.wpas-reply-history-table' ).html( responseTable );
                  }
                }else{
                  $( '.wpas-reply-notification' ).addClass( 'failed' ).html( '<p>' + response.message + '</p>' );
                  $( '.wpas-reply-history-table' ).html( '' );
                }
                $(".pop").fadeIn("slow");
              }
            });
          });
          
          
          $(".pop .icon-remove-sign a").click(function(e) {
            e.preventDefault();
            $(".pop").fadeOut("fast");
            $('.wpas-show-history-popup').removeClass('is-visible');
          });
          
          //Thanks for Iphone titlebar fix http://coding.smashingmagazine.com/2013/05/02/truly-responsive-lightbox/
          
          var getIphoneWindowHeight = function() {
            // Get zoom level of mobile Safari
            // Such zoom detection might not work correctly on other platforms
            //
            var zoomLevel = document.documentElement.clientWidth / window.innerWidth;
          
            // window.innerHeight returns height of the visible area.
            // We multiply it by zoom and get our real height.
            return window.innerHeight * zoomLevel;
          };        
    });

}(jQuery));