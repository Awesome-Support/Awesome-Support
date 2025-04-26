
( function($) {
        
        
        $( function() {
                
                
                function close_card( card ) {
                        card.removeClass('is-expanded').addClass('is-collapsed');
                        $('.card').not(card).removeClass('is-inactive');
                        card.trigger('card_collapsed');
                }
                
                
                Window.close_card = close_card;
                
                /**
                 * Expand a card once clicked
                 */
                $('body').delegate( '.wpas_cards .card .js-expander', 'click' , function() {
                        var thisCell = $(this).closest('.card');
                        
                        var cell = $('.card');
                        if (thisCell.hasClass('is-collapsed')) {
                                cell.not(thisCell).removeClass('is-expanded').addClass('is-collapsed').addClass('is-inactive');
                                thisCell.removeClass('is-collapsed').addClass('is-expanded');
                                
                                thisCell.trigger('card_expanded');
                                
                                if (cell.not(thisCell).hasClass('is-inactive')) {
                                        //do nothing
                                } else {
                                        cell.not(thisCell).addClass('is-inactive');
                                }

                        } else {
                                close_card(thisCell)
                        }
                });
                
                /**
                 * Trigger once close button is pressed from expanded card
                 */
                $('body').delegate( '.card_expanded_inner .btn-close', 'click', function() {
                        close_card( $(this).closest('.card') );
                });
        })
        
        
})( jQuery )