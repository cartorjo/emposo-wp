<?php
/**
 * The no-shell installer: runs scaffold, import and verify from wp-admin.
 *
 * The production host offers SFTP and wp-admin only — no terminal — so the
 * WP-CLI commands the install runbook is built on cannot be typed on the
 * host. This screen invokes the same tested command classes through the
 * CLI_Shim façade (cli/class-cli-shim.php) instead of reimplementing them,
 * one HTTP request per step so no single request has to survive the whole
 * import within PHP's execution limit.
 *
 * Temporary privileged code, gated three ways: this file loads only while
 * EMPOSO_INSTALLER is defined true in wp-config.php (an SFTP-level switch),
 * the screen and its handler require manage_options, and every action is a
 * nonce-checked POST. Remove the define after install day — the removal
 * check is that /wp-admin/admin.php?page=emposo-installer answers "not
 * permitted", and docs/security.md tracks it.
 *
 * @package Emposo\Core
 */

declare( strict_types = 1 );

namespace Emposo\Core\Installer;

use Emposo\Core\CLI\CLI_Shim;
use Emposo\Core\CLI\Halt_Exception;
use Emposo\Core\CLI\Import_Command;
use Emposo\Core\CLI\Scaffold_Command;
use Emposo\Core\CLI\Verify_Command;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'EMPOSO_INSTALLER' ) || ! EMPOSO_INSTALLER ) {
	return;
}

// Under real WP-CLI the commands are registered by inc/cli.php; the installer
// is web-only and must never alias the shim over the genuine WP_CLI class.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	return;
}

const PAGE_SLUG   = 'emposo-installer';
const POST_ACTION = 'emposo_installer_step';

/**
 * The md5 the bundled Lenis build must have; without this file every
 * front-end script early-returns. Mirrors the runbook's packaging check.
 */
const LENIS_MD5 = 'df4f0debbe0e2c502b6eeb46fae82ddd';

add_action( 'admin_menu', __NAMESPACE__ . '\\register_page' );
add_action( 'admin_post_' . POST_ACTION, __NAMESPACE__ . '\\handle_step' );

/**
 * Register the screen under the Emposo dashboard menu.
 */
function register_page(): void {
	add_submenu_page(
		'emposo',
		__( 'Installer', 'emposo' ),
		__( 'Installer', 'emposo' ),
		'manage_options',
		PAGE_SLUG,
		__NAMESPACE__ . '\\render'
	);
}

/**
 * The runnable steps, in runbook order.
 *
 * Each import stage is its own step (and its own HTTP request) because the
 * combined import — 22 image sideloads included — is the one thing that
 * could plausibly outrun a web request's time budget.
 *
 * @return array<string, array{label: string, note: string, run: callable(): void}>
 */
