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
	<div class="gutter"><div class="container"><div class="contact__grid"><div><p class="eyebrow eyebrow--light">Kontakt</p><h2 class="display-large display-large--light" id="contact-title">Jetzt Kontakt <em>aufnehmen!</em></h2><p class="contact__note">Ob konkretes Vorhaben, erste Orientierung oder weitere Fragen. Erzählen Sie uns kurz, worum es geht.</p></div><?php get_template_part( 'parts/contact-form' ); ?></div></div></div>
</section>
