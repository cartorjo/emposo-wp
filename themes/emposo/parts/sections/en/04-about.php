<?php
/**
 * Ported from the static build's sections/en/04-about.html.
 *
 * Markup is verbatim; only the template tokens became PHP calls. Generated once
 * by tools/port-partial.mjs and hand-maintained from here on — whitespace and
 * attribute order are load-bearing for the parity diff, so edit carefully.
 *
 * @package Emposo
 */

?>
<section class="services page-section" id="services" aria-labelledby="services-title">
  <div class="gutter"><div class="container">
    <div class="services-intro"><p class="eyebrow eyebrow--light">What we move for you</p><h2 class="display-large display-large--light" id="services-title">For every company today, change is a <em>permanent state.</em></h2><p>Emposo gives you room for what matters. With the usual market models, risk and control stay with the client. We take full responsibility for a defined service. From start to finish.</p></div>
    <div class="connection-model">
      <div class="connection-model__items">
        <p class="connection-model__label">What we deliver</p>
        <div class="connection-step"><span class="connection-step__icon"><?php emposo_icon( 'finance-trend-line' ); ?></span><div><h3>We optimize your core business</h3><p>We take over your processes, optimize them measurably and give you back room for your core business.</p></div></div>
        <div class="connection-step"><span class="connection-step__icon"><?php emposo_icon( 'reload-2-line' ); ?></span><div><h3>We actively transform your change</h3><p>We transform your processes with innovative technologies. Economically calculated, productively implemented and measured by the result.</p></div></div>
        <p class="connection-model__label connection-model__label--how">How we make it possible</p>
        <div class="connection-step connection-step--how"><span class="connection-step__icon"><?php emposo_icon( 'layers-4-vertical-line' ); ?></span><div><h3>We scale as needed</h3><p>250 specialists at the core, 3,000 experts and 10,000 partners behind us: we scale with your needs. You pay for results; we take responsibility.</p></div></div>
        <div class="connection-step connection-step--how"><span class="connection-step__icon"><?php emposo_icon( 'molecules-line' ); ?></span><div><h3>We integrate engineering and technology</h3><p>We bring together business units, IT and technology expertise into one result that we take responsibility for.</p></div></div>
      </div>
      <div class="connection-model__visual">
        <?php emposo_the_picture( 'verzahnung' ); ?>
        <p aria-hidden="true">One responsibility.<br><strong>One result.</strong></p>
      </div>
    </div>
  </div></div>
</section>
