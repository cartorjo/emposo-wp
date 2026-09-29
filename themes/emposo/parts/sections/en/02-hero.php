<?php
/**
 * Ported from the static build's sections/en/02-hero.html.
 *
 * Markup is verbatim; only the template tokens became PHP calls. Generated once
 * by tools/port-partial.mjs and hand-maintained from here on — whitespace and
 * attribute order are load-bearing for the parity diff, so edit carefully.
 *
 * @package Emposo
 */

?>
<section class="hero hero--feedback" id="hero" aria-labelledby="hero-title">
	<div class="gutter"><div class="container">
	<div class="hero__grid">
		<div class="hero__copy">
		<h1 class="display-large display-large--light" id="hero-title"><?php emposo_f( 'sections/en/02-hero.01' ); ?></h1>
		<div class="hero__intro"><p><?php emposo_f( 'sections/en/02-hero.02' ); ?></p></div>
		</div>
		<figure class="hero__visual"><?php emposo_the_picture( emposo_f_image( 'sections/en/02-hero.img1' ), 'hero', true ); ?></figure>
	</div>
	</div></div>
</section>
