/**
 * Programs filter, built with the WordPress Interactivity API.
 *
 * Ships as a native ES module: WordPress registers `@wordpress/interactivity`
 * as a script module and prints an import map for it, so the bare specifier
 * below resolves in the browser without any bundling. Bundling it instead
 * would produce a classic script that runs before the module graph.
 *
 * The cards are rendered by PHP, so the listing works with JavaScript
 * disabled. This store progressively enhances it: choosing a filter swaps
 * which cards are visible, updates the URL and drives loading and empty
 * states.
 */

import { store, getContext, getElement } from '@wordpress/interactivity';

const settings = window.ProgramsFilter || {};
const knownValues = settings.values || [ 'all' ];

const { state } = store( 'programs/filter', {
	state: {
		filter: settings.filter || 'all',
		isLoading: false,
		remoteIds: null,

		/**
		 * Whether the card this binding sits on should be hidden.
		 *
		 * The card's own context supplies its id and type. When a REST
		 * result is available it wins, so the visible set is genuinely
		 * driven by the API rather than by markup alone.
		 *
		 * @return {boolean} True when the card is filtered out.
		 */
		get cardHidden() {
			const context = getContext();

			if ( Array.isArray( state.remoteIds ) && state.remoteIds.length ) {
				return ! state.remoteIds.includes( context.id );
			}

			if ( 'all' === state.filter ) {
				return false;
			}

			return context.type !== state.filter;
		},

		/**
		 * Whether the empty state should be shown.
		 *
		 * @return {boolean} True when nothing matches the current filter.
		 */
		get isEmpty() {
			if ( state.isLoading ) {
				return false;
			}

			if ( Array.isArray( state.remoteIds ) ) {
				return 0 === state.remoteIds.length;
			}

			const context = getContext();

			return 0 === Number( context.counts?.[ state.filter ] ?? 0 );
		},

		/**
		 * aria-current for a filter link.
		 *
		 * @return {string} 'page' when this link is the active filter.
		 */
		get ariaCurrent() {
			const context = getContext();

			return context.value === state.filter ? 'page' : 'false';
		},
	},

	actions: {
		/**
		 * Applies a filter.
		 *
		 * @param {Event} event The click event.
		 */
		*selectFilter( event ) {
			// Without JavaScript the link simply navigates.
			event.preventDefault();

			const context = getContext();
			const element = getElement();
			const value = context.value || 'all';
			const url = element.ref.getAttribute( 'href' ) || '';
			const restUrl = context.restUrl || '';

			state.filter = value;
			state.remoteIds = null;
			state.isLoading = true;

			if ( url ) {
				window.history.pushState( { filter: value }, '', url );
			}

			if ( restUrl ) {
				try {
					const response = yield window.fetch( restUrl );

					if ( ! response.ok ) {
						throw new Error( 'Request failed' );
					}

					const programs = yield response.json();

					state.remoteIds = Array.isArray( programs )
						? programs.map( ( program ) => Number( program.id ) )
						: [];
				} catch ( error ) {
					// Fall back to the pre-rendered attributes: `cardHidden`
					// uses context.type when there is no REST result, so
					// filtering still works.
					state.remoteIds = null;
				}
			}

			state.isLoading = false;
		},
	},
} );

// Keep the store in step with browser back/forward navigation.
window.addEventListener( 'popstate', () => {
	const path = window.location.pathname.replace( /\/$/, '' );
	const match = path.match( /\/programmes\/([^/]+)$/ );
	const value = match ? match[ 1 ] : '';

	state.filter = knownValues.includes( value ) ? value : 'all';
	state.remoteIds = null;
} );
