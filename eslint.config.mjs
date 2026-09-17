import globals from 'globals';

/**
 * The site's five front-end scripts are deliberately ES5-era IIFEs with `var`
 * and `Array.prototype.slice.call` — that is a supported-browser-floor decision
 * carried over from the static build, not legacy drift. So this config checks
 * the things a porting mistake would actually break (undeclared globals, unused
 * bindings, accidental reassignment) and does not impose modern-syntax rules
 * that would demand rewriting audited, shipping code.
 */
export default [
	{
		files: ['themes/emposo/assets/js/**/*.js'],
		languageOptions: {
			ecmaVersion: 5,
			sourceType: 'script',
			globals: {
				...globals.browser,
				Lenis: 'readonly',
			},
		},
		linterOptions: {
			reportUnusedDisableDirectives: true,
		},
		rules: {
			'no-undef': 'error',
			'no-unused-vars': ['error', { args: 'after-used' }],
			'no-redeclare': 'error',
			'no-implicit-globals': 'error',
			eqeqeq: ['error', 'smart'],
			/*
			 * console.error is allowed; console.log and friends are not. The one
			 * call in the ported scripts is `if (window.console && console.error)
			 * console.error(err)` inside 00-core.js's ready-callback catch — a
			 * deliberate error path in a file that is byte-identical to the
			 * audited static build, so the rule had to give rather than the file.
			 * Debug logging left behind by a porting mistake still fails.
			 */
			'no-console': [ 'error', { allow: [ 'error' ] } ],
		},
	},
	{
		files: ['tools/**/*.mjs'],
		languageOptions: {
			ecmaVersion: 2024,
			sourceType: 'module',
			// Node AND browser: the harness runs in Node but page.evaluate()
			// callbacks are serialised and executed in the browser, so window
			// and document are legitimately in scope inside them.
			globals: { ...globals.node, ...globals.browser },
		},
		rules: {
			'no-undef': 'error',
			'no-unused-vars': 'error',
			eqeqeq: ['error', 'smart'],
		},
	},
];
