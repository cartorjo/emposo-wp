<?php
/**
 * Ported from the static build's pages/en/branchen.html.
 *
 * Markup is verbatim; only the template tokens became PHP calls. Generated once
 * by tools/port-partial.mjs and hand-maintained from here on — whitespace and
 * attribute order are load-bearing for the parity diff, so edit carefully.
 *
 * @package Emposo
 */

?>
<section class="page-hero" aria-labelledby="branchen-title"><div class="gutter"><div class="container @container"><div class="page-hero__grid @max-content:grid-cols-1"><div class="page-hero__copy"><nav class="page-breadcrumb" aria-label="Breadcrumb"><ol><li><a href="/en/">Home</a></li><li><span aria-hidden="true">/</span><span aria-current="page">Industries &amp; Projects</span></li></ol></nav><p class="eyebrow eyebrow--light">Industry know-how</p><h1 class="display-large display-large--light" id="branchen-title">Our teams come straight from your <em>industry.</em></h1><p class="page-hero__intro">We are at home in five industries and adapt our solutions to regulatory and operational requirements. Many years of industry experience mean fast onboarding and solutions that fit.</p></div><figure class="page-hero__visual"><?php emposo_the_picture( 'energy', 'hero', true ); ?></figure></div></div></div></section>
<section class="page-section page-section--paper" aria-labelledby="branchen-list-title"><div class="gutter"><div class="container"><p class="eyebrow">Our industries</p><h2 class="display-large" id="branchen-list-title">Industry knowledge and technology, <em>integrated.</em></h2><?php emposo_fragment( 'industry-cards' ); ?></div></div></section>
<section class="page-section" id="referenzen" aria-labelledby="branchen-case-title"><div class="gutter"><div class="container"><p class="eyebrow">Projects</p><h2 class="display-large" id="branchen-case-title">Our results speak <em>for themselves.</em></h2><p class="section-lede">Every project ends with a clear outcome.<br>We weren’t part of the solution, we created it.</p><?php emposo_fragment( 'projects-all' ); ?></div></div></section>
