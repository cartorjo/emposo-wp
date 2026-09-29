<?php
/**
 * Hyphenation dictionary for the display headlines (owner 2026-09-29).
 *
 * Browsers hyphenate German compounds by syllable, not by word joint:
 * "Gewichtsma-nagement" instead of "Gewichts-management". Each dictionary
 * entry marks the allowed breaks with "|". A headline word found in the
 * dictionary is wrapped in <span class="hy"> with soft hyphens at exactly
 * those points; the span is `hyphens: manual` (11-components.css), so the
 * browser breaks there and nowhere else. Words not in the dictionary keep
 * the browser's own hyphenation, so a long word is never cut off.
 *
 * Editors extend the list on "Emposo Inhalte → Texte" (option
 * `emposo_hyphenation`, one entry per line); their entries win over the
 * built-in ones.
 *
 * @package Emposo\Core
 */

declare( strict_types = 1 );

namespace Emposo\Core\Hyphenation;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const OPTION = 'emposo_hyphenation';

/**
 * The built-in entries: the long words of today's headlines.
 *
 * @return string[]
 */
function builtin(): array {
	return array(
		'Antriebs|steuer|geräte',
		'Antriebs|umrichter',
		'Aus|lagerung',
		'beherrsch|bar',
		'Branchen|wissen',
		'Cyber|security',
		'Dauer|zustand',
		'Doku|menta|tion',
		'Entwicklungs|projekte',
		'Fort|schritt',
		'Funktions|betreuung',
		'Gewichts|management',
		'Homo|logation',
		'Homo|logations|tests',
		'Implemen|tierung',
		'Informations|sicherheits',
		'Info|tainment',
		'Kreislauf|wirtschaft',
		'Medizin|produkte',
		'Medizin|technik',
		'Mittel|stand',
		'nach|haltigen',
		'Penetrations|tests',
		'Platt|formen',
		'Projekt|steuerung',
		'Qualitäts|arbeit',
		'Rechen|zentrums',
		'Risiko|management',
		'Schulungs|curricula',
		'sicherheits|relevante',
		'Speiche|rung',
		'Sport|wagen',
		'Techno|logie',
		'Test|spezifikation',
		'Unter|nehmen',
		'Verant|wortlich',
		'Verant|wortung',
		'Werk|leistung',
		'Wissens|basis',
		'Zerspanungs|leistungen',
		'Zugäng|lichkeit',
		'zusammen|arbeiten',
		'zusammen|gehört',
	);
}

/**
 * Parse entries into word => parts.
 *
 * @param string[] $entries Lines like "Gewichts|management".
 * @return array<string, string[]>
 */
function parse( array $entries ): array {
	$map = array();
	foreach ( $entries as $entry ) {
		$entry = trim( (string) $entry );
		if ( '' === $entry || false === strpos( $entry, '|' ) || ! preg_match( '/^[\p{L}|]+$/u', $entry ) ) {
			continue;
		}
		$parts = array_values(
			array_filter(
				explode( '|', $entry ),
				static function ( string $part ): bool {
					return '' !== $part;
				}
			)
		);
		if ( count( $parts ) > 1 ) {
			$map[ implode( '', $parts ) ] = $parts;
		}
	}

	return $map;
}

/**
 * The effective dictionary: built-in entries, overridden by the editors'.
 *
 * @return array<string, string[]>
 */
function dictionary(): array {
	static $map = null;

	if ( null === $map ) {
		$custom = get_option( OPTION, array() );
		$map    = array_merge( parse( builtin() ), parse( is_array( $custom ) ? array_map( 'strval', $custom ) : array() ) );
	}

	return $map;
}

/**
 * Mark the dictionary words of a headline's HTML with soft hyphens.
 *
 * Only text between tags is touched; attributes and entities stay as they are.
 *
 * @param string $html Escaped headline HTML.
 */
function mark( string $html ): string {
	$map = dictionary();
	if ( ! $map || '' === $html ) {
		return $html;
	}

	$pieces = preg_split( '/(<[^>]*>|&[#a-zA-Z0-9]+;)/u', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
	if ( ! is_array( $pieces ) ) {
		return $html;
	}

	foreach ( $pieces as $i => $piece ) {
		if ( '' === $piece || '<' === $piece[0] || '&' === $piece[0] ) {
			continue;
		}
		$pieces[ $i ] = (string) preg_replace_callback(
			'/\p{L}+/u',
			static function ( array $found ) use ( $map ): string {
				$parts = $map[ $found[0] ] ?? null;

				return null === $parts ? $found[0] : '<span class="hy">' . implode( '&shy;', $parts ) . '</span>';
			},
			$piece
		);
	}

	return implode( '', $pieces );
}
