(function ( $ ) {
	'use strict';

	var civicSip = new civic.sip( { appId: civic_app.id } );
	// Start scope request.
    $('.js-civic-signup').click(function (event) {
		event.preventDefault();
		civicSip.signup( { style: 'popup', scopeRequest: civicSip.ScopeRequests.PROOF_OF_IDENTITY } );
		var modal = $( 'div.civic-modal.civic-qrcode' );
		modal.find( '.civic-window' ).show();
		modal.find( '#civic-loader' ).hide();
	} );

	// Listen for data
	civicSip.on( 'auth-code-received', function ( event ) {
	console.log(event);
		$( 'body' ).addClass( 'civic-hit-the-lights civic-no-scroll' );
		var modal = $( 'div.civic-modal.civic-qrcode' );
		var loader = $( '#civic-loader' );
		if ( loader.length === 0 ) {
			modal.append(
				'<div id="civic-loader" class="civic-loader large civic-switch"><svg width="64px" height="64px" version="1.1" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g id="keyhole" transform="translate(4.000000, 4.000000)"><path d="M31.6750055,30.4909742 C34.1659012,29.271799 35.8812589,26.711394 35.8812589,23.750025 C35.8812589,19.607775 32.525102,16.250025 28.3848173,16.250025 C24.2445327,16.250025 20.8883758,19.607775 20.8883758,23.750025 C20.8883758,26.711493 22.6038482,29.2719702 25.094879,30.4910965 L25.094879,39.75 L31.6750055,39.75 L31.6750055,30.4909742 Z" fill="#FFFFFF"></path></g><g class="civic-circular" stroke="none" stroke-width="0" fill="none" fill-rule="evenodd"><circle class="civic-path" cx="32" cy="32" r="28" stroke="#FFFFFF" stroke-width="8" fill="none" miterlimit="10"></circle></g></svg></div>'
			);
		} else {
			loader.show();
		}
		modal.addClass( 'civic-show' );
		modal.find( '.civic-window' ).hide();

		// Send JWT token to app server
		sendAuthCode( event.response );
	} );

	civicSip.on( 'user-cancelled', function ( event ) {
		// Handle request cancellation if necessary.

		var userData = {
    			"data": [
      				{
        				"label": "verifications.levels.CIVIC:IAL1", 
        				"value": "CIVIC:IAL1", 
        				"isValid": true, 
        				"isOwner": true
      				}, 
      				{
        				"label": "documents.genericId.type", 
        				"value": "Driving License", 
        				"isValid": true, 
        				"isOwner": true
      				}, 
      				{
        				"label": "documents.genericId.number", 
        				"value": "A0000000", 
        				"isValid": true, 
        				"isOwner": true
      				}, 
      				{
        				"label": "documents.genericId.name", 
        				"value": "Jane Doe", 
        				"isValid": true, 
        				"isOwner": true
      				}, 
      				{
        				"label": "documents.genericId.dateOfBirth", 
        				"value": "YYYY-m-d", 
 				        "isValid": true, 
        				"isOwner": true
      				}, 
      				{
        				"label": "documents.genericId.dateOfIssue", 
        				"value": "YYYY-m-d", 
        				"isValid": true, 
        				"isOwner": true
      				},
      				{
        				"label": "documents.genericId.dateOfExpiry",
        				"value": "2021-1-01",
        				"isValid": true,
        				"isOwner": true
      				},
      				{
        				"label": "documents.genericId.image", 
        				"value": "/9j/4AA...", 
        				"isValid": true, 
        				"isOwner": true
      				}, 
      				{
        				"label": "documents.genericId.image_md5", 
        				"value": "d0a...", 
        				"isValid": true, 
        				"isOwner": true
      				}, 
      				{
        				"label": "documents.genericId.country", 
        				"value": "USA", 
        				"isValid": true, 
        				"isOwner": true
      				},
      				{
        				"label": "contact.personal.email",
        				"value": "jonsmith@example.com",
        				"isValid": true,
        				"isOwner": true
     				},
     				{
        				"label": "contact.personal.phoneNumber",
        				"value": "+1 5551234567",
        				"isValid": true,
        				"isOwner": true
    				}
    			], 
    			"userId": "c6d5795f8a059ez5ad29a33a60f8b402a172c3e0bbe50fd230ae8e0303609b42"
  		};

		console.log(userData.userId);
	} );

	// Error events.
	civicSip.on( 'civic-sip-error', function ( error ) {
		// Handle error display if necessary.
	} );

	function sendAuthCode( jwtToken ) {

		$.ajax( {
			type: 'POST',
			dataType: 'json',
			url: civic_ajax.url,
			data: {
				'action': 'civic_auth',
				'nonce': civic_ajax.nonce,
                'token': jwtToken,
                'rememberme': $('input[name=rememberme]').val()
			},
			success: function ( response ) {
				if ( response == 0 ) {
					// Already logged in, reload the document
					document.location.reload( true );
				} else if ( response.data && response.data.logged_in == true ) {
					document.location.href = civic_ajax.redirect_url;
				} else if ( response.data && response.data.modal ) {
					$( 'body' ).addClass( 'civic-hit-the-lights civic-no-scroll' );
					var modal = $( 'div.civic-modal.civic-qrcode' ).addClass( 'civic-show' );
					modal.find( 'iframe' ).remove();
					modal.find( '.civic-loader' ).hide();
					modal.find( '.civic-window' ).show();
					modal.find( '.civic-content' ).append( response.data.modal )
						.attr( 'style', '' ); // Removes style height.
			/*
			* Disabling registration feature using Civic
			*
					$( '#civic-register' ).on( 'click', function () {
						$.ajax( {
							type: 'POST',
							dataType: 'json',
							url: civic_ajax.url,
							data: {
								'action': 'civic_register',
								'nonce': civic_ajax.nonce,
								'email': response.data.email
							},
							success: function () {
								document.location.href = civic_ajax.redirect_url;
							}
						} );
					} );
			*/
					$( '#civic-cancel' ).on( 'click', function () {
						modal.removeClass( 'civic-show' );
						$( 'body' ).removeClass( 'civic-hit-the-lights civic-no-scroll' );
					} );
				} else {
					$( 'div.civic-modal.civic-qrcode' ).removeClass( 'civic-show' );
					$( 'body' ).removeClass( 'civic-hit-the-lights civic-no-scroll' );
				}
			}
		} );
	}

})( jQuery );
