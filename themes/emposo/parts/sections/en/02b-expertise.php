<?php
/**
 * Ported from the static build's sections/en/02b-expertise.html.
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
    <div class="expertise-intro__heading"><div><p class="eyebrow">Engineering + Technology</p><h2 class="display-large" id="home-expertise-title">We integrate what <em>belongs together.</em></h2></div><p>Many projects fail at the transitions between engineering and IT. Emposo integrates both worlds, so that technical concepts become productive solutions.</p></div>
    <div class="expertise-pair">
      <div><figure><?php emposo_the_picture( 'engineering' ); ?></figure><div><h3>Engineering</h3></div><p>From the requirement to the validated function.</p></div>
      <span class="expertise-pair__join" aria-hidden="true">+</span>
      <div><figure><?php emposo_the_picture( 'technology-team' ); ?></figure><div><h3>Technology</h3></div><p>From data and software to production.</p></div>
    </div>
    <p class="section-more"><strong>We connect what others treat separately.</strong></p>
  </div></div>
</section>
