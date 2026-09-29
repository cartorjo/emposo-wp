# Redaktion: was sich in wp-admin bearbeiten lässt

Stand 2026-09-29 (Phase 1). Die Website nutzt den klassischen Editor.
Jede Änderung ist nach dem Speichern sofort live, auf Deutsch und Englisch
getrennt.

## Case Studies (Menü „Case Studies“ und „Case Studies (EN)“)

| Was auf der Seite steht | Wo es bearbeitet wird |
|---|---|
| Projektname (Überschrift, Karte, Sitemap) | Titel |
| Satz unter dem Titel und auf der Karte | Kasten „Kurzbeschreibung (Seitenkopf und Karte)“ |
| Große Kennzahl und ihr Text | „Projektinhalt“ → Kennzahl, Text zur Kennzahl (leer lassen: kein Kennzahl-Block) |
| Branchenzeile über dem Titel | „Projektinhalt“ → Branche (Anzeige) |
| Kasten „Projekt“, Herausforderung, Lösung, Ergebnis | „Projektinhalt“, ein Punkt pro Zeile |
| Bild | Beitragsbild |
| Filter auf „Branchen & Projekte“ | Kästen Branchen, Disziplinen, Wirkungen (nur in der deutschen Fassung; die englische übernimmt sie) |
| Reihenfolge | Attribute → Reihenfolge |

Keine €-Beträge in Case Studies (Vorgabe 2026-09-28).

## Personen (Menü „Personen“)

Name = Titel, Foto = Beitragsbild, deutsche Biografie = Textfeld (Absätze
durch eine Leerzeile trennen). Im Kasten „Profil“: Rollen (DE/EN, eine pro
Zeile), englische Biografie, LinkedIn-Adresse. Formatierungen (fett, Links)
werden auf der Karte nicht angezeigt.

## Stellen (Menü „Stellen“ und „Stellen (EN)“)

Eine Stelle pro Eintrag. Titel = Stellenbezeichnung. Textfeld: zuerst die
Einleitung (der erste Absatz steht auf der Karte), dann je Abschnitt eine
Überschrift und eine Aufzählung. Kasten „Stellenangaben“: Eckdaten (eine pro
Zeile), Unterzeile, Schlusssatz. Entwurf oder Papierkorb nimmt die Stelle von
der Karriereseite.

## Emposo Inhalte (Menü „Emposo Inhalte“)

Kennzahlen-Leiste (DE/EN), Zertifikate, Themen im Kontaktformular (DE/EN).
Die Empfängeradresse des Formulars sehen nur Administratoren.

## Disziplinen, Branchen, Wirkungen (unter „Case Studies“)

Name (DE) ist der Begriffsname; darunter englischer Name, Texte, Symbol,
Kachelbild, „Als Kachel zeigen“ und Reihenfolge. Neue Begriffe erscheinen
automatisch am Ende.

## Medien

Beim Bild: „Alternativtext“ (Deutsch) und „Alternativtext (EN)“. Ein neues
Bild wird unter Medien hochgeladen und dann als Beitragsbild oder Kachelbild
gewählt; WordPress erzeugt die kleineren Größen selbst.

## Was (noch) nicht geht

- **Adressen (URLs)** von Seiten und Case Studies sind fest; Seiten und Case
  Studies lassen sich nicht löschen. Navigation, Sprachumschaltung und
  Weiterleitungen hängen an den Adressen.
- **Seitentexte** (Startseite, Leistungen, Über uns, Karriere, Kontakt …)
  sind noch Teil der Vorlage. Felder je Abschnitt folgen in Phase 2.
- **Rechtstexte** (Impressum, Datenschutz, Nutzungsbestimmungen) folgen in
  Phase 3.
- **Navigation, Footer, Buttons**: noch Vorlage (Phase 2).

## Für das Emposo-Team (Technik)

- `wp emposo migrate editorial` schaltet den Redaktionsmodus ein: danach
  verweigert `wp emposo import` jeden Lauf ohne `--override-editorial`, weil
  er Personen, Begriffe, Einstellungen und Medien-Metadaten überschreiben
  würde.
- `wp emposo migrate jobs` legt die Stellen aus den alten Optionen als Posts an
  (einmalig, idempotent).
- Code: `client-mu-plugins/emposo-core/inc/editorial/`.
