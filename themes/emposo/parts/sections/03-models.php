<?php
/**
 * Ported from the static build's sections/03-models.html.
 *
 * Markup is verbatim; only the template tokens became PHP calls. Generated once
 * by tools/port-partial.mjs and hand-maintained from here on — whitespace and
 * attribute order are load-bearing for the parity diff, so edit carefully.
 *
 * @package Emposo
 */

?>
<section class="statement page-section" id="about" aria-labelledby="statement-title">
	<div class="gutter"><div class="container">
	<p class="eyebrow">Warum Emposo</p>
	<h2 class="display-large" id="statement-title">Skalierbar in der Umsetzung. Vernetzt in der Lösung. <em>Verantwortlich im Ergebnis.</em></h2>
	<p class="section-lede">Wir bringen Expertise, Technologie und Kapazität zusammen, skalieren flexibel nach Bedarf und übernehmen Verantwortung für das Ergebnis.</p>
	<?php emposo_fragment( 'company-facts' ); ?>
	<?php emposo_fragment( 'trust-strip' ); ?>
	</div></div>
</section>
