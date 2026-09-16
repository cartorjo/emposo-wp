<?php
/**
 * Ported from the static build's pages/expertise-engineering.html.
 *
 * Markup is verbatim; only the template tokens became PHP calls. Generated once
 * by tools/port-partial.mjs and hand-maintained from here on — whitespace and
 * attribute order are load-bearing for the parity diff, so edit carefully.
 *
 * @package Emposo
 */

?>
<section class="page-hero" aria-labelledby="eng-title">
		<div class="gutter"><div class="container @container"><div class="page-hero__grid @max-content:grid-cols-1">
		<div class="page-hero__copy">
			<p class="page-breadcrumb"><a href="/">Startseite</a><span aria-hidden="true">/</span> <a href="/expertise/">Expertise</a><span aria-hidden="true">/</span> Engineering</p>
			<p class="page-kicker">Expertise / Engineering</p>
			<h1 class="page-display" id="eng-title">Engineering, das im Betrieb <em>ankommt.</em></h1>
			<p class="page-hero__intro">Vier Lieferdisziplinen von der Anforderung bis zur abgesicherten Funktion: Wir übernehmen definierte Entwicklungsumfänge und führen sie bis zur Abnahme.</p>
		</div>
		<figure class="page-hero__visual lg:@max-content:aspect-video lg:@max-content:min-h-0">
			<?php emposo_the_picture( 'engineering', 'hero', true ); ?>
			<figcaption><span>Engineering</span><span>01 / 02</span></figcaption>
			<div class="page-hero__metric"><strong>4</strong><span>Engineering-Lieferdisziplinen</span></div>
		</figure>
		</div></div></div>
	</section>

	<section class="page-section" aria-labelledby="eng-disciplines-title">
		<div class="gutter"><div class="container @container">
		<div class="page-section__top @max-content:grid-cols-1">
			<div>
			<p class="page-eyebrow">Lieferdisziplinen</p>
			<h2 class="page-title" id="eng-disciplines-title">Vier Wege zu belastbarer <em class="text-accent-text">Technik.</em></h2>
			</div>
			<p class="page-section__lede">Jede Disziplin ist eine klar abgegrenzte Leistung mit eigenem Ergebnisversprechen — einzeln beauftragbar, im Programm kombinierbar.</p>
		</div>
		<div class="expertise-list">
			<a class="expertise-card" id="systementwicklung" href="/expertise/system-engineering/">
			<span class="expertise-card__number text-accent-text">01 / Engineering</span>
			<h3>System Engineering &amp; Entwicklung</h3>
			<p>Anforderungen, Modellierung, Entwicklung und Embedded Systems werden zu einer abgesicherten Funktion verbunden.</p>
			<span class="expertise-card__promise">Wir übernehmen definierte Entwicklungsumfänge: von der Anforderung bis zur abgesicherten Funktion.</span>
			</a>
			<a class="expertise-card" id="industrialisierung" href="/expertise/produktion-industrialisierung/">
			<span class="expertise-card__number text-accent-text">02 / Engineering</span>
			<h3>Produktion &amp; Industrialisierung</h3>
			<p>Qualität, Logistik, Automatisierung, Anlagenintegration und Prozessoptimierung für stabile technische Abläufe.</p>
			<span class="expertise-card__promise">Wir industrialisieren Prozesse, Datenflüsse und Abläufe für einen belastbaren Betrieb.</span>
			</a>
			<a class="expertise-card" id="test-validierung" href="/expertise/test-validierung/">
			<span class="expertise-card__number text-accent-text">03 / Engineering</span>
			<h3>Test, Validierung &amp; Integration</h3>
			<p>Teststrategie, Automatisierung, Integration, Safety, Quality Gates und revisionssichere Nachweise.</p>
			<span class="expertise-card__promise">Wir machen Qualität, Sicherheit und Freigabeentscheidungen belastbar.</span>
			</a>
			<a class="expertise-card" id="engineering-services" href="/expertise/engineering-services/">
			<span class="expertise-card__number text-accent-text">04 / Engineering</span>
			<h3>Engineering Services</h3>
			<p>PMO, Change, Audit, technische Dokumentation, Reporting und Konfigurationsmanagement als Dauerleistung.</p>
			<span class="expertise-card__promise">Wir liefern technische Steuerung und Dokumentation als messbare Werkleistung.</span>
			</a>
		</div>
		</div></div>
	</section>

	<section class="page-section page-section--paper" aria-labelledby="eng-cases-title">
		<div class="gutter"><div class="container @container">
		<div class="page-section__top @max-content:grid-cols-1">
			<div>
			<p class="page-eyebrow">Belege</p>
			<h2 class="page-title" id="eng-cases-title">Engineering mit <em class="text-accent-text">Nachweis.</em></h2>
			</div>
			<p class="page-section__lede">Drei Beispiele, wie Engineering-Disziplinen als Werkleistung geliefert werden — mit Abnahme statt Anwesenheit.</p>
		</div>
		<?php emposo_fragment( 'projects-engineering' ); ?>
		<p style="margin-top: 2.7rem;"><a class="page-link min-h-11" href="/case-studies/">Alle Case Studies ansehen</a></p>
		</div></div>
	</section>

	<section class="page-section page-section--deep" aria-labelledby="eng-cta-title">
		<div class="gutter"><div class="container @container"><div class="page-cta @max-content:grid-cols-1">
		<div><p class="page-eyebrow page-eyebrow--light">Nächster Schritt</p><h2 class="page-cta__title" id="eng-cta-title">Welcher Umfang soll abgesichert <em>werden?</em></h2></div>
		<div class="page-cta__copy"><p>Von einzelnen Entwicklungsumfängen bis zum Langzeit-Werkprogramm: Sprechen wir über Ergebnisdefinition, Quality Gates und den sinnvollsten Einstieg.</p><a class="page-link page-link--light min-h-11" href="/kontakt/">Fachgespräch starten</a></div>
		</div></div></div>
	</section>
