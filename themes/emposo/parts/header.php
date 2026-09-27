<?php
/**
 * Ported from the static build's partials/header.html.
 *
 * Markup is verbatim; only the template tokens became PHP calls. Generated once
 * by tools/port-partial.mjs and hand-maintained from here on — whitespace and
 * attribute order are load-bearing for the parity diff, so edit carefully.
 *
 * @package Emposo
 */

?>
<a class="skip-link" href="#main"><?php echo emposo_t( 'skip' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dictionary text from the reference, escaped there. ?></a>
<header class="site-header" data-site-header>
	<div class="gutter"><div class="container"><div class="site-header__inner">
	<a class="site-logo" href="<?php echo esc_url( emposo_href( '/' ) ); ?>" aria-label="<?php echo emposo_t( 'logo.label' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dictionary text from the reference, escaped there. ?>"><?php emposo_brand( 'emposo-logo-neu26' ); ?></a>
	<nav class="site-nav max-nav:hidden" aria-label="<?php echo emposo_t( 'nav.label' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dictionary text from the reference, escaped there. ?>">
		<a class="min-h-11" href="<?php echo esc_url( emposo_href( '/portfolio/' ) ); ?>"<?php emposo_curattr( 'portfolio' ); ?>><?php echo emposo_t( 'nav.portfolio' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dictionary text from the reference, escaped there. ?></a>
		<a class="min-h-11" href="<?php echo esc_url( emposo_href( '/branchen/' ) ); ?>"<?php emposo_curattr( 'branchen' ); ?><?php emposo_curattr( 'case-studies' ); ?>><?php echo emposo_t( 'nav.branchen' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dictionary text from the reference, escaped there. ?></a>
		<a class="min-h-11" href="<?php echo esc_url( emposo_href( '/about-us/' ) ); ?>"<?php emposo_curattr( 'about' ); ?>><?php echo emposo_t( 'nav.about' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dictionary text from the reference, escaped there. ?></a>
		<a class="min-h-11" href="<?php echo esc_url( emposo_href( '/karriere/' ) ); ?>"<?php emposo_curattr( 'karriere' ); ?>><?php echo emposo_t( 'nav.karriere' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dictionary text from the reference, escaped there. ?></a>
	</nav><?php emposo_lang_switch( 'header' ); ?>

	<a class="header-contact max-nav:hidden min-h-11" href="<?php echo esc_url( emposo_href( '/kontakt/' ) ); ?>"<?php emposo_curattr( 'kontakt' ); ?>><?php echo emposo_t( 'nav.contact' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dictionary text from the reference, escaped there. ?> <span class="header-contact__arrow" aria-hidden="true">→</span></a>
	<details class="mobile-menu nav:hidden" id="mobile-menu">
		<summary aria-label="<?php echo emposo_t( 'menu.open' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dictionary text from the reference, escaped there. ?>" class="min-h-11"><span><?php echo emposo_t( 'menu' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dictionary text from the reference, escaped there. ?></span><span aria-hidden="true">+</span></summary>
		<nav class="mobile-menu__panel" aria-label="<?php echo emposo_t( 'nav.label' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dictionary text from the reference, escaped there. ?>">
		<a href="<?php echo esc_url( emposo_href( '/portfolio/' ) ); ?>"<?php emposo_curattr( 'portfolio' ); ?>><?php echo emposo_t( 'nav.portfolio' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dictionary text from the reference, escaped there. ?></a>
		<a href="<?php echo esc_url( emposo_href( '/branchen/' ) ); ?>"<?php emposo_curattr( 'branchen' ); ?><?php emposo_curattr( 'case-studies' ); ?>><?php echo emposo_t( 'nav.branchen' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dictionary text from the reference, escaped there. ?></a>
		<a href="<?php echo esc_url( emposo_href( '/about-us/' ) ); ?>"<?php emposo_curattr( 'about' ); ?>><?php echo emposo_t( 'nav.about' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dictionary text from the reference, escaped there. ?></a>
		<a href="<?php echo esc_url( emposo_href( '/karriere/' ) ); ?>"<?php emposo_curattr( 'karriere' ); ?>><?php echo emposo_t( 'nav.karriere' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dictionary text from the reference, escaped there. ?></a><?php emposo_lang_switch( 'menu' ); ?>

		<a class="mobile-menu__cta" href="<?php echo esc_url( emposo_href( '/kontakt/' ) ); ?>"<?php emposo_curattr( 'kontakt' ); ?>><?php echo emposo_t( 'nav.contact' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dictionary text from the reference, escaped there. ?> <span class="header-contact__arrow" aria-hidden="true">→</span></a>
		</nav>
	</details>
	</div></div></div>
</header>
