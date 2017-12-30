/**
 * The submit-ticket shortcode GUTENBERG block
 */

var el = wp.element.createElement,
    registerBlockType = wp.blocks.registerBlockType,
    blockStyle = { backgroundColor: '#900', color: '#fff', padding: '20px' };
	blockStyleSave = { } ;

registerBlockType( 'awesome-support/submit-ticket', {
    title: 'Submit Ticket',

    icon: 'forms',

    category: 'widgets',

    edit: function() {
        return el( 'p', { style: blockStyle }, 'Awesome Support: Submit Ticket' );
    },

    save: function() {
        return el( 'p', { style: blockStyleSave }, '[ticket-submit]' );
    },
} );