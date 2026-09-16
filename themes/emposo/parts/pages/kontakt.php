<?php
/**
 * Ported from the static build's pages/kontakt.html.
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
		<p class="page-breadcrumb"><a href="/">Startseite</a><span aria-hidden="true">/</span> Kontakt</p>
		<div class="contact__grid @max-content:grid-cols-1">
			<div>
			<p class="eyebrow eyebrow--light">Kontakt</p>
			<h1 class="display-large display-large--light" id="kontakt-title">Jetzt Kontakt <em>aufnehmen!</em></h1>
			<p class="contact__note">Ob konkretes Vorhaben, erste Orientierung oder weitere Fragen. Erzählen Sie uns kurz, worum es geht.</p>
			</div>
			<?php get_template_part( 'parts/contact-form' ); ?>
		</div>
		</div></div>
	</section>

	<section class="page-section" aria-labelledby="kontakt-orte-title">
		<div class="gutter"><div class="container @container"><div class="about-locations @max-content:grid-cols-1">
		<div class="about-locations__map" aria-hidden="true"><span></span><span></span><span></span><span></span><strong>Deutschland + Rumänien</strong></div>
		<div class="about-locations__copy"><p class="page-eyebrow">Standorte</p><h2 id="kontakt-orte-title">Nah am Kunden. Bereit zu <em class="text-accent-text">liefern.</em></h2><p>Emposo GmbH — die Outcome Factory im Hays-Netzwerk. Wir arbeiten standortübergreifend; die richtige Expertise kommt dorthin, wo sie gebraucht wird.</p><div class="location-list"><div><strong>Mannheim</strong><span>Hauptsitz</span></div><div><strong>Düsseldorf</strong><span>Deutschland</span></div><div><strong>Frankfurt</strong><span>Deutschland</span></div><div><strong>Timișoara</strong><span>Rumänien</span></div></div></div>
		</div></div></div>
	</section>
