<?php
/**
 * Ported from the static build's sections/02b-expertise.html.
 *
 * Markup is verbatim; only the template tokens became PHP calls. Generated once
 * by tools/port-partial.mjs and hand-maintained from here on — whitespace and
 * attribute order are load-bearing for the parity diff, so edit carefully.
 *
 * @package Emposo
 */

?>
<section class="page-section expertise-intro" aria-labelledby="home-expertise-title">
	<div class="gutter"><div class="container">
	<div class="expertise-intro__heading"><div><p class="eyebrow">Engineering + Technology</p><h2 class="display-large" id="home-expertise-title">Wir verzahnen, was <em>zusammengehört.</em></h2></div><p>Viele Projekte scheitern an den Übergängen zwischen Engineering und IT. Emposo verzahnt beide Welten, damit aus technischen Konzepten produktive Lösungen werden.</p></div>
	<div class="expertise-pair">
		<a href="/expertise/engineering/"><figure><?php emposo_the_picture( 'engineering' ); ?></figure><div><h3>Engineering</h3><span aria-hidden="true">→</span></div><p>Von der Anforderung bis zur abgesicherten Funktion.</p></a>
		<span class="expertise-pair__join" aria-hidden="true">+</span>
		<a href="/expertise/technology/"><figure><?php emposo_the_picture( 'technology-team' ); ?></figure><div><h3>Technology</h3><span aria-hidden="true">→</span></div><p>Von Daten und Software bis zum produktiven Betrieb.</p></a>
	</div>
	<p class="section-more"><a class="text-link" href="/portfolio/">Wir verbinden, was andere getrennt betrachten <span aria-hidden="true">→</span></a></p>
	</div></div>
</section>
