<?php
/**
 * Service contract.
 *
 * @package CinqCentral
 * @subpackage CinqCentral/Contracts
 */

declare( strict_types = 1 );

namespace CinqCentral\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * A unit of the plugin that hooks itself into WordPress.
 *
 * Mirrors the `AgenceCinq\Service` contract used by the theme and by
 * `cinq-wp-mcp`, so the three code bases read the same way.
 */
interface Service {

	/**
	 * Registers the service's hooks.
	 *
	 * @return void
	 */
	public function run(): void;
}
