<?php
/**
 * Ported from the static build's pages/portfolio-transformieren.html.
 *
 * Markup is verbatim; only the template tokens became PHP calls. Generated once
 * by tools/port-partial.mjs and hand-maintained from here on — whitespace and
 * attribute order are load-bearing for the parity diff, so edit carefully.
 *
 * @package Emposo
 */

?>
<section class="page-hero" aria-labelledby="trans-title">
		<div class="gutter"><div class="container @container"><div class="page-hero__grid @max-content:grid-cols-1">
		<div class="page-hero__copy">
			<p class="page-breadcrumb"><a href="/">Startseite</a><span aria-hidden="true">/</span> <a href="/portfolio/">Portfolio</a><span aria-hidden="true">/</span> Transformieren</p>
			<p class="page-kicker">Portfolio / Modell 02</p>
			<h1 class="page-display" id="trans-title">Wir <em>transformieren.</em></h1>
			<p class="page-hero__intro">Wir bauen Prozesse und Wertschöpfung mit innovativen Technologien um: gerechnet, bevor investiert wird, geliefert bis in die produktive Umgebung und dort nachgemessen.</p>
		</div>
		<figure class="page-hero__visual lg:@max-content:aspect-video lg:@max-content:min-h-0">
			<?php emposo_the_picture( 'hero-flow', 'hero', true ); ?>
			<figcaption><span>Wir transformieren</span><span>02 / 04</span></figcaption>
			<div class="page-hero__metric"><strong>7×</strong><span>Produktivitätssteigerung in der Data2AI Plattform</span></div>
		</figure>
		</div></div></div>
	</section>

	<section class="page-section" aria-labelledby="trans-how-title">
		<div class="gutter"><div class="container @container">
		<div class="page-section__top @max-content:grid-cols-1">
			<div>
			<p class="page-eyebrow">Was das bedeutet</p>
			<h2 class="page-title" id="trans-how-title">Technologie produktiv <em class="text-accent-text">machen.</em></h2>
			</div>
			<p class="page-section__lede">Neue Technologien führen wir gegen gemeinsam festgelegte Kriterien ein — vom Use Case über den Proof of Value bis zum MVP im Betrieb und der Skalierung.</p>
		</div>
		<div class="fact-grid fact-grid--5 fact-grid--flush">
			<article><span class="fact-grid__label">01</span><h3>Discovery</h3><p>Use Cases auswählen und Wirkung bewerten.</p></article>
			<article><span class="fact-grid__label">02</span><h3>Concept &amp; ROI</h3><p>Zielbild, Machbarkeit und Nutzen konkretisieren.</p></article>
			<article><span class="fact-grid__label">03</span><h3>Proof of Value</h3><p>Wirkung in der echten Umgebung verifizieren.</p></article>
			<article><span class="fact-grid__label">04</span><h3>MVP in Operation</h3><p>Eine erste produktive Version in Betrieb nehmen.</p></article>
			<article><span class="fact-grid__label">05</span><h3>Skalierung</h3><p>Vom bewiesenen Ergebnis in die Breite gehen.</p></article>
		</div>
		</div></div>
	</section>

	<section class="page-section page-section--paper" aria-labelledby="trans-expertise-title">
		<div class="gutter"><div class="container @container">
		<div class="page-section__top @max-content:grid-cols-1">
			<div><p class="page-eyebrow">Passende Expertise</p><h2 class="page-title" id="trans-expertise-title">Disziplinen, die hier <em class="text-accent-text">liefern.</em></h2></div>
			<p class="page-section__lede">Transformation verbindet Technology-Disziplinen mit Test und Validierung — bis das Ergebnis in der Produktivumgebung nachgemessen ist.</p>
		</div>
		<div class="expertise-list">
			<a class="expertise-card" href="/expertise/ai-daten/">
			<span class="expertise-card__number text-accent-text">05 / Technology</span>
			<h3>AI-Transformation &amp; Daten</h3>
			<p>AI-Strategie, Agentic AI, Knowledge Graphs, RAG, Datenplattformen, MLOps und Training.</p>
			<span class="expertise-card__promise">Wir überführen Daten und Wissen in produktive, nachvollziehbare AI-Anwendungen.</span>
			</a>
			<a class="expertise-card" href="/expertise/technology/">
			<span class="expertise-card__number text-accent-text">06 / Technology</span>
			<h3>Software, Cloud &amp; Architecture</h3>
			<p>Solution Architecture, Multi-Cloud, Fullstack, APIs, CI/CD, DevSecOps und Plattformintegration.</p>
			<span class="expertise-card__promise">Wir bauen und transformieren Plattformen und Anwendungen bis zum produktiven Betrieb.</span>
			</a>
			<a class="expertise-card" href="/expertise/engineering/">
			<span class="expertise-card__number text-accent-text">03 / Engineering</span>
			<h3>Test, Validierung &amp; Integration</h3>
			<p>Teststrategie, Automatisierung, Integration, Safety, Quality Gates und revisionssichere Nachweise.</p>
			<span class="expertise-card__promise">Wir machen Qualität, Sicherheit und Freigabeentscheidungen belastbar.</span>
			</a>
		</div>
		</div></div>
	</section>

	<section class="page-section" aria-labelledby="trans-cases-title">
		<div class="gutter"><div class="container @container">
		<div class="page-section__top @max-content:grid-cols-1">
			<div><p class="page-eyebrow">Belege</p><h2 class="page-title" id="trans-cases-title">Transformation mit <em class="text-accent-text">Nachweis.</em></h2></div>
			<p class="page-section__lede">Vier Lieferungen, in denen Technologie bis zum messbaren Ergebnis in die Produktivumgebung gebracht wurde.</p>
		</div>
		<?php emposo_fragment( 'projects-transform' ); ?>
		<p style="margin-top: 2.7rem;"><a class="page-link min-h-11" href="/case-studies/">Alle Case Studies ansehen</a></p>
		</div></div>
	</section>

	<section class="page-section page-section--deep" aria-labelledby="trans-cta-title">
		<div class="gutter"><div class="container @container"><div class="page-cta @max-content:grid-cols-1">
		<div><p class="page-eyebrow page-eyebrow--light">Nächster Schritt</p><h2 class="page-cta__title" id="trans-cta-title">Welcher Use Case rechnet sich <em>zuerst?</em></h2></div>
		<div class="page-cta__copy"><p>Wir rechnen, bevor investiert wird: Discovery, Concept &amp; ROI, dann der Proof of Value in Ihrer echten Umgebung. Sprechen wir über Ihr Zielbild.</p><a class="page-link page-link--light min-h-11" href="/kontakt/?interesse=Transformation%20%2F%20AI-Use-Case">Transformation besprechen</a></div>
		</div></div></div>
	</section>
