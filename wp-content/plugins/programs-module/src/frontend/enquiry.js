/**
 * "Enquire now" pop-up, built with the WordPress Interactivity API.
 *
 * Ships as a native ES module for the same reason the filter does:
 * `@wordpress/interactivity` is registered as a script module, so the bare
 * specifier resolves in the browser without bundling.
 *
 * The pop-up is progressively enhanced. Without JavaScript the Enquire
 * button is a real link to the enquiry page, so the flow never dead-ends;
 * with JavaScript the same markup becomes an accessible dialog with a
 * loading state, inline validation errors and a thank-you screen.
 */

import { store, getContext, getElement } from '@wordpress/interactivity';

/**
 * Selector for the elements that may receive focus inside the dialog.
 *
 * @type {string}
 */
const FOCUSABLE =
	'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

/**
 * Element that had focus before the dialog opened, so it can be restored.
 *
 * @type {HTMLElement|null}
 */
let lastFocused = null;

const { state } = store( 'programs/enquiry', {
	state: {
		isOpen: false,
		isSubmitting: false,
		isSent: false,
		generalError: '',
		errors: {},
		programId: 0,
		programTitle: '',

		/**
		 * Inline error for the name field.
		 *
		 * @return {string} The message, or an empty string.
		 */
		get errorName() {
			return state.errors.name || '';
		},

		/**
		 * Inline error for the email field.
		 *
		 * @return {string} The message, or an empty string.
		 */
		get errorEmail() {
			return state.errors.email || '';
		},

		/**
		 * Inline error for the phone field.
		 *
		 * @return {string} The message, or an empty string.
		 */
		get errorPhone() {
			return state.errors.phone || '';
		},

		/**
		 * Label shown in the dialog heading.
		 *
		 * @return {string} The program title, or a generic heading.
		 */
		get heading() {
			return state.programTitle || '';
		},
	},

	actions: {
		/**
		 * Opens the dialog for the program the trigger belongs to.
		 *
		 * @param {Event} event The click event.
		 */
		open( event ) {
			if ( event ) {
				event.preventDefault();
			}

			const context = getContext();

			// Reach the document through the trigger element rather than the
			// global, so this stays correct inside any embedded context.
			lastFocused = getElement().ref.ownerDocument.activeElement;

			state.programId = Number( context.programId || 0 );
			state.programTitle = context.programTitle || '';
			state.isSent = false;
			state.isSubmitting = false;
			state.generalError = '';
			state.errors = {};
			state.isOpen = true;
		},

		/**
		 * Closes the dialog and returns focus to the trigger.
		 *
		 * @param {Event} event The click event.
		 */
		close( event ) {
			if ( event ) {
				event.preventDefault();
				event.stopPropagation();
			}

			state.isOpen = false;

			if ( lastFocused && typeof lastFocused.focus === 'function' ) {
				lastFocused.focus();
			}

			lastFocused = null;
		},

		/**
		 * Closes the dialog when Escape is pressed inside it.
		 *
		 * @param {Event} event The keydown event.
		 */
		onKeydown( event ) {
			if ( 'Escape' === event.key ) {
				state.isOpen = false;
				return;
			}

			// Keep Tab inside the dialog while it is open.
			if ( 'Tab' !== event.key ) {
				return;
			}

			const dialog = getElement().ref;
			const { activeElement } = dialog.ownerDocument;
			const items = Array.from(
				dialog.querySelectorAll( FOCUSABLE )
			).filter( ( item ) => item.offsetParent !== null );

			if ( ! items.length ) {
				return;
			}

			const first = items[ 0 ];
			const last = items[ items.length - 1 ];

			if ( event.shiftKey && activeElement === first ) {
				event.preventDefault();
				last.focus();
			} else if ( ! event.shiftKey && activeElement === last ) {
				event.preventDefault();
				first.focus();
			}
		},

		/**
		 * Submits the lead to the enquiry endpoint.
		 *
		 * @param {Event} event The submit event.
		 */
		*submit( event ) {
			event.preventDefault();

			if ( state.isSubmitting ) {
				return;
			}

			const form = event.target;
			const endpoint = form.dataset.endpoint || '';
			const nonce = form.dataset.nonce || '';

			if ( ! endpoint ) {
				state.generalError = 'This form is not configured.';
				return;
			}

			const data = new FormData( form );
			const programmeField = form.elements.programme;
			const programmeId = Number( data.get( 'programme' ) || 0 );
			const programmeTitle =
				programmeField && programmeField.selectedOptions.length
					? programmeField.selectedOptions[ 0 ].textContent.trim()
					: '';

			if ( ! programmeId ) {
				state.generalError = 'Please select a programme.';
				return;
			}

			state.isSubmitting = true;
			state.generalError = '';
			state.errors = {};

			// Keep the store in step with the dropdown, so the heading and
			// the payload always describe the same programme.
			state.programId = programmeId;
			state.programTitle = programmeTitle;

			try {
				const response = yield window.fetch(
					`${ endpoint }${
						endpoint.includes( '?' ) ? '&' : '?'
					}_wpnonce=${ encodeURIComponent( nonce ) }`,
					{
						method: 'POST',
						headers: { 'Content-Type': 'application/json' },
						body: JSON.stringify( {
							name: String( data.get( 'name' ) || '' ),
							email: String( data.get( 'email' ) || '' ),
							phone: String( data.get( 'phone' ) || '' ),
							countryCode: String(
								data.get( 'countryCode' ) || ''
							),
							message: String( data.get( 'message' ) || '' ),
							program: programmeTitle,
							programId: programmeId,
						} ),
					}
				);

				const result = yield response.json();

				if ( ! response.ok ) {
					state.errors =
						( result && result.data && result.data.fields ) || {};

					state.generalError =
						( result && result.message ) ||
						'Something went wrong. Please try again.';

					return;
				}

				state.isSent = true;
				form.reset();
			} catch ( error ) {
				state.generalError =
					'We could not send your enquiry. Please try again.';
			} finally {
				state.isSubmitting = false;
			}
		},
	},

	effects: {
		/**
		 * Moves focus into the dialog once it is visible.
		 *
		 * Runs as an effect so it happens after the Interactivity runtime
		 * has applied the hidden/visible bindings to the DOM.
		 */
		focusDialog() {
			if ( ! state.isOpen ) {
				return;
			}

			const { ref } = getElement();
			const target = ref.querySelector( '[data-enquiry-autofocus]' );

			if ( target ) {
				target.focus();
			}
		},

		/**
		 * Locks page scrolling while the dialog is open.
		 */
		lockScroll() {
			document.body.classList.toggle( 'has-enquiry-open', state.isOpen );
		},
	},
} );
