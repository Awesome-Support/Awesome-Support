/**
 * The submit-ticket shortcode GUTENBERG block
 */
( function() {
	
	var el = wp.element.createElement,
		registerBlockType = wp.blocks.registerBlockType,
		blockStyle = { backgroundColor: '#900', color: '#fff', padding: '20px' };
		blockStyleSave = { } ;
		
	var __ = wp.i18n.__; // The __() for internationalization.	

	/**
	 * Register Basic Block.
	 *
	 * Registers a new block providing a unique name and an object defining its
	 * behavior. Once registered, the block is made available as an option to any
	 * editor interface where blocks are implemented.
	 *
	 * @param  {string}   name     Block name.
	 * @param  {Object}   settings Block settings.
	 * @return {?WPBlock}          The block, if it has been successfully
	 *                             registered; otherwise `undefined`.
	 */	
	registerBlockType( 'awesome-support/submit-ticket', {
		title: __( 'Submit Ticket', 'awesome-support' ) ,

		icon: 'forms',

		category: 'widgets',

		edit: function( props ) {
			return el( 'p', { style: blockStyle }, __( 'Awesome Support: Submit Ticket', 'awesome-support' ) );
		},

		save: function( props ) {
			return el( 'p', { style: blockStyleSave }, '[ticket-submit]' );
		},
	} );
	
})();