function steps(): array {
	return array(
		'preflight'        => array(
			'label' => __( 'Preflight', 'emposo' ),
			'note'  => __( 'Read-only environment checks. Run first; writes nothing.', 'emposo' ),
			'run'   => static function (): void {
				preflight();
			},
		),
		'scaffold-dry'     => array(
			'label' => __( 'Scaffold — dry run', 'emposo' ),
			'note'  => __( 'Prints the 41-route plan and writes nothing.', 'emposo' ),
			'run'   => static function (): void {
				( new Scaffold_Command() )( array(), array( 'dry-run' => '1' ) );
			},
		),
		'scaffold'         => array(
			'label' => __( 'Scaffold', 'emposo' ),
			'note'  => __( 'Creates the route skeleton, sets front page, permalinks and blog_public=0. Aborts on a slug conflict — resolve it with the finder below, then run again (idempotent).', 'emposo' ),
			'run'   => static function (): void {
				( new Scaffold_Command() )( array(), array() );
			},
		),
		'import-all-dry'   => array(
			'label' => __( 'Import — dry run (all stages)', 'emposo' ),
			'note'  => __( 'Prints the full import plan and writes nothing.', 'emposo' ),
			'run'   => static function (): void {
				( new Import_Command() )->all( array(), array( 'dry-run' => '1' ) );
			},
		),
		'import-terms'     => array(
			'label' => __( 'Import 1/6 — terms', 'emposo' ),
			'note'  => __( 'Classification terms.', 'emposo' ),
			'run'   => static function (): void {
				( new Import_Command() )->terms( array(), array() );
			},
		),
		'import-media'     => array(
			'label' => __( 'Import 2/6 — media', 'emposo' ),
			'note'  => __( 'Sideloads the 22 manifest images from the active theme. Failures are warnings; run again to retry the missing ones (idempotent).', 'emposo' ),
			'run'   => static function (): void {
				( new Import_Command() )->media( array(), array() );
			},
		),
		'import-records'   => array(
			'label' => __( 'Import 3/6 — records', 'emposo' ),
			'note'  => __( 'Populates the scaffolded objects with content.', 'emposo' ),
			'run'   => static function (): void {
				( new Import_Command() )->records( array(), array() );
			},
		),
		'import-relations' => array(
			'label' => __( 'Import 4/6 — relations', 'emposo' ),
			'note'  => __( 'Term assignments and entity relations.', 'emposo' ),
			'run'   => static function (): void {
				( new Import_Command() )->relations( array(), array() );
			},
		),
		'import-people'    => array(
			'label' => __( 'Import 5/6 — people', 'emposo' ),
			'note'  => __( 'The management roster.', 'emposo' ),
			'run'   => static function (): void {
				( new Import_Command() )->people( array(), array() );
			},
		),
		'import-options'   => array(
			'label' => __( 'Import 6/6 — options', 'emposo' ),
			'note'  => __( 'Facts, locations, trust strip, FAQ, interests.', 'emposo' ),
			'run'   => static function (): void {
				( new Import_Command() )->options( array(), array() );
			},
		),
		'verify-full'      => array(
			'label' => __( 'Verify — full (pre-launch)', 'emposo' ),
			'note'  => __( 'All checks. Asserts blog_public=0, so it passes only before the launch flip.', 'emposo' ),
			'run'   => static function (): void {
				( new Verify_Command() )( array(), array() );
			},
		),
		'verify-launch'    => array(
			'label' => __( 'Verify — routes, content, media (post-launch)', 'emposo' ),
			'note'  => __( 'The subset that stays green for the life of the site.', 'emposo' ),
			'run'   => static function (): void {
				( new Verify_Command() )(
					array(),
					array(
						'routes'  => '1',
						'content' => '1',
						'media'   => '1',
					)
				);
			},
		),
	);
}

/**
 * Run one step and stash its output for the redirect target.
 */
function handle_step(): void {
	check_admin_referer( POST_ACTION );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to do that.', 'emposo' ), '', array( 'response' => 403 ) );
	}

	$step  = isset( $_POST['step'] ) ? sanitize_key( wp_unslash( (string) $_POST['step'] ) ) : '';
	$steps = steps();

	if ( ! isset( $steps[ $step ] ) ) {
		wp_die( esc_html__( 'Unknown installer step.', 'emposo' ), '', array( 'response' => 400 ) );
	}

	load_commands();

	// A generous budget for the media sideloads; everything else is far under.
	// Guarded: hosts that list set_time_limit in disable_functions leave the
	// symbol undefined (PHP 8), and an Error thrown here would land outside
	// the try/catch below and kill every step with a white screen.
	if ( function_exists( 'set_time_limit' ) ) {
		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.set_time_limit_set_time_limit -- One nonce-gated admin request on install day; the host has no shell where this could run unbounded.
		set_time_limit( 300 );
	}
	wp_raise_memory_limit( 'admin' );

	CLI_Shim::drain();

	$ok    = true;
	$error = '';

	try {
		( $steps[ $step ]['run'] )();
	} catch ( Halt_Exception $e ) {
		$ok    = false;
		$error = $e->getMessage();
	} catch ( \Throwable $e ) {
		// Defensive: import does filesystem and image work that can throw
		// outside the command's own error handling. Surface, never white-screen.
		$ok    = false;
		$error = get_class( $e ) . ': ' . $e->getMessage();
		CLI_Shim::record( 'error', $error );
	} finally {
		/*
		 * The commands unwind their bulk-import state (suspended cache
		 * invalidation, deferred term counting) only on their happy path; an
		 * abort mid-run would leave both engaged for the rest of the request.
		 * Always unwind, and after a FAILED step flush the object cache so a
		 * persistent backend cannot keep entries written while invalidation
		 * was suspended.
		 */
		wp_suspend_cache_invalidation( false );
		wp_defer_term_counting( false );
		if ( ! $ok ) {
			wp_cache_flush();
		}
	}

	set_transient(
		result_key(),
		array(
			'step'  => $step,
			'ok'    => $ok,
			'error' => $error,
			'lines' => CLI_Shim::drain(),
		),
		10 * MINUTE_IN_SECONDS
	);

	wp_safe_redirect( page_url() );
	exit;
}

