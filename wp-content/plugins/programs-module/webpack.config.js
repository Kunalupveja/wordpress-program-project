/**
 * WordPress scripts does not auto-discover multiple entry points, so the
 * default config is extended with the bundles this plugin ships.
 *
 * Only classic scripts are bundled here:
 *
 *   admin/index        React meta panel
 *   frontend/tracking  click tracking
 *
 * The Interactivity API filter is deliberately NOT bundled. WordPress
 * registers `@wordpress/interactivity` as a script module, and script
 * modules always execute after classic scripts, so a bundled (classic)
 * build would run before `window.wp.interactivity` exists. That file is
 * therefore shipped as a native ES module - see scripts/build-module.js.
 */

const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		'admin/index': path.resolve( __dirname, 'src', 'admin', 'index.js' ),
		'frontend/tracking': path.resolve(
			__dirname,
			'src',
			'frontend',
			'tracking.js'
		),
	},
};
