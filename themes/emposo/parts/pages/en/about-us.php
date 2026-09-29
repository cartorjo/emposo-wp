<?php
/**
 * Ported from the static build's pages/en/about-us.html.
 *
 * Markup is verbatim; only the template tokens became PHP calls. Generated once
 * by tools/port-partial.mjs and hand-maintained from here on — whitespace and
 * attribute order are load-bearing for the parity diff, so edit carefully.
 *
 * @package Emposo
 */

?>
<section class="page-hero" aria-labelledby="about-title"><div class="gutter"><div class="container @container"><div class="page-hero__grid @max-content:grid-cols-1"><div class="page-hero__copy"><nav class="page-breadcrumb" aria-label="Breadcrumb"><ol><li><a href="/en/">Home</a></li><li><span aria-hidden="true">/</span><span aria-current="page">About us</span></li></ol></nav><p class="eyebrow eyebrow--light">Emposo GmbH</p>
			<h1 class="display-large display-large--light" id="about-title">We are <em>Emposo</em>, the Outcome Factory of the Hays Group.</h1>
			<p class="page-hero__intro">Since 2014, we have taken demanding engineering and technology projects through to an accepted result. With clear responsibility, defined acceptance criteria and one goal: measurable results. That is how we make change manageable, not just up to the presentation, but all the way to effective implementation.</p></div><figure class="page-hero__visual about-hero__visual"><?php emposo_the_picture( 'engineering-knowledge', 'hero', true ); ?></figure></div></div></div></section>

	<section class="page-section page-section--paper" id="delivery" aria-labelledby="mission-title">
		<div class="gutter"><div class="container">
		<p class="eyebrow">Our mission</p>
		<h2 class="display-large" id="mission-title">Only those who take <em>responsibility</em> can make a difference.</h2>
		<p class="section-lede">Today, companies have to secure their day-to-day business and shape the future at the same time. That is exactly why Emposo was founded. We don’t just provide expertise; we take responsibility for clearly defined results. Instead of buying working hours, our clients receive accepted deliverables, predictable costs and a partner who stands behind the implementation.</p>
		</div></div>
	</section>

	<section class="page-section page-section--dark" aria-labelledby="strength-title">
		<div class="gutter"><div class="container">
		<p class="eyebrow eyebrow--light">Our team</p>
		<h2 class="display-large display-large--light" id="strength-title">The strength behind <em>Emposo.</em></h2>
		<div class="company-values">
			<article><p>We can only take responsibility for a result if we are convinced of its quality ourselves. We can make this promise because we trust our experts.</p></article>
			<article><p>Some of the best in their fields work in our core team. They combine deep industry knowledge with many years of experience from demanding engineering and technology projects and master their craft from concept to implementation. The result is work we stand behind with conviction.</p></article>
			<article><p>When additional capacity or specialized skills are needed, we add to the core team selectively via the Hays platform. Management and accountability for the outcome always remain with Emposo.</p></article>
		</div>
		<figure class="about-netzwerk"><?php emposo_the_picture( 'about-netzwerk-en' ); ?></figure>
		</div></div>
	</section>

	<section class="page-section" aria-labelledby="facts-title">
		<div class="gutter"><div class="container @container">
		<div class="page-section__top @max-content:grid-cols-1">
			<div><p class="eyebrow">The company in numbers</p><h2 class="display-large" id="facts-title">Experience that makes results <em class="text-ink">reliable.</em></h2></div>
			<p class="page-section__lede">Our industry knowledge doesn’t come from textbooks but from practice. Since 2014, we have combined deep industry know-how with modern technology in demanding engineering and technology projects, creating solutions that work in operations.</p>
		</div>
		<?php emposo_fragment( 'company-facts' ); ?>
		</div></div>
	</section>

	<?php emposo_fragment( 'management' ); ?>
