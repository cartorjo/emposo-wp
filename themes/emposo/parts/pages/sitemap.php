<?php
/**
 * Ported from the static build's pages/sitemap.html.
 *
 * Markup is verbatim; only the template tokens became PHP calls. Generated once
 * by tools/port-partial.mjs and hand-maintained from here on — whitespace and
 * attribute order are load-bearing for the parity diff, so edit carefully.
 *
 * @package Emposo
 */

?>
<section class="page-section"><div class="gutter"><div class="container"><nav class="page-breadcrumb" aria-label="Brotkrümelnavigation"><ol><li><a href="/">Startseite</a></li><li><span aria-hidden="true">/</span><span aria-current="page">Sitemap</span></li></ol></nav><p class="eyebrow">Orientierung</p><h1 class="display-large">Alle Seiten im Überblick.</h1><nav class="sitemap-grid" aria-label="Sitemap"><?php emposo_fragment( 'sitemap' ); ?></nav></div></div></section>
