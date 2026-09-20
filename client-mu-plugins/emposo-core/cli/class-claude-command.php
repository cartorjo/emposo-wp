<?php
/**
 * `wp claude` — Anthropic-backed editorial tooling.
 *
 * Four subcommands, all of them editorial aids rather than build steps: a raw
 * prompt for ad-hoc work, alt text for newly uploaded images, a claims audit
 * against the approved facts sheet, and a doctor that proves the credential and
 * the round trip without printing the key.
 *
 * Nothing here runs on a web request. The transport guard lives in
 * inc/claude.php so it holds however the client is called.
 *
 * @package Emposo\Core
 */

declare( strict_types = 1 );

namespace Emposo\Core\CLI;

use WP_CLI;
use WP_CLI\Utils;
use WP_Post;
use function Emposo\Core\Claude\is_configured;
use function Emposo\Core\Claude\key_source;
use function Emposo\Core\Claude\message as claude_request;
use function Emposo\Core\Claude\served_by;
use function Emposo\Core\Claude\text as claude_text;
use function Emposo\Core\Claude\usage as claude_usage;
use const Emposo\Core\Claude\FALLBACK_BETA;
use const Emposo\Core\Claude\MAX_TOKENS_CEILING;
use const Emposo\Core\Claude\MODEL;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Anthropic-backed editorial commands.
 */
class Claude_Command {

