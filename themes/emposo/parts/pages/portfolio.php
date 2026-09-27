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
<section class="page-hero portfolio-hero" aria-labelledby="portfolio-title"><div class="gutter"><div class="container @container"><div class="page-hero__grid @max-content:grid-cols-1"><div class="page-hero__copy"><nav class="page-breadcrumb" aria-label="Brotkrümelnavigation"><ol><li><a href="/">Startseite</a></li><li><span aria-hidden="true">/</span><span aria-current="page">Leistungen</span></li></ol></nav><p class="eyebrow eyebrow--light">Engineering und Technologie aus einer Hand</p>
			<h1 class="display-large display-large--light" id="portfolio-title">Wert maximieren. <em>Risiken minimieren.</em></h1>
			<p class="page-hero__intro">Von System Engineering, Industrialisierung und Test über AI-Transformation, Software und Cloud bis zu Cyber Security und Enterprise Services.</p></div><figure class="page-hero__visual"><?php emposo_the_picture( 'portfolio-hero', 'hero', true ); ?></figure></div></div></div></section>

	<section class="page-section" aria-labelledby="portfolio-disciplines-title"><div class="gutter"><div class="container"><p class="eyebrow">Unsere Disziplinen</p><h2 class="display-large" id="portfolio-disciplines-title">Wenn Engineering und Technologie zusammenarbeiten, entsteht <em>echter Fortschritt.</em></h2><p class="section-lede">Viele Projekte scheitern nicht an fehlenden Ideen, sondern an fehlender Verbindung zwischen Fachlichkeit, Technologie und Umsetzung. Emposo schließt genau diese Lücke.</p><div class="company-values company-values--paper company-values--2"><article><p>Wir verzahnen tiefes Branchenwissen, technologische Expertise und operative Umsetzungskraft. Das Ergebnis: Lösungen aus einer Hand, kürzere Wege und messbare Ergebnisse.</p></article><article><p>Unsere Disziplinen überzeugen einzeln und entfalten gemeinsam ihre volle Wirkung. Praxisnah, technologisch offen und mit voller End-to-End-Verantwortung.</p></article></div><?php emposo_fragment( 'disciplines' ); ?></div></div></section>
	<section class="page-section page-section--dark" aria-labelledby="modes-title">
		<div class="gutter"><div class="container @container">
		<div class="page-section__top page-section__top--wide @max-content:grid-cols-1">
			<div>
			<p class="eyebrow eyebrow--light">Was wir liefern</p>
			<h2 class="display-large display-large--light" id="modes-title">Wir liefern Ergebnisse und stehen <em>dafür ein.</em></h2>
			</div>
			<p class="page-section__lede page-section__lede--light">Bei marktüblichen Modellen bleiben Risiko und Steuerung beim Kunden. Wir übernehmen die komplette Verantwortung für eine definierte Leistung. Vom Anfang bis zum Ende.</p>
		</div>

		<div class="portfolio-modes">
			<article class="portfolio-mode @max-content:grid-cols-1" id="optimieren">
			<span class="portfolio-mode__number">01</span>
			<h3>Wir optimieren.</h3>
			<p class="portfolio-mode__copy @max-content:col-auto">Wir übernehmen die Aufgaben, die laufen müssen, damit Ihr Geschäft läuft, und geben sie verbessert zurück. Wir automatisieren, wo es sich rechnet, nachvollziehbar gemessen an gemeinsam definierten Kennzahlen. Laufende Services erbringen wir mit vereinbarten Service-Leveln, Reaktionszeiten und Eskalationswegen. Ihre Mannschaft kann sich auf Ihr Kerngeschäft konzentrieren.</p>
			<p class="portfolio-mode__outcome @max-content:col-auto">Stabiler. Schneller. Nachvollziehbar.</p>
			</article>
			<article class="portfolio-mode @max-content:grid-cols-1" id="transformieren">
			<span class="portfolio-mode__number">02</span>
			<h3>Wir transformieren.</h3>
			<p class="portfolio-mode__copy @max-content:col-auto">Wir bauen Ihre Prozesse und Ihre Wertschöpfung mit innovativen Technologien um, gemeinsam kalkuliert, bevor Sie investieren. Wir liefern in Ihre produktive Umgebung und justieren nach. Wo es regulatorisch erforderlich ist, prüfen wir gemeinsam nach festgelegten Kriterien und nach einem Qualifizierungs- und Validierungsplan.</p>
			<p class="portfolio-mode__outcome @max-content:col-auto">Technologie produktiv machen.</p>
			</article>
			<article class="portfolio-mode @max-content:grid-cols-1" id="skalieren">
			<span class="portfolio-mode__number">03</span>
			<h3>Wir skalieren.</h3>
			<p class="portfolio-mode__copy @max-content:col-auto">Hinter unserem Inhouse-Team mit über 250 Profis steht das Hays-Netzwerk mit weiteren 3.000 Spezialistinnen und Spezialisten und 10.000 aktiven Partnerunternehmen. Braucht Ihr Vorhaben mehr Kapazitäten, skaliert unser Team mit. Sie zahlen für Ergebnisse und vereinbarte Service-Level, keine Leerlaufkosten. Unabhängig vom Modell oder der Skalierung bleibt Emposo in der Verantwortung und Ihr einziger Vertragspartner.</p>
			<p class="portfolio-mode__outcome @max-content:col-auto">Wirkung in die Breite bringen.</p>
			</article>
			<article class="portfolio-mode @max-content:grid-cols-1" id="verzahnen">
			<span class="portfolio-mode__number">04</span>
			<h3>Wir verzahnen.</h3>
			<p class="portfolio-mode__copy @max-content:col-auto">Branchenwissen, AI und Technologieexpertise arbeiten bei uns Hand in Hand. Wir beherrschen die Technik und unsere Expertinnen und Experten kommen direkt aus Ihrer Branche. Wir verzahnen, was in Ihrer Organisation getrennt arbeitet: den Fachbereich mit der IT. Das zeichnet uns aus: eine nahtlose Schnittstelle mit der Verantwortung für das Gesamtergebnis.</p>
			<p class="portfolio-mode__outcome @max-content:col-auto">Business-Mehrwert im Betrieb.</p>
			</article>
		</div>
		</div></div>
	</section>

	<section class="page-section page-section--paper" id="delivery-model" aria-labelledby="model-title">
		<div class="gutter"><div class="container @container">
		<div class="page-section__top @max-content:grid-cols-1">
			<div>
			<p class="eyebrow">Delivery Model</p>
			<h2 class="display-large" id="model-title">Vom Use Case bis zur nachhaltigen <em>Implementierung.</em></h2>
			</div>
			<p class="page-section__lede">Unser 5-Stufen-Modell schafft Klarheit und echte Ergebnisse. Geliefert aus einer Hand, vom Anfang bis zum Ende.</p>
		</div>
		<div class="fact-grid fact-grid--5">
			<article><span class="fact-grid__label fact-grid__label--display">01</span><h3>Discovery</h3><p>Use Cases auswählen und bewerten. Machbarkeit sowie Kosten und Nutzen analysieren.</p></article>
			<article><span class="fact-grid__label fact-grid__label--display">02</span><h3>Concept &amp; ROI</h3><p>Konzept entwickeln, technische Machbarkeit prüfen (PoC) und ROI berechnen.</p></article>
			<article><span class="fact-grid__label fact-grid__label--display">03</span><h3>Proof of Value</h3><p>Konzept implementieren und ROI in der realistischen Kundenumgebung verifizieren.</p></article>
			<article><span class="fact-grid__label fact-grid__label--display">04</span><h3>MVP in Operation</h3><p>Erste produktive Version in Betrieb nehmen und mit kleinstem Funktionsumfang Mehrwert liefern.</p></article>
			<article><span class="fact-grid__label fact-grid__label--display">05</span><h3>Skalierung &amp; Transformation</h3><p>Das MVP weiterentwickeln und weitere Use Cases implementieren.</p></article>
		</div>
		</div></div>
	</section>

	<section class="page-section page-section--paper" id="qualitaet" aria-labelledby="quality-title"><div class="gutter"><div class="container"><p class="eyebrow">Qualität und Compliance</p><h2 class="display-large" id="quality-title">Qualität, Sicherheit und belastbare <em>Nachweise.</em></h2><p class="section-lede">Etablierte Methoden, Quality Gates und praxiserprobte Compliance machen Risiken beherrschbar. Unser Qualitätsmanagement ist nach ISO 9001 zertifiziert, unser Compliance-Management-System nach ISO 37301. Das Ergebnis des TISAX-Assessments teilen wir über das ENX-Portal; Geltungsbereiche und Nachweise stellen wir auf Anfrage bereit.</p><div class="company-values"><article><span class="company-values__icon" aria-hidden="true"><?php emposo_icon( 'checkbox-list-line' ); ?></span><h3>Standards im Projekt</h3><p>(A)SPICE, IEC 62304, GxP und die Anforderungen des EU AI Act fließen entsprechend dem Projektkontext in unsere Arbeit ein.</p></article><article><span class="company-values__icon" aria-hidden="true"><?php emposo_icon( 'target-line' ); ?></span><h3>Methoden für die Lieferung</h3><p>Quality Gates, agiles und ticketbasiertes Projektmanagement sowie die Time-to-Value-Methode strukturieren die Umsetzung.</p></article><article><span class="company-values__icon" aria-hidden="true"><?php emposo_icon( 'handshake-2-line' ); ?></span><h3>Verantwortung in der Zusammenarbeit</h3><p>Contract Compliance und Compliant Sourcing® – seit 2019 als Marke eingetragen – unterstützen eine klare und verlässliche Zusammenarbeit.</p></article></div><?php emposo_fragment( 'trust-strip' ); ?></div></div></section>
	<?php emposo_fragment( 'cta-portfolio' ); ?>
