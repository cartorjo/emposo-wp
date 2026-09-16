<?php
/**
 * Ported from the static build's pages/portfolio.html.
 *
 * Markup is verbatim; only the template tokens became PHP calls. Generated once
 * by tools/port-partial.mjs and hand-maintained from here on — whitespace and
 * attribute order are load-bearing for the parity diff, so edit carefully.
 *
 * @package Emposo
 */

?>
<section class="page-hero portfolio-hero" aria-labelledby="portfolio-title">
		<div class="gutter"><div class="container @container"><div class="page-hero__grid @max-content:grid-cols-1">
		<div class="page-hero__copy">
			<p class="page-breadcrumb"><a href="/">Startseite</a><span aria-hidden="true">/</span> Leistungen</p>
			<p class="page-kicker">Engineering und Technologie aus einer Hand</p>
			<h1 class="page-display" id="portfolio-title">Wert maximieren. <em>Risiken minimieren.</em></h1>
			<p class="page-hero__intro">Von System Engineering, Industrialisierung und Test über AI-Transformation, Software und Cloud bis zu Cyber Security und Enterprise Services.</p>
		</div>
		<figure class="page-hero__visual"><?php emposo_the_picture( 'hero-flow', 'hero', true ); ?></figure>
		</div></div></div>
	</section>

	<section class="page-section" aria-labelledby="portfolio-disciplines-title"><div class="gutter"><div class="container"><p class="eyebrow">Unsere Disziplinen</p><h2 class="page-title" id="portfolio-disciplines-title">Wenn Engineering und Technologie zusammenarbeiten, entsteht <em>echter Fortschritt.</em></h2><p class="section-lede">Viele Projekte scheitern nicht an fehlenden Ideen, sondern an fehlender Verbindung zwischen Fachlichkeit, Technologie und Umsetzung. Emposo schließt genau diese Lücke.</p><p class="section-lede">Mit unserem Engineering-Wissen und unserer Technologiekompetenz verzahnen wir tiefes Branchenwissen, technologische Expertise und operative Umsetzungskraft. Das Ergebnis: Lösungen aus einer Hand, kürzere Wege, höhere Geschwindigkeit und messbare Ergebnisse.</p><p class="section-lede">Entdecken Sie, wie unsere Disziplinen Engineering und Technologie einzeln überzeugen und gemeinsam ihre volle Wirkung entfalten. Praxisnah, technologisch offen und mit voller End-to-End-Verantwortung.</p><?php emposo_fragment( 'disciplines' ); ?></div></div></section>
	<section class="page-section page-section--dark" aria-labelledby="modes-title">
		<div class="gutter"><div class="container @container">
		<div class="page-section__top page-section__top--wide @max-content:grid-cols-1">
			<div>
			<p class="page-eyebrow page-eyebrow--light">Was wir liefern</p>
			<h2 class="page-title page-title--light" id="modes-title">Wir liefern Ergebnisse und stehen <em>dafür ein.</em></h2>
			</div>
			<p class="page-section__lede page-section__lede--light">Bei marktüblichen Modellen bleiben Risiko und Steuerung beim Kunden. Wir übernehmen die komplette Verantwortung für eine definierte Leistung. Vom Anfang bis zum Ende.</p>
		</div>

		<div class="portfolio-modes">
			<article class="portfolio-mode scroll-mt-28 @max-content:grid-cols-1" id="optimieren">
			<span class="portfolio-mode__number">01</span>
			<h3>Wir optimieren.</h3>
			<p class="portfolio-mode__copy @max-content:col-auto">Wir übernehmen die Aufgaben, die laufen müssen, damit Ihr Geschäft läuft, und geben sie verbessert zurück. Wir automatisieren, wo es sich rechnet, nachvollziehbar gemessen an gemeinsam definierten Kennzahlen. Laufende Services erbringen wir mit vereinbarten Service-Leveln, Reaktionszeiten und Eskalationswegen. Ihre Mannschaft kann sich auf Ihr Kerngeschäft konzentrieren.</p>
			<p class="portfolio-mode__outcome @max-content:col-auto">Stabiler. Schneller. Nachvollziehbar.</p>
			<p class="portfolio-mode__more @max-content:col-auto"><a class="page-link page-link--light min-h-11" href="/portfolio/optimieren/">Mehr zu diesem Modell</a></p>
			</article>
			<article class="portfolio-mode scroll-mt-28 @max-content:grid-cols-1" id="transformieren">
			<span class="portfolio-mode__number">02</span>
			<h3>Wir transformieren.</h3>
			<p class="portfolio-mode__copy @max-content:col-auto">Wir bauen Ihre Prozesse und Ihre Wertschöpfung mit innovativen Technologien um, gemeinsam kalkuliert, bevor Sie investieren. Wir liefern in Ihre produktive Umgebung und justieren nach. Wo es regulatorisch erforderlich ist, prüfen wir gemeinsam nach festgelegten Kriterien und nach einem Qualifizierungs- und Validierungsplan.</p>
			<p class="portfolio-mode__outcome @max-content:col-auto">Technologie produktiv machen.</p>
			<p class="portfolio-mode__more @max-content:col-auto"><a class="page-link page-link--light min-h-11" href="/portfolio/transformieren/">Mehr zu diesem Modell</a></p>
			</article>
			<article class="portfolio-mode scroll-mt-28 @max-content:grid-cols-1" id="skalieren">
			<span class="portfolio-mode__number">03</span>
			<h3>Wir skalieren.</h3>
			<p class="portfolio-mode__copy @max-content:col-auto">Hinter unserem Inhouse-Team mit über 250 Profis steht das Hays-Netzwerk mit weiteren 3.000 Spezialistinnen und Spezialisten und 10.000 aktiven Partnerunternehmen. Braucht Ihr Vorhaben mehr Kapazitäten, skaliert unser Team mit. Sie zahlen für Ergebnisse und vereinbarte Service-Level, keine Leerlaufkosten. Unabhängig vom Modell oder der Skalierung bleibt Emposo in der Verantwortung und Ihr einziger Vertragspartner.</p>
			<p class="portfolio-mode__outcome @max-content:col-auto">Wirkung in die Breite bringen.</p>
			<p class="portfolio-mode__more @max-content:col-auto"><a class="page-link page-link--light min-h-11" href="/portfolio/skalieren/">Mehr zu diesem Modell</a></p>
			</article>
			<article class="portfolio-mode scroll-mt-28 @max-content:grid-cols-1" id="verzahnen">
			<span class="portfolio-mode__number">04</span>
			<h3>Wir verzahnen.</h3>
			<p class="portfolio-mode__copy @max-content:col-auto">Branchenwissen, AI und Technologieexpertise arbeiten bei uns Hand in Hand. Wir beherrschen die Technik und unsere Expertinnen und Experten kommen direkt aus Ihrer Branche. Wir verzahnen, was in Ihrer Organisation getrennt arbeitet: den Fachbereich mit der IT. Das zeichnet uns aus: eine nahtlose Schnittstelle mit der Verantwortung für das Gesamtergebnis.</p>
			<p class="portfolio-mode__outcome @max-content:col-auto">Business-Mehrwert im Betrieb.</p>
			<p class="portfolio-mode__more @max-content:col-auto"><a class="page-link page-link--light min-h-11" href="/portfolio/verzahnen/">Mehr zu diesem Modell</a></p>
			</article>
		</div>
		</div></div>
	</section>

	<section class="page-section page-section--paper" id="delivery-model" aria-labelledby="model-title">
		<div class="gutter"><div class="container @container">
		<div class="page-section__top @max-content:grid-cols-1">
			<div>
			<p class="page-eyebrow">Delivery Model</p>
			<h2 class="page-title" id="model-title">Vom Use Case bis zur nachhaltigen <em>Implementierung.</em></h2>
			</div>
			<p class="page-section__lede">Unser 5-Stufen-Modell schafft Klarheit und echte Ergebnisse. Geliefert aus einer Hand, vom Anfang bis zum Ende.</p>
		</div>
		<div class="portfolio-model">
			<article><span class="text-accent-text">01</span><h3>Discovery</h3><p>Use Cases auswählen und bewerten. Machbarkeit sowie Kosten und Nutzen analysieren.</p></article>
			<article><span class="text-accent-text">02</span><h3>Concept &amp; ROI</h3><p>Konzept entwickeln, technische Machbarkeit prüfen (PoC) und ROI berechnen.</p></article>
			<article><span class="text-accent-text">03</span><h3>Proof of Value</h3><p>Konzept implementieren und ROI in der realistischen Kundenumgebung verifizieren.</p></article>
			<article><span class="text-accent-text">04</span><h3>MVP in Operation</h3><p>Erste produktive Version in Betrieb nehmen und mit kleinstem Funktionsumfang Mehrwert liefern.</p></article>
			<article><span class="text-accent-text">05</span><h3>Skalierung, Transformation &amp; Wachstum</h3><p>Das MVP weiterentwickeln und weitere Use Cases implementieren.</p></article>
		</div>
		</div></div>
	</section>

	<section class="page-section page-section--deep" aria-labelledby="assurance-title">
		<div class="gutter"><div class="container @container">
		<div class="page-section__top @max-content:grid-cols-1">
			<div>
			<p class="page-eyebrow page-eyebrow--light">Verantwortung</p>
			<h2 class="page-title page-title--light" id="assurance-title">Eine Unterschrift. Ein <em>Haftungsträger.</em></h2>
			</div>
			<p class="page-section__lede page-section__lede--light">Eine Leistung wird nicht durch Anwesenheit bezahlt. Sie wird an klaren Anforderungen, Qualitätskriterien und der vereinbarten Abnahme gemessen.</p>
		</div>
		<div class="portfolio-assurance @max-content:grid-cols-1">
			<div class="portfolio-assurance__mark"><span>Werkvertrag mit Acceptance</span><strong>Ergebnis<br>definiert.</strong></div>
			<div class="portfolio-assurance__copy"><p>Wir steuern unsere Teams selbst und übernehmen Verantwortung für die klar abgegrenzte Leistung. So bleiben Steuerung, Qualität und Risiko nicht beim Kunden, sondern dort, wo die Lieferung stattfindet.</p><p>Quality Gates, Security und Compliance sind Bestandteil der Leistung, nicht ein nachträglicher Prüfschritt.</p><p><a class="page-link page-link--light min-h-11" href="/about-us/#delivery">So arbeiten wir</a></p></div>
		</div>
		</div></div>
	</section>

	<section class="page-section page-section--paper" id="qualitaet" aria-labelledby="quality-title"><div class="gutter"><div class="container"><p class="eyebrow">Qualität und Compliance</p><h2 class="page-title" id="quality-title">Qualität, Sicherheit und belastbare <em>Nachweise.</em></h2><p class="section-lede">Etablierte Methoden, Quality Gates und praxiserprobte Compliance machen Risiken beherrschbar. Unser Qualitätsmanagement ist nach ISO 9001 zertifiziert. Das Ergebnis des TISAX-Assessments teilen wir über das ENX-Portal; Geltungsbereiche und Nachweise stellen wir auf Anfrage bereit.</p><div class="company-values"><article><h3>Standards im Projekt</h3><p>(A)SPICE, IEC 62304, GxP und die Anforderungen des EU AI Act fließen entsprechend dem Projektkontext in unsere Arbeit ein.</p></article><article><h3>Methoden für die Lieferung</h3><p>Quality Gates, agiles und ticketbasiertes Projektmanagement sowie die Time-to-Value-Methode strukturieren die Umsetzung.</p></article><article><h3>Verantwortung in der Zusammenarbeit</h3><p>Contract Compliance und Compliant Sourcing® – seit 2019 als Marke eingetragen – unterstützen eine klare und verlässliche Zusammenarbeit.</p></article></div><p class="section-more"><a class="text-link" href="/zertifizierungen/">Alle Zertifizierungen und Nachweise <span aria-hidden="true">→</span></a></p></div></div></section>
	<section class="page-section" aria-labelledby="portfolio-case-title">
		<div class="gutter"><div class="container @container">
		<div class="page-section__top @max-content:grid-cols-1">
			<div>
			<p class="page-eyebrow">Beleg</p>
			<h2 class="page-title" id="portfolio-case-title">Nicht bis zur Präsentation. Bis zum <em class="text-accent-text">Ergebnis.</em></h2>
			</div>
			<p class="page-section__lede">Unsere Case Studies zeigen Herausforderung, Lieferung und dominanten Outcome: nicht nur, woran ein Team gearbeitet hat, sondern was im Betrieb entstanden ist.</p>
		</div>
		<div class="expertise-case-strip @max-content:grid-cols-1">
			<figure class="expertise-case-strip__image"><?php emposo_the_picture( 'datacenter' ); ?></figure>
			<div class="expertise-case-strip__copy"><p class="page-eyebrow">Case Study 01</p><h3>Data2AI Plattform</h3><p>Verteilte Engineering-Daten werden über eine produktive Daten- und Wissensbasis für die Arbeit mit KI verfügbar gemacht.</p><div class="expertise-case-strip__metric"><strong class="text-accent-text">7×</strong><span>Produktivitätssteigerung</span></div><p style="margin-top: 2rem;"><a class="page-link min-h-11" href="/case-studies/data2ai-platform/">Case Study lesen</a></p></div>
		</div>
		</div></div>
	</section>

	<section class="page-section page-section--deep" aria-labelledby="portfolio-cta-title">
		<div class="gutter"><div class="container @container"><div class="page-cta @max-content:grid-cols-1">
		<div><p class="page-eyebrow page-eyebrow--light">Nächster Schritt</p><h2 class="page-cta__title" id="portfolio-cta-title">Welche Leistung sollen wir für Sie <em>liefern?</em></h2></div>
		<div class="page-cta__copy"><p>Von der bestehenden Leistung bis zum neuen Use Case: Sprechen wir über die Ergebnisdefinition und den sinnvollsten Einstieg.</p><a class="page-link page-link--light min-h-11" href="/kontakt/">Projekt besprechen</a></div>
		</div></div></div>
	</section>
