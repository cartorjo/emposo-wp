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
<a class="skip-link" href="#main">Zum Inhalt springen</a>
<header class="site-header" data-site-header>
	<div class="gutter"><div class="container"><div class="site-header__inner">
	<a class="site-logo" href="/" aria-label="Emposo — Startseite"><?php emposo_brand( 'emposo-logo-neu26' ); ?></a>
	<nav class="site-nav max-nav:hidden" aria-label="Hauptnavigation">
		<a class="min-h-11" href="/portfolio/"<?php emposo_curattr( 'portfolio' ); ?>>Leistungen</a>
		<a class="min-h-11" href="/branchen/"<?php emposo_curattr( 'branchen' ); ?><?php emposo_curattr( 'case-studies' ); ?>>Branchen &amp; Projekte</a>
		<a class="min-h-11" href="/about-us/"<?php emposo_curattr( 'about' ); ?>>Über uns</a>
		<a class="min-h-11" href="/karriere/"<?php emposo_curattr( 'karriere' ); ?>>Karriere</a>
	</nav>
	<a class="header-contact max-nav:hidden min-h-11" href="/kontakt/"<?php emposo_curattr( 'kontakt' ); ?>>Projekt besprechen <span class="header-contact__arrow" aria-hidden="true">→</span></a>
	<details class="mobile-menu nav:hidden" id="mobile-menu">
		<summary aria-label="Menü öffnen" class="min-h-11"><span>Menü</span><span aria-hidden="true">+</span></summary>
		<nav class="mobile-menu__panel" aria-label="Hauptnavigation">
		<a href="/portfolio/"<?php emposo_curattr( 'portfolio' ); ?>>Leistungen</a>
		<a href="/branchen/"<?php emposo_curattr( 'branchen' ); ?><?php emposo_curattr( 'case-studies' ); ?>>Branchen &amp; Projekte</a>
		<a href="/about-us/"<?php emposo_curattr( 'about' ); ?>>Über uns</a>
		<a href="/karriere/"<?php emposo_curattr( 'karriere' ); ?>>Karriere</a>
		<a class="mobile-menu__cta" href="/kontakt/"<?php emposo_curattr( 'kontakt' ); ?>>Projekt besprechen <span class="header-contact__arrow" aria-hidden="true">→</span></a>
		</nav>
	</details>
	</div></div></div>
</header>
