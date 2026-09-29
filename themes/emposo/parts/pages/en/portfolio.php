<?php
/**
 * Ported from the static build's pages/en/portfolio.html.
 *
 * Markup is verbatim; only the template tokens became PHP calls. Generated once
 * by tools/port-partial.mjs and hand-maintained from here on — whitespace and
 * attribute order are load-bearing for the parity diff, so edit carefully.
 *
 * @package Emposo
 */

?>
<section class="page-hero portfolio-hero" aria-labelledby="portfolio-title"><div class="gutter"><div class="container @container"><div class="page-hero__grid @max-content:grid-cols-1"><div class="page-hero__copy"><nav class="page-breadcrumb" aria-label="Breadcrumb"><ol><li><a href="/en/">Home</a></li><li><span aria-hidden="true">/</span><span aria-current="page">Services</span></li></ol></nav><p class="eyebrow eyebrow--light"><?php emposo_f( 'pages/en/portfolio.01' ); ?></p>
			<h1 class="display-large display-large--light" id="portfolio-title"><?php emposo_f( 'pages/en/portfolio.02' ); ?></h1>
			<p class="page-hero__intro"><?php emposo_f( 'pages/en/portfolio.03' ); ?></p></div><figure class="page-hero__visual"><?php emposo_the_picture( emposo_f_image( 'pages/en/portfolio.img1' ), 'hero', true ); ?></figure></div></div></div></section>

	<section class="page-section" aria-labelledby="portfolio-disciplines-title"><div class="gutter"><div class="container"><p class="eyebrow"><?php emposo_f( 'pages/en/portfolio.04' ); ?></p><h2 class="display-large" id="portfolio-disciplines-title"><?php emposo_f( 'pages/en/portfolio.05' ); ?></h2><p class="section-lede"><?php emposo_f( 'pages/en/portfolio.06' ); ?></p><div class="company-values company-values--paper company-values--2"><article><p><?php emposo_f( 'pages/en/portfolio.07' ); ?></p></article><article><p><?php emposo_f( 'pages/en/portfolio.08' ); ?></p></article></div><?php emposo_fragment( 'disciplines' ); ?></div></div></section>
	<section class="page-section page-section--dark" aria-labelledby="modes-title">
		<div class="gutter"><div class="container @container">
		<div class="page-section__top page-section__top--wide @max-content:grid-cols-1">
			<div>
			<p class="eyebrow eyebrow--light"><?php emposo_f( 'pages/en/portfolio.09' ); ?></p>
			<h2 class="display-large display-large--light" id="modes-title"><?php emposo_f( 'pages/en/portfolio.10' ); ?></h2>
			</div>
			<p class="page-section__lede page-section__lede--light"><?php emposo_f( 'pages/en/portfolio.11' ); ?></p>
		</div>

		<div class="portfolio-modes">
			<article class="portfolio-mode @max-content:grid-cols-1" id="optimieren">
			<span class="portfolio-mode__number">01</span>
			<h3><?php emposo_f( 'pages/en/portfolio.12' ); ?></h3>
			<p class="portfolio-mode__copy @max-content:col-auto"><?php emposo_f( 'pages/en/portfolio.13' ); ?></p>
			<p class="portfolio-mode__outcome @max-content:col-auto"><?php emposo_f( 'pages/en/portfolio.14' ); ?></p>
			</article>
			<article class="portfolio-mode @max-content:grid-cols-1" id="transformieren">
			<span class="portfolio-mode__number">02</span>
			<h3><?php emposo_f( 'pages/en/portfolio.15' ); ?></h3>
			<p class="portfolio-mode__copy @max-content:col-auto"><?php emposo_f( 'pages/en/portfolio.16' ); ?></p>
			<p class="portfolio-mode__outcome @max-content:col-auto"><?php emposo_f( 'pages/en/portfolio.17' ); ?></p>
			</article>
			<article class="portfolio-mode @max-content:grid-cols-1" id="skalieren">
			<span class="portfolio-mode__number">03</span>
			<h3><?php emposo_f( 'pages/en/portfolio.18' ); ?></h3>
			<p class="portfolio-mode__copy @max-content:col-auto"><?php emposo_f( 'pages/en/portfolio.19' ); ?></p>
			<p class="portfolio-mode__outcome @max-content:col-auto"><?php emposo_f( 'pages/en/portfolio.20' ); ?></p>
			</article>
			<article class="portfolio-mode @max-content:grid-cols-1" id="verzahnen">
			<span class="portfolio-mode__number">04</span>
			<h3><?php emposo_f( 'pages/en/portfolio.21' ); ?></h3>
			<p class="portfolio-mode__copy @max-content:col-auto"><?php emposo_f( 'pages/en/portfolio.22' ); ?></p>
			<p class="portfolio-mode__outcome @max-content:col-auto"><?php emposo_f( 'pages/en/portfolio.23' ); ?></p>
			</article>
		</div>
		</div></div>
	</section>

	<section class="page-section page-section--paper" id="delivery-model" aria-labelledby="model-title">
		<div class="gutter"><div class="container @container">
		<div class="page-section__top @max-content:grid-cols-1">
			<div>
			<p class="eyebrow"><?php emposo_f( 'pages/en/portfolio.24' ); ?></p>
			<h2 class="display-large" id="model-title"><?php emposo_f( 'pages/en/portfolio.25' ); ?></h2>
			</div>
			<p class="page-section__lede"><?php emposo_f( 'pages/en/portfolio.26' ); ?></p>
		</div>
		<div class="fact-grid fact-grid--5">
			<article><span class="fact-grid__label fact-grid__label--display">01</span><h3><?php emposo_f( 'pages/en/portfolio.27' ); ?></h3><p><?php emposo_f( 'pages/en/portfolio.28' ); ?></p></article>
			<article><span class="fact-grid__label fact-grid__label--display">02</span><h3><?php emposo_f( 'pages/en/portfolio.29' ); ?></h3><p><?php emposo_f( 'pages/en/portfolio.30' ); ?></p></article>
			<article><span class="fact-grid__label fact-grid__label--display">03</span><h3><?php emposo_f( 'pages/en/portfolio.31' ); ?></h3><p><?php emposo_f( 'pages/en/portfolio.32' ); ?></p></article>
			<article><span class="fact-grid__label fact-grid__label--display">04</span><h3><?php emposo_f( 'pages/en/portfolio.33' ); ?></h3><p><?php emposo_f( 'pages/en/portfolio.34' ); ?></p></article>
			<article><span class="fact-grid__label fact-grid__label--display">05</span><h3><?php emposo_f( 'pages/en/portfolio.35' ); ?></h3><p><?php emposo_f( 'pages/en/portfolio.36' ); ?></p></article>
		</div>
		</div></div>
	</section>

	<section class="page-section page-section--paper" id="qualitaet" aria-labelledby="quality-title"><div class="gutter"><div class="container"><p class="eyebrow"><?php emposo_f( 'pages/en/portfolio.37' ); ?></p><h2 class="display-large" id="quality-title"><?php emposo_f( 'pages/en/portfolio.38' ); ?></h2><p class="section-lede"><?php emposo_f( 'pages/en/portfolio.39' ); ?></p><div class="company-values"><article><span class="company-values__icon" aria-hidden="true"><?php emposo_icon( 'checkbox-list-line' ); ?></span><h3><?php emposo_f( 'pages/en/portfolio.40' ); ?></h3><p><?php emposo_f( 'pages/en/portfolio.41' ); ?></p></article><article><span class="company-values__icon" aria-hidden="true"><?php emposo_icon( 'target-line' ); ?></span><h3><?php emposo_f( 'pages/en/portfolio.42' ); ?></h3><p><?php emposo_f( 'pages/en/portfolio.43' ); ?></p></article><article><span class="company-values__icon" aria-hidden="true"><?php emposo_icon( 'handshake-2-line' ); ?></span><h3><?php emposo_f( 'pages/en/portfolio.44' ); ?></h3><p><?php emposo_f( 'pages/en/portfolio.45' ); ?></p></article></div><?php emposo_fragment( 'trust-strip' ); ?></div></div></section>
	<?php emposo_fragment( 'cta-portfolio' ); ?>
