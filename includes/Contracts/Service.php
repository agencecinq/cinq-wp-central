<?php
/**
 * Service contract.
 *
 * @package CinqWpCentral
 * @subpackage CinqWpCentral/Contracts
 */

declare( strict_types = 1 );

namespace CinqWpCentral\Contracts;

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
