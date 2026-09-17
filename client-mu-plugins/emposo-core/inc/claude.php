<?php
/**
 * Anthropic Messages API client.
 *
 * Transport is wp_remote_post(), not the official Anthropic PHP SDK. That is a
 * deliberate constraint of this codebase rather than a preference: WPCS and
 * VIPCS reject raw cURL and Guzzle in favour of the WP HTTP API and would fail
 * the build, and an mu-plugin cannot carry its own Composer autoloader on VIP.
 * The wire format is small enough that the trade is cheap — but it means every
 * request shape below is hand-maintained, so the constraints are documented
 * where they are enforced.
 *
 * Outbound only from WP-CLI and wp-admin. A visitor request never reaches this
 * file, which is what keeps the zero-third-party-request rule (and therefore
 * the default-src 'self' CSP in inc/security.php) true.
 *
 * @package Emposo\Core
 */

declare( strict_types = 1 );

namespace Emposo\Core\Claude;

use WP_Error;
use function Emposo\Core\Environment\get_env_var;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const API_URL     = 'https://api.anthropic.com/v1/messages';
const API_VERSION = '2023-06-01';

/**
 * Default model.
 *
 * Fixed ID, no date suffix — appending one 404s.
 */
const MODEL = 'claude-opus-5';

/**
 * Hard ceiling on max_tokens for a non-streaming request.
 *
 * The model can emit up to 128K, but anything much above this has to stream or
 * the request dies on an HTTP timeout instead of returning. A CLI call should
 * not stream, so the ceiling is enforced here and the error says why.
 *
 * Note this budget covers THINKING AND RESPONSE TEXT TOGETHER: thinking is on
 * by default on this model, so a value sized for the answer alone truncates.
 */
const MAX_TOKENS_CEILING = 16000;

/**
 * Server-side fallback, opted into by default.
 *
 * The model runs safety classifiers and can decline a request — a refusal is an
 * HTTP 200, not an error. With this on, the API re-runs a declined request on a
 * fallback model of its own choosing, routed by refusal category, and returns
 * that answer instead. 'default' is used rather than naming a model because the
 * right substitute depends on WHY the request was declined, and a named model
 * is a migration waiting to happen when it is deprecated.
 *
 * The header and the parameter are a matched pair: 'default' requires the
 * 2026-07-01 beta, while the older array form requires 2026-06-01. Crossing
 * them is a 400.
 */
const FALLBACK_BETA = 'server-side-fallback-2026-07-01';

/**
 * Request timeout in seconds.
 *
 * Far above the WP default of 5: thinking is on by default on this model and a
 * single hard prompt can legitimately run for minutes. Acceptable only because
 * this is never a visitor request — see the context guard in message().
 */
const TIMEOUT = 300;

const EFFORT_LEVELS = array( 'low', 'medium', 'high', 'xhigh', 'max' );

/**
 * The API key.
 *
 * Read from the environment or a wp-config constant, never from wp_options: an
 * options-table secret lands in every database export, backup and migration.
 */
function api_key(): ?string {
	$key = get_env_var( 'ANTHROPIC_API_KEY' );

	return ( null === $key || '' === trim( $key ) ) ? null : trim( $key );
}

/**
 * Whether a key is available.
 */
function is_configured(): bool {
	return null !== api_key();
}

/**
 * Where the key came from, for diagnostics.
 *
 * Returns the SOURCE, never the value — so `wp claude doctor` can be run in
 * front of anyone, and its output pasted into an issue.
 */
function key_source(): string {
	if ( function_exists( 'vip_get_env_var' ) && null !== get_env_var( 'ANTHROPIC_API_KEY' ) ) {
		return 'VIP environment variable';
	}

	if ( defined( 'ANTHROPIC_API_KEY' ) ) {
		return 'wp-config constant';
	}

	if ( false !== getenv( 'ANTHROPIC_API_KEY' ) && '' !== getenv( 'ANTHROPIC_API_KEY' ) ) {
		return 'process environment';
	}

	return 'not configured';
}

/**
 * Send a Messages API request.
 *
 * @param array<string, mixed> $args Request arguments: `messages` (required), `system`,
 *                                    `model`, `max_tokens`, `effort`, `format`,
 *                                    `fallbacks`, `timeout`.
 * @phpstan-param array{messages: array<int, array<string, mixed>>, system?: string, model?: string, max_tokens?: int, effort?: string, format?: array<string, mixed>, fallbacks?: bool, timeout?: int} $args
 * @return array<string, mixed>|WP_Error Decoded response, or an error.
 */
