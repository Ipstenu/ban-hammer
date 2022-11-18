<?php
/*
Plugin Name: Ban Hammer
Plugin URI: http://halfelf.org/plugins/ban-hammer/
Description: Prevent people from registering with any email you list.
Version: 3.1.2
Author: Mika Epstein
Author URI: http://halfelf.org/
Network: true
Text Domain: ban-hammer

Copyright 2009-23 Mika Epstein (email: ipstenu@halfelf.org)

	This file is part of Ban Hammer, a plugin for WordPress.

	Ban Hammer is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 2 of the License, or
	(at your option) any later version.

	Ban Hammer is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with WordPress.  If not, see <http://www.gnu.org/licenses/>.

*/

class BanHammer {

	/*
	 * Starter defines and vars for use later
	 *
	 * @since 2.6
	 */

	// Holds option data.
	public $option_name = 'banhammer_options';
	public $option_defaults;

	// Instance
	public static $instance;

	/**
	 * Construct
	 *
	 * @since 2.5
	 * @access public
	 */
	public function __construct() {

		// Allow this instance to be called from outside the class:
		self::$instance = $this;

		add_action( 'init', array( &$this, 'init' ) );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );

		// Add admin panel:
		if ( is_multisite() ) {
			add_action( 'network_admin_menu', array( &$this, 'network_admin_menu' ) );
			add_action( 'current_screen', array( &$this, 'network_admin_screen' ) );
		} else {
			add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
		}

