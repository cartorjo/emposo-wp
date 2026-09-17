/**
 * Stylelint configuration.
 *
 * A .mjs config rather than the .stylelintrc.json it replaces, because most of
 * what is below is a switched-off rule and a switched-off rule without a reason
 * is indistinguishable from laziness.
 *
 * The governing fact: every partial in themes/emposo/src/styles/ except
 * main.css is BYTE-IDENTICAL to reference/static/styles/, the audited static
 * build. Restyling them to satisfy a formatting rule would break that
 * provenance — and the provenance is what makes the parity harness meaningful.
 * So stylistic rules are off and correctness rules stay on, the same stance
 * eslint.config.mjs takes for the ported ES5 scripts.
 */
export default {
	extends: 'stylelint-config-standard',

	// The build output is generated; linting it would report on Tailwind's
	// formatting choices. The lint glob excludes it already — this is belt and
	// braces for anyone running stylelint by hand.
	ignoreFiles: [ 'themes/emposo/assets/css/site.css' ],

	rules: {
		// --- Tailwind v4 vocabulary ------------------------------------------
		// These at-rules are Tailwind's, resolved by its compiler; to plain CSS
		// they look unknown.
		'at-rule-no-unknown': [ true, {
			ignoreAtRules: [ 'theme', 'source', 'utility', 'variant', 'custom-variant', 'apply', 'layer', 'plugin', 'config', 'reference' ],
		} ],

		/*
		 * main.css imports its partials at the BOTTOM, after the @layer and
		 * @source blocks, which in plain served CSS would mean the browser
		 * ignores them — a real bug, and the only rule here that is switched
		 * off for a non-cosmetic reason. Tailwind resolves @import at build
		 * time, so nothing reaches a browser as an @import at all; the import
		 * position is what puts the editorial partials in the `components`
		 * layer, and moving them to the top would change the cascade.
		 */
		'no-invalid-position-at-import-rule': null,

		// Tailwind accepts both @import "x" and @import url("x"); the reference
		// build uses the bare-string form.
		'import-notation': null,

		// --- notation the ported CSS does not use ----------------------------
		// Modernisations, not fixes. `(min-width: 48rem)` has wider support than
		// the `(width >= 48rem)` range syntax, and `:not(a):not(b)` wider than
		// `:not(a, b)` — so these would be a support regression, not a cleanup.
		'media-feature-range-notation': null,
		'selector-not-notation': null,
		'color-function-alias-notation': null,
		'color-function-notation': null,
		'alpha-value-notation': null,
		'color-hex-length': null,
		'value-keyword-case': null,
		'declaration-block-no-redundant-longhand-properties': null,

		// --- whitespace and formatting ---------------------------------------
		// The reference build writes short rules on one line and does not keep
		// blank lines above every at-rule. 329 of the 411 violations this
		// configuration silences are that single-line style.
		'declaration-block-single-line-max-declarations': null,
		'at-rule-empty-line-before': null,
		'declaration-empty-line-before': null,
		'rule-empty-line-before': null,
		'comment-empty-line-before': null,
		'custom-property-empty-line-before': null,

		// --- naming ----------------------------------------------------------
		// Class and custom-property names come from the reference build and are
		// asserted by tools/class-inventory.mjs against it, which is a stricter
		// check than a pattern.
		'custom-property-pattern': null,
		'selector-class-pattern': null,

		// Reports Tailwind's own theme values (e.g. --spacing arithmetic) as
		// unknown, because it cannot resolve them.
		'declaration-property-value-no-unknown': null,

		// The reference's cascade depends on source order in places; reordering
		// to satisfy this rule would change rendering.
		'no-descending-specificity': null,
	},
};
