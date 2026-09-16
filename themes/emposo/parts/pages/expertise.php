<?php
/**
 * Ported from the static build's pages/expertise.html.
 *
 * Markup is verbatim; only the template tokens became PHP calls. Generated once
 * by tools/port-partial.mjs and hand-maintained from here on — whitespace and
 * attribute order are load-bearing for the parity diff, so edit carefully.
 *
 * @package Emposo
 */

?>
<section class="page-hero" aria-labelledby="expertise-title">
		<div class="gutter"><div class="container @container"><div class="page-hero__grid @max-content:grid-cols-1">
		<div class="page-hero__copy">
			<p class="page-breadcrumb"><a href="/">Startseite</a><span aria-hidden="true">/</span> Expertise</p>
			<p class="page-kicker">Unsere Lieferdisziplinen</p>
			<h1 class="page-display" id="expertise-title">Engineering trifft <em>Technology.</em></h1>
			<p class="page-hero__intro">Acht Lieferdisziplinen verbinden Fachwissen, Technologie und Umsetzung. Nicht als Leistungslisten, sondern als klar definierte Ergebnisse für den Betrieb.</p>
		</div>
		<figure class="page-hero__visual lg:@max-content:aspect-video lg:@max-content:min-h-0">
			<?php emposo_the_picture( 'engineering', 'hero', true ); ?>
			<figcaption><span>Engineering + Technology</span><span>01 / 08</span></figcaption>
			<div class="page-hero__metric"><strong>8</strong><span>Lieferdisziplinen in Engineering und Technology</span></div>
		</figure>
		</div></div></div>
	</section>

	<section class="page-section" aria-labelledby="disciplines-title">
		<div class="gutter"><div class="container @container">
		<div class="page-section__top @max-content:grid-cols-1">
			<div>
			<p class="page-eyebrow">Portfolio-Übersicht</p>
			<h2 class="page-title" id="disciplines-title">Kompetenz, die im Betrieb <em class="text-accent-text">ankommt.</em></h2>
			</div>
			<p class="page-section__lede">Von System Engineering und Industrialisierung bis zu AI, Cloud und Enterprise Services: Unsere Teams übernehmen klar abgegrenzte Leistungen und führen sie bis zur Abnahme.</p>
		</div>

		<div class="expertise-matrix">
			<section class="expertise-column scroll-mt-28" id="engineering" aria-labelledby="engineering-title">
			<h2 class="expertise-column__title text-accent-text" id="engineering-title">Engineering</h2>
			<div class="expertise-list">
				<a class="expertise-card hover:text-accent-text focus-visible:text-accent-text scroll-mt-28" href="/expertise/system-engineering/">
				<span class="expertise-card__number text-accent-text">01 / Engineering</span>
				<h3>System Engineering &amp; Entwicklung</h3>
				<p>Anforderungen, Modellierung, Entwicklung und Embedded Systems werden zu einer abgesicherten Funktion verbunden.</p>
				<span class="expertise-card__promise">Wir übernehmen definierte Entwicklungsumfänge: von der Anforderung bis zur abgesicherten Funktion.</span>
				</a>
				<a class="expertise-card hover:text-accent-text focus-visible:text-accent-text scroll-mt-28" href="/expertise/produktion-industrialisierung/">
				<span class="expertise-card__number text-accent-text">02 / Engineering</span>
				<h3>Produktion &amp; Industrialisierung</h3>
				<p>Qualität, Logistik, Automatisierung, Anlagenintegration und Prozessoptimierung für stabile technische Abläufe.</p>
				<span class="expertise-card__promise">Wir industrialisieren Prozesse, Datenflüsse und technische Abläufe für einen stabilen Betrieb.</span>
				</a>
				<a class="expertise-card hover:text-accent-text focus-visible:text-accent-text scroll-mt-28" href="/expertise/test-validierung/">
				<span class="expertise-card__number text-accent-text">03 / Engineering</span>
				<h3>Test, Validierung &amp; Integration</h3>
				<p>Teststrategie, Automatisierung, Integration, Safety, Quality Gates und revisionssichere Nachweise.</p>
				<span class="expertise-card__promise">Wir machen Qualität, Sicherheit und Freigabeentscheidungen belastbar.</span>
				</a>
				<a class="expertise-card hover:text-accent-text focus-visible:text-accent-text scroll-mt-28" href="/expertise/engineering-services/">
				<span class="expertise-card__number text-accent-text">04 / Engineering</span>
				<h3>Engineering Services</h3>
				<p>PMO, Change, Audit, technische Dokumentation, Reporting und Konfigurationsmanagement als Dauerleistung.</p>
				<span class="expertise-card__promise">Wir liefern technische Steuerung und Dokumentation als messbare Werkleistung.</span>
				</a>
			</div>
			</section>

			<section class="expertise-column scroll-mt-28" id="technology" aria-labelledby="technology-title">
			<h2 class="expertise-column__title text-accent-text" id="technology-title">Technology</h2>
			<div class="expertise-list">
				<a class="expertise-card hover:text-accent-text focus-visible:text-accent-text scroll-mt-28" id="ai-data" href="/expertise/ai-daten/">
				<span class="expertise-card__number text-accent-text">05 / Technology</span>
				<h3>AI-Transformation &amp; Daten</h3>
				<p>AI-Strategie, Agentic AI, Knowledge Graphs, RAG, Datenplattformen, MLOps und Training.</p>
				<span class="expertise-card__promise">Wir überführen Daten und Wissen in produktive, nachvollziehbare AI-Anwendungen.</span>
				</a>
				<a class="expertise-card hover:text-accent-text focus-visible:text-accent-text scroll-mt-28" href="/expertise/software-cloud/">
				<span class="expertise-card__number text-accent-text">06 / Technology</span>
				<h3>Software, Cloud &amp; Architecture</h3>
				<p>Solution Architecture, Multi-Cloud, Fullstack, APIs, CI/CD, DevSecOps und Plattformintegration.</p>
				<span class="expertise-card__promise">Wir bauen und transformieren Plattformen und Anwendungen bis zum produktiven Betrieb.</span>
				</a>
				<a class="expertise-card hover:text-accent-text focus-visible:text-accent-text scroll-mt-28" href="/expertise/cyber-compliance/">
				<span class="expertise-card__number text-accent-text">07 / Technology</span>
				<h3>Cyber &amp; Compliance</h3>
				<p>Cyber Security, Security Architecture, Penetration Testing, GxP, EU AI Act und auditfähige Nachweise.</p>
				<span class="expertise-card__promise">Wir integrieren Sicherheit und Compliance in Architektur, Prozesse und Lieferung.</span>
				</a>
				<a class="expertise-card hover:text-accent-text focus-visible:text-accent-text scroll-mt-28" href="/expertise/enterprise-services/">
				<span class="expertise-card__number text-accent-text">08 / Technology</span>
				<h3>Enterprise Services</h3>
				<p>Managed Services, Migration, Integration, S/4HANA und SLA-gesteuerter Betrieb.</p>
				<span class="expertise-card__promise">Wir übernehmen Migration, Integration und Betrieb mit klarer Ergebnisverantwortung.</span>
				</a>
			</div>
			</section>
		</div>
		</div></div>
	</section>

	<section class="page-section page-section--dark" aria-labelledby="outcome-title">
		<div class="gutter"><div class="container @container">
		<div class="page-section__top @max-content:grid-cols-1">
			<div>
			<p class="page-eyebrow page-eyebrow--light">Unser Anspruch</p>
			<h2 class="page-title page-title--light" id="outcome-title">Vier Wege zur <em>Wirkung.</em></h2>
			</div>
			<p class="page-section__lede page-section__lede--light">Die Disziplinen sind nicht getrennt organisiert. Je nach Ausgangslage verbinden wir sie zu einer Lieferung, die besser läuft, verändert, skaliert oder die richtige Expertise verzahnt.</p>
		</div>
		<div class="expertise-proof">
			<article><span>01 / Entlasten</span><h3>Wir optimieren.</h3><p>Bestehende Aufgaben zuverlässig übernehmen und entlang gemeinsamer Kennzahlen verbessern.</p></article>
			<article><span>02 / Verändern</span><h3>Wir transformieren.</h3><p>Technologie, Daten und Prozesse bis zum messbaren Ergebnis in die Produktivumgebung bringen.</p></article>
			<article><span>03 / Wachsen</span><h3>Wir skalieren.</h3><p>Teams und Lösungen entlang des tatsächlichen Bedarfs ausbauen.</p></article>
			<article><span>04 / Verbinden</span><h3>Wir verzahnen.</h3><p>Branchenwissen und Technologiekompetenz zu Business-Mehrwert im Betrieb verbinden.</p></article>
		</div>
		<p style="margin-top: 3rem;"><a class="page-link page-link--light min-h-11" href="/portfolio/">Unser Portfolio ansehen</a></p>
		</div></div>
	</section>

	<section class="page-section page-section--paper" aria-labelledby="expertise-case-title">
		<div class="gutter"><div class="container @container">
		<div class="page-section__top @max-content:grid-cols-1">
			<div>
			<p class="page-eyebrow">Expertise in Anwendung</p>
			<h2 class="page-title" id="expertise-case-title">Aus verteiltem Wissen wird produktive <em class="text-accent-text">KI.</em></h2>
			</div>
			<p class="page-section__lede">Die Data2AI Plattform verbindet Engineering-Daten, Knowledge Graph und KI-Agenten zu einem produktiven System.</p>
		</div>
		<div class="expertise-case-strip @max-content:grid-cols-1">
			<figure class="expertise-case-strip__image"><?php emposo_the_picture( 'data2ai' ); ?></figure>
			<div class="expertise-case-strip__copy">
			<p class="page-eyebrow">Case Study 01</p>
			<h3>Data2AI Plattform</h3>
			<p>Aus verteilten Engineering-Daten wird produktive KI: mit strukturierter Wissensbasis, nachweisbarer Verknüpfung und KI-Agenten für die Anwendung.</p>
			<div class="expertise-case-strip__metric"><strong class="text-accent-text">7×</strong><span>Produktivitätssteigerung</span></div>
			<p style="margin-top: 2rem;"><a class="page-link min-h-11" href="/case-studies/data2ai-platform/">Case Study lesen</a></p>
			</div>
		</div>
		</div></div>
	</section>

	<section class="page-section page-section--deep" aria-labelledby="expertise-cta-title">
		<div class="gutter"><div class="container @container"><div class="page-cta @max-content:grid-cols-1">
		<div><p class="page-eyebrow page-eyebrow--light">Nächster Schritt</p><h2 class="page-cta__title" id="expertise-cta-title">Welche Expertise bringt Ihr Vorhaben <em>weiter?</em></h2></div>
		<div class="page-cta__copy"><p>Beschreiben Sie Ihre Ausgangslage. Wir bringen Sie direkt mit der passenden Engineering- und Technology-Expertise zusammen.</p><a class="page-link page-link--light min-h-11" href="/kontakt/">Fachgespräch starten</a></div>
		</div></div></div>
	</section>
