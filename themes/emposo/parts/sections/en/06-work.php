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
	<div class="work__heading"><div><p class="eyebrow">Projects</p><h2 class="display-large" id="work-title">Our results speak <em>for themselves.</em></h2></div><p>Every project ends with a clear outcome.<br>We weren’t part of the solution, we created it, because our teams and specialists come straight from your industry.</p></div>
	<?php emposo_fragment( 'projects-featured' ); ?>
	<p class="section-more"><a class="text-link" href="/en/industries/#referenzen">All projects <span aria-hidden="true">→</span></a></p>
	</div></div>
</section>