		// Setting plugin defaults here:
		$this->option_defaults = array(
			'redirect'     => 'no',
			'redirect_url' => 'https://example.com',
			'message'      => __( '<strong>ERROR</strong>: Your email has been banned from registration.', 'ban-hammer' ),
		);
	}

	/**
	 * Admin init Callback
	 *
	 * @since 2.6
	 */
	public function admin_init() {

		// Links to Plugin Details.
		add_filter( 'plugin_row_meta', array( &$this, 'plugin_links' ), 10, 2 );
		$plugin = plugin_basename( __FILE__ );

		// Register Settings
		$this->register_settings();
	}

	/**
	 * Init
	 *
	 * @since 2.5
	 * @access public
	 */
	public function init() {
		// Filter for if multisite but NOT BuddyPress
		if ( is_multisite() && ! defined( 'BP_PLUGIN_DIR' ) ) {
			add_filter( 'wpmu_validate_user_signup', array( &$this, 'wpmu_validation' ), 99 );
		}

		if ( defined( 'BP_PLUGIN_DIR' ) ) {
			add_filter( 'bp_core_validate_user_signup', array( &$this, 'buddypress_signup' ) );
		}

		// The magic sauce
		add_action( 'register_post', array( &$this, 'banhammer_drop' ), 1, 3 );
	}

	public function get_options() {
		// Fetch and set up options.
		$options = wp_parse_args( get_site_option( $this->option_name ), $this->option_defaults, false );

		// Return
		return $options;
	}

	/**
	 * Get Keylist
	 *
	 * @since 3.0
	 * @return string - list of blocked/disallowed keys.
	 */
	public function get_keylist() {
		if ( is_multisite() ) {
			$keylist = get_site_option( 'banhammer_keys', 'spammer@example.com', false );
		} else {
			$keylist = get_option( 'disallowed_keys' );
		}

		return $keylist;
	}

	/**
	 * BuddyPress Signup Filter
	 *
	 * @since 2.6
	 * @access public
	 */
	public function buddypress_signup( $result ) {
		$options = $this->get_options();
		if ( $this->banhammer_drop( $result['user_name'], $result['user_email'], $result['errors'] ) ) {
			$result['errors']->add( 'user_email', $options['message'] );
		}
		return $result;
	}

	/**
	 * Admin Notices
	 *
	 * Display admin notices as needed.
	 *
	 * @since 2.6
	 */
	public function admin_notices() {
		printf( '<div class="notice notice-%1$s"><p>%2$s</p></div>', esc_attr( $this->notice_class ), wp_kses_post( $this->notice_message ) );
	}

	/**
	 * Admin Menu
	 *
	 * @since 2.5
	 * @access public
	 */
	public function admin_menu() {
		add_management_page( __( 'Ban Hammer', 'ban-hammer' ), __( 'Ban Hammer', 'ban-hammer' ), 'moderate_comments', 'ban-hammer', array( &$this, 'options' ) );
	}

	/**
	 * Network Admin Menu
	 *
	 * @since 2.5.1
	 * @access public
	 */
	public function network_admin_menu() {
		add_submenu_page( 'settings.php', __( 'Ban Hammer', 'ban-hammer' ), __( 'Ban Hammer', 'ban-hammer' ), 'manage_networks', 'ban-hammer', array( &$this, 'options' ) );
	}

	/**
	 * Network Admin Screen Callback
	 *
	 * @since 3.0
	 */
	public function network_admin_screen() {
		$current_screen = get_current_screen();
		if ( 'settings_page_ban-hammer-network' === $current_screen->id ) {

			if ( isset( $_POST['update'] ) && check_admin_referer( 'banhammer_networksave' ) ) {
				$options = $this->get_options();
				$keylist = $this->get_keylist();

				// Message.
				$output['message'] = ( $_POST['message'] !== $options['message'] ) ? wp_kses_post( $_POST['message'] ) : $options['message'];

				// Redirect.
				$output['redirect']     = ( ! isset( $_POST['redirect'] ) || is_null( $_POST['redirect'] ) || '0' === $_POST['redirect'] || 'no' === $_POST['redirect'] ) ? 'no' : 'yes';
				$output['redirect_url'] = ( $_POST['redirect_url'] !== $options['redirect_url'] ) ? sanitize_url( $_POST['redirect_url'] ) : $options['redirect_url'];

				// Banned List.
				if ( empty( $_POST['bannedlist'] ) ) {
					update_site_option( 'banhammer_keys', '' );
				} elseif ( $_POST['bannedlist'] !== $keylist ) {
					$new_bannedlist = array_map( 'sanitize_text_field', explode( "\n", $_POST['bannedlist'] ) );
					$new_bannedlist = array_filter( array_map( 'trim', $new_bannedlist ) );
					$new_bannedlist = array_unique( $new_bannedlist );
					$new_bannedlist = implode( "\n", $new_bannedlist );
					update_site_option( 'banhammer_keys', $new_bannedlist );
				}
				unset( $_POST['bannedlist'] );

				$this->get_options = $output;
				update_site_option( $this->option_name, $output );

				?>
				<div class="notice notice-success is-dismissible"><p><strong><?php esc_html_e( 'Options Updated!', 'ban-hammer' ); ?></strong></p></div>
				<?php
			}
		}
	}

	/**
	 * Register Admin Settings
	 *
	 * @since 2.6
	 */
	public function register_settings() {
		register_setting( 'ban-hammer', 'banhammer_options', array( &$this, 'banhammer_sanitize' ) );

		// The main section
		add_settings_section( 'banhammer-settings', '', array( &$this, 'banhammer_settings_callback' ), 'ban-hammer-settings' );

		// The Fields
		add_settings_field( 'message', __( 'Blocked Message', 'ban-hammer' ), array( &$this, 'message_callback' ), 'ban-hammer-settings', 'banhammer-settings' );
		add_settings_field( 'redirect', __( 'Redirect Blocked Users?', 'ban-hammer' ), array( &$this, 'redirect_callback' ), 'ban-hammer-settings', 'banhammer-settings' );
		add_settings_field( 'bannedlist', __( 'The Blocked List', 'ban-hammer' ), array( &$this, 'bannedlist_callback' ), 'ban-hammer-settings', 'banhammer-settings' );
	}

	/**
	 * Ban Hammer Settings Callback
	 *
	 * @since 2.6
	 */
	public function banhammer_settings_callback() {
		?>
		<p><?php esc_html_e( 'Customize your Ban Hammer experience via the settings below.', 'ban-hammer' ); ?></p>
		<?php
	}

	/**
	 * The Banned List Callback
	 *
	 * @since 2.6
	 */
	public function bannedlist_callback() {
		$keylist = $this->get_keylist();
		?>
		<p><?php esc_html_e( 'The terms below will not be allowed to be used during registration. You can add in full emails (i.e. foo@example.com) or domains (i.e. @domain.com), and partials (i.e. viagra). Wildcards (i.e. *) will not work.', 'ban-hammer' ); ?></p>
		<p><textarea name="banhammer_options[bannedlist]" id="banhammer_options[bannedlist]" cols="40" rows="15"><?php echo esc_textarea( $keylist ); ?></textarea></p>
		<?php
	}

	/**
	 * Message Callback
	 *
	 * @since 2.6
	 */
	public function message_callback() {
		$options = $this->get_options();
		?>
		<p><?php esc_html_e( 'The message below is displayed to users who are not allowed to register on your blog. Edit is as you see fit, but remember you don\'t get a lot of space so keep it simple.', 'ban-hammer' ); ?></p>
		<p><textarea name="banhammer_options[message]" id="banhammer_options[message]" cols="80" rows="2"><?php echo esc_html( $options['message'] ); ?></textarea></p>
		<?php
	}

	/**
	 * Redirect Callback
	 *
	 * @since 2.6
	 */
	public function redirect_callback() {
		$options = $this->get_options();
		?>
		<p><?php esc_html_e( 'If you\'d rather redirect users to a custom URL, please check the box below. If you do, the message above will not show.', 'ban-hammer' ); ?></p>
		<p><input type="checkbox" id="banhammer_options[redirect]" name="banhammer_options[redirect]" value="yes" <?php checked( $options['redirect'], 'yes', true ); ?> <?php checked( $options['redirect'], '1', true ); ?> >
		<label for="banhammer_options[redirect]"><?php esc_html_e( 'Redirect failed logins to a custom URL.', 'ban-hammer' ); ?></label></p>

		<?php
		if ( isset( $options['redirect'] ) && 'no' !== $options['redirect'] && ! empty( $options['redirect'] ) ) {
			?>
			<p><textarea name="banhammer_options[redirect_url]" id="banhammer_options[redirect_url]" cols="60" rows="1"><?php echo esc_url( $options['redirect_url'] ); ?></textarea>
			<br /><span class="description"><?php esc_html_e( 'Set redirect URL (example: http://example.com).', 'ban-hammer' ); ?></span></p>
			<?php
		}
	}

	/**
	 * Options
	 *
	 * The options page. Since this has to run on Multisite, it can't use the settings API.
	 *
	 * @since 1.0
	 * @access public
	 */
	public function options() {
		?>
		<div class="wrap">

		<h1><?php esc_html_e( 'Ban Hammer', 'ban-hammer' ); ?></h1>

		<?php
		settings_errors();

		if ( is_network_admin() ) {
			?>
			<form method="post" width='1'>
			<?php
			wp_nonce_field( 'banhammer_networksave' );
		} else {
			?>
			<form action="options.php" method="POST" >
			<?php
			settings_fields( 'ban-hammer' );
		}
				do_settings_sections( 'ban-hammer-settings' );
				submit_button( '', 'primary', 'update' );
		?>
			</form>
		</div>
		<?php
	}

	/**
	 * Options sanitization and validation
	 * @param  array $input Data submitted
	 * @return array        Sanitized data
	 * @since  2.6
	 */
	public function banhammer_sanitize( $input ) {
		// Get current options
		$options = $this->get_options();
		$keylist = $this->get_keylist();

		// Message
		$output['message'] = ( $input['message'] !== $options['message'] ) ? wp_kses_post( $input['message'] ) : $options['message'];

		// Redirect
		$output['redirect']     = ( ! isset( $input['redirect'] ) || is_null( $input['redirect'] ) || '0' === $input['redirect'] ) ? 'no' : 'yes';
		$output['redirect_url'] = ( isset( $input['redirect_url'] ) && $input['redirect_url'] !== $options['redirect_url'] ) ? sanitize_url( $input['redirect_url'] ) : $options['redirect_url'];

		// Banned List (not saved in the Ban Hammer options)
		if ( empty( $input['bannedlist'] ) ) {
			update_option( 'disallowed_keys', '' );
		} elseif ( $input['bannedlist'] !== $keylist ) {
			$new_bannedlist = explode( "\n", $input['bannedlist'] );
			$new_bannedlist = array_filter( array_map( 'trim', $new_bannedlist ) );
			$new_bannedlist = array_unique( $new_bannedlist );
			foreach ( $new_bannedlist as &$keyname ) {
				$keyname = sanitize_text_field( $keyname );
			}
			$new_bannedlist = implode( "\n", $new_bannedlist );
			update_option( 'disallowed_keys', $new_bannedlist );
		}

		return $output;
	}

	/**
	 * Ban Hammer
	 *
	 * Here's the basic plugin for WordPress SANS BuddyPress
	 *
	 * @since 1.0
	 * @access public
	 */
	public function banhammer_drop( $user_login, $user_email, $errors ) {
		$options           = $this->get_options();
		$bannedlist_string = $this->get_keylist();
		$bannedlist_array  = explode( "\n", $bannedlist_string );
		$bannedlist_size   = count( $bannedlist_array );

		// Go through bannedlist
		for ( $i = 0; $i < $bannedlist_size; $i++ ) {
			$bannedlist_current = trim( $bannedlist_array[ $i ] );
			if ( stripos( $user_email, $bannedlist_current ) !== false ) {

				$errors->add( 'invalid_email', $options['message'] );
				if ( 'yes' === $options['redirect'] ) {
					wp_safe_redirect( $options['redirect_url'] );
				} else {
					return true;
				}
			}
		}
	}

	/**
	 * Validation
	 *
	 * Validate the keys for Multisite
	 *
	 * @since 1.0
	 * @access public
	 */
	public function wpmu_validation( $result ) {
		if ( $this->banhammer_drop( sanitize_user( $_POST['user_name'] ), sanitize_email( $_POST['user_email'] ), $result['errors'] ) ) {
			$result['errors']->add( 'user_email', $output['message'] );
		}
		return $result;
	}

	/**
	 * Plugin links
	 *
	 * Adds link to donate and settings on the plugin list page.
	 *
	 * @access public
	 */
	public function plugin_links( $links, $file ) {
		if ( plugin_basename( __FILE__ ) === $file ) {

			// Determine settings links based on Multisite or not...
			if ( is_multisite() ) {
				$settings_url = network_admin_url( 'settings.php?page=ban-hammer' );
			} else {
				$settings_url = admin_url( 'tools.php?page=ban-hammer' );
			}

			// Settings Link:
			$settings = '<a href="' . $settings_url . '">' . __( 'Settings', 'ban-hammer' ) . '</a>';
			$links[]  = $settings;

			// Donation Link:
			$donate  = '<a href="https://ko-fi.com/A236CEN/">' . __( 'Donate', 'ban-hammer' ) . '</a>';
			$links[] = $donate;
		}

		return $links;
	}

}

new BanHammer();
