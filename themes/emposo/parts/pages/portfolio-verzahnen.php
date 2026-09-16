<?php
/**
 * Ported from the static build's pages/portfolio-verzahnen.html.
 *
 * Markup is verbatim; only the template tokens became PHP calls. Generated once
 * by tools/port-partial.mjs and hand-maintained from here on — whitespace and
 * attribute order are load-bearing for the parity diff, so edit carefully.
 *
 * @package Emposo
 */

?>
<section class="page-hero" aria-labelledby="verz-title">
		<div class="gutter"><div class="container @container"><div class="page-hero__grid @max-content:grid-cols-1">
		<div class="page-hero__copy">
			<p class="page-breadcrumb"><a href="/">Startseite</a><span aria-hidden="true">/</span> <a href="/portfolio/">Portfolio</a><span aria-hidden="true">/</span> Verzahnen</p>
			<p class="page-kicker">Portfolio / Modell 04</p>
			<h1 class="page-display" id="verz-title">Wir <em>verzahnen.</em></h1>
			<p class="page-hero__intro">Branchenwissen und Technologie-Expertise arbeiten bei uns Hand in Hand. Wir verbinden Fachbereich und IT dort, wo getrennte Perspektiven sonst den Nutzen im Betrieb ausbremsen.</p>
		</div>
		<figure class="page-hero__visual lg:@max-content:aspect-video lg:@max-content:min-h-0">
			<?php emposo_the_picture( 'technology-team', 'hero', true ); ?>
			<figcaption><span>Wir verzahnen</span><span>04 / 04</span></figcaption>
			<div class="page-hero__metric"><strong>04</strong><span>Business-Mehrwert im Betrieb</span></div>
		</figure>
		</div></div></div>
	</section>

	<section class="page-section" aria-labelledby="verz-how-title">
		<div class="gutter"><div class="container @container">
		<div class="page-section__top @max-content:grid-cols-1">
			<div>
			<p class="page-eyebrow">Was das bedeutet</p>
			<h2 class="page-title" id="verz-how-title">Fachbereich und IT, eine <em class="text-accent-text">Verantwortung.</em></h2>
			</div>
			<p class="page-section__lede">Die Verantwortung bleibt beim Gesamtergebnis: Quality Gates, Security und Compliance sind Bestandteil der Leistung, nicht ein nachträglicher Prüfschritt.</p>
		</div>
		<div class="fact-grid fact-grid--3 fact-grid--flush">
			<article><span class="fact-grid__label">01 / Perspektiven</span><h3>Ein Tisch statt zwei Lager</h3><p>Branchen- und Technologie-Expertise arbeiten im selben Team — nicht in getrennten Linien mit Übergabepunkten.</p></article>
			<article><span class="fact-grid__label">02 / Integration</span><h3>Compliance eingebaut</h3><p>Security, GxP und EU AI Act werden in Architektur, Prozesse und Lieferung integriert.</p></article>
			<article><span class="fact-grid__label">03 / Ergebnis</span><h3>Nutzen im Betrieb</h3><p>Gemessen wird am Business-Mehrwert in der Produktivumgebung — nicht an Übergabedokumenten.</p></article>
		</div>
		</div></div>
	</section>

	<section class="page-section page-section--paper" aria-labelledby="verz-expertise-title">
		<div class="gutter"><div class="container @container">
		<div class="page-section__top @max-content:grid-cols-1">
			<div><p class="page-eyebrow">Passende Expertise</p><h2 class="page-title" id="verz-expertise-title">Disziplinen, die hier <em class="text-accent-text">liefern.</em></h2></div>
			<p class="page-section__lede">Verzahnung braucht Disziplinen, die Sicherheit, Nachweise und Branchenanforderungen in die Lieferung integrieren.</p>
		</div>
		<div class="expertise-list">
			<a class="expertise-card" href="/expertise/technology/">
			<span class="expertise-card__number text-accent-text">07 / Technology</span>
			<h3>Cyber &amp; Compliance</h3>
			<p>Cyber Security, Security Architecture, Penetration Testing, GxP, EU AI Act und auditfähige Nachweise.</p>
			<span class="expertise-card__promise">Wir integrieren Sicherheit und Compliance in Architektur, Prozesse und Lieferung.</span>
			</a>
			<a class="expertise-card" href="/branchen/">
			<span class="expertise-card__number text-accent-text">05 / Branchen</span>
			<h3>Branchenwissen im Netzwerk</h3>
			<p>Fünf Branchen von Aerospace bis Technology &amp; Media — mit den Standards und Nachweispflichten, die dort gelten.</p>
			<span class="expertise-card__promise">Wir sprechen den Dialekt Ihrer Branche.</span>
			</a>
		</div>
		</div></div>
	</section>

	<section class="page-section" aria-labelledby="verz-case-title">
		<div class="gutter"><div class="container @container">
		<div class="page-section__top @max-content:grid-cols-1">
			<div><p class="page-eyebrow">Beleg</p><h2 class="page-title" id="verz-case-title">Verzahnung in <em class="text-accent-text">Anwendung.</em></h2></div>
			<p class="page-section__lede">Ein regulierter Labor-Rollout, in dem Fachbereich, IT und Compliance in einer Lieferung zusammenkamen.</p>
		</div>
		<?php emposo_fragment( 'projects-verzahnen' ); ?>
		<p style="margin-top: 2.7rem;"><a class="page-link min-h-11" href="/case-studies/">Alle Case Studies ansehen</a></p>
		</div></div>
	</section>

	<section class="page-section page-section--deep" aria-labelledby="verz-cta-title">
		<div class="gutter"><div class="container @container"><div class="page-cta @max-content:grid-cols-1">
		<div><p class="page-eyebrow page-eyebrow--light">Nächster Schritt</p><h2 class="page-cta__title" id="verz-cta-title">Wo bremsen getrennte Perspektiven Ihren <em>Betrieb?</em></h2></div>
		<div class="page-cta__copy"><p>Sprechen wir darüber, wo Fachbereich und IT heute aneinander vorbeilaufen — und wie eine verzahnte Lieferung dort Business-Mehrwert schafft.</p><a class="page-link page-link--light min-h-11" href="/kontakt/">Projekt besprechen</a></div>
		</div></div></div>
	</section>
