<?php
/**
 * Ported from the static build's pages/branchen.html.
 *
 * Markup is verbatim; only the template tokens became PHP calls. Generated once
 * by tools/port-partial.mjs and hand-maintained from here on — whitespace and
 * attribute order are load-bearing for the parity diff, so edit carefully.
 *
 * @package Emposo
 */

?>
<section class="page-hero" aria-labelledby="branchen-title"><div class="gutter"><div class="container"><div class="page-hero__grid"><div class="page-hero__copy"><p class="page-breadcrumb"><a href="/">Startseite</a><span aria-hidden="true">/</span>Branchen &amp; Case Studies</p><p class="page-kicker">Industrie-Know-how</p><h1 class="page-display" id="branchen-title">Unsere Teams kommen direkt aus Ihrer <em>Branche.</em></h1><p class="page-hero__intro">Wir sind in fünf Branchen zu Hause und passen die Lösungen an regulatorische und operative Anforderungen an. Langjährige Branchenerfahrung ermöglicht eine schnelle Einarbeitung und passende Lösungen.</p></div><figure class="page-hero__visual"><?php emposo_the_picture( 'energy', 'hero', true ); ?></figure></div></div></div></section>
<section class="page-section page-section--paper" aria-labelledby="branchen-list-title"><div class="gutter"><div class="container"><p class="eyebrow">Unsere Branchen</p><h2 class="page-title" id="branchen-list-title">Branchenwissen und Technologie <em>verzahnt.</em></h2><?php emposo_fragment( 'industry-cards' ); ?></div></div></section>
<section class="page-section" id="referenzen" aria-labelledby="branchen-case-title"><div class="gutter"><div class="container"><p class="eyebrow">Referenzprojekte</p><h2 class="page-title" id="branchen-case-title">Unsere Erfolge sprechen <em>für sich.</em></h2><p class="section-lede">Jedes Projekt endet mit einem klaren Outcome. Wir waren nicht Teil der Lösung, wir haben sie geschaffen.</p><?php emposo_fragment( 'projects-all' ); ?></div></div></section>
