<?php
/**
 * Ported from the static build's sections/02-hero.html.
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
		<h1 class="display-hero" id="hero-title">Wir machen<br><em>Wandel</em><br>beherrschbar.</h1>
		<div class="hero__intro"><p>Für unsere Kunden bauen wir produktive Lösungen und tragen die Verantwortung bis zum Outcome.</p></div>
		</div>
		<figure class="hero__visual"><?php emposo_the_picture( 'hero-flow', 'hero', true ); ?></figure>
	</div>
	</div></div>
</section>
