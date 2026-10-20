<?php
/**
 * Minimal WordPress stubs.
 *
 * @package CinqWpCentral
 */

declare( strict_types = 1 );

/**
 * The in-memory world the stubs read and write.
 */
final class World {
	/** @var array<string, mixed> */
	public static array $options = array();
	/** @var array<string, mixed> */
	public static array $transients = array();
	/** @var array<string, array<int, callable>> */
	public static array $filters = array();
	/** @var array<int, array<string, mixed>> */
	public static array $requests = array();
	/** @var array<int, mixed> */
	public static array $responses = array();
	/** @var array<int, array<string, mixed>> */
	public static array $routes = array();
	public static ?WP_User $user = null;
	public static string $home = 'https://boutique.fr';

	public static function reset(): void {
		self::$options    = array();
		self::$transients = array();
		self::$filters    = array();
		self::$requests   = array();
		self::$responses  = array();
		self::$routes     = array();
		self::$user       = null;
	}

	/** Queues an HTTP answer: [status, body array] or a WP_Error. */
	public static function answer( $response ): void {
		self::$responses[] = $response;
	}
}

class WP_Error {
	public function __construct( public string $code = '', public string $message = '', public $data = null ) {}
	public function get_error_message(): string { return $this->message; }
	public function get_error_code(): string { return $this->code; }
	public function get_error_data() { return $this->data; }
}

class WP_User {
	public array $roles = array();
	public function __construct( public int $ID = 0, public string $user_email = '', public string $display_name = '', public string $user_login = '', array $roles = array() ) {
		$this->roles = $roles;
	}
	public function exists(): bool { return $this->ID > 0; }
}

function is_wp_error( $thing ): bool { return $thing instanceof WP_Error; }
function __( string $text, string $domain = '' ): string { return $text; }
function esc_html__( string $text, string $domain = '' ): string { return $text; }
function esc_html( string $text ): string { return htmlspecialchars( $text ); }
function esc_url( string $url ): string { return $url; }
function wp_json_encode( $data ) { return json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); }
function untrailingslashit( string $s ): string { return rtrim( $s, '/\\' ); }
function trailingslashit( string $s ): string { return untrailingslashit( $s ) . '/'; }
function wpautop( string $s ): string { return $s; }
function home_url( string $path = '' ): string { return World::$home . $path; }
function admin_url( string $path = '' ): string { return World::$home . '/wp-admin/' . $path; }
function wp_parse_url( string $url, int $component = -1 ) { return parse_url( $url, $component ); }
function wp_generate_password( int $length = 12, bool $special = true ): string { return str_repeat( 'x', $length ); }
function is_multisite(): bool { return false; }
function is_super_admin( $id = 0 ): bool { return false; }
function plugin_basename( string $file ): string { return 'cinq-wp-central/' . basename( $file ); }
function rest_authorization_required_code(): int { return World::$user ? 403 : 401; }

function add_action( ...$args ): void {}
function add_filter( string $hook, callable $cb, int $priority = 10, int $args = 1 ): void { World::$filters[ $hook ][] = $cb; }
function apply_filters( string $hook, $value, ...$args ) {
	foreach ( World::$filters[ $hook ] ?? array() as $cb ) {
		$value = $cb( $value, ...$args );
	}
	return $value;
}

function get_option( string $name, $default = false ) { return World::$options[ $name ] ?? $default; }
function update_option( string $name, $value, $autoload = null ): bool { World::$options[ $name ] = $value; return true; }
function delete_option( string $name ): bool { unset( World::$options[ $name ] ); return true; }
function get_transient( string $name ) { return World::$transients[ $name ] ?? false; }
function set_transient( string $name, $value, int $ttl = 0 ): bool { World::$transients[ $name ] = $value; return true; }
function delete_transient( string $name ): bool { unset( World::$transients[ $name ] ); return true; }

function wp_get_current_user(): WP_User { return World::$user ?? new WP_User(); }
function current_user_can( string $cap ): bool {
	return null !== World::$user && in_array( 'administrator', World::$user->roles, true );
}

function register_rest_route( string $ns, string $route, array $args ): void {
	World::$routes[] = array( 'route' => $route ) + $args;
}

function wp_remote_request( string $url, array $args ) {
	World::$requests[] = array( 'url' => $url ) + $args;
	$next = array_shift( World::$responses ) ?? array( 200, array() );
	if ( $next instanceof WP_Error ) {
		return $next;
	}
	return array( 'response' => array( 'code' => $next[0] ), 'body' => json_encode( $next[1] ) );
}
function wp_remote_get( string $url, array $args = array() ) { return wp_remote_request( $url, $args ); }
function wp_remote_retrieve_response_code( $r ) { return $r['response']['code'] ?? 0; }
function wp_remote_retrieve_body( $r ) { return $r['body'] ?? ''; }
