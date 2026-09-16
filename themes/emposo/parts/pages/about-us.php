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
<section class="page-hero" aria-labelledby="about-title">
		<div class="gutter"><div class="container @container"><div class="page-hero__grid @max-content:grid-cols-1">
		<div class="page-hero__copy">
			<p class="page-breadcrumb"><a href="/">Startseite</a><span aria-hidden="true">/</span> Über uns</p>
			<p class="page-kicker">Emposo GmbH</p>
			<h1 class="page-display" id="about-title">Wir sind <em>Emposo</em>, die Outcome Factory der Hays Gruppe.</h1>
			<p class="page-hero__intro">Wir übernehmen klar abgegrenzte Leistungen und führen Wandel bis zum messbaren Ergebnis: mit kalkulierbaren Kosten, abgesicherten Ergebnissen und einem verantwortlichen Haftungsträger.</p>
		</div>
		<figure class="page-hero__visual about-hero__visual lg:@max-content:aspect-video lg:@max-content:min-h-0">
			<?php emposo_the_picture( 'collaboration', 'hero', true ); ?>
			<figcaption><span>Outcome Factory</span><span>seit 2014</span></figcaption>
			<div class="page-hero__metric"><strong>1</strong><span>Unterschrift für eine klar definierte Leistung</span></div>
		</figure>
		</div></div></div>
	</section>

	<section class="page-section" aria-labelledby="facts-title">
		<div class="gutter"><div class="container @container">
		<div class="page-section__top @max-content:grid-cols-1">
			<div><p class="page-eyebrow">Unternehmen in Zahlen</p><h2 class="page-title" id="facts-title">Erfahrung, die Verantwortung <em class="text-accent-text">trägt.</em></h2></div>
			<p class="page-section__lede">Seit der Gründung verbindet Emposo Engineering-, Technologie- und Branchenexpertise in einem Delivery Model, das auf klaren Ergebnissen basiert.</p>
		</div>
		<div class="about-facts">
			<article><span class="text-accent-text">Gründung</span><strong>2014</strong><p>anspruchsvolle Engineering- und Technologievorhaben als Werkleistung</p></article>
			<article><span class="text-accent-text">Team</span><strong>250+</strong><p>Mitarbeitende in Engineering, Technology und Operations</p></article>
			<article><span class="text-accent-text">Erfahrung</span><strong>2.900+</strong><p>Projekte und Dienstleistungen als Grundlage für praxisnahes Wissen</p></article>
			<article><span class="text-accent-text">Delivery</span><strong>4</strong><p>Standorte in Deutschland und Rumänien</p></article>
		</div>
		</div></div>
	</section>

	<section class="page-section page-section--dark" id="delivery" aria-labelledby="mission-title">
		<div class="gutter"><div class="container @container"><div class="about-statement @max-content:grid-cols-1">
		<p class="about-statement__mark" aria-hidden="true">01—</p>
		<div class="about-statement__copy"><p class="page-eyebrow page-eyebrow--light">Unsere Mission</p><h2 id="mission-title">Wandel wird durch <em>Lieferung</em> beherrschbar.</h2><p>Bei marktüblichen Modellen bleiben Risiko und Steuerung beim Kunden. Emposo übernimmt die Verantwortung für eine definierte Leistung: vom Anfang bis zum Ende, nicht nur bis zur Präsentation.</p></div>
		</div></div></div>
	</section>

	<section class="page-section page-section--paper" aria-labelledby="principles-title">
		<div class="gutter"><div class="container @container">
		<div class="page-section__top @max-content:grid-cols-1">
			<div><p class="page-eyebrow">Unser Delivery Model</p><h2 class="page-title" id="principles-title">Was Zusammenarbeit bei Emposo <em class="text-accent-text">verbindlich</em> macht.</h2></div>
			<p class="page-section__lede">Wir definieren Leistung, Qualitätskriterien und Abnahme vor dem Start. So ist für alle Beteiligten klar, was geliefert wird und woran Erfolg erkennbar ist.</p>
		</div>
		<div class="about-principles">
			<article><span class="text-accent-text">01 / Ergebnis</span><h3>Definierte Leistung.</h3><p>Ein Arbeitspaket hat einen klaren Gegenstand, ein Zielbild und nachvollziehbare Kriterien für die Abnahme.</p></article>
			<article><span class="text-accent-text">02 / Verantwortung</span><h3>Ein verantwortliches Team.</h3><p>Unsere Teams werden durch Emposo geführt. Steuerung, Methode und Ergebnisverantwortung liegen dort, wo die Lieferung stattfindet.</p></article>
			<article><span class="text-accent-text">03 / Acceptance</span><h3>Abnahme als Maßstab.</h3><p>Die Leistung wird gegen die vereinbarten Kriterien geprüft. Das schafft Transparenz, sichere Freigaben und belastbare Ergebnisse.</p></article>
		</div>
		<p style="margin-top: 3.2rem;"><a class="page-link min-h-11" href="/portfolio/#delivery-model">Unser Delivery Model ansehen</a></p>
		</div></div>
	</section>

	<section class="page-section" aria-labelledby="values-title"><div class="gutter"><div class="container"><p class="eyebrow">Wir sind Emposo</p><h2 class="page-title" id="values-title">Ein vernetztes Team von <em>Lösungsfindern.</em></h2><p class="section-lede">Wir gestalten die digitale Transformation und die Mobilität der Zukunft. Über 250 Mitarbeitende aus 30 Nationalitäten verbinden Engineering, Technologie und operative Umsetzung.</p><div class="company-values"><article><h3>Build Partnerships</h3><p>Wir hören zu, verstehen die Bedürfnisse unserer Kunden und arbeiten gemeinsam an Lösungen. Wir lernen voneinander und teilen Wissen und Erfahrung.</p></article><article><h3>Think Beyond</h3><p>Wir bleiben neugierig, betrachten jede Herausforderung aus mehreren Blickwinkeln und verbinden das große Ganze von heute mit einer langfristigen Perspektive.</p></article><article><h3>Do the Right Thing</h3><p>Wir stehen zu unseren Aussagen, halten unsere Versprechen und begegnen jedem Menschen mit Respekt. So entsteht Vertrauen.</p></article></div><div class="company-values"><article><h3>Expertise im Netzwerk</h3><p>Unsere Partnerschaft mit Hays verbindet Lösungskompetenz mit dem Zugang zu 3.000 Spezialistinnen und Spezialisten und 10.000 aktiven Partnerunternehmen.</p></article><article><h3>Hohe Lieferfähigkeit</h3><p>Inhouse-Know-how und Partnernetzwerk bringen die richtige Expertise für anspruchsvolle Vorhaben zusammen.</p></article><article><h3>Bestshore in Europa</h3><p>Unser deutsch-rumänisches Setup verbindet Kundennähe, spezialisierte Teams und skalierbare Umsetzung.</p></article></div></div></div></section>
	<?php emposo_fragment( 'management' ); ?>

	<section class="page-section" aria-labelledby="locations-title">
		<div class="gutter"><div class="container @container"><div class="about-locations @max-content:grid-cols-1">
		<div class="about-locations__map" aria-hidden="true"><span></span><span></span><span></span><span></span><strong>Deutschland + Rumänien</strong></div>
		<div class="about-locations__copy"><p class="page-eyebrow">Standorte &amp; Reichweite</p><h2 id="locations-title">Nah am Kunden. Bereit zu <em class="text-accent-text">liefern.</em></h2><p>Kundennähe in Deutschland trifft auf standortübergreifende Zusammenarbeit und skalierbare Delivery. Die richtige Expertise kommt dorthin, wo sie im Projekt gebraucht wird.</p><div class="location-list"><div><strong>Mannheim</strong><span>Hauptsitz</span></div><div><strong>Düsseldorf</strong><span>Deutschland</span></div><div><strong>Frankfurt</strong><span>Deutschland</span></div><div><strong>Timișoara</strong><span>Rumänien</span></div></div></div>
		</div></div></div>
	</section>

	<section class="page-section page-section--deep" aria-labelledby="about-cta-title">
		<div class="gutter"><div class="container @container"><div class="page-cta @max-content:grid-cols-1">
		<div><p class="page-eyebrow page-eyebrow--light">Nächster Schritt</p><h2 class="page-cta__title" id="about-cta-title">Lernen wir Ihre Aufgabe <em>kennen.</em></h2></div>
		<div class="page-cta__copy"><p>Sie haben ein konkretes Vorhaben oder möchten zunächst klären, wie Ergebnisverantwortung bei Ihnen aussehen kann? Wir sprechen darüber.</p><a class="page-link page-link--light min-h-11" href="/kontakt/">Projekt besprechen</a></div>
		</div></div></div>
	</section>
