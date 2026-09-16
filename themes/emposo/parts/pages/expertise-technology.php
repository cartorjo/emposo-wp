<?php
/**
 * Ported from the static build's pages/expertise-technology.html.
 *
 * Markup is verbatim; only the template tokens became PHP calls. Generated once
 * by tools/port-partial.mjs and hand-maintained from here on — whitespace and
 * attribute order are load-bearing for the parity diff, so edit carefully.
 *
 * @package Emposo
 */

?>
<section class="page-hero" aria-labelledby="tech-title">
		<div class="gutter"><div class="container @container"><div class="page-hero__grid @max-content:grid-cols-1">
		<div class="page-hero__copy">
			<p class="page-breadcrumb"><a href="/">Startseite</a><span aria-hidden="true">/</span> <a href="/expertise/">Expertise</a><span aria-hidden="true">/</span> Technology</p>
			<p class="page-kicker">Expertise / Technology</p>
			<h1 class="page-display" id="tech-title">Technologie, produktiv <em>gemacht.</em></h1>
			<p class="page-hero__intro">Vier Lieferdisziplinen von AI und Cloud bis Cyber und Enterprise Services: Wir bauen, transformieren und betreiben Plattformen bis in den produktiven Betrieb.</p>
		</div>
		<figure class="page-hero__visual lg:@max-content:aspect-video lg:@max-content:min-h-0">
			<?php emposo_the_picture( 'technology', 'hero', true ); ?>
			<figcaption><span>Technology</span><span>02 / 02</span></figcaption>
			<div class="page-hero__metric"><strong>4</strong><span>Technology-Lieferdisziplinen</span></div>
		</figure>
		</div></div></div>
	</section>

	<section class="page-section" aria-labelledby="tech-disciplines-title">
		<div class="gutter"><div class="container @container">
		<div class="page-section__top @max-content:grid-cols-1">
			<div>
			<p class="page-eyebrow">Lieferdisziplinen</p>
			<h2 class="page-title" id="tech-disciplines-title">Vier Wege zu produktiver <em class="text-accent-text">Technologie.</em></h2>
			</div>
			<p class="page-section__lede">Jede Disziplin ist eine klar abgegrenzte Leistung mit eigenem Ergebnisversprechen — einzeln beauftragbar, im Programm kombinierbar.</p>
		</div>
		<div class="expertise-list">
			<a class="expertise-card" id="ai-daten" href="/expertise/ai-daten/">
			<span class="expertise-card__number text-accent-text">05 / Technology</span>
			<h3>AI-Transformation &amp; Daten</h3>
			<p>AI-Strategie, Agentic AI, Knowledge Graphs, RAG, Datenplattformen, MLOps und Training.</p>
			<span class="expertise-card__promise">Wir überführen Daten und Wissen in produktive, nachvollziehbare AI-Anwendungen.</span>
			</a>
			<a class="expertise-card" id="software-cloud" href="/expertise/software-cloud/">
			<span class="expertise-card__number text-accent-text">06 / Technology</span>
			<h3>Software, Cloud &amp; Architecture</h3>
			<p>Solution Architecture, Multi-Cloud, Fullstack, APIs, CI/CD, DevSecOps und Plattformintegration.</p>
			<span class="expertise-card__promise">Wir bauen und transformieren Plattformen und Anwendungen bis zum produktiven Betrieb.</span>
			</a>
			<a class="expertise-card" id="cyber-compliance" href="/expertise/cyber-compliance/">
			<span class="expertise-card__number text-accent-text">07 / Technology</span>
			<h3>Cyber &amp; Compliance</h3>
			<p>Cyber Security, Security Architecture, Penetration Testing, GxP, EU AI Act und auditfähige Nachweise.</p>
			<span class="expertise-card__promise">Wir integrieren Sicherheit und Compliance in Architektur, Prozesse und Lieferung.</span>
			</a>
			<a class="expertise-card" id="enterprise-services" href="/expertise/enterprise-services/">
			<span class="expertise-card__number text-accent-text">08 / Technology</span>
			<h3>Enterprise Services</h3>
			<p>Managed Services, Migration, Integration, S/4HANA und SLA-gesteuerter Betrieb.</p>
			<span class="expertise-card__promise">Wir übernehmen Migration, Integration und Betrieb mit klarer Ergebnisverantwortung.</span>
			</a>
		</div>
		</div></div>
	</section>

	<section class="page-section page-section--paper" aria-labelledby="tech-cases-title">
		<div class="gutter"><div class="container @container">
		<div class="page-section__top @max-content:grid-cols-1">
			<div>
			<p class="page-eyebrow">Belege</p>
			<h2 class="page-title" id="tech-cases-title">Betrieb statt <em class="text-accent-text">Beratungsfolie.</em></h2>
			</div>
			<p class="page-section__lede">Zwei Beispiele, wie Technology-Disziplinen als Werkleistung geliefert werden — mit Service-Leveln, Migration und Betrieb aus einer Hand.</p>
		</div>
		<?php emposo_fragment( 'projects-technology' ); ?>
		<p style="margin-top: 2.7rem;"><a class="page-link min-h-11" href="/case-studies/">Alle Case Studies ansehen</a></p>
		</div></div>
	</section>

	<section class="page-section page-section--deep" aria-labelledby="tech-cta-title">
		<div class="gutter"><div class="container @container"><div class="page-cta @max-content:grid-cols-1">
		<div><p class="page-eyebrow page-eyebrow--light">Nächster Schritt</p><h2 class="page-cta__title" id="tech-cta-title">Welche Plattform soll produktiv <em>werden?</em></h2></div>
		<div class="page-cta__copy"><p>Von der Architektur bis zum SLA-gesteuerten Betrieb: Sprechen wir über Ihre Systeme, die Ergebnisdefinition und den sinnvollsten Einstieg.</p><a class="page-link page-link--light min-h-11" href="/kontakt/">Fachgespräch starten</a></div>
		</div></div></div>
	</section>
