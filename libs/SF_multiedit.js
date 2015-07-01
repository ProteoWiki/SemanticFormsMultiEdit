/**
 * Javascript handler for the multiedit parser function
 *
 * @author Stephan Gambke
 */

/*global confirm */

( function ( $, mw ) {

	'use strict';

	var autoEditHandler = function handleAutoEdit(){

		if ( mw.config.get( 'wgUserName' ) === null &&
			! confirm( mw.msg( 'sf_multiedit_anoneditwarning' ) ) ) {

			return;
		}

		var jtrigger = jQuery( this );
		var jmultiedit = jtrigger.closest( '.multiedit' );
		var jresult = jmultiedit.find( '.multiedit-result' );

		var reload = jtrigger.hasClass( 'reload' );

		jtrigger.attr( 'class', 'multiedit-trigger multiedit-trigger-wait' );
		jresult.attr( 'class', 'multiedit-result multiedit-result-wait' );

		jresult.text( mw.msg( 'sf-multiedit-wait' ) );


		// data array to be sent to the server
		var data = {
			action: 'sfmultiedit',
			format: 'json'
		};

		// add form values to the data
		data.query =  jmultiedit.find( 'form.multiedit-data' ).serialize();

		$.ajax( {

			type:     'POST', // request type ( GET or POST )
			url:      mw.util.wikiScript( 'api' ), // URL to which the request is sent
			data:     data, // data to be sent to the server
			dataType: 'json', // type of data expected back from the server
			success:  function ( result ){
				jresult.empty().append( result.responseText );

				if ( result.status === 200 ) {

					if ( reload ) {
						window.location.reload();
					}

					jresult.removeClass( 'multiedit-result-wait' ).addClass( 'multiedit-result-ok' );
					jtrigger.removeClass( 'multiedit-trigger-wait' ).addClass( 'multiedit-trigger-ok' );
				} else {
					jresult.removeClass( 'multiedit-result-wait' ).addClass( 'multiedit-result-error' );
					jtrigger.removeClass( 'multiedit-trigger-wait' ).addClass( 'multiedit-trigger-error' );
				}
			}, // function to be called if the request succeeds
			error:  function ( jqXHR, textStatus, errorThrown ) {
				var result = jQuery.parseJSON(jqXHR.responseText);
				var text = result.responseText;

				for ( var i = 0; i < result.errors.length; i++ ) {
					text += ' ' + result.errors[i].message;
				}

				jresult.empty().append( text );
				jresult.removeClass( 'multiedit-result-wait' ).addClass( 'multiedit-result-error' );
				jtrigger.removeClass( 'multiedit-trigger-wait' ).addClass( 'multiedit-trigger-error' );
			} // function to be called if the request fails
		} );
	};

	jQuery( document ).ready( function ( $ ) {
		$( '.multiedit-trigger' ).click( autoEditHandler );
	} );

}( jQuery, mediaWiki ) );
