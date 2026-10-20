<?php
/**
 * Plugin Name:       CINQ Central
 * Plugin URI:        https://github.com/agencecinq/wp-central
 * Description:       Votre espace client CINQ dans l'administration WordPress : projets, validations, roadmap, demandes et factures, au fil de Central.
 * Version:           0.1.0
 * Requires at least: 6.6
 * Requires PHP:      8.1
 * Author:            CINQ
 * Author URI:        https://agencecinq.com
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Update URI:        https://github.com/agencecinq/wp-central
 * Text Domain:       cinq-central
 * Domain Path:       /languages
 *
 * @package CinqCentral
 */

declare( strict_types = 1 );

namespace CinqCentral;

defined( 'ABSPATH' ) || exit;

const VERSION     = '0.1.0';
const PLUGIN_FILE = __FILE__;

/**
 * Absolute path to the plugin directory, with a trailing slash.
 */
define( 'CINQ_CENTRAL_PATH', plugin_dir_path( __FILE__ ) );

/**
 * URL to the plugin directory, with a trailing slash.
 */
define( 'CINQ_CENTRAL_URL_PLUGIN', plugin_dir_url( __FILE__ ) );

require_once CINQ_CENTRAL_PATH . 'includes/autoload.php';

Plugin::instance()->run();
