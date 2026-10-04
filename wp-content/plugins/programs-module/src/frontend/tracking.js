/**
 * Click tracking.
 *
 * A single delegated listener on the document, so elements rendered later
 * (for example cards swapped in by the filter) are tracked without any
 * re-binding. No jQuery.
 */

( () => {
	const settings = window.ProgramsTracking || {};
	const endpoint = settings.endpoint || '';

	if ( ! endpoint ) {
		return;
	}

	/**
	 * Sends the payload without ever blocking navigation.
	 *
	 * @param {Object} payload The click payload.
	 */
	const send = ( payload ) => {
		const body = JSON.stringify( payload );

		// sendBeacon is the most reliable option when the page is unloading,
		// but it cannot carry custom headers, so the nonce rides along in the
		// query string when one is available.
		if ( settings.nonce ) {
			const separator = endpoint.includes( '?' ) ? '&' : '?';
			const url =
				endpoint +
				separator +
				'_wpnonce=' +
				encodeURIComponent( settings.nonce );

			if ( window.navigator.sendBeacon ) {
				if (
					window.navigator.sendBeacon(
						url,
						new Blob( [ body ], { type: 'application/json' } )
					)
				) {
					return;
				}
			}

			try {
				window.fetch( url, {
					method: 'POST',
					body,
					keepalive: true,
					credentials: 'same-origin',
					headers: { 'Content-Type': 'application/json' },
				} );
			} catch ( error ) {
				/* Tracking must never break the page. */
			}

			return;
		}

		if ( window.navigator.sendBeacon ) {
			if (
				window.navigator.sendBeacon(
					endpoint,
					new Blob( [ body ], { type: 'application/json' } )
				)
			) {
				return;
			}
		}

		try {
			window.fetch( endpoint, {
				method: 'POST',
				body,
				keepalive: true,
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/json' },
			} );
		} catch ( error ) {
			/* Tracking must never break the page. */
		}
	};

	/**
	 * Handles a click anywhere in the document.
	 *
	 * Uses capture so tracking happens even if another handler stops
	 * propagation.
	 *
	 * @param {Event} event The click event.
	 */
	const onClick = ( event ) => {
		const target = event.target;

		if ( ! target || 'function' !== typeof target.closest ) {
			return;
		}

		const tracked = target.closest( '[data-track]' );

		if ( ! tracked ) {
			return;
		}

		let payload = null;

		try {
			payload = JSON.parse( tracked.getAttribute( 'data-track' ) );
		} catch ( error ) {
			// Invalid JSON is ignored rather than thrown.
			return;
		}

		if ( ! payload || 'object' !== typeof payload ) {
			return;
		}

		send( payload );
	};

	document.addEventListener( 'click', onClick, true );

	// Expose a tiny API so other code can emit an event explicitly.
	window.ProgramsTracking = Object.assign( {}, settings, {
		track: send,
	} );
} )();
