<?php
/**
 * Ported from the static build's sections/en/05-industries.html.
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
	<div class="section-heading"><p class="eyebrow">Industries</p><h2 class="display-large" id="industries-title">Our industries, with deep <em>industry know-how.</em></h2><p class="section-lede">Our teams and specialists come straight from your industry.</p></div>
	<?php emposo_fragment( 'industry-cards' ); ?>
	</div></div>
</section>
