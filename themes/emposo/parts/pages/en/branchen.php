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
<section class="page-hero" aria-labelledby="branchen-title"><div class="gutter"><div class="container @container"><div class="page-hero__grid @max-content:grid-cols-1"><div class="page-hero__copy"><nav class="page-breadcrumb" aria-label="Breadcrumb"><ol><li><a href="/en/">Home</a></li><li><span aria-hidden="true">/</span><span aria-current="page">Industries &amp; Projects</span></li></ol></nav><p class="eyebrow eyebrow--light"><?php emposo_f( 'pages/en/branchen.01' ); ?></p><h1 class="display-large display-large--light" id="branchen-title"><?php emposo_f( 'pages/en/branchen.02' ); ?></h1><p class="page-hero__intro"><?php emposo_f( 'pages/en/branchen.03' ); ?></p></div><figure class="page-hero__visual"><?php emposo_the_picture( emposo_f_image( 'pages/en/branchen.img1' ), 'hero', true ); ?></figure></div></div></div></section>
<section class="page-section page-section--paper" aria-labelledby="branchen-list-title"><div class="gutter"><div class="container"><p class="eyebrow"><?php emposo_f( 'pages/en/branchen.04' ); ?></p><h2 class="display-large" id="branchen-list-title"><?php emposo_f( 'pages/en/branchen.05' ); ?></h2><?php emposo_fragment( 'industry-cards' ); ?></div></div></section>
<section class="page-section" id="referenzen" aria-labelledby="branchen-case-title"><div class="gutter"><div class="container"><p class="eyebrow"><?php emposo_f( 'pages/en/branchen.06' ); ?></p><h2 class="display-large" id="branchen-case-title"><?php emposo_f( 'pages/en/branchen.07' ); ?></h2><p class="section-lede"><?php emposo_f( 'pages/en/branchen.08' ); ?></p><?php emposo_fragment( 'projects-all' ); ?></div></div></section>
