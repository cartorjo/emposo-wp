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
		<h1 class="display-large display-large--light" id="hero-title">We make<br><em>change</em><br>manageable.</h1>
		<div class="hero__intro"><p>We build productive solutions for our clients and take responsibility all the way to the outcome.</p></div>
		</div>
		<figure class="hero__visual"><?php emposo_the_picture( 'hero-flow', 'hero', true ); ?></figure>
	</div>
	</div></div>
</section>