/**
 * Load the shim and the command classes; inc/cli.php only does so under WP-CLI.
 */
function load_commands(): void {
	require_once EMPOSO_CORE_DIR . '/cli/class-cli-shim.php';
	require_once EMPOSO_CORE_DIR . '/cli/class-verify-command.php';
	require_once EMPOSO_CORE_DIR . '/cli/class-scaffold-command.php';
	require_once EMPOSO_CORE_DIR . '/cli/class-import-command.php';
}

/**
 * The read-only environment checks from the runbook's preflight section.
 *
 * Emits through the shim so results render like every other step. Hard
 * requirements end the step through CLI_Shim::error(); conditions that only
 * need an eye kept on them are warnings.
 */
function preflight(): void {
	$failures = 0;

	$require = static function ( bool $ok, string $good, string $bad ) use ( &$failures ): void {
		if ( $ok ) {
			CLI_Shim::log( '  ok  ' . $good );
		} else {
			CLI_Shim::warning( 'FAIL  ' . $bad );
			++$failures;
		}
	};

	$require(
		version_compare( PHP_VERSION, '8.1', '>=' ),
		'PHP ' . PHP_VERSION,
		'PHP ' . PHP_VERSION . ' — need 8.1+'
	);
	$require(
		version_compare( get_bloginfo( 'version' ), '6.7', '>=' ),
		'WordPress ' . get_bloginfo( 'version' ),
		'WordPress ' . get_bloginfo( 'version' ) . ' — need 6.7+'
	);
	$require(
		extension_loaded( 'gd' ) || extension_loaded( 'imagick' ),
		'image library: ' . ( extension_loaded( 'imagick' ) ? 'imagick' : 'gd' ),
		'neither GD nor Imagick is loaded — media import cannot generate sizes'
	);

	$theme = wp_get_theme( 'emposo' );
	$require(
		$theme->exists(),
		'theme "emposo" present' . ( 'emposo' === get_stylesheet() ? ' and active' : ' (not yet active)' ),
		'theme "emposo" is not installed'
	);

	if ( $theme->exists() ) {
		$lenis = $theme->get_stylesheet_directory() . '/assets/vendor/lenis.min.js';
		$md5   = file_exists( $lenis ) ? (string) md5_file( $lenis ) : '';
		$require(
			LENIS_MD5 === $md5,
			'lenis.min.js md5 ' . $md5,
			'' === $md5 ? 'lenis.min.js is missing — every front-end script early-returns without it' : 'lenis.min.js md5 is ' . $md5 . ', expected ' . LENIS_MD5
		);
	}

	$require(
		file_exists( EMPOSO_CORE_DIR . '/data/routes.json' ),
		'route contract bundled',
		'data/routes.json missing from the mu-plugin'
	);
	$require(
		file_exists( EMPOSO_CORE_DIR . '/data/site-export.json' ),
		'content export bundled',
		'data/site-export.json missing from the mu-plugin'
	);
	$require(
		current_user_can( 'unfiltered_html' ),
		'current user has unfiltered_html',
		'current user lacks unfiltered_html — import would let kses strip stored markup'
	);

	CLI_Shim::log( '  --  blog_public is ' . (string) get_option( 'blog_public' ) . ' (scaffold forces 0; the launch flip sets 1)' );

	/*
	 * Scaffold's rewrite flush is soft — it regenerates the rules option but
	 * never writes .htaccess. On an Apache host whose .htaccess lacks the
	 * WordPress front-controller block, every pretty URL 404s at the server
	 * before WordPress runs (found in rehearsal: a fresh install wrote an
	 * empty block while permalinks were still plain). Warning, not failure:
	 * nginx hosts have no .htaccess and configure this in the server block.
	 */
	$htaccess = ABSPATH . '.htaccess';
	// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- Reads the local .htaccess on a one-off preflight; no remote fetch and nothing to cache.
	if ( ! file_exists( $htaccess ) || false === strpos( (string) file_get_contents( $htaccess ), 'RewriteRule ^index\.php$' ) ) {
		CLI_Shim::warning( '.htaccess has no WordPress rewrite block — fine on nginx, but on Apache every pretty URL will 404. Scaffold does not write this file.' );
	}

	if ( file_exists( ABSPATH . 'robots.txt' ) ) {
		CLI_Shim::warning( 'a PHYSICAL robots.txt exists — WordPress\'s virtual robots.txt (and the blog_public launch lever) never applies while it does. Delete it over SFTP before launch.' );
	}
	foreach ( array( 'advanced-cache.php', 'object-cache.php' ) as $dropin ) {
		if ( file_exists( WP_CONTENT_DIR . '/' . $dropin ) ) {
			CLI_Shim::warning( $dropin . ' drop-in present — if it belongs to a removed caching plugin it can serve stale pages after cutover.' );
		}
	}
	if ( ! file_exists( WP_CONTENT_DIR . '/audit-evidence/static-baseline.json' ) ) {
		CLI_Shim::warning( 'wp-content/audit-evidence/static-baseline.json not uploaded — the dashboard\'s Messungen panel will report "no baseline".' );
	}

	if ( 0 < $failures ) {
		CLI_Shim::error( sprintf( '%d preflight requirement(s) failed.', $failures ) );
	}

	CLI_Shim::success( 'Preflight passed.' );
}

