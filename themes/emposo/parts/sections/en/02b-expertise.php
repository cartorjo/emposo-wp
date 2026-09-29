<?php
/**
 * Ported from the static build's sections/en/02b-expertise.html.
 *
 * Markup is verbatim; only the template tokens became PHP calls. Generated once
 * by tools/port-partial.mjs and hand-maintained from here on — whitespace and
 * attribute order are load-bearing for the parity diff, so edit carefully.
 *
 * @package Emposo
 */

?>
<section class="page-section expertise-intro" aria-labelledby="home-expertise-title">
	<div class="gutter"><div class="container">
	<div class="expertise-intro__heading"><div><p class="eyebrow"><?php emposo_f( 'sections/en/02b-expertise.01' ); ?></p><h2 class="display-large" id="home-expertise-title"><?php emposo_f( 'sections/en/02b-expertise.02' ); ?></h2></div><p><?php emposo_f( 'sections/en/02b-expertise.03' ); ?></p></div>
	<div class="expertise-pair">
		<div><figure><?php emposo_the_picture( emposo_f_image( 'sections/en/02b-expertise.img1' ) ); ?></figure><div><h3><?php emposo_f( 'sections/en/02b-expertise.04' ); ?></h3></div><p><?php emposo_f( 'sections/en/02b-expertise.05' ); ?></p></div>
		<span class="expertise-pair__join" aria-hidden="true">+</span>
		<div><figure><?php emposo_the_picture( emposo_f_image( 'sections/en/02b-expertise.img2' ) ); ?></figure><div><h3><?php emposo_f( 'sections/en/02b-expertise.06' ); ?></h3></div><p><?php emposo_f( 'sections/en/02b-expertise.07' ); ?></p></div>
	</div>
	<p class="section-more"><?php emposo_f( 'sections/en/02b-expertise.08' ); ?></p>
	</div></div>
</section>
