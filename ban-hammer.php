<?php
/*
Plugin Name: Ban Hammer
Plugin URI: http://halfelf.org/plugins/ban-hammer/
Description: This plugin prevent people from registering with any email you list.
Version: 2.6
Author: Mika Epstein
Author URI: http://halfelf.org/
Network: true
Text Domain: ban-hammer

Copyright 2009-17 Mika Epstein (email: ipstenu@halfelf.org)

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
    var $option_name = 'banhammer_options';
    var $option_defaults;
    
    // DB version, for schema upgrades.
    var $db_version = 1;

	// Constants
	var $buddypress;
	var $blacklist;

	// Instance
	static $instance;

	/**
	 * Construct
	 *
	 * @since 2.5
	 * @access public
	 */
	public function __construct() {

	    //allow this instance to be called from outside the class
        self::$instance = $this;

		add_action( 'init', array( &$this, 'init' ) );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );

		//add admin panel
		if ( is_multisite() ) {
			add_action( 'network_admin_menu', array( &$this, 'network_admin_menu' ) );
			add_action( 'current_screen', array( &$this, 'network_admin_screen') );
		} else {
			add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
		}

    		// Setting plugin defaults here:
		$this->option_defaults = array( 
			'db_version' 		=> $this->db_version,
			'redirect' 			=> 'no', 
			'redirect_url' 		=> 'http://example.com',
			'message'			=> '<strong>ERROR</strong>: Your email has been banned from registration.',
		);

        // Fetch and set up options.
		$this->options = wp_parse_args( get_site_option( $this->option_name ), $this->option_defaults, false );

		if ( is_multisite() ) {
			$this->blacklist = get_site_option( 'banhammer_keys', 'spammer@example.com', false );
		} else {
			$this->blacklist = get_option( 'blacklist_keys' );
		}

	    // check if DB needs to be upgraded (this will merge old settings to new)
	    $naked_options = get_site_option( $this->option_name );
	    
	    if ( !isset( $naked_options['db_version'] ) || $naked_options['db_version'] < $this->db_version ) {
			if ( isset($old_b_message) && !is_null( $old_b_message ) ) { 
				$current_db_version = 0;
			} else {
				$current_db_version = isset( $naked_options['db_version'] ) ? $naked_options['db_version'] : 0; 
			}
	       $this->upgrade( $current_db_version );
	    }
	}

	/**
	 * Init
	 *
	 * @since 2.5
	 * @access public
	 */
	public function init() {
		// Filter for if multisite but NOT BuddyPress
		if( is_multisite() && !defined( 'BP_PLUGIN_DIR' ) )
			add_filter( 'wpmu_validate_user_signup', array( &$this, 'wpmu_validation' ), 99);
		
		if ( defined( 'BP_PLUGIN_DIR' ) )
			add_filter( 'bp_core_validate_user_signup', array( &$this, 'buddypress_signup' ) );

		// The magic sauce
		add_action( 'register_post', array( &$this, 'banhammer_drop' ), 1, 3);
	}

	/**
	 * BuddyPress Signup Filter
	 *
	 * @since 2.6
	 * @access public
	 */
	public function buddypress_signup( $result ) {
		if( $this->banhammer_drop( $result['user_name'] , $result['user_email'], $result['errors'] ) )
			$result['errors']->add( 'user_email', $this->options['message'] );
		return $result;
	}
	
	/**
	 * Admin init Callback
	 *
	 * @since 2.6
	 */
    function admin_init() {	

		// Settings links
		add_filter( 'plugin_row_meta', array( &$this, 'donate_link' ), 10, 2);
		$plugin = plugin_basename(__FILE__);
		add_filter("plugin_action_links_$plugin", array( &$this, 'settings_link' ) );

	    // Register Settings
		$this->register_settings();
		
		// Warn if Registration isn't active
		if ( !get_option( 'users_can_register' ) ) {
			$this->notice_message = __('Registration is disabled. Ban Hammer will not work.', 'ban-hammer');
			$this->notice_class = 'warning';
			
			if ( is_multisite() ) {
				add_action( 'network_admin_notices', array( &$this, 'admin_notices' ), 10, 2 );
			} else {
				add_action( 'admin_notices', array( &$this, 'admin_notices' ), 10, 2 );
			}
		}
	}

    /**
	 * Admin Notices
	 *
	 * Display admin notices as needed.
	 *
	 * @since 2.6
	 */
	public function admin_notices () {
		printf( '<div class="notice notice-%1$s"><p>%2$s</p></div>', $this->notice_class, $this->notice_message ); 
	}

    /**
	 * Upgrades Database
	 *
	 * @param int $current_db_version the current DB version
	 * @since 2.6
	 */
	function upgrade( $current_db_version ) {
	    if ( $current_db_version < 1 ) {

		    // Migrate old options to new
	        $this->options['redirect'] = $this->option_defaults['redirect'];
	        $this->options['redirect_url'] = $this->option_defaults['redirect_url'];
	        $this->options['message'] = get_option('banhammer_message', $this->option_defaults['message']);
	        	$this->options['db_version'] = '1';
	        
	        // Delete old options
	        delete_site_option( 'banhammer_message' );
	    } 
		
		update_site_option( $this->option_name, $this->options );
    }


	/**
	 * Admin Menu
	 *
	 * @since 2.5
	 * @access public
	 */
	public function admin_menu (){
		add_management_page( __( 'Ban Hammer', 'ban-hammer' ), __( 'Ban Hammer', 'ban-hammer' ), 'moderate_comments', 'ban-hammer', array( &$this,'options' ) );
	}

	/**
	 * Network Admin Menu
	 *
	 * @since 2.5.1
	 * @access public
	 */
	public function network_admin_menu(){
		add_submenu_page( 'settings.php', __( 'Ban Hammer', 'ban-hammer' ), __( 'Ban Hammer', 'ban-hammer' ), 'manage_networks', 'ban-hammer', array( &$this, 'options' ) );
	}

	/**
	 * Network Admin Screen Callback
	 *
	 * @since 3.0
	 */

	function network_admin_screen() {
	    $current_screen = get_current_screen();
	    if( $current_screen->id === "settings_page_ban-hammer-network" ) {
	
	        	if ( isset( $_POST['update'] ) && check_admin_referer( 'banhammer_networksave') ) {
				
				$options = $this->options;
				$input = $_POST['banhammer_options'];
				$output['db_version'] = $this->db_version;

				// Message
				if( $input['message'] !== $options['message'] && $input['message'] == wp_kses_post( $input['message'] ) ) {
					$output['message'] = wp_kses_post( $input['message'] );
				} else {
					$output['message'] = $options['message'];
				}

				// Blacklist
				if( empty( $input['blacklist'] ) ) {
					update_site_option( 'banhammer_keys', '' );
				} elseif( $input['blacklist'] != $this->blacklist ) {
					$new_blacklist = explode( "\n", $input['blacklist'] );
					$new_blacklist = array_filter( array_map( 'trim', $new_blacklist ) );
					$new_blacklist = array_unique( $new_blacklist );
					foreach( $new_blacklist as &$keyname ) {
						$keyname = sanitize_text_field( $keyname );
					}	
					$new_blacklist = implode( "\n", $new_blacklist );
					update_site_option( 'banhammer_keys', $new_blacklist);
				}
		        unset( $input['blacklist'] );
		
				// Redirect
				if ( !isset($input['redirect']) || is_null( $input['redirect'] ) || $input['redirect'] == '0' ) {
					$output['redirect'] = false;
				} else {
					$output['redirect'] = true;
				}
				
				// Redirect URL
				if ( isset($input['redirect_url']) && $input['redirect_url'] !== $options['redirect_url'] ) {
					$output['redirect_url'] = esc_url( $input['redirect_url'] );;
				} else {
					$output['redirect_url'] = $options['redirect_url'];		
				}
				
				$this->options = $output;
				update_site_option( $this->option_name, $output );

				?><div class="notice notice-success is-dismissible"><p><strong><?php _e('Options Updated!', 'ban-hammer'); ?></strong></p></div><?php
			}
	    }
	}

	/**
	 * Register Admin Settings
	 *
	 * @since 2.6
	 */
    function register_settings() {
	    register_setting( 'ban-hammer', 'banhammer_options', array( &$this, 'banhammer_sanitize' ) );

		// The main section
		add_settings_section( 'banhammer-settings', '', array( &$this, 'banhammer_settings_callback'), 'ban-hammer-settings' );

		// The Fields
		add_settings_field( 'message', __( 'Blocked Message' , 'ban-hammer' ), array( &$this, 'message_callback'), 'ban-hammer-settings', 'banhammer-settings' );
		add_settings_field( 'redirect', __( 'Redirect Blocked Users?' , 'ban-hammer' ), array( &$this, 'redirect_callback'), 'ban-hammer-settings', 'banhammer-settings' );
		add_settings_field( 'blacklist', __( 'The Blacklist' , 'ban-hammer' ), array( &$this, 'blacklist_callback'), 'ban-hammer-settings', 'banhammer-settings' );
	}

	/**
	 * Ban Hammer Settings Callback
	 *
	 * @since 2.6
	 */
	function banhammer_settings_callback() {
	    ?>
	    <p><?php _e('Customize your Ban Hammer experience via the settings below.', 'ban-hammer'); ?></p>
	    <?php
	}

	/**
	 * The Blacklist Callback
	 *
	 * @since 2.6
	 */
	function blacklist_callback() {
		if ( is_multisite() ) {
			$blacklist = get_site_option( 'banhammer_keys' );
		} else {
			$blacklist = get_option( 'blacklist_keys' );
		}

		?>
			<p><?php _e('The terms below will not be allowed to be used during registration. You can add in full emails (i.e. foo@example.com) or domains (i.e. @domain.com), and partials (i.e. viagra). Wildcards (i.e. *) will not work.', 'ban-hammer'); ?></p>			
			<p><textarea name="banhammer_options[blacklist]" id="banhammer_options[blacklist]" cols="40" rows="15"><?php
				echo esc_textarea($blacklist);
			?></textarea></p>
		<?php
	}

	/**
	 * Message Callback
	 *
	 * @since 2.6
	 */
	function message_callback() {
		?>
			<p><?php _e( 'The message below is displayed to users who are not allowed to register on your blog. Edit is as you see fit, but remember you don\'t get a lot of space so keep it simple.', 'ban-hammer' ); ?></p>
			<p><textarea name="banhammer_options[message]" id="banhammer_options[message]" cols="80" rows="2"><?php echo esc_html( $this->options['message'] ); ?></textarea></p>
		<?php
	}

	/**
	 * Redirect Callback
	 *
	 * @since 2.6
	 */
	function redirect_callback() {
		?>
		<p><?php _e( 'If you\'d rather redirect users to a custom URL, please check the box below. If you do, the message above will not show.', 'ban-hammer' ); ?></p>
		<p><input type="checkbox" id="banhammer_options[redirect]" name="banhammer_options[redirect]" value="yes" <?php checked( $this->options['redirect'], 'yes', true ); ?> <?php checked( $this->options['redirect'], '1', true ); ?> >
		<label for="banhammer_options[redirect]"><?php _e('Redirect failed logins to a custom URL.', 'ban-hammer'); ?></label></p>

		<?php
		if( isset( $this->options['redirect'] ) && $this->options['redirect'] !== 'no' && !empty( $this->options['redirect'] ) ) {
			?>
			<p><textarea name="banhammer_options[redirect_url]" id="banhammer_options[redirect_url]" cols="60" rows="1"><?php echo esc_url( $this->options['redirect_url'] ); ?></textarea>
			<br /><span class="description"><?php _e('Set redirect URL (example: http://example.com).', 'ban-hammer'); ?></span></p>
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
	function options() { 
		?>

		<div class="wrap">
			
		<h1><?php _e( 'Ban Hammer', 'ban-hammer' ); ?></h1>
		
		<?php settings_errors();
		
			if ( is_network_admin() ) {
				?><form method="post" width='1'><?php
					wp_nonce_field( 'banhammer_networksave' ); 
			} else {
				?><form action="options.php" method="POST" ><?php
					settings_fields('ban-hammer');
			}
				do_settings_sections( 'ban-hammer-settings' );
				submit_button( '', 'primary', 'update');
			    
			    ?></form>
		</div>
		<?php
	}

	/**
	 * Options sanitization and validation
	 *
	 * @param $input the input to be sanitized
	 * @since 2.6
	 */
	function banhammer_sanitize( $input ) {
		
		$options = $this->options;
		$input['db_version'] = $this->db_version;
				
		// Message
		if( $input['message'] !== $options['message'] && $input['message'] == wp_kses_post( $input['message'] ) ) {
			$input['message'] = wp_kses_post( $input['message'] );
		} else {
			$input['message'] = $options['message'];
		}
		
		// Blacklist
		if( empty( $input['blacklist'] ) ) {
			update_option( 'blacklist_keys', '' );
		} elseif( $input['blacklist'] != $this->blacklist ) {
			$new_blacklist = explode( "\n", $input['blacklist'] );
			$new_blacklist = array_filter( array_map( 'trim', $new_blacklist ) );
			$new_blacklist = array_unique( $new_blacklist );
			foreach( $new_blacklist as &$keyname ) {
				$keyname = sanitize_text_field( $keyname );
			}	
			$new_blacklist = implode( "\n", $new_blacklist );
			update_option('blacklist_keys', $new_blacklist);
		}
        unset( $input['blacklist'] );

		// Redirect		
		if ( !isset($input['redirect']) || is_null( $input['redirect'] ) || $input['redirect'] == '0' ) {
			$input['redirect'] = 'no';
		} else {
			$input['redirect'] = 'yes';
		}
		
		// Redirect URL
		if ( isset($input['redirect_url']) && $input['redirect_url'] !== $options['redirect_url'] ) {
			$input['redirect_url'] = esc_url( $input['redirect_url'] );;
		} else {
			$input['redirect_url'] = $options['redirect_url'];		
		}

		return $input;
	}

	/**
	 * Banhammer
	 *
	 * Here's the basic plugin for WordPress SANS BuddyPress
	 *
	 * @since 1.0
	 * @access public
	 */
	public function banhammer_drop($user_login, $user_email, $errors) {
		if( is_multisite() ) {
			$the_blacklist = get_site_option( 'banhammer_keys' );
		} else {
			$the_blacklist = get_option( 'blacklist_keys' );
		}

		$blacklist_string = $the_blacklist;
		$blacklist_array = explode("\n", $blacklist_string);
		$blacklist_size = sizeof($blacklist_array);

		// Go through blacklist
		for($i = 0; $i < $blacklist_size; $i++) {
			$blacklist_current = trim($blacklist_array[$i]);
			if( stripos($user_email, $blacklist_current) !== false ) {
				
				$errors->add( 'invalid_email', __( $this->options['message'] ));
				if ( $this->options['redirect'] == 'yes' ) {
					wp_redirect( $this->options['redirect_url'] );
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
	public function wpmu_validation($result) {
		if( $this->banhammer_drop( $_POST['user_name'], $_POST['user_email'], $result['errors'] ) )
			$result['errors']->add( 'user_email', $this->options['message'] );
		return $result;
	}

	/**
	 * Donate link
	 *
	 * Adds link to donate on the plugins page
	 *
	 * @access public
	 */
	public function donate_link($links, $file) {
			if( $file == plugin_basename(__FILE__)) {
					$donate_link = '<a href="https://store.halfelf.org/donate/">'.__("Donate", "ban-hammer").'</a>';
					$links[] = $donate_link;
			}
			return $links;
	}

	/**
	 * Settings link
	 *
	 * Adds link to settings page on the plugins page
	 *
	 * @access public
	 */
	public function settings_link($links) {
		if( is_multisite() ) {
			$settings_link = network_admin_url( 'settings.php?page=ban-hammer' );
		} else {
			$settings_link = admin_url( 'tools.php?page=ban-hammer' );
		}

		$settings_link = '<a href="'.$settings_link.'">'.__("Settings", "ban-hammer").'</a>';

		array_unshift($links, $settings_link);
		return $links;
	}

}

new BanHammer();