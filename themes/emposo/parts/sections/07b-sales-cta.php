<?php
/**
 * Ported from the static build's sections/07b-sales-cta.html.
 *
 * Markup is verbatim; only the template tokens became PHP calls. Generated once
 * by tools/port-partial.mjs and hand-maintained from here on — whitespace and
 * attribute order are load-bearing for the parity diff, so edit carefully.
 *
 * @package Emposo
 */

?>
<section class="contact page-section" id="contact" aria-labelledby="contact-title">
	<div class="gutter"><div class="container"><div class="contact__grid"><div><p class="eyebrow eyebrow--light"><?php emposo_f( 'sections/07b-sales-cta.01' ); ?></p><h2 class="display-large display-large--light" id="contact-title"><?php emposo_f( 'sections/07b-sales-cta.02' ); ?></h2><p class="contact__note"><?php emposo_f( 'sections/07b-sales-cta.03' ); ?></p></div><?php echo emposo_part_html( 'parts/contact-form' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template output, escaped at its own point of use. ?></div></div></div>
</section>
