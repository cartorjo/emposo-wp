<?php
/**
 * Ported from the static build's pages/en/kontakt.html.
 *
 * Markup is verbatim; only the template tokens became PHP calls. Generated once
 * by tools/port-partial.mjs and hand-maintained from here on — whitespace and
 * attribute order are load-bearing for the parity diff, so edit carefully.
 *
 * @package Emposo
 */

?>
<section class="contact page-section page-section--dark" aria-labelledby="kontakt-title">
		<div class="gutter"><div class="container @container">
		<nav class="page-breadcrumb" aria-label="Breadcrumb"><ol><li><a href="/en/">Home</a></li><li><span aria-hidden="true">/</span><span aria-current="page">Contact</span></li></ol></nav>
		<div class="contact__grid">
			<div>
			<p class="eyebrow eyebrow--light">Discuss your project</p>
			<h1 class="display-large display-large--light" id="kontakt-title">Your project. Our focus on <em>outcomes.</em></h1>
			<p class="contact__note">Let’s discuss your project. Whether you have a concrete project, need initial guidance or have other questions: tell us briefly what it is about.</p>
			</div>
			<?php echo emposo_part_html( 'parts/contact-form' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template output, escaped at its own point of use. ?>
		</div>
		</div></div>
	</section>