	/**
	 * Image formats the API accepts.
	 *
	 * AVIF is deliberately absent — it is not an accepted input format, and
	 * this site is AVIF-first on the front end, so sending the served variant
	 * would fail on every image. The attachments are JPEG; the AVIF renditions
	 * are build artefacts, not attachments.
	 *
	 * @var array<int, string>
	 */
	private const IMAGE_MIMES = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );

	/**
	 * Send a prompt and print the response.
	 *
	 * ## OPTIONS
	 *
	 * <text>
	 * : The prompt.
	 *
	 * [--system=<text>]
	 * : System prompt.
	 *
	 * [--model=<id>]
	 * : Model ID. Defaults to the configured default.
	 *
	 * [--max-tokens=<n>]
	 * : Output ceiling, covering thinking and text together. Default 4096.
	 *
	 * [--effort=<level>]
	 * : low, medium, high, xhigh or max. Default high.
	 *
	 * [--json]
	 * : Print the raw API response instead of the text.
	 *
	 * [--dry-run]
	 * : Print the request that would be sent — URL, headers, body — and send nothing. The key is redacted. Use it to review the wire format without spending a token.
	 *
	 * ## EXAMPLES
	 *
	 *     wp claude prompt "Fasse die Positionierung in einem Satz zusammen."
	 *     wp claude prompt "Review this copy" --system="You are a German copy chief." --effort=max
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Flags.
	 */
	public function prompt( array $args, array $assoc_args ): void {
		$response = claude_request(
			array_filter(
				array(
					'messages'   => array(
						array(
							'role'    => 'user',
							'content' => $args[0],
						),
					),
					'system'     => Utils\get_flag_value( $assoc_args, 'system', '' ),
					'model'      => Utils\get_flag_value( $assoc_args, 'model', MODEL ),
					'max_tokens' => (int) Utils\get_flag_value( $assoc_args, 'max-tokens', 4096 ),
					'effort'     => Utils\get_flag_value( $assoc_args, 'effort', 'high' ),
					'dry_run'    => (bool) Utils\get_flag_value( $assoc_args, 'dry-run', false ),
				)
			)
		);

		if ( is_wp_error( $response ) ) {
			WP_CLI::error( $response->get_error_message() );
		}

		if ( isset( $response['dry_run'] ) ) {
			WP_CLI::log( (string) wp_json_encode( $response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
			WP_CLI::success( 'Nothing was sent.' );

			return;
		}

		if ( (bool) Utils\get_flag_value( $assoc_args, 'json', false ) ) {
			WP_CLI::log( (string) wp_json_encode( $response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );

			return;
		}

		WP_CLI::log( claude_text( $response ) );
		$this->log_provenance( $response );
	}

	/**
	 * Report the credential source and prove the round trip, without printing the key.
	 *
	 * The dashboard's unconfigured-key notice sends operators here, and the
	 * README and docs/security.md both name it — so it must run cleanly whether
	 * or not a key is set. With no key it reports the source and exits 0: "not
	 * configured" is a diagnosis, not a command failure. With a key it sends the
	 * cheapest possible request and reports which model answered, so the failure
	 * modes it distinguishes — no key, wrong key, network/transport, provider —
	 * are legible from one line each. The key value is never emitted on any path;
	 * key_source() returns the SOURCE precisely so the output is safe to paste.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Report the key source and assemble the probe request without sending it.
	 *
	 * ## EXAMPLES
	 *
	 *     wp claude doctor
	 *     wp claude doctor --dry-run
	 *
	 * @param array<int, string>    $args       Positional arguments (unused).
	 * @param array<string, string> $assoc_args Flags.
	 */
	public function doctor( array $args, array $assoc_args ): void {
		unset( $args );

		WP_CLI::log( sprintf( 'Key source: %s', key_source() ) );

		$dry_run = (bool) Utils\get_flag_value( $assoc_args, 'dry-run', false );

		if ( ! is_configured() && ! $dry_run ) {
			// Not an error: the command's job is to report this state clearly.
			WP_CLI::success( 'No key configured. Set ANTHROPIC_API_KEY as an environment variable or a wp-config constant — never in the options table.' );

			return;
		}

		$response = claude_request(
			array(
				'messages'   => array(
					array(
						'role'    => 'user',
						'content' => 'Reply with the single word: ok.',
					),
				),
				'max_tokens' => 16,
				'effort'     => 'low',
				'dry_run'    => $dry_run,
			)
		);

		if ( is_wp_error( $response ) ) {
			WP_CLI::error( sprintf( 'Round trip failed: %s', $response->get_error_message() ) );
		}

		if ( isset( $response['dry_run'] ) ) {
			WP_CLI::log( (string) wp_json_encode( $response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
			WP_CLI::success( 'Request assembled; nothing was sent.' );

			return;
		}

		$this->log_provenance( $response );
		WP_CLI::success( 'Round trip complete.' );
	}

	/**
	 * Write alt text for one or more attachments.
	 *
	 * Existing alt text is never overwritten without --force: the 22 imported
	 * images carry alt text from the audited static build, and `wp emposo
	 * verify --media` asserts it is present. This command is for what an editor
	 * uploads afterwards.
	 *
	 * ## OPTIONS
	 *
	 * <attachment-id>...
	 * : One or more attachment IDs.
	 *
	 * [--force]
	 * : Replace alt text that is already set.
	 *
	 * [--dry-run]
	 * : Print what would be written without writing it.
	 *
	 * [--effort=<level>]
	 * : Default low — describing an image is not a reasoning task.
	 *
	 * ## EXAMPLES
	 *
	 *     wp claude alt-text 42 --dry-run --user=1
	 *     wp claude alt-text 42 43 44 --force --user=1
	 *
	 * @subcommand alt-text
	 *
	 * @param array<int, string>    $args       Attachment IDs.
	 * @param array<string, string> $assoc_args Flags.
	 */
	public function alt_text( array $args, array $assoc_args ): void {
		$dry_run = (bool) Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$force   = (bool) Utils\get_flag_value( $assoc_args, 'force', false );
		$effort  = (string) Utils\get_flag_value( $assoc_args, 'effort', 'low' );
		$written = 0;
		$skipped = 0;

		foreach ( $args as $raw_id ) {
			$id   = (int) $raw_id;
			$post = get_post( $id );

			if ( ! $post instanceof WP_Post || 'attachment' !== $post->post_type ) {
				WP_CLI::warning( sprintf( '%d: not an attachment.', $id ) );
				++$skipped;
				continue;
			}

			/*
			 * Capability, not just existence. Under WP-CLI there is no current
			 * user unless --user is passed, so this also catches the common
			 * mistake of running a content-writing command as nobody.
			 */
			if ( ! $dry_run && ! current_user_can( 'edit_post', $id ) ) {
				WP_CLI::error(
					sprintf(
						'%d: no permission to edit this attachment. Re-run with --user=1 (or --dry-run to preview).',
						$id
					)
				);
			}

			$mime = (string) $post->post_mime_type;

			if ( ! in_array( $mime, self::IMAGE_MIMES, true ) ) {
				WP_CLI::warning(
					sprintf( '%d: %s is not an accepted image format (%s).', $id, $mime, implode( ', ', self::IMAGE_MIMES ) )
				);
				++$skipped;
				continue;
			}

			$existing = (string) get_post_meta( $id, '_wp_attachment_image_alt', true );

			if ( '' !== trim( $existing ) && ! $force ) {
				WP_CLI::log( sprintf( '%d: alt text already set, skipping (use --force to replace).', $id ) );
				++$skipped;
				continue;
			}

			$file = $this->image_path( $id );

			if ( null === $file ) {
				WP_CLI::warning( sprintf( '%d: no readable file on disk.', $id ) );
				++$skipped;
				continue;
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents,WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- A local attachment path, never a URL, so there is nothing to cache; WP_Filesystem is an admin-request abstraction and this is CLI-only.
			$bytes = file_get_contents( $file );

			if ( false === $bytes ) {
				WP_CLI::warning( sprintf( '%d: could not read %s.', $id, $file ) );
				++$skipped;
				continue;
			}

			$response = claude_request(
				array(
					'system'     => 'Du schreibst deutschen Alternativtext für die Website eines IT- und Engineering-Dienstleisters. '
						. 'Beschreibe, was auf dem Bild zu sehen ist, sachlich und in einem Satz von maximal 125 Zeichen. '
						. 'Beginne nicht mit "Bild von" oder "Foto von". Keine Anführungszeichen, keine Vermutungen über Personen, '
						. 'Firmen oder Orte, keine Interpretation. Antworte ausschließlich mit dem Alternativtext.',
					'max_tokens' => 2048,
					'effort'     => $effort,
					'messages'   => array(
						array(
							'role'    => 'user',
							'content' => array(
								array(
									'type'   => 'image',
									'source' => array(
										'type'       => 'base64',
										'media_type' => $mime,
										// base64_encode(), never chunk_split():
										// newlines in the data are rejected.
										'data'       => base64_encode( $bytes ),
									),
								),
								array(
									'type' => 'text',
									'text' => sprintf(
										'Titel der Mediendatei: %s. Schreibe den Alternativtext.',
										'' !== trim( (string) $post->post_title ) ? $post->post_title : '(kein Titel)'
									),
								),
							),
						),
					),
				)
			);

			if ( is_wp_error( $response ) ) {
				WP_CLI::warning( sprintf( '%d: %s', $id, $response->get_error_message() ) );
				++$skipped;
				continue;
			}

			$alt = $this->single_line( claude_text( $response ) );

			if ( '' === $alt ) {
				WP_CLI::warning( sprintf( '%d: empty response.', $id ) );
				++$skipped;
				continue;
			}

			if ( $dry_run ) {
				WP_CLI::log( sprintf( '%d: would write "%s" (%d chars)', $id, $alt, mb_strlen( $alt, 'UTF-8' ) ) );
				continue;
			}

			update_post_meta( $id, '_wp_attachment_image_alt', $alt );
			++$written;
			WP_CLI::log( sprintf( '%d: %s', $id, $alt ) );
		}

		WP_CLI::success(
			sprintf(
				'%d written, %d skipped%s.',
				$written,
				$skipped,
				$dry_run ? ' (dry run — nothing was written)' : ''
			)
		);
	}

	/**
	 * Check published copy against the approved facts sheet.
	 *
	 * The facts sheet is the only permitted fact source for this site's copy,
	 * and it says so itself: anything it does not list must appear as the
	 * placeholder «bestätigen». This command looks for the opposite — assertions
	 * presented as fact that the sheet does not support and that are not marked
	 * as unconfirmed.
	 *
	 * The copy audited is the RENDERED PAGE, fetched over HTTP, not post
	 * content. Thirty-one of the 41 routes are Pages whose bodies live in PHP
	 * template parts, and the rest assemble their copy from meta through
	 * fragments.php — so post_content is empty or a pattern reference on almost
	 * every route, and a database-side audit silently checks nothing. What a
	 * visitor reads is the only source that covers all of it.
	 *
	 * Findings are editorial judgement, not build failures, so the command
	 * exits 0 unless --strict is passed.
	 *
	 * ## OPTIONS
	 *
	 * [--post-type=<type>]
	 * : Restrict to routes resolving to one object type, e.g. page or emposo_case_study.
	 *
	 * [--route=<path>]
	 * : Audit a single route, e.g. /about-us/.
	 *
	 * [--base=<url>]
	 * : HTTP base to fetch from. Defaults to home_url(). Under wp-env the CLI container cannot reach localhost, so pass --base=http://wordpress.
	 *
	 * [--contract=<file>]
	 * : Route contract JSON. Defaults to the bundled copy.
	 *
	 * [--facts=<file>]
	 * : Fact source. Defaults to the bundled mirror of docs/facts.md.
	 *
	 * [--effort=<level>]
	 * : Default high — this is a reasoning task over German copy.
	 *
	 * [--strict]
	 * : Exit non-zero when anything is flagged or any route could not be checked.
	 *
	 * [--dry-run]
	 * : Print the copy that would be sent and make no API request. Use this to confirm the extractor sees real text before spending tokens.
	 *
	 * ## EXAMPLES
	 *
	 *     wp claude audit-claims --base=http://wordpress --dry-run
	 *     wp claude audit-claims --base=http://wordpress --post-type=emposo_case_study
	 *     wp claude audit-claims --base=http://wordpress --strict
	 *
	 * @subcommand audit-claims
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Flags.
	 */
	public function audit_claims( array $args, array $assoc_args ): void {
		$facts_file = (string) Utils\get_flag_value( $assoc_args, 'facts', EMPOSO_CORE_DIR . '/data/facts.md' );

		if ( ! is_readable( $facts_file ) ) {
			WP_CLI::error( sprintf( 'Fact source not readable: %s', $facts_file ) );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents,WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- A file inside this plugin, never a URL, read once under WP-CLI.
		$facts  = (string) file_get_contents( $facts_file );
		$routes = $this->routes( (string) Utils\get_flag_value( $assoc_args, 'contract', EMPOSO_CORE_DIR . '/data/routes.json' ) );

		$post_type = (string) Utils\get_flag_value( $assoc_args, 'post-type', '' );
		$route     = (string) Utils\get_flag_value( $assoc_args, 'route', '' );

		$routes = array_values(
			array_filter(
				$routes,
				static function ( array $candidate ) use ( $post_type, $route ): bool {
					// The 404 probe is a route in the contract but has no copy
					// to audit.
					if ( 'not_found' === ( $candidate['objectType'] ?? '' ) ) {
						return false;
					}

					if ( '' !== $post_type && ( $candidate['objectType'] ?? '' ) !== $post_type ) {
						return false;
					}

					return '' === $route || ( $candidate['url'] ?? '' ) === $route;
				}
			)
		);

		if ( ! $routes ) {
			WP_CLI::error( 'No route matched.' );
		}

		$base    = rtrim( (string) Utils\get_flag_value( $assoc_args, 'base', home_url() ), '/' );
		$dry_run = (bool) Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$rows    = array();
		$errors  = array();

		if ( $dry_run ) {
			$extracted = 0;

			foreach ( $routes as $candidate ) {
				$url  = (string) $candidate['url'];
				$copy = $this->fetch_copy( $base, $url );

				if ( is_wp_error( $copy ) ) {
					WP_CLI::warning( sprintf( '%s: %s', $url, $copy->get_error_message() ) );
					continue;
				}

				WP_CLI::log( sprintf( '--- %s (%d chars) ---', $url, mb_strlen( $copy, 'UTF-8' ) ) );
				WP_CLI::log( $copy );
				WP_CLI::log( '' );
				++$extracted;
			}

			WP_CLI::success( sprintf( '%d of %d route(s) extracted; no request was made.', $extracted, count( $routes ) ) );

			return;
		}

		$progress = Utils\make_progress_bar( 'Auditing', count( $routes ) );

		foreach ( $routes as $candidate ) {
			$url  = (string) $candidate['url'];
			$copy = $this->fetch_copy( $base, $url );

			if ( is_wp_error( $copy ) ) {
				$errors[] = sprintf( '%s: %s', $url, $copy->get_error_message() );
				$progress->tick();
				continue;
			}

			$response = claude_request(
				array(
					'system'     => $this->audit_system_prompt( $facts ),
					'max_tokens' => 8192,
					'effort'     => (string) Utils\get_flag_value( $assoc_args, 'effort', 'high' ),
					'format'     => array(
						'type'   => 'json_schema',
						'schema' => $this->audit_schema(),
					),
					'messages'   => array(
						array(
							'role'    => 'user',
							'content' => sprintf(
								"Prüfe diese Seite.\n\nURL: %s\nTitel: %s\n\n--- COPY ---\n%s",
								$url,
								(string) ( $candidate['title'] ?? '' ),
								$copy
							),
						),
					),
				)
			);

			if ( is_wp_error( $response ) ) {
				$errors[] = sprintf( '%s: %s', $url, $response->get_error_message() );
				$progress->tick();
				continue;
			}

			$parsed = json_decode( claude_text( $response ), true );

			if ( ! is_array( $parsed ) || ! isset( $parsed['findings'] ) || ! is_array( $parsed['findings'] ) ) {
				$errors[] = sprintf( '%s: response did not match the schema.', $url );
				$progress->tick();
				continue;
			}

			foreach ( $parsed['findings'] as $finding ) {
				if ( ! is_array( $finding ) ) {
					continue;
				}

				$rows[] = array(
					'route'    => $url,
					'severity' => (string) ( $finding['severity'] ?? 'low' ),
					'quote'    => $this->single_line( (string) ( $finding['quote'] ?? '' ) ),
					'problem'  => $this->single_line( (string) ( $finding['problem'] ?? '' ) ),
				);
			}

			$progress->tick();
		}

		$progress->finish();

		foreach ( $errors as $error ) {
			WP_CLI::warning( $error );
		}

		/*
		 * A route that failed is not a route that passed. Without this, an
		 * absent key or an unreachable base turns every route into a warning
		 * and the command still reports a clean audit — the one outcome a fact
		 * check must never get wrong.
		 */
		$audited = count( $routes ) - count( $errors );

		if ( 0 === $audited ) {
			WP_CLI::error(
				sprintf( 'No route could be audited: all %d attempt(s) failed. Nothing was checked.', count( $routes ) )
			);
		}

		if ( ! $rows ) {
			WP_CLI::success(
				sprintf(
					'%d of %d route(s) audited, nothing flagged%s.',
					$audited,
					count( $routes ),
					$errors ? sprintf( ' (%d unchecked — see the warnings above)', count( $errors ) ) : ''
				)
			);

			return;
		}

		Utils\format_items( 'table', $rows, array( 'route', 'severity', 'quote', 'problem' ) );

		$summary = sprintf(
			'%d finding(s) across %d of %d route(s)%s.',
			count( $rows ),
			$audited,
			count( $routes ),
			$errors ? sprintf( ', %d unchecked', count( $errors ) ) : ''
		);

		if ( (bool) Utils\get_flag_value( $assoc_args, 'strict', false ) ) {
			WP_CLI::error( $summary );
		}

		WP_CLI::warning( $summary . ' Review each one — a finding is a question, not a verdict.' );
	}

	/**
	 * The route contract.
	 *
	 * @param string $file Path to the contract JSON.
	 * @return array<int, array<string, mixed>>
	 */
	private function routes( string $file ): array {
		if ( ! is_readable( $file ) ) {
			WP_CLI::error( sprintf( 'Route contract not readable: %s', $file ) );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents,WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- A file inside this plugin, never a URL, read once under WP-CLI.
		$decoded = json_decode( (string) file_get_contents( $file ), true );

		if ( ! is_array( $decoded ) || ! isset( $decoded['routes'] ) || ! is_array( $decoded['routes'] ) ) {
			WP_CLI::error( 'Route contract is malformed: expected a "routes" array.' );
		}

		$routes = array();

		foreach ( $decoded['routes'] as $candidate ) {
			if ( is_array( $candidate ) && isset( $candidate['url'] ) ) {
				$routes[] = $candidate;
			}
		}

		return $routes;
	}

	/**
	 * The reader-visible copy of one route.
	 *
	 * Extracts the <main> landmark and nothing else: the header, nav and footer
	 * are identical on all 41 routes, so auditing them would pay for the same
	 * tokens 41 times and report the same finding 41 times.
	 *
	 * @param string $base Scheme and host to fetch from.
	 * @param string $url  Route path.
	 * @return string|\WP_Error
	 */
	private function fetch_copy( string $base, string $url ) {
		/*
		 * The Host header is set from home_url() rather than from $base. When
		 * the two differ — which is the normal case locally, because the wp-env
		 * CLI container reaches WordPress at http://wordpress while the site's
		 * canonical URL is localhost:8888 — WordPress would otherwise issue a
		 * canonical redirect and the body would be empty.
		 */
		$host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
		$port = wp_parse_url( home_url(), PHP_URL_PORT );

		/*
		 * wp_remote_get(), not vip_safe_wp_remote_get(): the VIP helper exists
		 * to protect a VISITOR from a slow third party — it caps the timeout at
		 * 3 seconds and trips a circuit breaker. Both are wrong here. This is a
		 * CLI request to the site's own front end, a cold render can exceed 3
		 * seconds, and a timeout must surface as a failed audit rather than be
		 * swallowed as an empty result.
		 */
		// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown,WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get,WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- See above: CLI-only self-fetch where a swallowed failure would be worse than a slow one.
		$response = wp_remote_get(
			$base . $url,
			array(
				'headers'     => array( 'Host' => $host . ( $port ? ':' . (string) $port : '' ) ),
				'redirection' => 0,
				// phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- See the comment above the call: CLI-only self-fetch, where a 3-second cap would report a slow render as a failed audit.
				'timeout'     => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new \WP_Error(
				'emposo_claude_fetch',
				sprintf(
					'%s (fetching %s). Under wp-env pass --base=http://wordpress; the CLI container cannot reach localhost.',
					$response->get_error_message(),
					$base . $url
				)
			);
		}

		$status = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $status ) {
			return new \WP_Error(
				'emposo_claude_fetch_status',
				sprintf( 'HTTP %s fetching %s', (string) $status, $base . $url )
			);
		}

		$body = wp_remote_retrieve_body( $response );

		if ( ! preg_match( '#<main\b[^>]*>(.*?)</main>#s', $body, $matches ) ) {
			return new \WP_Error(
				'emposo_claude_no_main',
				sprintf( 'No <main> landmark in %s — nothing to audit.', $base . $url )
			);
		}

		$copy = wp_strip_all_tags( $matches[1] );
		$copy = html_entity_decode( $copy, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$copy = (string) preg_replace( '/[ \t]+/u', ' ', $copy );
		$copy = (string) preg_replace( '/\n\s*\n\s*\n+/u', "\n\n", $copy );
		$copy = trim( $copy );

		if ( '' === $copy ) {
			return new \WP_Error(
				'emposo_claude_empty',
				sprintf( '<main> in %s rendered no text.', $base . $url )
			);
		}

		return $copy;
	}

	/**
	 * Response schema for the claims audit.
	 *
	 * Structured output rather than prose, because the result is parsed. The
	 * supported subset of JSON Schema requires additionalProperties:false and a
	 * required list on every object, and rejects numeric or length constraints.
	 *
	 * @return array<string, mixed>
	 */
	private function audit_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'findings' => array(
					'type'  => 'array',
					'items' => array(
						'type'                 => 'object',
						'properties'           => array(
							'quote'    => array(
								'type'        => 'string',
								'description' => 'The exact wording from the copy, verbatim.',
							),
							'problem'  => array(
								'type'        => 'string',
								'description' => 'Why the facts sheet does not support it.',
							),
							'severity' => array(
								'type' => 'string',
								'enum' => array( 'high', 'medium', 'low' ),
							),
						),
						'required'             => array( 'quote', 'problem', 'severity' ),
						'additionalProperties' => false,
					),
				),
			),
			'required'             => array( 'findings' ),
			'additionalProperties' => false,
		);
	}

	/**
	 * The fact-checking system prompt.
	 *
	 * A heredoc rather than concatenation: this is prose with blank lines in it,
	 * and prose reads better unquoted than as a stack of string literals.
	 *
	 * @param string $facts The fact source, verbatim.
	 */
	private function audit_system_prompt( string $facts ): string {
		return <<<PROMPT
Du bist Faktenprüfer für die Website der Emposo GmbH.

Die folgende Faktenliste ist die EINZIGE zulässige Faktenquelle. Alles, was nicht
darin steht, muss in der Copy als Platzhalter «bestätigen» markiert sein.

Melde jede Aussage, die als Tatsache formuliert ist, von der Liste nicht gedeckt
wird und nicht als «bestätigen» markiert ist. Melde insbesondere Kennzahlen,
Kundennamen, Projektnamen, Zertifizierungen, Mitarbeiterzahlen, Kontaktdaten und
alles, was die Liste ausdrücklich ausschließt.

Melde NICHT: Meinungen, Positionierung, Werbesprache ohne Tatsachenbehauptung,
korrekt als «bestätigen» markierte Stellen, und nichts, was die Liste deckt.

--- FAKTENLISTE ---
{$facts}
PROMPT;
	}

	/**
	 * Report which model answered and what it cost.
	 *
	 * @param array<string, mixed> $response Decoded response.
	 */
	private function log_provenance( array $response ): void {
		$served = served_by( $response );
		$tokens = claude_usage( $response );

		WP_CLI::log(
			sprintf(
				'%s%s · %d in / %d out tokens',
				$served['model'],
				$served['fell_back'] ? ' (served by a fallback after a refusal)' : '',
				$tokens['input'],
				$tokens['output']
			)
		);
	}

	/**
	 * Absolute path to an image file to send.
	 *
	 * Prefers a registered intermediate size over the original: the original is
	 * up to 3 MB, base64 inflates it by a third, and a smaller rendition is
	 * enough to describe. Falls back to the original when no subsize exists.
	 *
	 * @param int $id Attachment ID.
	 */
	private function image_path( int $id ): ?string {
		$original = get_attached_file( $id );

		if ( ! is_string( $original ) || '' === $original ) {
			return null;
		}

		$intermediate = image_get_intermediate_size( $id, 'detail' );

		if ( is_array( $intermediate ) && isset( $intermediate['file'] ) ) {
			$candidate = dirname( $original ) . '/' . (string) $intermediate['file'];

			if ( is_readable( $candidate ) ) {
				return $candidate;
			}
		}

		return is_readable( $original ) ? $original : null;
	}

	/**
	 * Collapse a value to one line, for table and log output.
	 *
	 * @param string $value Raw value.
	 */
	private function single_line( string $value ): string {
		return trim( (string) preg_replace( '/\s+/u', ' ', $value ) );
	}
}
