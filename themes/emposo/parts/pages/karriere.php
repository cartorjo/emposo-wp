<?php
/**
 * Ported from the static build's pages/karriere.html.
 *
 * Markup is verbatim; only the template tokens became PHP calls. Generated once
 * by tools/port-partial.mjs and hand-maintained from here on — whitespace and
 * attribute order are load-bearing for the parity diff, so edit carefully.
 *
 * @package Emposo
 */

?>
<section class="page-hero" aria-labelledby="karriere-title"><div class="gutter"><div class="container @container"><div class="page-hero__grid @max-content:grid-cols-1"><div class="page-hero__copy"><nav class="page-breadcrumb" aria-label="Brotkrümelnavigation"><ol><li><a href="/">Startseite</a></li><li><span aria-hidden="true">/</span><span aria-current="page">Karriere</span></li></ol></nav><p class="eyebrow eyebrow--light">Karriere bei Emposo</p>
			<h1 class="display-large display-large--light" id="karriere-title">Mehr als Expertise. Die Menschen hinter dem <em>Outcome.</em></h1>
			<p class="page-hero__intro">Bei Emposo arbeiten Menschen, die Verantwortung übernehmen wollen. Menschen, die nicht bei Konzepten stehen bleiben, sondern Ergebnisse schaffen. Wir verbinden Engineering, Technologie und Branchen-Know-how zu Lösungen, die im Betrieb funktionieren und echten Mehrwert erzeugen.</p></div><figure class="page-hero__visual"><?php emposo_the_picture( 'technology-team', 'hero', true ); ?></figure></div></div></div></section>

	<section class="page-section" aria-label="Was uns verbindet">
		<div class="gutter"><div class="container">
		<p class="section-lede">Was uns verbindet, ist nicht nur fachliche Exzellenz. Es ist die gemeinsame Überzeugung, dass Wandel durch Lieferung beherrschbar wird. Deshalb arbeiten wir partnerschaftlich, denken über Grenzen hinweg und übernehmen Verantwortung für das, was wir gemeinsam erreichen.</p>
		</div></div>
	</section>

	<?php emposo_fragment( 'cta-karriere' ); ?>

	<section class="page-section page-section--paper" id="positionen" aria-labelledby="positionen-title">
		<div class="gutter"><div class="container">
		<p class="eyebrow">Karriere</p>
		<h2 class="display-large" id="positionen-title">Offene <em>Positionen.</em></h2>
		<?php emposo_fragment( 'jobs' ); ?>
		</div></div>
	</section>

