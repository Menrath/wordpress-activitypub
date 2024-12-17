<?php
/**
 * Bootstrap file for ActivityPub.
 *
 * @package Activitypub
 */

\define( 'ACTIVITYPUB_DISABLE_INCOMING_INTERACTIONS', false );

// Defined here because setting them in .wp-env.json doesn't work for some reason.
\define( 'WP_TESTS_DOMAIN', 'example.org' );
\define( 'WP_SITEURL', 'http://example.org' );
\define( 'WP_HOME', 'http://example.org' );

$_tests_dir = \getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = \rtrim( \sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! \file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . \PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require \dirname( __DIR__ ) . '/activitypub.php';
	$enable_mastodon_apps_plugin = dirname( dirname( __DIR__ ) ) . '/enable-mastodon-apps/enable-mastodon-apps.php'; // phpcs:ignore
	if ( file_exists( $enable_mastodon_apps_plugin ) ) {
		require $enable_mastodon_apps_plugin;
	}
}
\tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
require __DIR__ . '/class-activitypub-testcase-cache-http.php';

\Activitypub\Migration::add_default_settings();
