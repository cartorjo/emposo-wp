<?php
/**
 * Ported from the static build's pages/about-us.html.
 *
 * Markup is verbatim; only the template tokens became PHP calls. Generated once
 * by tools/port-partial.mjs and hand-maintained from here on — whitespace and
 * attribute order are load-bearing for the parity diff, so edit carefully.
 *
 * @package Emposo
 */

?>
<section class="page-hero" aria-labelledby="about-title"><div class="gutter"><div class="container @container"><div class="page-hero__grid @max-content:grid-cols-1"><div class="page-hero__copy"><nav class="page-breadcrumb" aria-label="Brotkrümelnavigation"><ol><li><a href="/">Startseite</a></li><li><span aria-hidden="true">/</span><span aria-current="page">Über uns</span></li></ol></nav><p class="eyebrow eyebrow--light">Emposo GmbH</p>
          <h1 class="display-large display-large--light" id="about-title">Wir sind <em>Emposo</em>, die Outcome Factory der Hays Gruppe.</h1>
          <p class="page-hero__intro">Seit 2014 führen wir anspruchsvolle Engineering- und Technologievorhaben bis zum abgenommenen Ergebnis. Mit klarer Verantwortung, definierten Abnahmekriterien und einem Ziel: messbare Ergebnisse. So machen wir Wandel beherrschbar, nicht nur bis zur Präsentation, sondern bis zur wirksamen Umsetzung.</p></div><figure class="page-hero__visual about-hero__visual"><?php emposo_the_picture( 'engineering-knowledge', 'hero', true ); ?></figure></div></div></div></section>

    <section class="page-section page-section--paper" id="delivery" aria-labelledby="mission-title">
      <div class="gutter"><div class="container">
        <p class="eyebrow">Unsere Mission</p>
        <h2 class="display-large" id="mission-title">Nur wer <em>Verantwortung</em> übernimmt, kann etwas ändern.</h2>
        <p class="section-lede">Unternehmen müssen heute das Tagesgeschäft sichern und gleichzeitig die Zukunft gestalten. Genau dafür wurde Emposo gegründet. Wir liefern nicht nur Expertise, sondern übernehmen Verantwortung für klar definierte Ergebnisse. Statt Arbeitszeit einzukaufen, erhalten unsere Kunden abgenommene Leistungen, kalkulierbare Kosten und einen Partner, der für die Umsetzung einsteht.</p>
      </div></div>
    </section>

    <section class="page-section page-section--dark" aria-labelledby="strength-title">
      <div class="gutter"><div class="container">
        <p class="eyebrow eyebrow--light">Unser Team</p>
        <h2 class="display-large display-large--light" id="strength-title">Die Stärke hinter <em>Emposo.</em></h2>
        <div class="company-values">
          <article><p>Verantwortung für ein Ergebnis können wir nur übernehmen, wenn wir selbst von seiner Qualität überzeugt sind. Dieses Versprechen können wir geben, weil wir unseren Expertinnen und Experten vertrauen.</p></article>
          <article><p>In unserem Kernteam arbeiten einige der Besten ihres Fachs. Sie verbinden fundiertes Branchenwissen mit langjähriger Erfahrung aus anspruchsvollen Engineering- und Technologieprojekten und beherrschen ihr Handwerk von der Konzeption bis zur Umsetzung. So entstehen Ergebnisse, für die wir mit Überzeugung einstehen.</p></article>
          <article><p>Wenn zusätzliche Kapazitäten oder spezielle Kompetenzen erforderlich sind, ergänzen wir das Kernteam gezielt über die Hays-Plattform. Die Steuerung und Ergebnisverantwortung bleiben dabei jederzeit bei Emposo.</p></article>
        </div>
        <figure class="about-netzwerk"><?php emposo_the_picture( 'about-netzwerk' ); ?></figure>
      </div></div>
    </section>

    <section class="page-section" aria-labelledby="facts-title">
      <div class="gutter"><div class="container @container">
        <div class="page-section__top @max-content:grid-cols-1">
          <div><p class="eyebrow">Unternehmen in Zahlen</p><h2 class="display-large" id="facts-title">Erfahrung, die Ergebnisse belastbar <em class="text-ink">macht.</em></h2></div>
          <p class="page-section__lede">Unser Branchenwissen stammt nicht aus Lehrbüchern, sondern aus der Praxis. Seit 2014 verbinden wir in anspruchsvollen Engineering- und Technologievorhaben tiefes Industrie-Know-how mit moderner Technologie und schaffen daraus Lösungen, die im Betrieb wirken.</p>
        </div>
        <?php emposo_fragment( 'company-facts' ); ?>
      </div></div>
    </section>

    <?php emposo_fragment( 'management' ); ?>
