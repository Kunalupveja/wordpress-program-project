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
		level: '',

		/**
		 * Inline error for the enquirer-type field.
		 *
		 * @return {string} The message, or an empty string.
		 */
		get errorEnquirer() {
			return state.errors.enquirer || '';
		},

		/**
		 * Inline error for the level-of-interest field.
		 *
		 * @return {string} The message, or an empty string.
		 */
		get errorLevel() {
			return state.errors.level || '';
		},

		/**
		 * Inline error for the programme field.
		 *
		 * @return {string} The message, or an empty string.
		 */
		get errorProgramme() {
			return state.errors.programme || '';
		},

		/**
		 * Inline error for the first-name field.
		 *
		 * @return {string} The message, or an empty string.
		 */
		get errorFirstName() {
			return state.errors.firstName || '';
		},

		/**
		 * Inline error for the last-name field.
		 *
		 * @return {string} The message, or an empty string.
		 */
		get errorLastName() {
			return state.errors.lastName || '';
		},

		/**
		 * Inline error for the country field.
		 *
		 * @return {string} The message, or an empty string.
		 */
		get errorCountry() {
			return state.errors.country || '';
		},

		/**
		 * Inline error for the city field.
		 *
		 * @return {string} The message, or an empty string.
		 */
		get errorCity() {
			return state.errors.city || '';
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
			state.level = '';
			state.isSent = false;
			state.isSubmitting = false;
			state.generalError = '';
			state.errors = {};

			// Pre-select the programme that the trigger belongs to, so a
			// card or detail page never has to be chosen again by hand.
			if ( state.programId ) {
				const form = getElement()
					.ref.ownerDocument.getElementById( 'programmes-enquiry' )
					?.querySelector( 'form' );

				if ( form ) {
					const level = form.querySelector(
						`#enquiry-programme option[value="${ state.programId }"]`
					)?.dataset?.type;

					state.level = level || '';

					if ( level ) {
						const levelField = form.querySelector( '#enquiry-level' );
						if ( levelField ) {
							levelField.value = level;
						}
					}
				}
			}

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
		 * Keeps the programme dropdown in step with the chosen level.
		 *
		 * @param {Event} event The change event.
		 */
		syncProgrammes( event ) {
			state.level = event.target.value || '';

			const form = event.target.form;
			if ( ! form ) {
				return;
			}

			const programme = form.querySelector( '#enquiry-programme' );

			if ( ! programme ) {
				return;
			}

			// Hide programmes of the other level; the browser keeps the
			// current selection only while it is still offered.
			Array.from( programme.options ).forEach( ( option ) => {
				if ( '0' === option.value ) {
					return;
				}

				const matches =
					'' === state.level || option.dataset.type === state.level;

				option.hidden = ! matches;
			} );

			if (
				'' !== state.level &&
				programme.selectedOptions.length &&
				'0' !== programme.selectedOptions[ 0 ].value &&
				programme.selectedOptions[ 0 ].dataset.type !== state.level
			) {
				programme.value = '0';
				state.programId = 0;
			}
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
			const programmeId = Number( data.get( 'programme' ) || 0 );

			if ( ! programmeId ) {
				state.generalError = 'Please select a programme.';
				return;
			}

			state.isSubmitting = true;
			state.generalError = '';
			state.errors = {};

			// Keep the store in step with the dropdown, so the payload
			// always describes the programme actually chosen.
			state.programId = programmeId;

			try {
				const response = yield window.fetch(
					`${ endpoint }${
						endpoint.includes( '?' ) ? '&' : '?'
					}_wpnonce=${ encodeURIComponent( nonce ) }`,
					{
						method: 'POST',
						headers: { 'Content-Type': 'application/json' },
						body: JSON.stringify( {
							enquirer: String( data.get( 'enquirer' ) || '' ),
							level: String( data.get( 'level' ) || '' ),
							programId: programmeId,
							firstName: String( data.get( 'firstName' ) || '' ),
							lastName: String( data.get( 'lastName' ) || '' ),
							country: String( data.get( 'country' ) || '' ),
							city: String( data.get( 'city' ) || '' ),
							phone: String( data.get( 'phone' ) || '' ),
							email: String( data.get( 'email' ) || '' ),
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
