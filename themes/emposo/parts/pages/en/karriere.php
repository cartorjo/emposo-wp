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
<section class="page-hero" aria-labelledby="karriere-title"><div class="gutter"><div class="container @container"><div class="page-hero__grid @max-content:grid-cols-1"><div class="page-hero__copy"><nav class="page-breadcrumb" aria-label="Breadcrumb"><ol><li><a href="/en/">Home</a></li><li><span aria-hidden="true">/</span><span aria-current="page">Careers</span></li></ol></nav><p class="eyebrow eyebrow--light">Careers at Emposo</p>
			<h1 class="display-large display-large--light" id="karriere-title">More than expertise. The people behind the <em>outcome.</em></h1>
			<p class="page-hero__intro">At Emposo, you’ll find people who want to take on responsibility. People who don’t stop at concepts but create results. We combine engineering, technology and industry know-how into solutions that work in operations and create real value.</p></div><figure class="page-hero__visual"><?php emposo_the_picture( 'technology-team', 'hero', true ); ?></figure></div></div></div></section>

	<section class="page-section" aria-label="What connects us">
		<div class="gutter"><div class="container">
		<p class="section-lede">What connects us is not just professional excellence. It is the shared conviction that change becomes manageable through delivery. That’s why we work as partners, think across boundaries and take responsibility for what we achieve together.</p>
		</div></div>
	</section>

	<?php emposo_fragment( 'cta-karriere' ); ?>

	<section class="page-section page-section--paper" id="positionen" aria-labelledby="positionen-title">
		<div class="gutter"><div class="container">
		<p class="eyebrow">Careers</p>
		<h2 class="display-large" id="positionen-title">Open <em>positions.</em></h2>
		<?php emposo_fragment( 'jobs' ); ?>
		</div></div>
	</section>

	<?php emposo_fragment( 'keep-exploring' ); ?>
