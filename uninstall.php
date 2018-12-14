<?php
/*
 * Copyright 2009-18 Mika Epstein (email: ipstenu@halfelf.org)
 *
 * This file is part of Ban Hammer, a plugin for WordPress.
 */

// This is the uninstall script.

if ( ! defined( 'ABSPATH' ) && ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

delete_site_option( 'banhammer_message' );
delete_site_option( 'banhammer_options' );
delete_site_option( 'banhammer_keys' );
