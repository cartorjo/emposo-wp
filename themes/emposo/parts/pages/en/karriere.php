<?php
/**
 * Ported from the static build's pages/en/karriere.html.
 *
 * Markup is verbatim; only the template tokens became PHP calls. Generated once
 * by tools/port-partial.mjs and hand-maintained from here on — whitespace and
 * attribute order are load-bearing for the parity diff, so edit carefully.
 *
 * @package Emposo
 */

?>
<section class="page-hero" aria-labelledby="karriere-title"><div class="gutter"><div class="container @container"><div class="page-hero__grid @max-content:grid-cols-1"><div class="page-hero__copy"><nav class="page-breadcrumb" aria-label="Breadcrumb"><ol><li><a href="/en/">Home</a></li><li><span aria-hidden="true">/</span><span aria-current="page">Careers</span></li></ol></nav><p class="eyebrow eyebrow--light"><?php emposo_f( 'pages/en/karriere.01' ); ?></p>
			<h1 class="display-large display-large--light" id="karriere-title"><?php emposo_f( 'pages/en/karriere.02' ); ?></h1>
			<p class="page-hero__intro"><?php emposo_f( 'pages/en/karriere.03' ); ?></p></div><figure class="page-hero__visual"><?php emposo_the_picture( emposo_f_image( 'pages/en/karriere.img1' ), 'hero', true ); ?></figure></div></div></div></section>

	<section class="page-section" aria-label="What connects us">
		<div class="gutter"><div class="container">
		<p class="section-lede"><?php emposo_f( 'pages/en/karriere.04' ); ?></p>
		</div></div>
	</section>

	<?php emposo_fragment( 'cta-karriere' ); ?>

	<section class="page-section page-section--paper" id="positionen" aria-labelledby="positionen-title">
		<div class="gutter"><div class="container">
		<p class="eyebrow"><?php emposo_f( 'pages/en/karriere.05' ); ?></p>
		<h2 class="display-large" id="positionen-title"><?php emposo_f( 'pages/en/karriere.06' ); ?></h2>
		<?php emposo_fragment( 'jobs' ); ?>
		</div></div>
	</section>

