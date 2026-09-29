<?php
/**
 * Ported from the static build's sections/en/06-work.html.
 *
 * Markup is verbatim; only the template tokens became PHP calls. Generated once
 * by tools/port-partial.mjs and hand-maintained from here on — whitespace and
 * attribute order are load-bearing for the parity diff, so edit carefully.
 *
 * @package Emposo
 */

?>
<section class="work page-section" id="work" aria-labelledby="work-title">
	<div class="gutter"><div class="container">
	<div class="work__heading"><div><p class="eyebrow"><?php emposo_f( 'sections/en/06-work.01' ); ?></p><h2 class="display-large" id="work-title"><?php emposo_f( 'sections/en/06-work.02' ); ?></h2></div><p><?php emposo_f( 'sections/en/06-work.03' ); ?></p></div>
	<?php emposo_fragment( 'projects-featured' ); ?>
	<p class="section-more"><a class="text-link" href="/en/industries/#referenzen"><?php emposo_f( 'sections/en/06-work.04' ); ?> <span aria-hidden="true">→</span></a></p>
	</div></div>
</section>
