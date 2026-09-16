<?php
/**
 * Ported from the static build's pages/expertise-ai-daten.html.
 *
 * Markup is verbatim; only the template tokens became PHP calls. Generated once
 * by tools/port-partial.mjs and hand-maintained from here on — whitespace and
 * attribute order are load-bearing for the parity diff, so edit carefully.
 *
 * @package Emposo
 */

?>
<section class="page-hero" aria-labelledby="ai-title">
		<div class="gutter"><div class="container @container"><div class="page-hero__grid @max-content:grid-cols-1">
		<div class="page-hero__copy">
			<p class="page-breadcrumb"><a href="/">Startseite</a><span aria-hidden="true">/</span> <a href="/expertise/">Expertise</a><span aria-hidden="true">/</span> AI &amp; Daten</p>
			<p class="page-kicker">Expertise / AI-Transformation &amp; Daten</p>
			<h1 class="page-display" id="ai-title">Aus verteiltem Wissen wird produktive <em>KI.</em></h1>
			<p class="page-hero__intro">AI-Strategie, Agentic AI, Knowledge Graphs, RAG, Datenplattformen, MLOps und Training: Wir überführen Daten und Wissen in produktive, nachvollziehbare AI-Anwendungen.</p>
		</div>
		<figure class="page-hero__visual lg:@max-content:aspect-video lg:@max-content:min-h-0">
			<?php emposo_the_picture( 'data2ai', 'hero', true ); ?>
			<figcaption><span>AI &amp; Daten</span><span>05 / 08</span></figcaption>
			<div class="page-hero__metric"><strong>7×</strong><span>Produktivitätssteigerung in der Data2AI Plattform</span></div>
		</figure>
		</div></div></div>
	</section>

	<section class="page-section" aria-labelledby="ai-fields-title">
		<div class="gutter"><div class="container @container">
		<div class="page-section__top @max-content:grid-cols-1">
			<div>
			<p class="page-eyebrow">Leistungsfelder</p>
			<h2 class="page-title" id="ai-fields-title">KI, die den Betrieb <em class="text-accent-text">aushält.</em></h2>
			</div>
			<p class="page-section__lede">Nicht der nächste Piloten-Showcase, sondern Anwendungen mit Wissensbasis, Nachvollziehbarkeit und Betriebsmodell — eingeführt gegen gemeinsam festgelegte Kriterien.</p>
		</div>
		<div class="fact-grid fact-grid--3">
			<article><span class="fact-grid__label">01 / Fundament</span><h3>Daten &amp; Wissensbasis</h3><p>Datenplattformen, Knowledge Graphs und strukturierte Wissensbasen machen verteiltes Engineering-Wissen verknüpfbar und nachweisbar.</p></article>
			<article><span class="fact-grid__label">02 / Anwendung</span><h3>Agentic AI &amp; RAG</h3><p>KI-Agenten und Retrieval-Anwendungen bringen das Wissen in die tägliche Arbeit — nachvollziehbar statt Blackbox.</p></article>
			<article><span class="fact-grid__label">03 / Betrieb</span><h3>MLOps &amp; Training</h3><p>Modelle werden regulatorisch sicher aktualisiert, überwacht und betrieben; Teams werden für die Arbeit mit KI befähigt.</p></article>
		</div>
		</div></div>
	</section>

	<section class="page-section page-section--paper" aria-labelledby="ai-case-title">
		<div class="gutter"><div class="container @container">
		<div class="page-section__top @max-content:grid-cols-1">
			<div>
			<p class="page-eyebrow">Expertise in Anwendung</p>
			<h2 class="page-title" id="ai-case-title">Die Data2AI <em class="text-accent-text">Plattform.</em></h2>
			</div>
			<p class="page-section__lede">Aus verteilten Engineering-Daten wird produktive KI: mit strukturierter Wissensbasis, nachweisbarer Verknüpfung und KI-Agenten für die Anwendung.</p>
		</div>
		<div class="expertise-case-strip @max-content:grid-cols-1">
			<figure class="expertise-case-strip__image"><?php emposo_the_picture( 'data2ai' ); ?></figure>
			<div class="expertise-case-strip__copy">
			<p class="page-eyebrow">Case Study 01</p>
			<h3>Data2AI Plattform</h3>
			<p>Verteilte Engineering-Daten werden über eine produktive Daten- und Wissensbasis für die Arbeit mit KI verfügbar gemacht.</p>
			<div class="expertise-case-strip__metric"><strong class="text-accent-text">7×</strong><span>Produktivitätssteigerung</span></div>
			<p style="margin-top: 1.8rem;"><a class="page-link min-h-11" href="/case-studies/data2ai-platform/">Case Study lesen</a></p>
			</div>
		</div>
		</div></div>
	</section>

	<section class="page-section" aria-labelledby="ai-cases-title">
		<div class="gutter"><div class="container @container">
		<div class="page-section__top @max-content:grid-cols-1">
			<div>
			<p class="page-eyebrow">Weitere Belege</p>
			<h2 class="page-title" id="ai-cases-title">KI mit <em class="text-accent-text">Nachweis.</em></h2>
			</div>
			<p class="page-section__lede">Drei weitere AI-Lieferungen — von der Wissensbasis bis zum regulierten Modellbetrieb.</p>
		</div>
		<?php emposo_fragment( 'projects-ai' ); ?>
		</div></div>
	</section>

	<section class="page-section page-section--deep" aria-labelledby="ai-cta-title">
		<div class="gutter"><div class="container @container"><div class="page-cta @max-content:grid-cols-1">
		<div><p class="page-eyebrow page-eyebrow--light">Nächster Schritt</p><h2 class="page-cta__title" id="ai-cta-title">Welcher Use Case rechnet sich <em>zuerst?</em></h2></div>
		<div class="page-cta__copy"><p>Wir starten mit Discovery und Concept &amp; ROI: Use Cases auswählen, Wirkung bewerten und erst dann investieren. Sprechen wir über Ihre Daten und Ihr Zielbild.</p><a class="page-link page-link--light min-h-11" href="/kontakt/?interesse=Transformation%20%2F%20AI-Use-Case">AI-Use-Case besprechen</a></div>
		</div></div></div>
	</section>
