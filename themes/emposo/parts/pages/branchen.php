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
<section class="page-hero" aria-labelledby="branchen-title"><div class="gutter"><div class="container @container"><div class="page-hero__grid @max-content:grid-cols-1"><div class="page-hero__copy"><nav class="page-breadcrumb" aria-label="Brotkrümelnavigation"><ol><li><a href="/">Startseite</a></li><li><span aria-hidden="true">/</span><span aria-current="page">Branchen &amp; Projekte</span></li></ol></nav><p class="eyebrow eyebrow--light">Industrie-Know-how</p><h1 class="display-large display-large--light" id="branchen-title">Unsere Teams kommen direkt aus Ihrer <em>Branche.</em></h1><p class="page-hero__intro">Wir sind in fünf Branchen zu Hause und passen die Lösungen an regulatorische und operative Anforderungen an. Langjährige Branchenerfahrung ermöglicht eine schnelle Einarbeitung und passende Lösungen.</p></div><figure class="page-hero__visual"><?php emposo_the_picture( 'energy', 'hero', true ); ?></figure></div></div></div></section>
<section class="page-section page-section--paper" aria-labelledby="branchen-list-title"><div class="gutter"><div class="container"><p class="eyebrow">Unsere Branchen</p><h2 class="display-large" id="branchen-list-title">Branchenwissen und Technologie <em>verzahnt.</em></h2><?php emposo_fragment( 'industry-cards' ); ?></div></div></section>
<section class="page-section" id="referenzen" aria-labelledby="branchen-case-title"><div class="gutter"><div class="container"><p class="eyebrow">Projekte</p><h2 class="display-large" id="branchen-case-title">Unsere Erfolge sprechen <em>für sich.</em></h2><p class="section-lede">Jedes Projekt endet mit einem klaren Outcome.<br>Wir waren nicht Teil der Lösung, wir haben sie geschaffen.</p><?php emposo_fragment( 'projects-all' ); ?></div></div></section>
