/**
 * Javascript handler for the multiedit parser function
 *
 * @author Stephan Gambke
 */

jQuery( function( $ ) {

	$('.multiedit-trigger').click(function(){

		if ( mw.config.get( 'wgUserName' ) == null ) {
			if ( confirm( sfgAnonEditWarning ) ) {
				handleMultiEdit( this );
			}
		} else {
			handleMultiEdit( this );
		}

		return false;
	});

	function handleMultiEdit( trigger ){
		var jtrigger = jQuery( trigger );
		var jmultiedit = jtrigger.closest( '.multiedit' );
		var jresult = jmultiedit.find('.multiedit-result');

		var reload = jtrigger.hasClass( 'reload' );

		var data = new Array();
		data.push( jmultiedit.find('form.multiedit-data').serialize() );

		jtrigger.attr('class', 'multiedit-trigger multiedit-trigger-wait');
		jresult.attr('class', 'multiedit-result multiedit-result-wait');

		jresult[0].innerHTML="Wait..."; // TODO: replace by localized message

		sajax_request_type = 'POST';
		sajax_do_call( 'SFMultieditAPI::handleMultiEdit', data, function( ajaxHeader ){
			jresult.empty().append( ajaxHeader.responseText );

			if ( ajaxHeader.status == 200 ) {

				if ( reload ) window.location.reload();

				jresult.removeClass('multiedit-result-wait').addClass('multiedit-result-ok');
				jtrigger.removeClass('multiedit-trigger-wait').addClass('multiedit-trigger-ok');
			} else {
				jresult.removeClass('multiedit-result-wait').addClass('multiedit-result-error');
				jtrigger.removeClass('multiedit-trigger-wait').addClass('multiedit-trigger-error');
			}
		} );
	}

})
