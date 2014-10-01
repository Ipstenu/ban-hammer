<?php
/*
Plugin Name: Ban Hammer
Plugin URI: http://halfelf.org/plugins/ban-hammer/
Description: This plugin prevent people from registering with any email you list.
Version: 2.4
Author: Mika Epstein
Author URI: http://halfelf.org/
Network: true
Text Domain: ban-hammer
Domain Path: /languages

Copyright 2009-14 Mika Epstein (email: ipstenu@halfelf.org)

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

// First we check to make sure you meet the requirements
global $wp_version;
$exit_msg_ver = 'Sorry, but this plugin is no longer supported on pre-3.4 WordPress installs.';
if (version_compare($wp_version,"3.4","<")) { exit($exit_msg_ver); }

// Quick BuddyPress Check
if (defined('BP_PLUGIN_DIR'))
	DEFINE('banhammer_buddypress',1);
else
	DEFINE('banhammer_buddypress',0);

// Languages
if ( !defined('ban-hammer')) {define('ban-hammer','ban-hammer');} // Translation
load_plugin_textdomain('ban-hammer', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

// Here's the basic plugin for WordPress SANS BuddyPress
function banhammer($user_login, $user_email, $errors) {

        if( is_multisite() ) { 
            $banhammer_blacklist = get_site_option('banhammer_keys');
        } else {
            $banhammer_blacklist = get_option('blacklist_keys');
        }

        $blacklist_string = $banhammer_blacklist;
        $blacklist_array = explode("\n", $blacklist_string);
        $blacklist_size = sizeof($blacklist_array);

        // Go through blacklist
        for($i = 0; $i < $blacklist_size; $i++)
        {
                $blacklist_current = trim($blacklist_array[$i]);
                if(stripos($user_email, $blacklist_current) !== false)
                {
                        $errors->add('invalid_email', __( get_option('banhammer_message') ));
                        return;
                }
        }
}

// And here's Multisite Crap

if( is_multisite() && ($banhammer_buddypress == 0) ) {
    add_filter('wpmu_validate_user_signup', 'banhammer_validation', 99);
}

function banhammer_validation($result) {

    $banhammer_blacklist = get_site_option('banhammer_keys');
    $blacklist_string = $banhammer_blacklist;
    $blacklist_array = explode("\n", $blacklist_string);
    $blacklist_size = sizeof($blacklist_array);

    $data = $_POST['user_email'];
    
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


// And here's the plugin for BuddyPress
// Due to how BuddyPress Works, I had to break this out. See the link for why.
// http://codex.buddypress.org/plugin-development/checking-buddypress-is-active/

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
            for($i = 0; $i < $blacklist_size; $i++)
            {
                    $blacklist_current = trim($blacklist_array[$i]);
                    if(stripos($user_email, $blacklist_current) !== false)
                    {
                     return true;
                    }
            }
    }
}

// Create the options for the message and spam assassin and set some defaults.
function banhammer_activate() {
    if( is_multisite() ) {
		add_site_option('banhammer_keys','spammer@example.com');
		add_site_option('banhammer_message', '<strong>ERROR</strong>: Your email has been banned from registration.');   
    } else {
        add_option('banhammer_message', '<strong>ERROR</strong>: Your email has been banned from registration.');   
    }
}

// Hooks
if( is_multisite() ) {
    add_action('network_admin_menu', 'banhammer_admin_add_page');
} else {
    add_action('admin_menu', 'banhammer_optionsmenu');
}
add_action('register_post', 'banhammer', 10, 3);

register_activation_hook( __FILE__, 'banhammer_activate' );

// donate link on manage plugin page
add_filter('plugin_row_meta', 'banhammer_donate_link', 10, 2);
function banhammer_donate_link($links, $file) {
        if ($file == plugin_basename(__FILE__)) {
                $donate_link = '<a href="https://store.halfelf.org/donate/">'.__("Donate", "ban-hammer").'</a>';
                $links[] = $donate_link;
        }
        return $links;
}

// add settings to manage plugin page
$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'banhammer_settings_link' );
function banhammer_settings_link($links) { 
    $settings_link = '<a href="options-discussion.php">'.__("Settings", "ban-hammer").'</a>'; 
    array_unshift($links, $settings_link); 
    return $links; 
}

// Options Pages

// add the admin options page

if( is_multisite() ) {
    
    function banhammer_admin_add_page() {
	   global $ippy_banhammer_options_page;
	   $ippy_banhammer_options_page = add_submenu_page('settings.php', __('Ban Hammer', 'ban-hammer'), __('Ban Hammer', 'ban-hammer'), 'manage_networks', 'ban-hammer', 'banhammer_options');
	   }
} else {
    function banhammer_optionsmenu() {
    add_submenu_page('tools.php', __('Ban Hammer', 'ban-hammer'), __('Ban Hammer', 'ban-hammer'), 'moderate_comments', 'ban-hammer', 'banhammer_options');
    }
  
}

function banhammer_plugin_help() {
	global $banhammer_options_page;
	$screen = get_current_screen();
	if ( $screen->id == 'tools_page_banhammer' || $screen->id == 'settings_page_banhammer-network' )
		
	$screen->add_help_tab( array(
		'id'      => 'ippy-banhammer-base',
		'title'   => __('Readme', 'ban-hammer'),
		'content' => 
		'<h3>' . __('Ban Hammer', 'ban-hammer') .'</h3>' .
		'<p>' . __( 'We\'ve all had this problem.  A group of spammers from mail.ru are registering to your blog, but you want to keep registration open.  How do you kill the spammers without bothering your clientele?  While you could edit your functions.php and block the domain, once you get past a few bad eggs, you have to escalate.', 'ban-hammer' ) . '</p>' . 
		'<p>' . __( 'Ban Hammer does that for you, preventing unwanted users from registering.', 'ban-hammer' ) . '</p>' . 
		'<p>' . __( 'On a single install of WP, instead of using its own database table, Ban Hammer pulls from your list of blacklisted emails from the Comment Blacklist feature, native to WordPress.  Since emails never equal IP addresses, it simply skips over and ignores them. On a network instance, there\'s a network wide setting for banned emails and domains. This means you only have <em>one</em> place to update and maintain your blacklist.  When a blacklisted user attempts to register, they get a customizable message that they cannot register.', 'ban-hammer' ) . '</p>' .
		'<p>' . __( 'Limited free support can be found in the WordPress forums.','ban-hammer').'</p>'.
		'<ul>'.
			'<li><a href="http://wordpress.org/support/plugin/ban-hammer">'. __( 'Support Forums','ban-hammer').'</a></li>'.
			'<li><a href="http://halfelf.org/my-plugins/ban-hammer/">'. __( 'Plugin Site','ban-hammer').'</a></li>'.
			'<li><a href="https://store.halfelf.org/donate/">'. __( 'Donate','ban-hammer').'</a></li>'.
		'</ul>'
	));

}
add_action('contextual_help', 'banhammer_plugin_help', 10, 3);

register_activation_hook( __FILE__, 'banhammer_activate' );

// Settings Page
function banhammer_options() {

        ?>
        <div class="wrap">
        
        <div id="icon-edit-comments" class="icon32"></div>
        <h2><?php _e("Ban Hammer", 'ban-hammer'); ?></h2>
        
        <?php
        
                if (isset($_POST['update']))
                {
                // Update the Blacklist
                    if ($blacklist_new_keys = $_POST['blacklist_keys'])
                    {
                        $banhammer_array = explode("\n", $blacklist_new_keys);
                        sort ($banhammer_array);
                        $banhammer_string = implode("\n", $banhammer_array);                            
                        if( is_multisite() ) { 
                            update_site_option('banhammer_keys', $banhammer_string);
                        } else {
                            update_option('blacklist_keys', $banhammer_string);
                      }
                    }
        				
                // Update Ban Message
                    if ($banhammer_newmess = $_POST['banhammer_newmess'])
                    {
                        if( is_multisite() ) { 
                            update_site_option('banhammer_message', $banhammer_newmess);
                        } else {
                            update_option('banhammer_message', $banhammer_newmess);
                        }
                    }
        ?>
                <div id="message" class="updated fade"><p><strong><?php _e('Options Updated!', 'ban-hammer'); ?></strong></p></div>

<?php   } 
    
if( is_multisite() ) { 
    $banhammer_blacklist = get_site_option('banhammer_keys');
    $banhammer_blackmess = get_site_option('banhammer_message');
} else {
    $banhammer_blacklist = get_option('blacklist_keys');
    $banhammer_blackmess = get_option('banhammer_message');
}

?>
        
        <form method="post" width='1'>
        
        <fieldset class="options">
        <legend><h3><?php _e('Personalize the Message', 'ban-hammer'); ?></h3></legend>
        <p><?php _e('The message below is displayed to users who are not allowed to register on your blog. Edit is as you see fit, but remember you don\'t get a lot of space so keep it simple.', 'ban-hammer'); ?></p>
        
        <textarea name='banhammer_newmess' cols='80' rows='2'><?php echo $banhammer_blackmess; ?></textarea>
        </fieldset>
        
        <fieldset class="options">
        <legend><h3><?php _e('Blacklisted Emails', 'ban-hammer'); ?></h3></legend>
        <p><?php _e('The emails and domains added below will not be allowed to be used during registration. You can add in full emails (i.e. foo@example.com) or domains (i.e. @domain.com), but not partials past that.', 'ban-hammer'); ?></p>
        
        <textarea name="blacklist_keys" cols="40" rows="15"><?php
                echo $banhammer_blacklist;
        ?></textarea>
        </fieldset>
                <p class="submit"><input class='button-primary' type='submit' name='update' value='<?php _e("Update Options", 'ban-hammer'); ?>' id='submitbutton' /></p>
        
        </form>
        
        </div>
        
        </div> <?php
        }