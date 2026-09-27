<?php
/**
 * Router for previewing the new install with PHP's built-in server.
 *
 * docs/launch-plan.md, phase B: the new WordPress is built outside the live
 * webroot, and there is no staging subdomain, so it is previewed over an SSH
 * tunnel with `php -S 127.0.0.1:8099 .preview-router.php`. Existing files
 * (theme assets, uploads) are served as-is; every other path goes to
 * WordPress, which is what nginx's try_files does in production.
 *
 * Copied into the new root as `.preview-router.php` for the preview and
 * DELETED before the swap: it must never be reachable on the live site.
 *
 * @package Emposo
 */

$emposo_path = (string) parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '/' ), PHP_URL_PATH ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Runs before WordPress loads.

if ( '/' !== $emposo_path && is_file( __DIR__ . $emposo_path ) ) {
	return false;
}

$_SERVER['SCRIPT_NAME']     = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';

require __DIR__ . '/index.php';
