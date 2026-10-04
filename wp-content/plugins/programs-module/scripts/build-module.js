/**
 * Copies the Interactivity API modules into build/ verbatim.
 *
 * They are already native ES modules: WordPress prints an import map for
 * `@wordpress/interactivity`, so the browser resolves that bare specifier
 * at runtime. Bundling them would turn them into classic scripts and they
 * would execute before the script-module graph has run.
 *
 * Everything under src/frontend except tracking.js is treated as a module,
 * so adding a new one needs no change here.
 */

const fs = require( 'fs' );
const path = require( 'path' );

const sourceDir = path.resolve( __dirname, '..', 'src', 'frontend' );
const targetDir = path.resolve( __dirname, '..', 'build', 'frontend' );
const bundled = new Set( [ 'tracking.js' ] );

fs.mkdirSync( targetDir, { recursive: true } );

fs.readdirSync( sourceDir )
	.filter( ( file ) => file.endsWith( '.js' ) && ! bundled.has( file ) )
	.forEach( ( file ) => {
		const source = path.join( sourceDir, file );
		const target = path.join( targetDir, file );

		fs.copyFileSync( source, target );

		process.stdout.write(
			`Copied ${ path.relative(
				process.cwd(),
				source
			) } -> ${ path.relative( process.cwd(), target ) }\n`
		);
	} );