/**
 * Render the screen.
 */
function render(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to view this page.', 'emposo' ), '', array( 'response' => 403 ) );
	}

	echo '<div class="wrap">';
	echo '<h1>' . esc_html__( 'Emposo Installer', 'emposo' ) . '</h1>';
	echo '<p>' . esc_html__( 'Runs the scaffold → import → verify pipeline from the install runbook, one step per click, in the order listed. Temporary: remove the EMPOSO_INSTALLER define from wp-config.php once the install is verified — this screen must answer 404/permission-denied afterwards.', 'emposo' ) . '</p>';

	render_result();
	render_steps();
	render_squatter_finder();

	echo '</div>';
}

/**
 * Show the outcome of the last step, once.
 */
function render_result(): void {
	$result = get_transient( result_key() );
	delete_transient( result_key() );

	if ( ! is_array( $result ) || ! isset( $result['step'], $result['ok'], $result['lines'] ) ) {
		return;
	}

	$steps = steps();
	$label = isset( $steps[ $result['step'] ] ) ? $steps[ $result['step'] ]['label'] : (string) $result['step'];

	printf(
		'<div class="notice %s"><p><strong>%s:</strong> %s</p></div>',
		esc_attr( $result['ok'] ? 'notice-success' : 'notice-error' ),
		esc_html( $label ),
		esc_html( $result['ok'] ? __( 'completed.', 'emposo' ) : __( 'FAILED — output below.', 'emposo' ) )
	);

	if ( ! is_array( $result['lines'] ) || array() === $result['lines'] ) {
		return;
	}

	$prefix = array(
		'log'     => '      ',
		'warning' => 'WARN  ',
		'success' => 'OK    ',
		'error'   => 'ERROR ',
	);

	echo '<pre style="background:#fff;border:1px solid #c3c4c7;padding:12px;max-height:32em;overflow:auto;">';
	foreach ( $result['lines'] as $line ) {
		if ( ! is_array( $line ) || ! isset( $line['level'], $line['text'] ) ) {
			continue;
		}
		$level = (string) $line['level'];
		echo esc_html( ( $prefix[ $level ] ?? '      ' ) . $line['text'] ) . "\n";
	}
	echo '</pre>';
}

/**
 * One nonce-checked POST form per step.
 */
