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
        <nav class="page-breadcrumb" aria-label="Brotkrümelnavigation"><ol><li><a href="/">Startseite</a></li><li><span aria-hidden="true">/</span><span aria-current="page">Kontakt</span></li></ol></nav>
        <div class="contact__grid">
          <div>
            <p class="eyebrow eyebrow--light">Projekt besprechen</p>
            <h1 class="display-large display-large--light" id="kontakt-title">Ihr Projekt. Unser Fokus auf <em>Outcomes.</em></h1>
            <p class="contact__note">Lassen Sie uns Ihr Projekt besprechen. Ob konkretes Vorhaben, erste Orientierung oder weitere Fragen. Erzählen Sie uns kurz, worum es geht.</p>
          </div>
          <?php echo emposo_part_html( 'parts/contact-form' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template output, escaped at its own point of use. ?>
        </div>
      </div></div>
    </section>

    <?php emposo_fragment( 'keep-exploring' ); ?>
