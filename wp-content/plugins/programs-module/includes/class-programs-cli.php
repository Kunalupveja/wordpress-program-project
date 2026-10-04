<?php
/**
 * WP-CLI commands for the Programs module.
 *
 * @package Programs_Module
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return; // Only meaningful when running under WP-CLI.
}

/**
 * Class Programs_CLI
 *
 * Manages the Programs sample data from the command line.
 */
class Programs_CLI {

	/**
	 * Seeds the program types and the ten sample programs.
	 *
	 * Seeding is idempotent: existing terms and programs are left alone
	 * unless --force is given.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Refresh programs that already exist instead of skipping them.
	 *
	 * ## EXAMPLES
	 *
	 *     # Seed anything that is missing.
	 *     $ wp programs seed
	 *
	 *     # Rebuild the sample programs and regenerate their images.
	 *     $ wp programs seed --force
	 *
	 * @param array $args       Positional arguments (unused).
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function seed( $args, $assoc_args ) {
		$force  = ! empty( $assoc_args['force'] );
		$result = Programs_Seeder::seed_all( $force );

		WP_CLI::success(
			sprintf(
				'Seeded %1$d program type(s) and %2$d program(s).',
				(int) $result['terms'],
				(int) $result['programs']
			)
		);
	}

	/**
	 * Removes the sample programs and their generated images.
	 *
	 * Only programs created by this plugin are removed; manually created
	 * programs are never touched.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp programs remove
	 *
	 * @param array $args       Positional arguments (unused).
	 * @param array $assoc_args Associative arguments (unused).
	 * @return void
	 */
	public function remove( $args, $assoc_args ) {
		$removed = Programs_Seeder::remove_sample_programs();

		WP_CLI::success(
			sprintf(
				'Removed %d sample program(s).',
				(int) $removed
			)
		);
	}

	/**
	 * Reports how many sample programs currently exist.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp programs count
	 *
	 * @param array $args       Positional arguments (unused).
	 * @param array $assoc_args Associative arguments (unused).
	 * @return void
	 */
	public function count( $args, $assoc_args ) {
		WP_CLI::log(
			sprintf(
				'%d sample program(s) present.',
				(int) Programs_Seeder::count_sample_programs()
			)
		);
	}
}
