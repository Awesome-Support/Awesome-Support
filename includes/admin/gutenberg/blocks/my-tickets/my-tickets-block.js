/**
 * The TICKETS shortcode GUTENBERG block
 */

var el = wp.element.createElement,
    registerBlockType = wp.blocks.registerBlockType,
    blockStyle = { backgroundColor: '#900', color: '#fff', padding: '20px' };
	blockStyleSave = { } ;

registerBlockType( 'awesome-support/my-tickets', {
    title: 'My Tickets',

    icon: 'schedule',

    category: 'widgets',

    edit: function() {
        return el( 'p', { style: blockStyle }, 'Awesome Support: My Tickets' );
    },

    save: function() {
        return el( 'p', { style: blockStyleSave }, '[tickets]' );
    },
} );