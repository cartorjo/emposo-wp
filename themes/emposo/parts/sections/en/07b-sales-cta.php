<?php
/**
 * Ported from the static build's sections/en/07b-sales-cta.html.
 *
 * Markup is verbatim; only the template tokens became PHP calls. Generated once
 * by tools/port-partial.mjs and hand-maintained from here on — whitespace and
 * attribute order are load-bearing for the parity diff, so edit carefully.
 *
 * @package Emposo
 */

?>
<section class="contact page-section" id="contact" aria-labelledby="contact-title">
  <div class="gutter"><div class="container"><div class="contact__grid"><div><p class="eyebrow eyebrow--light">Contact</p><h2 class="display-large display-large--light" id="contact-title">Get in touch <em>now!</em></h2><p class="contact__note">Whether you have a concrete project, need initial guidance or have other questions: tell us briefly what it is about.</p></div><?php echo emposo_part_html( 'parts/contact-form' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template output, escaped at its own point of use. ?></div></div></div>
</section>
