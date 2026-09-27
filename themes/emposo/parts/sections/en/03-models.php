<?php
/**
 * Ported from the static build's sections/en/03-models.html.
 *
 * Markup is verbatim; only the template tokens became PHP calls. Generated once
 * by tools/port-partial.mjs and hand-maintained from here on — whitespace and
 * attribute order are load-bearing for the parity diff, so edit carefully.
 *
 * @package Emposo
 */

?>
<section class="statement page-section" id="about" aria-labelledby="statement-title">
	<div class="gutter"><div class="container">
	<p class="eyebrow">Why Emposo</p>
	<h2 class="display-large" id="statement-title">Scalable in delivery. Connected in the solution. <em>Accountable for the result.</em></h2>
	<p class="section-lede">We bring together expertise, technology and capacity, scale flexibly as needed and take responsibility for the result.</p>
	<?php emposo_fragment( 'company-facts' ); ?>
	<?php emposo_fragment( 'trust-strip' ); ?>
	</div></div>
</section>
