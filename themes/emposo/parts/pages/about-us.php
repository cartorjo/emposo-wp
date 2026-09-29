<?php
/**
 * Ported from the static build's pages/about-us.html.
 *
 * Markup is verbatim; only the template tokens became PHP calls. Generated once
 * by tools/port-partial.mjs and hand-maintained from here on — whitespace and
 * attribute order are load-bearing for the parity diff, so edit carefully.
 *
 * @package Emposo
 */

?>
<section class="page-hero" aria-labelledby="about-title"><div class="gutter"><div class="container @container"><div class="page-hero__grid @max-content:grid-cols-1"><div class="page-hero__copy"><nav class="page-breadcrumb" aria-label="Brotkrümelnavigation"><ol><li><a href="/">Startseite</a></li><li><span aria-hidden="true">/</span><span aria-current="page">Über uns</span></li></ol></nav><p class="eyebrow eyebrow--light"><?php emposo_f( 'pages/about-us.01' ); ?></p>
			<h1 class="display-large display-large--light" id="about-title"><?php emposo_f( 'pages/about-us.02' ); ?></h1>
			<p class="page-hero__intro"><?php emposo_f( 'pages/about-us.03' ); ?></p></div><figure class="page-hero__visual about-hero__visual"><?php emposo_the_picture( emposo_f_image( 'pages/about-us.img1' ), 'hero', true ); ?></figure></div></div></div></section>

	<section class="page-section page-section--paper" id="delivery" aria-labelledby="mission-title">
		<div class="gutter"><div class="container">
		<p class="eyebrow"><?php emposo_f( 'pages/about-us.04' ); ?></p>
		<h2 class="display-large" id="mission-title"><?php emposo_f( 'pages/about-us.05' ); ?></h2>
		<p class="section-lede"><?php emposo_f( 'pages/about-us.06' ); ?></p>
		</div></div>
	</section>

	<section class="page-section page-section--dark" aria-labelledby="strength-title">
		<div class="gutter"><div class="container">
		<p class="eyebrow eyebrow--light"><?php emposo_f( 'pages/about-us.07' ); ?></p>
		<h2 class="display-large display-large--light" id="strength-title"><?php emposo_f( 'pages/about-us.08' ); ?></h2>
		<div class="company-values">
			<article><p><?php emposo_f( 'pages/about-us.09' ); ?></p></article>
			<article><p><?php emposo_f( 'pages/about-us.10' ); ?></p></article>
			<article><p><?php emposo_f( 'pages/about-us.11' ); ?></p></article>
		</div>
		<figure class="about-netzwerk"><?php emposo_the_picture( emposo_f_image( 'pages/about-us.img2' ) ); ?></figure>
		</div></div>
	</section>

	<section class="page-section" aria-labelledby="facts-title">
		<div class="gutter"><div class="container @container">
		<div class="page-section__top @max-content:grid-cols-1">
			<div><p class="eyebrow"><?php emposo_f( 'pages/about-us.12' ); ?></p><h2 class="display-large" id="facts-title"><?php emposo_f( 'pages/about-us.13' ); ?></h2></div>
			<p class="page-section__lede"><?php emposo_f( 'pages/about-us.14' ); ?></p>
		</div>
		<?php emposo_fragment( 'company-facts' ); ?>
		</div></div>
	</section>

	<?php emposo_fragment( 'management' ); ?>
