( function () {
	'use strict';

	var consent = document.querySelector( '[data-wa-cookie-consent]' );
	var reopenButton = document.querySelector( '[data-wa-cookie-reopen]' );

	if ( ! consent || ! reopenButton ) {
		return;
	}

	var preferences = consent.querySelector( '[data-wa-cookie-preferences]' );
	var customizeButton = consent.querySelector( '[data-wa-cookie-customize]' );
	var categories = consent.querySelectorAll( '[data-wa-cookie-category]' );
	var cookieName = 'wa_suit_cookie_consent';

	function readConsent() {
		var cookie = document.cookie.split( '; ' ).find( function ( item ) {
			return item.indexOf( cookieName + '=' ) === 0;
		} );

		if ( ! cookie ) {
			return null;
		}

		var value = cookie.slice( cookieName.length + 1 );
		var match = value.match( /^v1:([01]):([01]):([01])$/ );

		if ( ! match || '1' !== match[1] ) {
			return null;
		}

		return {
			analytics: '1' === match[2],
			advertising: '1' === match[3],
		};
	}

	function setCategoryChoices( choices ) {
		categories.forEach( function ( input ) {
			input.checked = Boolean( choices[ input.getAttribute( 'data-wa-cookie-category' ) ] );
		} );
	}

	function saveConsent( choices ) {
		var days = parseInt( consent.getAttribute( 'data-consent-days' ), 10 );
		if ( ! Number.isFinite( days ) || days < 1 || days > 365 ) {
			days = 180;
		}

		var value = [
			'v1',
			'1',
			choices.analytics ? '1' : '0',
			choices.advertising ? '1' : '0',
		].join( ':' );
		var cookie = cookieName + '=' + value + '; Max-Age=' + ( days * 86400 ) + '; Path=/; SameSite=Lax';

		if ( 'https:' === window.location.protocol ) {
			cookie += '; Secure';
		}

		document.cookie = cookie;
		consent.hidden = true;
		reopenButton.hidden = false;
		preferences.hidden = true;
		customizeButton.setAttribute( 'aria-expanded', 'false' );
		reopenButton.focus();

		document.dispatchEvent( new CustomEvent( 'wa-suit-cookie-consent', {
			detail: {
				necessary: true,
				analytics: choices.analytics,
				advertising: choices.advertising,
			},
		} ) );
	}

	function currentChoices() {
		var choices = {
			analytics: false,
			advertising: false,
		};

		categories.forEach( function ( input ) {
			choices[ input.getAttribute( 'data-wa-cookie-category' ) ] = input.checked;
		} );

		return choices;
	}

	function showConsent( savedChoices ) {
		consent.hidden = false;
		reopenButton.hidden = true;

		if ( savedChoices ) {
			setCategoryChoices( savedChoices );
		}
	}

	var savedChoices = readConsent();
	if ( savedChoices ) {
		reopenButton.hidden = false;
	} else {
		consent.hidden = false;
	}

	consent.querySelector( '[data-wa-cookie-accept]' ).addEventListener( 'click', function () {
		var allChoices = {};
		categories.forEach( function ( input ) {
			allChoices[ input.getAttribute( 'data-wa-cookie-category' ) ] = true;
		} );
		saveConsent( allChoices );
	} );

	consent.querySelector( '[data-wa-cookie-reject]' ).addEventListener( 'click', function () {
		saveConsent( {
			analytics: false,
			advertising: false,
		} );
	} );

	customizeButton.addEventListener( 'click', function () {
		var opening = preferences.hidden;
		preferences.hidden = ! opening;
		customizeButton.setAttribute( 'aria-expanded', opening ? 'true' : 'false' );

		if ( opening ) {
			setCategoryChoices( savedChoices || {} );
		}
	} );

	consent.querySelector( '[data-wa-cookie-save]' ).addEventListener( 'click', function () {
		saveConsent( currentChoices() );
	} );

	reopenButton.addEventListener( 'click', function () {
		savedChoices = readConsent();
		showConsent( savedChoices );
		customizeButton.click();
		customizeButton.focus();
	} );
} )();
