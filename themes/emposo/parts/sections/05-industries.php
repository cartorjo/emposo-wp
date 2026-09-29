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
	<div class="section-heading"><p class="eyebrow"><?php emposo_f( 'sections/05-industries.01' ); ?></p><h2 class="display-large" id="industries-title"><?php emposo_f( 'sections/05-industries.02' ); ?></h2><p class="section-lede"><?php emposo_f( 'sections/05-industries.03' ); ?></p></div>
	<?php emposo_fragment( 'industry-cards' ); ?>
	</div></div>
</section>
