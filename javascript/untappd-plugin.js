/**
 * Wrapper function to safely use $
 */
function untappdWrapper( $ ) {
	var untappd = {

		/**
		 * Main entry point
		 */
		init: function () {
			untappd.prefix      = 'untappd_';
			untappd.templateURL = $( '#template-url' ).val();
			untappd.ajaxPostURL = $( '#ajax-post-url' ).val();

			untappd.registerEventHandlers();
		},

		/**
		 * Registers event handlers
		 */
		registerEventHandlers: function () {
			$( '#example-container' ).children( 'a' ).click( untappd.exampleHandler );
		},

		/**
		 * Example event handler
		 *
		 * @param object event
		 */
		exampleHandler: function ( event ) {
			alert( $( this ).attr( 'href' ) );

			event.preventDefault();
		}
	}; // end untappd

	$( document ).ready( untappd.init );

} // end untappdWrapper()

untappdWrapper( jQuery );
