( function () {
	'use strict';

	function closePopup( popup ) {
		var opener = popup.waPopupOpener;
		popup.hidden = true;
		popup.waPopupOpener = null;

		if ( opener ) {
			opener.focus();
		}
	}

	document.addEventListener( 'click', function ( event ) {
		var opener = event.target.closest( '[data-wa-popup-open]' );

		if ( opener ) {
			var popupId = opener.getAttribute( 'aria-controls' );
			var popup = popupId ? document.getElementById( popupId ) : null;

			if ( popup && popup.hasAttribute( 'data-wa-popup-dialog' ) ) {
				popup.waPopupOpener = opener;
				popup.hidden = false;
				popup.querySelector( '[data-wa-popup-close]' ).focus();
			}

			return;
		}

		var closeButton = event.target.closest( '[data-wa-popup-close]' );

		if ( closeButton ) {
			var popupToClose = closeButton.closest( '[data-wa-popup-dialog]' );
			if ( popupToClose ) {
				closePopup( popupToClose );
			}
			return;
		}

		var openPopup = event.target.closest( '[data-wa-popup-dialog]' );
		if ( openPopup && ! event.target.closest( '[data-wa-popup-panel]' ) ) {
			closePopup( openPopup );
		}
	} );

	document.addEventListener( 'keydown', function ( event ) {
		if ( 'Escape' !== event.key ) {
			return;
		}

		var openPopups = document.querySelectorAll( '[data-wa-popup-dialog]:not([hidden])' );
		if ( openPopups.length ) {
			closePopup( openPopups[ openPopups.length - 1 ] );
		}
	} );
} )();
