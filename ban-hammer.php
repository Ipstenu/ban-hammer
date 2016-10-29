<?php
/*
Plugin Name: Ban Hammer
Plugin URI: http://halfelf.org/plugins/ban-hammer/
Description: This plugin prevent people from registering with any email you list.
Version: 2.5.4
Author: Mika Epstein
Author URI: http://halfelf.org/
Network: true
Text Domain: ban-hammer

Copyright 2009-16 Mika Epstein (email: ipstenu@halfelf.org)

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

	var $buddypress;

	/**
	 * Construct
	 *
	 * @since 2.5
	 * @access public
	 */
	public function __construct() {
		if ( version_compare( floatval( get_bloginfo( 'version' ) ), '3.4', '<=' ) ) {
			add_action( 'admin_init', array( &$this, 'plugin_deactivate' ) );
		    add_action( 'admin_notices', array( &$this, 'plugin_deactivate_notice' ) );
		} else {
			if (defined('BP_PLUGIN_DIR')) {
				$this->buddypress = 1;
			} else {
				$this->buddypress = 0;
			}
		    add_action( 'init', array( &$this, 'init' ) );
		    load_plugin_textdomain( 'ban-hammer' );
		}
	}

	/**
	 * plugin_deactivate
	 *
	 * Deactive the plugin if called.
	 *
	 * @since 2.5
	 * @access public
	 */
	public function plugin_deactivate() {
		deactivate_plugins( plugin_basename( __FILE__ ) );
	}

	/**
	 * plugin_deactivate_message
	 *
	 * Why we deactivated the plugin
	 *
	 * @since 2.5
	 * @access public
	 */
	public function plugin_deactivate_notice() {
		echo '<div class="updated"><p><strong>BanHammer</strong> will not run on WordPress installs older than 3.4; the plug-in has been <strong>deactivated</strong>.</p></div>';
		if ( isset( $_GET['activate'] ) )
			unset( $_GET['activate'] );
	}

	/**
	 * Init
	 *
	 * @since 2.5
	 * @access public
	 */
    public function init() {
		// Admin Menus
		if( is_multisite() ) {
			add_action( 'network_admin_menu', array( $this, 'network_admin_menu'));
		} else {
			add_action( 'admin_menu', array( $this, 'admin_menu'));
		}

		// Filter for if multisite but NOT BuddyPress
		if( is_multisite() && ($this->buddypress == 0) ) {
		    add_filter('wpmu_validate_user_signup', array(&$this, 'validation') , 99);
		}

		// If BuddyPress, we have to do something extra
		add_action( 'bp_include', array(&$this, 'buddypress_init') );

		// The magic sauce
		add_action('register_post', array(&$this, 'banhammer'), 1, 3);
		register_activation_hook( __FILE__, array(&$this, 'activate') );

	    //Woocommerce Support
	    add_filter('woocommerce_registration_errors', array(&$this, 'woocommerce_validation'), 1);

		// Settings links
		add_filter('plugin_row_meta', array(&$this, 'donate_link'), 10, 2);
		$plugin = plugin_basename(__FILE__);
		add_filter("plugin_action_links_$plugin", array(&$this, 'settings_link') );
	}

	/**
	 * Admin Menu
	 *
	 * @since 2.5
	 * @access public
	 */
    public function admin_menu(){
		add_management_page( __('Ban Hammer', 'ban-hammer'), __('Ban Hammer', 'ban-hammer'), 'moderate_comments', 'ban-hammer', array(&$this,'options') );
	}

	/**
	 * Network Admin Menu
	 *
	 * @since 2.5.1
	 * @access public
	 */
    public function network_admin_menu(){
		add_submenu_page('settings.php', __('Ban Hammer', 'ban-hammer'), __('Ban Hammer', 'ban-hammer'), 'manage_networks', 'ban-hammer', array(&$this,'options') );
	}

	/**
	 * Banhammer
	 *
	 * Here's the basic plugin for WordPress SANS BuddyPress
	 *
	 * @since 1.0
	 * @access public
	 */
	public function banhammer($user_login, $user_email, $errors) {
        if( is_multisite() ) {
            $the_blacklist = get_site_option('banhammer_keys');
        } else {
            $the_blacklist = get_option('blacklist_keys');
        }

        $blacklist_string = $the_blacklist;
        $blacklist_array = explode("\n", $blacklist_string);
        $blacklist_size = sizeof($blacklist_array);

        // Go through blacklist
        for($i = 0; $i < $blacklist_size; $i++) {
            $blacklist_current = trim($blacklist_array[$i]);
            if(stripos($user_email, $blacklist_current) !== false) {
                $errors->add('invalid_email', __( get_option('banhammer_message') ));
                return;
            }
        }
	}

	/**
	 * Validation
	 *
	 * Validate the keys
	 *
	 * @since 1.0
	 * @access public
	 */
	public function validation($result) {

	    $the_blacklist = get_site_option('banhammer_keys');
	    $blacklist_string = $the_blacklist;
	    $blacklist_array = explode("\n", $blacklist_string);
	    $blacklist_size = sizeof($blacklist_array);

	    $data = sanitize_email($_POST['user_email']);

	    // Go through blacklist
	    for($i = 0; $i < $blacklist_size; $i++) {
	            $blacklist_current = trim($blacklist_array[$i]);
	            if(stripos($data, $blacklist_current) !== false) {
	                $result['errors']->add('invalid_email', __( get_site_option('banhammer_message') ));
	                echo '<p class="error">'.get_site_option('banhammer_message').'</p>';
	            }
	    }
	    return $result;
	}


	/**
	 * Validation Woocommerce login
	 *
	 * Validate the keys
	 *
	 * @since 1.0
	 * @access public
	 */
	public function woocommerce_validation($errors) {


		$the_blacklist = get_site_option('blacklist_keys');
		$blacklist_string = $the_blacklist;
		$blacklist_array = explode("\n", $blacklist_string);
		$blacklist_size = sizeof($blacklist_array);

		$data = sanitize_email($_POST['email']);

		// Go through blacklist
		for($i = 0; $i < $blacklist_size; $i++) {
			$blacklist_current = trim($blacklist_array[$i]);
			if(stripos($data, $blacklist_current) !== false) {
				$errors->add( 'blocked', __( get_site_option('banhammer_message') ));
			}
		}
		return $errors;
	}



	/**
	 * Activate
	 *
	 * Setting defaults
	 *
	 * @since 1.0
	 * @access public
	 */
	public function activate() {
	    if( is_multisite() ) {
			add_site_option('banhammer_keys','spammer@example.com');
			add_site_option('banhammer_message', '<strong>ERROR</strong>: Your email has been banned from registration.');
	    } else {
	        add_option('banhammer_message', '<strong>ERROR</strong>: Your email has been banned from registration.');
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
	    ?><div class="wrap">
	    <h1><?php _e("Ban Hammer", 'ban-hammer'); ?></h1>

        <?php
        if ( isset($_POST['update']) && check_admin_referer( 'banhammer_saveit') ) {
			// Update the Blacklist
			if ($new_blacklist = $_POST['blacklist_keys'])	{
				$new_blacklist = explode( "\n", $new_blacklist );
				$new_blacklist = array_filter( array_map( 'trim', $new_blacklist ) );
				$new_blacklist = array_unique( $new_blacklist );

				// Sanitize emails!
				foreach ($new_blacklist as &$keyname) {
				    $keyname = sanitize_text_field($keyname);
				}

				$new_blacklist = implode( "\n", $new_blacklist );

				if( is_multisite() ) {
					update_site_option('banhammer_keys', $new_blacklist);
				} else {
	            		update_option('blacklist_keys', $new_blacklist);
				}
			} elseif ( empty($_POST['blacklist_keys']) ) {
				if( is_multisite() ) {
					update_site_option('banhammer_keys', '');
				} else {
	            		update_option('blacklist_keys', '');
				}
			}

			// Update Ban Message
			if ( $new_message = wp_kses_post($_POST['new_message']) ) {
				if( is_multisite() ) {
					update_site_option('banhammer_message', $new_message);
				} else {
					update_option('banhammer_message', $new_message);
				}
			}
        ?>
	        <div id="message" class="updated fade"><p><strong><?php _e('Options Updated!', 'ban-hammer'); ?></strong></p></div>
	<?php
			}

	if( is_multisite() ) {
	    $the_blacklist = get_site_option('banhammer_keys');
	    $the_message = get_site_option('banhammer_message');
	} else {
	    $the_blacklist = get_option('blacklist_keys');
	    $the_message = get_option('banhammer_message');
	}

	?>

	        <form method="post" width='1'>
	        <?php wp_nonce_field( 'banhammer_saveit' ); ?>

	        <fieldset class="options">
	        <legend><h3><?php _e('Personalize the Message', 'ban-hammer'); ?></h3></legend>
	        <p><?php _e('The message below is displayed to users who are not allowed to register on your blog. Edit is as you see fit, but remember you don\'t get a lot of space so keep it simple.', 'ban-hammer'); ?></p>

	        <textarea name='new_message' cols='80' rows='2'><?php echo esc_html( $the_message ); ?></textarea>
	        </fieldset>

	        <fieldset class="options">
	        <legend><h3><?php _e('Blacklisted Emails', 'ban-hammer'); ?></h3></legend>
	        <p><?php _e('The emails and domains added below will not be allowed to be used during registration. You can add in full emails (i.e. foo@example.com) or domains (i.e. @domain.com), but not partials past that.', 'ban-hammer'); ?></p>

	        <textarea name="blacklist_keys" cols="40" rows="15"><?php
	                echo esc_textarea($the_blacklist);
	        ?></textarea>
	        </fieldset>
	                <p class="submit"><input class='button-primary' type='submit' name='update' value='<?php _e("Update Options", 'ban-hammer'); ?>' id='submitbutton' /></p>

	        </form>

	        </div>
	<?php
	    }

	/**
	 * Donate link
	 *
	 * Adds link to donate on the plugins page
	 *
	 * @access public
	 */
	public function donate_link($links, $file) {
	        if ($file == plugin_basename(__FILE__)) {
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

/**
 * BuddyPress Initialization
 *
 * Due to how BuddyPress Works, I had to break this out. See the link for why.
 * http://codex.buddypress.org/plugin-development/checking-buddypress-is-active/
 * I don't know why it won't work in the singleton and getting it working again for people
 * is more important right now.
 *
 * @since 1.5
 * @access public
 */

add_action( 'bp_include', 'banhammer_bp_init' );

function banhammer_bp_init() {
    function banhammer_bp_signup( $result ) {
    	if ( banhammer_bp_bademail( $result['user_email'] ) )
    		$result['errors']->add('user_email',  __( get_option('banhammer_message') ) );
    	return $result;
    }
    add_filter( 'bp_core_validate_user_signup', 'banhammer_bp_signup' );

    function banhammer_bp_bademail( $user_email ) {

        if( is_multisite() ) {
            $banhammer_blacklist = get_site_option('banhammer_keys');
        } else {
            $banhammer_blacklist = get_option('blacklist_keys');
        }

        // Get blacklist
        $blacklist_string = $banhammer_blacklist;
        $blacklist_array = explode("\n", $blacklist_string);
        $blacklist_size = sizeof($blacklist_array);

        // Go through blacklist
        for($i = 0; $i < $blacklist_size; $i++) {
            $blacklist_current = trim($blacklist_array[$i]);
            if(stripos($user_email, $blacklist_current) !== false) {
                return true;
            }
        }
    }
}