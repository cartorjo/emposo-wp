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
		<details class="site-nav__group<?php emposo_cur( 'expertise', 'is-current' ); ?><?php emposo_cur( 'portfolio', 'is-current' ); ?>">
		<summary class="min-h-11">Leistungen</summary>
		<div class="mega-panel"><div class="gutter"><div class="container mega-panel__grid">
			<div class="mega-panel__intro">
			<p class="mega-panel__eyebrow">Leistungen</p>
			<a class="mega-panel__all" href="/portfolio/"<?php emposo_curattr( 'portfolio' ); ?>>Unsere Leistungen <span aria-hidden="true">→</span></a>
			<p>Engineering und Technology aus einer Hand. Von der ersten Idee bis zum messbaren Ergebnis.</p>
			<a class="mega-panel__all" href="/expertise/"<?php emposo_curattr( 'expertise' ); ?>>Alle acht Disziplinen <span aria-hidden="true">→</span></a>
			</div>
			<a class="mega-panel__item" href="/expertise/engineering/"<?php emposo_curattr( 'expertise-engineering' ); ?>><span class="mega-panel__num">01</span><strong>Engineering</strong><span>Systementwicklung, Industrialisierung, Test und Engineering Services.</span></a>
			<a class="mega-panel__item" href="/expertise/technology/"<?php emposo_curattr( 'expertise-technology' ); ?>><span class="mega-panel__num">02</span><strong>Technology</strong><span>AI &amp; Daten, Software, Cloud, Cyber und Enterprise Services.</span></a>
			<a class="mega-panel__item" href="/expertise/ai-daten/"<?php emposo_curattr( 'expertise-ai-daten' ); ?>><span class="mega-panel__num">03</span><strong>AI &amp; Daten</strong><span>Von der AI-Strategie zur produktiven Anwendung.</span></a>
			<div class="mega-panel__featured">
			<p class="mega-panel__eyebrow">Was und wie wir liefern</p>
			<a href="/portfolio/optimieren/"<?php emposo_curattr( 'portfolio-optimieren' ); ?>>Wir optimieren</a>
			<a href="/portfolio/transformieren/"<?php emposo_curattr( 'portfolio-transformieren' ); ?>>Wir transformieren</a>
			<a href="/portfolio/skalieren/"<?php emposo_curattr( 'portfolio-skalieren' ); ?>>Wir skalieren</a>
			<a href="/portfolio/verzahnen/"<?php emposo_curattr( 'portfolio-verzahnen' ); ?>>Wir verzahnen</a>
			<a href="/portfolio/#delivery-model">Unser 5-Stufen-Modell <span aria-hidden="true">→</span></a>
			</div>
		</div></div></div>
		</details>
		<details class="site-nav__group<?php emposo_cur( 'branchen', 'is-current' ); ?>">
		<summary class="min-h-11">Branchen</summary>
		<div class="mega-panel"><div class="gutter"><div class="container mega-panel__grid mega-panel__grid--4">
			<div class="mega-panel__intro"><p class="mega-panel__eyebrow">Industrie-Know-how</p><a class="mega-panel__all" href="/branchen/"<?php emposo_curattr( 'branchen' ); ?>>Alle Branchen <span aria-hidden="true">→</span></a><p>Unsere Teams und Spezialisten kommen direkt aus Ihrer Branche.</p></div>
			<div class="mega-panel__links"><a href="/branchen/aerospace-defense/">Aerospace &amp; Defense</a><a href="/branchen/energy-resources/">Energy &amp; Resources</a></div>
			<div class="mega-panel__links"><a href="/branchen/health-pharma/">Health &amp; Pharma</a><a href="/branchen/industrials-manufacturing/">Industrials &amp; Manufacturing <span>inklusive Automotive</span></a></div>
			<div class="mega-panel__links"><a href="/branchen/technology-telecoms-media/">Technology, Telecoms &amp; Media</a></div>
		</div></div></div>
		</details>
		<a class="min-h-11" href="/case-studies/"<?php emposo_curattr( 'case-studies' ); ?>>Referenzprojekte</a>
		<a class="min-h-11" href="/about-us/"<?php emposo_curattr( 'about' ); ?>>Über uns</a>
		<a class="min-h-11" href="/karriere/"<?php emposo_curattr( 'karriere' ); ?>>Karriere</a>
	</nav>
	<a class="header-contact max-nav:hidden min-h-11" href="/kontakt/"<?php emposo_curattr( 'kontakt' ); ?>>Projekt besprechen <span aria-hidden="true">→</span></a>
	<details class="mobile-menu nav:hidden" id="mobile-menu">
		<summary aria-label="Menü öffnen" class="min-h-11"><span>Menü</span><span aria-hidden="true">+</span></summary>
		<nav class="mobile-menu__panel" aria-label="Hauptnavigation">
		<p class="mobile-menu__label">Leistungen</p>
		<a href="/portfolio/"<?php emposo_curattr( 'portfolio' ); ?>>Unsere Leistungen</a>
		<a href="/expertise/"<?php emposo_curattr( 'expertise' ); ?>>Alle Disziplinen</a>
		<a href="/expertise/engineering/"<?php emposo_curattr( 'expertise-engineering' ); ?>>Engineering</a>
		<a href="/expertise/technology/"<?php emposo_curattr( 'expertise-technology' ); ?>>Technology</a>
		<a href="/expertise/ai-daten/"<?php emposo_curattr( 'expertise-ai-daten' ); ?>>AI &amp; Daten</a>
		<p class="mobile-menu__label">Branchen &amp; Referenzprojekte</p>
		<a href="/branchen/"<?php emposo_curattr( 'branchen' ); ?>>Alle Branchen</a>
		<a href="/case-studies/"<?php emposo_curattr( 'case-studies' ); ?>>Referenzprojekte</a>
		<p class="mobile-menu__label">Unternehmen</p>
		<a href="/about-us/"<?php emposo_curattr( 'about' ); ?>>Über uns</a>
		<a href="/about-us/#management">Management</a>
		<a href="/karriere/"<?php emposo_curattr( 'karriere' ); ?>>Karriere</a>
		<a class="mobile-menu__cta" href="/kontakt/"<?php emposo_curattr( 'kontakt' ); ?>>Projekt besprechen <span aria-hidden="true">→</span></a>
		</nav>
	</details>
	</div></div></div>
</header>