function render_steps(): void {
	echo '<table class="widefat striped" style="max-width:960px;margin-top:12px;"><tbody>';

	foreach ( steps() as $id => $step ) {
		echo '<tr>';
		echo '<td style="width:16em;vertical-align:top;">';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( POST_ACTION );
		echo '<input type="hidden" name="action" value="' . esc_attr( POST_ACTION ) . '" />';
		echo '<input type="hidden" name="step" value="' . esc_attr( $id ) . '" />';
		// A distinct name per button: submit_button()'s default gives every
		// form the same id="submit", which is invalid HTML across forms.
		submit_button( $step['label'], 'preflight' === $id || false !== strpos( $id, '-dry' ) ? 'secondary' : 'primary', 'run-' . $id, false );
		echo '</form>';
		echo '</td>';
		echo '<td>' . esc_html( $step['note'] ) . '</td>';
		echo '</tr>';
	}

	echo '</tbody></table>';
}

/**
 * Find whatever occupies a slug, across every post type and status.
 *
 * The web replacement for the runbook's recovery command
 * `wp post list --post_type=any --post_status=any --name=<slug>`: when
 * scaffold aborts on a slug conflict, this names the squatter and links to
 * its edit screen, where it can be deleted.
 */
function render_squatter_finder(): void {
	echo '<h2 style="margin-top:2em;">' . esc_html__( 'Slug conflict finder', 'emposo' ) . '</h2>';
	echo '<p>' . esc_html__( 'When scaffold aborts because a slug is taken, look the slug up here, delete the occupant from its edit screen, then run scaffold again.', 'emposo' ) . '</p>';

	echo '<form method="get" action="' . esc_url( admin_url( 'admin.php' ) ) . '">';
	echo '<input type="hidden" name="page" value="' . esc_attr( PAGE_SLUG ) . '" />';
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only lookup rendered on a manage_options screen; no state changes.
	$slug = isset( $_GET['slug'] ) ? sanitize_title( wp_unslash( (string) $_GET['slug'] ) ) : '';
	echo '<input type="text" name="slug" value="' . esc_attr( $slug ) . '" placeholder="kontakt" /> ';
	submit_button( __( 'Find', 'emposo' ), 'secondary', 'submit', false );
	echo '</form>';

	if ( '' === $slug ) {
		return;
	}

	$query = new \WP_Query(
		array(
			'name'           => $slug,
			'post_type'      => array_values( get_post_types() ),
			'post_status'    => array( 'publish', 'future', 'draft', 'pending', 'private', 'inherit' ),
			'posts_per_page' => 20,
			'no_found_rows'  => true,
		)
	);

	if ( ! $query->have_posts() ) {
		echo '<p>' . esc_html( sprintf( /* translators: %s: slug */ __( 'Nothing holds the slug "%s".', 'emposo' ), $slug ) ) . '</p>';

		return;
	}

	echo '<table class="widefat striped" style="max-width:960px;margin-top:8px;">';
	echo '<thead><tr><th>ID</th><th>' . esc_html__( 'Type', 'emposo' ) . '</th><th>' . esc_html__( 'Status', 'emposo' ) . '</th><th>' . esc_html__( 'Title', 'emposo' ) . '</th></tr></thead><tbody>';

	foreach ( (array) $query->posts as $post ) {
		if ( ! $post instanceof \WP_Post ) {
			continue;
		}
		$edit = get_edit_post_link( $post->ID );
		echo '<tr>';
		echo '<td>' . esc_html( (string) $post->ID ) . '</td>';
		echo '<td>' . esc_html( $post->post_type ) . '</td>';
		echo '<td>' . esc_html( $post->post_status ) . '</td>';
		echo '<td>';
		if ( is_string( $edit ) && '' !== $edit ) {
			echo '<a href="' . esc_url( $edit ) . '">' . esc_html( get_the_title( $post ) ) . '</a>';
		} else {
			echo esc_html( get_the_title( $post ) );
		}
		echo '</td>';
		echo '</tr>';
	}

	echo '</tbody></table>';
}

/**
 * Per-user transient key for the last step's output.
 */
function result_key(): string {
	return 'emposo_installer_' . (string) get_current_user_id();
}

/**
 * This screen's URL.
 */
function page_url(): string {
	return add_query_arg( 'page', PAGE_SLUG, admin_url( 'admin.php' ) );
}
