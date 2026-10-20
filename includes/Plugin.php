<?php
/**
 * Plugin bootstrap.
 *
 * @package CinqCentral
 */

declare( strict_types = 1 );

namespace CinqCentral;

use CinqCentral\Admin\AppPage;
use CinqCentral\Admin\DashboardWidget;
use CinqCentral\Admin\SettingsPage;
use CinqCentral\Front\Widget;
use CinqCentral\Rest\Proxy;
use CinqCentral\Update\GitHubUpdater;

defined( 'ABSPATH' ) || exit;

/**
 * Wires the plugin's services.
 *
 * The plugin is a window, not a copy: nothing from Central is stored here
 * beyond a few minutes of cache. Every screen asks Central, through this
 * site's PHP, with the site's secret and the name of the person logged in.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Returns the singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Registers the plugin's hooks.
	 *
	 * @return void
	 */
	public function run(): void {
		add_action( 'plugins_loaded', array( $this, 'boot' ) );

		// The updater runs whatever the connection state: an update may be
		// exactly what fixes it.
		( new GitHubUpdater() )->run();
	}

	/**
	 * Boots the plugin's services once every plugin is loaded.
	 *
	 * @return void
	 */
	public function boot(): void {
		load_plugin_textdomain( 'cinq-central', false, dirname( plugin_basename( PLUGIN_FILE ) ) . '/languages' );

		$services = array(
			new SettingsPage(),
			new AppPage(),
			new DashboardWidget(),
			new Proxy(),
			new Widget(),
		);

		// Every entry implements Service and registers its own hooks.
		foreach ( $services as $service ) {
			$service->run();
		}
	}
}
