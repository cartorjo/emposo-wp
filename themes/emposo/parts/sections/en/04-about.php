<?php
/**
 * Ported from the static build's sections/en/04-about.html.
 *
 * Markup is verbatim; only the template tokens became PHP calls. Generated once
 * by tools/port-partial.mjs and hand-maintained from here on — whitespace and
 * attribute order are load-bearing for the parity diff, so edit carefully.
 *
 * @package Emposo
 */

?>
<section class="services page-section" id="services" aria-labelledby="services-title">
	<div class="gutter"><div class="container">
	<div class="services-intro"><p class="eyebrow eyebrow--light"><?php emposo_f( 'sections/en/04-about.01' ); ?></p><h2 class="display-large display-large--light" id="services-title"><?php emposo_f( 'sections/en/04-about.02' ); ?></h2><p><?php emposo_f( 'sections/en/04-about.03' ); ?></p></div>
	<div class="connection-model">
		<div class="connection-model__items">
		<p class="connection-model__label"><?php emposo_f( 'sections/en/04-about.04' ); ?></p>
		<div class="connection-step"><span class="connection-step__icon"><?php emposo_icon( 'finance-trend-line' ); ?></span><div><h3><?php emposo_f( 'sections/en/04-about.05' ); ?></h3><p><?php emposo_f( 'sections/en/04-about.06' ); ?></p></div></div>
		<div class="connection-step"><span class="connection-step__icon"><?php emposo_icon( 'reload-2-line' ); ?></span><div><h3><?php emposo_f( 'sections/en/04-about.07' ); ?></h3><p><?php emposo_f( 'sections/en/04-about.08' ); ?></p></div></div>
		<p class="connection-model__label connection-model__label--how"><?php emposo_f( 'sections/en/04-about.09' ); ?></p>
		<div class="connection-step connection-step--how"><span class="connection-step__icon"><?php emposo_icon( 'layers-4-vertical-line' ); ?></span><div><h3><?php emposo_f( 'sections/en/04-about.10' ); ?></h3><p><?php emposo_f( 'sections/en/04-about.11' ); ?></p></div></div>
		<div class="connection-step connection-step--how"><span class="connection-step__icon"><?php emposo_icon( 'molecules-line' ); ?></span><div><h3><?php emposo_f( 'sections/en/04-about.12' ); ?></h3><p><?php emposo_f( 'sections/en/04-about.13' ); ?></p></div></div>
		</div>
		<div class="connection-model__visual">
		<?php emposo_the_picture( emposo_f_image( 'sections/en/04-about.img1' ) ); ?>
		<p aria-hidden="true">One responsibility.<br><strong>One result.</strong></p>
		</div>
	</div>
	</div></div>
</section>
