<?php
/**
 * Ported from the static build's sections/05-industries.html.
 *
 * Markup is verbatim; only the template tokens became PHP calls. Generated once
 * by tools/port-partial.mjs and hand-maintained from here on — whitespace and
 * attribute order are load-bearing for the parity diff, so edit carefully.
 *
 * @package Emposo
 */

?>
<section class="industries page-section" id="industries" aria-labelledby="industries-title">
	<div class="gutter"><div class="container">
	<div class="section-heading"><p class="eyebrow">Branchen</p><h2 class="display-large" id="industries-title">Unsere Branchen mit tiefem <em>Industrie-Know-how.</em></h2><p class="section-lede">Unsere Teams und Spezialisten kommen direkt aus Ihrer Branche.</p></div>
	<?php emposo_fragment( 'industry-cards' ); ?>
	<p class="section-more"><a class="text-link" href="/branchen/">Branchen und Referenzprojekte <span aria-hidden="true">→</span></a></p>
	</div></div>
</section>
