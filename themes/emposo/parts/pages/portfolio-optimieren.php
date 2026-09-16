<?php
/**
 * Ported from the static build's pages/portfolio-optimieren.html.
 *
 * Markup is verbatim; only the template tokens became PHP calls. Generated once
 * by tools/port-partial.mjs and hand-maintained from here on — whitespace and
 * attribute order are load-bearing for the parity diff, so edit carefully.
 *
 * @package Emposo
 */

?>
<section class="page-hero" aria-labelledby="opt-title">
		<div class="gutter"><div class="container @container"><div class="page-hero__grid @max-content:grid-cols-1">
		<div class="page-hero__copy">
			<p class="page-breadcrumb"><a href="/">Startseite</a><span aria-hidden="true">/</span> <a href="/portfolio/">Portfolio</a><span aria-hidden="true">/</span> Optimieren</p>
			<p class="page-kicker">Portfolio / Modell 01</p>
			<h1 class="page-display" id="opt-title">Wir <em>optimieren.</em></h1>
			<p class="page-hero__intro">Wir übernehmen die Aufgaben, die laufen müssen, damit Ihr Geschäft läuft, und geben sie verbessert zurück: automatisiert, wo es sich rechnet, nachvollziehbar und an gemeinsam definierten Kennzahlen gemessen.</p>
		</div>
		<figure class="page-hero__visual lg:@max-content:aspect-video lg:@max-content:min-h-0">
			<?php emposo_the_picture( 'logistics', 'hero', true ); ?>
			<figcaption><span>Wir optimieren</span><span>01 / 04</span></figcaption>
			<div class="page-hero__metric"><strong>8+</strong><span>Jahre stabiler Betrieb im Managed Service</span></div>
		</figure>
		</div></div></div>
	</section>

	<section class="page-section" aria-labelledby="opt-how-title">
		<div class="gutter"><div class="container @container">
		<div class="page-section__top @max-content:grid-cols-1">
			<div>
			<p class="page-eyebrow">Was das bedeutet</p>
			<h2 class="page-title" id="opt-how-title">Stabiler. Schneller. <em class="text-accent-text">Nachvollziehbar.</em></h2>
			</div>
			<p class="page-section__lede">Laufende Services liefern wir mit vereinbarten Service-Leveln, Reaktionszeiten und Eskalationswegen. Der Auftrag endet erst, wenn die vereinbarten Kriterien in der echten Umgebung erfüllt sind.</p>
		</div>
		<div class="fact-grid fact-grid--3 fact-grid--flush">
			<article><span class="fact-grid__label">01 / Übernahme</span><h3>Verantwortung statt Staffing</h3><p>Wir übernehmen eine klar abgegrenzte Leistung — mit eigener Steuerung, Methode und Ergebnisverantwortung.</p></article>
			<article><span class="fact-grid__label">02 / Verbesserung</span><h3>Automatisiert, wo es sich rechnet</h3><p>Prozesse, Datenflüsse und Abläufe werden industrialisiert und an gemeinsam definierten Kennzahlen gemessen.</p></article>
			<article><span class="fact-grid__label">03 / Betrieb</span><h3>Service-Level statt Zusagen</h3><p>Vereinbarte Reaktionszeiten und Eskalationswege machen den Dauerbetrieb planbar und nachvollziehbar.</p></article>
		</div>
		</div></div>
	</section>

	<section class="page-section page-section--paper" aria-labelledby="opt-expertise-title">
		<div class="gutter"><div class="container @container">
		<div class="page-section__top @max-content:grid-cols-1">
			<div><p class="page-eyebrow">Passende Expertise</p><h2 class="page-title" id="opt-expertise-title">Disziplinen, die hier <em class="text-accent-text">liefern.</em></h2></div>
			<p class="page-section__lede">Optimierung stützt sich vor allem auf Engineering-Disziplinen — von der Entwicklung bis zur Industrialisierung.</p>
		</div>
		<div class="expertise-list">
			<a class="expertise-card" href="/expertise/engineering/">
			<span class="expertise-card__number text-accent-text">01 / Engineering</span>
			<h3>System Engineering &amp; Entwicklung</h3>
			<p>Anforderungen, Modellierung, Entwicklung und Embedded Systems werden zu einer abgesicherten Funktion verbunden.</p>
			<span class="expertise-card__promise">Wir übernehmen definierte Entwicklungsumfänge: von der Anforderung bis zur abgesicherten Funktion.</span>
			</a>
			<a class="expertise-card" href="/expertise/engineering/">
			<span class="expertise-card__number text-accent-text">02 / Engineering</span>
			<h3>Produktion &amp; Industrialisierung</h3>
			<p>Qualität, Logistik, Automatisierung, Anlagenintegration und Prozessoptimierung für stabile technische Abläufe.</p>
			<span class="expertise-card__promise">Wir industrialisieren Prozesse, Datenflüsse und Abläufe für einen belastbaren Betrieb.</span>
			</a>
		</div>
		</div></div>
	</section>

	<section class="page-section" aria-labelledby="opt-cases-title">
		<div class="gutter"><div class="container @container">
		<div class="page-section__top @max-content:grid-cols-1">
			<div><p class="page-eyebrow">Belege</p><h2 class="page-title" id="opt-cases-title">Optimierung mit <em class="text-accent-text">Abnahme.</em></h2></div>
			<p class="page-section__lede">Drei Lieferungen, in denen bestehende Aufgaben übernommen und messbar verbessert wurden.</p>
		</div>
		<?php emposo_fragment( 'projects-optimize' ); ?>
		<p style="margin-top: 2.7rem;"><a class="page-link min-h-11" href="/case-studies/">Alle Case Studies ansehen</a></p>
		</div></div>
	</section>

	<section class="page-section page-section--deep" aria-labelledby="opt-cta-title">
		<div class="gutter"><div class="container @container"><div class="page-cta @max-content:grid-cols-1">
		<div><p class="page-eyebrow page-eyebrow--light">Nächster Schritt</p><h2 class="page-cta__title" id="opt-cta-title">Welche Leistung soll besser <em>laufen?</em></h2></div>
		<div class="page-cta__copy"><p>Beschreiben Sie die Aufgabe, die heute Zeit und Aufmerksamkeit bindet. Wir sprechen über Kennzahlen, Übernahme und den sinnvollsten Einstieg.</p><a class="page-link page-link--light min-h-11" href="/kontakt/?interesse=Optimierung%20einer%20bestehenden%20Leistung">Optimierung besprechen</a></div>
		</div></div></div>
	</section>