function message( array $args ) {
	/*
	 * Context guard, not a capability check. Nothing on the front end may make
	 * an outbound request: it would break the GDPR zero-third-party rule, and
	 * a 300-second timeout on a visitor request would hold a PHP worker open.
	 */
	if ( ! ( defined( 'WP_CLI' ) && WP_CLI ) && ! is_admin() ) {
		return new WP_Error(
			'emposo_claude_context',
			'The Anthropic API is reachable from WP-CLI and wp-admin only, never from a front-end request.'
		);
	}

	$key = api_key();

	if ( null === $key ) {
		return new WP_Error(
			'emposo_claude_no_key',
			'ANTHROPIC_API_KEY is not set. Provide it as an environment variable or a wp-config constant — never in the options table.'
		);
	}

	if ( empty( $args['messages'] ) ) {
		return new WP_Error( 'emposo_claude_no_messages', 'At least one message is required.' );
	}

	$max_tokens = isset( $args['max_tokens'] ) ? (int) $args['max_tokens'] : 4096;

	if ( $max_tokens < 1 || $max_tokens > MAX_TOKENS_CEILING ) {
		return new WP_Error(
			'emposo_claude_max_tokens',
			sprintf(
				'max_tokens must be between 1 and %d. Above that the request has to stream, which this client deliberately does not do.',
				MAX_TOKENS_CEILING
			)
		);
	}

	$effort = isset( $args['effort'] ) ? (string) $args['effort'] : 'high';

	if ( ! in_array( $effort, EFFORT_LEVELS, true ) ) {
		return new WP_Error(
			'emposo_claude_effort',
			sprintf( 'effort must be one of: %s.', implode( ', ', EFFORT_LEVELS ) )
		);
	}

	/*
	 * The body carries no temperature, top_p, top_k or thinking.budget_tokens.
	 * All four were REMOVED on this model and return a 400 — they are not
	 * merely ignored. Thinking is on by default and its depth is set by
	 * output_config.effort, which is nested rather than top-level.
	 */
	$body = array(
		'model'         => isset( $args['model'] ) ? (string) $args['model'] : MODEL,
		'max_tokens'    => $max_tokens,
		'messages'      => $args['messages'],
		'output_config' => array( 'effort' => $effort ),
	);

	if ( ! empty( $args['system'] ) ) {
		$body['system'] = (string) $args['system'];
	}

	/*
	 * Structured output, when the caller parses the answer rather than printing
	 * it. Constrains the response to a JSON schema, which is worth the one-off
	 * schema-compilation latency on the first request for anything machine-read.
	 * Schema support is a subset of JSON Schema: every object needs
	 * additionalProperties:false and a required list, and numeric/string
	 * constraints (minimum, maxLength) are not supported.
	 */
	if ( ! empty( $args['format'] ) ) {
		$body['output_config']['format'] = $args['format'];
	}

	$headers = array(
		'x-api-key'         => $key,
		'anthropic-version' => API_VERSION,
		'content-type'      => 'application/json',
	);

	if ( false !== ( $args['fallbacks'] ?? true ) ) {
		$body['fallbacks']         = 'default';
		$headers['anthropic-beta'] = FALLBACK_BETA;
	}

	$encoded = wp_json_encode( $body );

	if ( false === $encoded ) {
		return new WP_Error( 'emposo_claude_encode', 'Request body could not be encoded as JSON.' );
	}

	$response = wp_remote_post(
		API_URL,
		array(
			'headers' => $headers,
			'body'    => $encoded,
			// phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.DefaultTimeout,WordPressVIPMinimum.Performance.RemoteRequestTimeout.HighTimeout -- CLI and admin only, enforced by the context guard above; a thinking model needs minutes and there is no visitor request to hold open.
			'timeout' => isset( $args['timeout'] ) ? (int) $args['timeout'] : TIMEOUT,
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$status  = wp_remote_retrieve_response_code( $response );
	$decoded = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( ! is_array( $decoded ) ) {
		return new WP_Error(
			'emposo_claude_malformed',
			sprintf( 'HTTP %s with a body that is not JSON.', (string) $status )
		);
	}

	if ( 200 !== $status ) {
		$error = isset( $decoded['error'] ) && is_array( $decoded['error'] ) ? $decoded['error'] : array();

		return new WP_Error(
			'emposo_claude_http_' . (string) $status,
			sprintf(
				'HTTP %s %s: %s',
				(string) $status,
				isset( $error['type'] ) ? (string) $error['type'] : 'error',
				isset( $error['message'] ) ? (string) $error['message'] : 'no message'
			)
		);
	}

	$stop_reason = isset( $decoded['stop_reason'] ) ? (string) $decoded['stop_reason'] : '';

	/*
	 * The refusal check comes BEFORE anything reads content. A declined request
	 * is a successful HTTP 200 whose content array is empty or partial, so
	 * indexing content[0] first crashes on exactly the responses that most need
	 * a clear message. Branch on stop_reason only: stop_details is informational
	 * and can be absent even on a refusal.
	 */
	if ( 'refusal' === $stop_reason ) {
		$details  = isset( $decoded['stop_details'] ) && is_array( $decoded['stop_details'] ) ? $decoded['stop_details'] : array();
		$category = isset( $details['category'] ) ? (string) $details['category'] : 'unspecified';

		return new WP_Error(
			'emposo_claude_refusal',
			sprintf(
				'The request was declined by the model\'s safety classifiers (category: %s)%s.',
				$category,
				isset( $decoded['fallbacks'] ) ? '' : ' and no fallback accepted it'
			)
		);
	}

	/*
	 * Truncation is an error, not a short answer. max_tokens bounds thinking
	 * plus text, so a response sized for the answer alone can stop mid-sentence
	 * with no other signal than this.
	 */
	if ( 'max_tokens' === $stop_reason ) {
		return new WP_Error(
			'emposo_claude_truncated',
			sprintf(
				'The response hit the %d-token ceiling and is incomplete. Raise --max-tokens, or lower --effort so less of the budget goes to thinking.',
				$max_tokens
			)
		);
	}

	return $decoded;
}

/**
 * The response text.
 *
 * Concatenates every text block and ignores the rest: a response can also carry
 * thinking blocks (empty text unless display is requested) and, after a
 * server-side fallback, a `fallback` marker block.
 *
 * @param array<string, mixed> $response Decoded response.
 */
function text( array $response ): string {
	if ( ! isset( $response['content'] ) || ! is_array( $response['content'] ) ) {
		return '';
	}

	$parts = array();

	foreach ( $response['content'] as $block ) {
		if ( is_array( $block ) && 'text' === ( $block['type'] ?? '' ) && isset( $block['text'] ) ) {
			$parts[] = (string) $block['text'];
		}
	}

	return trim( implode( '', $parts ) );
}

/**
 * Which model actually answered.
 *
 * A fallback_message entry in usage.iterations is the reliable signal that a
 * fallback served the response — the `fallback` content block is absent when
 * the API routes a whole conversation to the fallback model, so reading the
 * block alone under-reports.
 *
 * @param array<string, mixed> $response Decoded response.
 * @return array{model: string, fell_back: bool}
 */
function served_by( array $response ): array {
	$model      = isset( $response['model'] ) ? (string) $response['model'] : 'unknown';
	$iterations = array();

	if ( isset( $response['usage']['iterations'] ) && is_array( $response['usage']['iterations'] ) ) {
		$iterations = $response['usage']['iterations'];
	}

	$fell_back = false;

	foreach ( $iterations as $iteration ) {
		if ( is_array( $iteration ) && 'fallback_message' === ( $iteration['type'] ?? '' ) ) {
			$fell_back = true;
			break;
		}
	}

	return array(
		'model'     => $model,
		'fell_back' => $fell_back,
	);
}

/**
 * Token usage, for the caller to log.
 *
 * @param array<string, mixed> $response Decoded response.
 * @return array{input: int, output: int}
 */
function usage( array $response ): array {
	$usage = isset( $response['usage'] ) && is_array( $response['usage'] ) ? $response['usage'] : array();

	return array(
		'input'  => (int) ( $usage['input_tokens'] ?? 0 ),
		'output' => (int) ( $usage['output_tokens'] ?? 0 ),
	);
}
