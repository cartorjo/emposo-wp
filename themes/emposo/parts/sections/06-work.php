<?php
/**
 * Ported from the static build's sections/06-work.html.
 *
 * Markup is verbatim; only the template tokens became PHP calls. Generated once
 * by tools/port-partial.mjs and hand-maintained from here on — whitespace and
 * attribute order are load-bearing for the parity diff, so edit carefully.
 *
 * @package Emposo
 */

?>
<section class="work page-section" id="work" aria-labelledby="work-title">
	<div class="gutter"><div class="container">
	<div class="work__heading"><div><p class="eyebrow">Referenzprojekte</p><h2 class="display-large" id="work-title">Unsere Erfolge sprechen <em>für sich.</em></h2></div><p>Jedes Projekt endet mit einem klaren Outcome. Wir waren nicht Teil der Lösung, wir haben sie geschaffen, da unsere Teams und Spezialisten direkt aus Ihrer Branche kommen.</p></div>
	<?php emposo_fragment( 'projects-featured' ); ?>
	<p class="section-more"><a class="text-link" href="/branchen/#referenzen">Alle Referenzen <span aria-hidden="true">→</span></a></p>
	</div></div>
</section>
