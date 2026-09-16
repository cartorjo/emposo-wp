<?php
/**
 * Ported from the static build's pages/portfolio-skalieren.html.
 *
 * Markup is verbatim; only the template tokens became PHP calls. Generated once
 * by tools/port-partial.mjs and hand-maintained from here on — whitespace and
 * attribute order are load-bearing for the parity diff, so edit carefully.
 *
 * @package Emposo
 */

?>
<section class="page-hero" aria-labelledby="skal-title">
		<div class="gutter"><div class="container @container"><div class="page-hero__grid @max-content:grid-cols-1">
		<div class="page-hero__copy">
			<p class="page-breadcrumb"><a href="/">Startseite</a><span aria-hidden="true">/</span> <a href="/portfolio/">Portfolio</a><span aria-hidden="true">/</span> Skalieren</p>
			<p class="page-kicker">Portfolio / Modell 03</p>
			<h1 class="page-display" id="skal-title">Wir <em>skalieren.</em></h1>
			<p class="page-hero__intro">Wenn ein Vorhaben mehr Expertise braucht, wächst das Team entlang des tatsächlichen Bedarfs. Wo Spezialwissen von Partnern kommt, bleibt Emposo Ihr verantwortlicher Vertragspartner und bündelt die Lieferung in einer Leistung.</p>
		</div>
		<figure class="page-hero__visual lg:@max-content:aspect-video lg:@max-content:min-h-0">
			<?php emposo_the_picture( 'collaboration', 'hero', true ); ?>
			<figcaption><span>Wir skalieren</span><span>03 / 04</span></figcaption>
			<div class="page-hero__metric"><strong>5</strong><span>Jahre planbare Lieferung im Langzeit-Werkprogramm</span></div>
		</figure>
		</div></div></div>
	</section>

	<section class="page-section" aria-labelledby="skal-how-title">
		<div class="gutter"><div class="container @container">
		<div class="page-section__top @max-content:grid-cols-1">
			<div>
			<p class="page-eyebrow">Was das bedeutet</p>
			<h2 class="page-title" id="skal-how-title">Wirkung in die Breite <em class="text-accent-text">bringen.</em></h2>
			</div>
			<p class="page-section__lede">Skalierung heißt bei uns nicht mehr Köpfe, sondern mehr Ergebnis: Teams, Partner und die Hays-Plattform wachsen entlang des Bedarfs — in einer Leistung, mit einem Haftungsträger.</p>
		</div>
		<div class="fact-grid fact-grid--3 fact-grid--flush">
			<article><span class="fact-grid__label">01 / Bedarf</span><h3>Wachstum am Bedarf</h3><p>Das Team wächst entlang des tatsächlichen Bedarfs — nicht entlang eines Staffing-Plans.</p></article>
			<article><span class="fact-grid__label">02 / Partner</span><h3>Ein Vertragspartner</h3><p>Wo Spezialwissen von Partnern kommt, bündelt Emposo die Lieferung und bleibt verantwortlich.</p></article>
			<article><span class="fact-grid__label">03 / Programm</span><h3>Planbar über Jahre</h3><p>Langzeit-Werkprogramme liefern komplette Umfänge über mehrere Jahre planbar.</p></article>
		</div>
		</div></div>
	</section>

	<section class="page-section page-section--paper" aria-labelledby="skal-expertise-title">
		<div class="gutter"><div class="container @container">
		<div class="page-section__top @max-content:grid-cols-1">
			<div><p class="page-eyebrow">Passende Expertise</p><h2 class="page-title" id="skal-expertise-title">Disziplinen, die hier <em class="text-accent-text">liefern.</em></h2></div>
			<p class="page-section__lede">Skalierung stützt sich auf Dauerleistungen: Engineering Services und Enterprise Services mit klarer Ergebnisverantwortung.</p>
		</div>
		<div class="expertise-list">
			<a class="expertise-card" href="/expertise/engineering/">
			<span class="expertise-card__number text-accent-text">04 / Engineering</span>
			<h3>Engineering Services</h3>
			<p>PMO, Change, Audit, technische Dokumentation, Reporting und Konfigurationsmanagement als Dauerleistung.</p>
			<span class="expertise-card__promise">Wir liefern technische Steuerung und Dokumentation als messbare Werkleistung.</span>
			</a>
			<a class="expertise-card" href="/expertise/technology/">
			<span class="expertise-card__number text-accent-text">08 / Technology</span>
			<h3>Enterprise Services</h3>
			<p>Managed Services, Migration, Integration, S/4HANA und SLA-gesteuerter Betrieb.</p>
			<span class="expertise-card__promise">Wir übernehmen Migration, Integration und Betrieb mit klarer Ergebnisverantwortung.</span>
			</a>
		</div>
		</div></div>
	</section>

	<section class="page-section" aria-labelledby="skal-cases-title">
		<div class="gutter"><div class="container @container">
		<div class="page-section__top @max-content:grid-cols-1">
			<div><p class="page-eyebrow">Belege</p><h2 class="page-title" id="skal-cases-title">Skalierung mit <em class="text-accent-text">Verantwortung.</em></h2></div>
			<p class="page-section__lede">Zwei Programme, in denen Umfang und Team gewachsen sind — die Verantwortung aber an einer Stelle blieb.</p>
		</div>
		<?php emposo_fragment( 'projects-scale' ); ?>
		<p style="margin-top: 2.7rem;"><a class="page-link min-h-11" href="/case-studies/">Alle Case Studies ansehen</a></p>
		</div></div>
	</section>

	<section class="page-section page-section--deep" aria-labelledby="skal-cta-title">
		<div class="gutter"><div class="container @container"><div class="page-cta @max-content:grid-cols-1">
		<div><p class="page-eyebrow page-eyebrow--light">Nächster Schritt</p><h2 class="page-cta__title" id="skal-cta-title">Welches Programm soll <em>wachsen?</em></h2></div>
		<div class="page-cta__copy"><p>Sprechen wir über Umfang, Meilensteine und die Expertise, die Ihr Vorhaben in die Breite trägt — mit Emposo als verantwortlichem Vertragspartner.</p><a class="page-link page-link--light min-h-11" href="/kontakt/?interesse=Skalierung%20eines%20Programms">Skalierung besprechen</a></div>
		</div></div></div>
	</section>
