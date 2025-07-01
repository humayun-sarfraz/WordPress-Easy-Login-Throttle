<?php
/**
 * Plugin Name:     WordPress Easy Login Throttle
 * Plugin URI:      https://github.com/humayun-sarfraz/wp-easy-login-throttle
 * Description:     Adds a simple limit (e.g. 3 tries in 5 minutes) to the login form before temporarily locking out an IP.
 * Version:         1.0.0
 * Author:          Humayun Sarfraz
 * Author URI:      https://github.com/humayun-sarfraz
 * Text Domain:     wp-easy-login-throttle
 * Domain Path:     /languages
 * License:         GPLv2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 */

defined('ABSPATH') || exit;

if (! class_exists('WP_Easy_Login_Throttle_Core', false)) {

	final class WP_Easy_Login_Throttle_Core {

		/** @var WP_Easy_Login_Throttle_Core */
		private static $instance;

		/** Default max attempts */
		private $max_attempts = 3;

		/** Time window in seconds */
		private $time_window = 300;

		/** Transient key prefix */
		private $transient_prefix = 'we_lt_';

		/**
		 * Get or create singleton instance.
		 */
		public static function instance(): self {
			if (null === self::$instance) {
				self::$instance = new self();
				self::$instance->init_hooks();
			}
			return self::$instance;
		}

		/** Private constructor. */
		private function __construct() {
			add_action('plugins_loaded', [ $this, 'load_textdomain' ]);
		}

		/** Register hooks. */
		private function init_hooks(): void {
			add_filter( 'authenticate',        [ $this, 'maybe_block_login' ], 1, 3 );
			add_action( 'wp_login_failed',     [ $this, 'login_failed' ] );
			add_action( 'wp_login',            [ $this, 'login_success' ], 10, 2 );
		}

		/** Load translations. */
		public function load_textdomain(): void {
			load_plugin_textdomain(
				'wp-easy-login-throttle',
				false,
				dirname(plugin_basename(__FILE__)) . '/languages/'
			);
		}

		/**
		 * Block login if too many attempts.
		 *
		 * @param WP_User|WP_Error|null $user     User or null.
		 * @param string                $username Username.
		 * @param string                $password Password.
		 * @return WP_User|WP_Error|null
		 */
		public function maybe_block_login( $user, $username, $password ) {
			$ip    = $this->get_ip();
			if ( ! $ip ) {
				return $user;
			}

			$key   = $this->transient_prefix . md5( $ip );
			$count = intval( get_transient( $key ) );
			$limit = absint( apply_filters( 'we_lt_max_attempts', $this->max_attempts ) );

			if ( $count >= $limit ) {
				$minutes = ceil( apply_filters( 'we_lt_time_window', $this->time_window ) / 60 );
				return new WP_Error(
					'too_many_attempts',
					sprintf(
						/* translators: %d: number of minutes */
						__( 'Too many login attempts. Please try again in %d minutes.', 'wp-easy-login-throttle' ),
						$minutes
					)
				);
			}

			return $user;
		}

		/**
		 * Record a failed login attempt.
		 *
		 * @param string $username Username attempted.
		 */
		public function login_failed( $username ): void {
			$ip    = $this->get_ip();
			if ( ! $ip ) {
				return;
			}

			$key     = $this->transient_prefix . md5( $ip );
			$count   = intval( get_transient( $key ) );
			$window  = absint( apply_filters( 'we_lt_time_window', $this->time_window ) );

			$count++;
			set_transient( $key, $count, $window );
		}

		/**
		 * Clear the attempt counter on successful login.
		 *
		 * @param string   $user_login Username.
		 * @param WP_User  $user       WP_User object.
		 */
		public function login_success( $user_login, $user ): void {
			$ip  = $this->get_ip();
			if ( ! $ip ) {
				return;
			}

			$key = $this->transient_prefix . md5( $ip );
			delete_transient( $key );
		}

		/**
		 * Retrieve the user’s IP address.
		 *
		 * @return string|null
		 */
		private function get_ip(): ?string {
			if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
				return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
			}
			return null;
		}
	}

	// Initialize the plugin
	WP_Easy_Login_Throttle_Core::instance();
}
