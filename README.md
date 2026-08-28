# Centralized Notification Suite für Contao 5

*[English version](README.en.md)*

Transaktions-Benachrichtigungen für Contao 5.3 LTS und 5.7+: Formular-, Mitglieder-, Kommentar-
und Newsletter-Mails sowie Slack- oder Webhook-Meldungen – im Backend konfiguriert und über
einen einzigen Service-Aufruf ausgelöst.

## Inhalt

- [Projektübersicht](#projektübersicht)
- [Status](#status)
- [Unterstützte Versionen](#unterstützte-versionen)
- [Systemvoraussetzungen](#systemvoraussetzungen)
- [Installation](#installation)
- [Lizenzierung](#lizenzierung)
- [Backend-Module](#backend-module)
- [Erste Schritte](#erste-schritte)
- [Tokens](#tokens)
- [Nachrichteninhalt](#nachrichteninhalt)
- [E-Mail-Layouts und Branding](#e-mail-layouts-und-branding)
- [Absender (Gateways)](#absender-gateways)
- [Versandprotokoll](#versandprotokoll)
- [Auslösen aus eigenem Code](#auslösen-aus-eigenem-code)
- [Berechtigungen](#berechtigungen)
- [Funktionsstatus](#funktionsstatus)
- [Sicherheitsmodell](#sicherheitsmodell)
- [Betriebssicherheit](#betriebssicherheit)
- [Laufzeitverzeichnisse](#laufzeitverzeichnisse)
- [Externe Kommunikation](#externe-kommunikation)
- [Protokollierung](#protokollierung)
- [Deployment](#deployment)
- [Cache leeren](#cache-leeren)
- [Tests](#tests)
- [Fehlerbehebung](#fehlerbehebung)
- [Bekannte Einschränkungen](#bekannte-einschränkungen)
- [Lizenz und Urheberrecht](#lizenz-und-urheberrecht)

## Projektübersicht

Die Centralized Notification Suite bündelt den gesamten Benachrichtigungsversand einer
Contao-Installation an einer Stelle. Eine *Benachrichtigung* beschreibt einen Anlass, ihre
*Nachrichten* beschreiben die konkreten Aussendungen je Sprache, und ein *Absender* beschreibt
den Weg, über den ausgeliefert wird.

Gegenüber vergleichbaren Lösungen bietet die Suite vier Dinge zusätzlich:

| | Centralized Notification Suite | Übliche Alternativen |
|---|---|---|
| **Ist die Nachricht angekommen?** | Versandprotokoll mit echtem Zustellstatus, Fehlertext und erneutem Versand per Klick | Blick in `var/logs/` |
| **Test einer Benachrichtigung** | Vorschau und Testversand mit eigenen Token-Werten | Das echte Ereignis auslösen |
| **Eingefügte HTML-Designs** | CSS wird automatisch in `style`-Attribute überführt, damit Outlook und Gmail korrekt darstellen | Manuelles Inlining |
| **Gemeinsames Branding** | Wiederverwendbare Layouts mit Platzhalter für den Nachrichteninhalt | Kopf und Fuß in jede Nachricht kopieren |

Ergänzend: automatische Text-Alternative, eingebettete Bilder, CC/BCC/Antwort-an je Nachricht,
automatische Wiederholung fehlgeschlagener Sendungen, eine typbezogene Token-Referenz im
Backend sowie ein Importbefehl für bestehende Notification-Center-Installationen.

## Status

Produktiv einsetzbar. Alle in diesem Dokument beschriebenen Funktionen sind implementiert und
durch die mitgelieferte Testsuite abgedeckt. Die Suite setzt eine aktivierte Lizenz voraus;
Einzelheiten siehe [Lizenzierung](#lizenzierung).

## Unterstützte Versionen

| Komponente | Unterstützt |
|---|---|
| Contao | 5.3 LTS bis 5.7 |
| PHP | 8.1 oder neuer |
| Symfony Mailer / Mime | 6.4 oder 7.x (von Contao mitgebracht) |

Auf Contao 5.3 bis 5.6 werden die Vorschaukarten der Inhaltsblöcke über eine ältere
Contao-Schnittstelle erzeugt, weil diese Kerne noch keine Datensatz-Beschriftung für
Eltern-Ansichten kennen. Darstellung, Sortierung per Drag-and-drop und alle übrigen Funktionen
verhalten sich identisch; die passende Schnittstelle wird anhand der laufenden Contao-Version
selbst gewählt.

## Systemvoraussetzungen

- PHP 8.1 oder neuer mit den Erweiterungen `curl`, `json` und `sodium`
- Contao 5.3 oder neuer mit funktionsfähiger Datenbank
- Ein erreichbarer Mailversand (SMTP oder ein anderer von Symfony Mailer unterstützter Transport)
- Ausgehende HTTPS-Verbindungen für Lizenzvorgänge und für Webhook-Absender
- Schreibrechte im Verzeichnis `var/` der Installation

## Installation

```bash
composer require vtinnovations/centralized-notification-suite
vendor/bin/contao-console contao:migrate
```

Optional lassen sich fertige Layouts und Beispiel-E-Mails einspielen:

```bash
vendor/bin/contao-console notification:install-layouts
vendor/bin/contao-console notification:install-emails
```

Anschließend im Backend die Lizenz aktivieren (siehe unten). Ohne gültige Lizenz versendet die
Suite nichts.

### Dateisystemberechtigungen

Der Webserver-Benutzer benötigt Schreibrechte auf `var/`. Die Suite legt dort ihre privaten
Daten sowie – bei Verwendung des Datei-Absenders – abgelegte Nachrichten ab. Diese
Verzeichnisse liegen außerhalb des öffentlichen Web-Wurzelverzeichnisses und dürfen nicht
über den Webserver ausgeliefert werden.

## Lizenzierung

Die Suite wird als kostenfreies Produkt mit unbefristeter Lizenz ausgeliefert. „Kostenfrei“
bezieht sich ausschließlich auf den Preis: Eine von V-T.ONE ausgestellte Lizenz muss aktiviert
sein, bevor Nachrichten versendet werden.

### Verwaltung

Die Lizenzverwaltung befindet sich unter **Contao → Einstellungen** im Abschnitt
**Centralized Notification Suite Licence management**. Dort stehen zur Verfügung:

- **Lizenzschlüssel eintragen und speichern** – aktiviert die Installation.
- **Lizenz aktualisieren** – gleicht den gespeicherten Stand mit dem Lizenzdienst ab.
- **Lizenz entfernen** – setzt die Installation sofort in den nicht lizenzierten Zustand zurück.

Angezeigt werden Status, Paket, lizenzierter Host, abgedeckte Hosts, Version und Laufzeit. Der
Schlüssel selbst wird nach dem Speichern nicht erneut in der Oberfläche ausgegeben.

### Zustände

| Zustand | Wirkung |
|---|---|
| Keine Lizenz aktiviert | Es wird nichts versendet; Contao verhält sich wie ohne das Paket |
| Aktive unbefristete Lizenz | Voller Funktionsumfang |
| Lizenz nicht prüfbar oder für diese Website nicht gültig | Wie „keine Lizenz“; vorhandene Inhalte bleiben unverändert |

Eine Lizenz gilt für die Hostnamen, für die sie ausgestellt wurde. Maßgeblich sind die
Domains, die auf den Startseiten der Website hinterlegt sind. Unterschiedliche Hostnamen sind
verschiedene Identitäten – `example.com` und `www.example.com` gelten nicht automatisch
füreinander.

Fällt die Lizenzprüfung negativ aus, werden ausschließlich Versandfunktionen deaktiviert.
Benachrichtigungen, Nachrichten, Layouts, Branding und Versandprotokoll bleiben vollständig
erhalten.

## Backend-Module

Die Suite ergänzt die Backend-Navigation um die Gruppe **Centralized Notification Suite** mit
sechs Modulen in dieser Reihenfolge:

| Modul | Zweck |
|---|---|
| **Benachrichtigungen** | Benachrichtigungen und ihre Nachrichten verwalten |
| **E-Mail-Layouts** | Wiederverwendbare HTML-Layouts für Ihre Nachrichten verwalten |
| **Branding** | Logo, Markenfarbe und Firmenangaben für alle Designs |
| **Absender** | Die Absender (Gateways) verwalten, die Nachrichten versenden |
| **Versandprotokoll** | Sehen, was versendet wurde, warum etwas fehlgeschlagen ist, und erneut senden |
| **SMTP-Konfiguration** | SMTP-Zugangsdaten, über die die Website versendet |

## Erste Schritte

1. **Absender** → einen Eintrag vom Typ *E-Mail* anlegen und die Absenderadresse eintragen.
2. **Benachrichtigungen** → einen Eintrag anlegen. Der gewählte Typ bestimmt, welche Tokens
   angeboten werden und an welchen Stellen die Benachrichtigung zur Auswahl steht. Der Alias
   wird aus dem Titel erzeugt.
3. Die Nachrichten der Benachrichtigung öffnen, eine Nachricht anlegen, Absender und Sprache
   wählen, Betreff und Inhalt erfassen.
4. Über **Vorschau** das fertige Ergebnis ansehen oder über **Testversand** eine Probe an die
   eigene Adresse schicken.
5. Verknüpfen: über das Feld *Benachrichtigungen* eines Formulars, das gleichnamige Feld eines
   Mitgliedermoduls oder aus eigenem Code.

## Tokens

`##token##` steht in Betreff, Inhalt und Adressfeldern zur Verfügung. Contao-Insert-Tags
(`{{env::url}}`, `{{link_url::42}}`) funktionieren ebenfalls, ebenso `{if}` / `{else}` /
`{endif}`.

Die **Hilfe-Schaltfläche** an den Inhaltsfeldern listet alle Tokens auf, die für den Typ der
jeweiligen Benachrichtigung zur Verfügung stehen. Überall verfügbar:

`##admin_email##` `##date##` `##time##` `##datim##` `##host##` `##url##` `##page_id##`
`##page_title##` `##page_url##`

Formular-Benachrichtigungen erhalten zusätzlich jedes übermittelte Feld unter seinem Namen sowie:

| Token | Inhalt |
|---|---|
| `##all_fields##` | Alle Felder als `Bezeichnung: Wert` |
| `##all_fields_filled##` | Dasselbe ohne leere Felder |
| `##all_fields_html##` | Alle Felder als formatierte Tabelle |
| `##all_fields_filled_html##` | Dieselbe Tabelle ohne leere Felder |
| `##uploads##` / `##uploads_html##` | Namen hochgeladener Dateien |
| `##label_<feld>##` | Die Bezeichnung eines Feldes |

Mitglieder-, Kommentar- und Newsletter-Benachrichtigungen erhalten entsprechend `##member_*##`,
`##comment_*##` und `##newsletter_*##`.

### Maskierung

In einem **HTML**-Inhalt werden Token-Werte maskiert und Zeilenumbrüche in `<br>` überführt.
Eingaben aus Formularen können damit weder Markup einschleusen noch das Layout zerstören.
Tokens, deren Name auf `_html` endet, werden bewusst als Markup eingesetzt – so arbeitet
`##all_fields_html##`. Im Textinhalt wird nicht maskiert.

## Nachrichteninhalt

Der Inhalt einer Nachricht ist entweder in **Blöcken** aufgebaut oder **eigenes HTML**; die
Einstellung *Inhalt* an der Nachricht entscheidet. Bestehende Nachrichten bleiben unverändert
auf eigenem HTML.

Im Blockmodus erhält die Nachricht eine untergeordnete Liste **Blöcke**. Blöcke lassen sich per
Drag-and-drop sortieren und zeigen jeweils eine Vorschaukarte. Verfügbare Blocktypen:

| Typ | Gruppe |
|---|---|
| Überschrift, Absatz, Liste, Tabelle, Token | Text |
| Bild, Teaser | Medien |
| Schaltfläche | Aktion |
| Trenner | Layout |
| Eigenes HTML | Erweitert |

### Fertige E-Mails

`notification:install-emails` legt drei vollständig aufgebaute Beispiel-E-Mails an
(Transaktion, Newsletter, Ankündigung), die als Ausgangspunkt bearbeitet werden können.

## E-Mail-Layouts und Branding

Ein Layout ist der gestaltete Rahmen, den sich mehrere Nachrichten teilen. Drei Betriebsarten:

- **Design** – eines von 20 mitgelieferten Designs, das Logo, Markenfarbe und Firmenangaben aus
  dem Modul **Branding** übernimmt.
- **Kopf und Fuß** um den Inhalt herum.
- **Vollständiges HTML-Dokument** mit einem Platzhalter für den Inhalt.

Das CSS eines Layouts wird vor dem Versand in `style`-Attribute überführt. Genau das lässt ein
eingefügtes Design in Outlook und Gmail korrekt aussehen. `@media`-Regeln lassen sich nicht
inline setzen und bleiben als `<style>`-Block erhalten, damit responsive Regeln weiter wirken.

Je Nachricht zusätzlich einstellbar:

- **Textfassung aus dem HTML erzeugen** – Links werden zu `Bezeichnung (URL)`. Geschieht
  automatisch, wenn das Textfeld leer ist.
- **Bilder einbetten** – auf dieser Website liegende Bilder werden als Anhang eingebettet und
  ohne Nachladen externer Inhalte dargestellt.

## Absender (Gateways)

- **E-Mail** – über Symfony Mailer, optional über einen benannten Transport.
- **Webhook / JSON** – ein beliebiger HTTPS-Endpunkt. Ohne eigenen Payload wird ein einfaches
  Textformat gesendet, das Slack, Mattermost und Discord annehmen. Für Microsoft Teams, Google
  Chat und andere Dienste steht ein Hilfe-Assistent mit fertigen Vorlagen bereit. Token-Werte
  werden JSON-sicher eingesetzt.
- **Datei** – schreibt die Nachricht nach `var/notification-mail`, statt sie zu versenden.
  Damit lassen sich Benachrichtigungen ohne Mailserver und ohne Risiko für echte Empfänger
  prüfen.

Eigene Absender lassen sich ergänzen, indem `GatewayInterface` implementiert oder
`AbstractGateway` erweitert wird. Die Backend-Felder deklariert der Absender selbst;
`contao:migrate` legt die zugehörigen Spalten an.

## Versandprotokoll

Jeder Versuch wird festgehalten: Empfänger, gerenderter Betreff, beide Inhaltsfassungen, Status
und Fehler.

Der Status ist wörtlich zu verstehen. Bei E-Mail bedeutet **In Warteschlange**, dass die
Nachricht an die Messenger-Warteschlange von Contao übergeben wurde; erst wenn ein Worker
tatsächlich mit dem Mailserver gesprochen hat, wird daraus **Versendet** oder
**Fehlgeschlagen**.

Fehlgeschlagene Einträge lassen sich erneut senden und werden einmal pro Stunde automatisch
wiederholt.

```yaml
# config/config.yaml
centralized_notification_suite:
    log:
        enabled: true
        store_body: true        # nötig für erneuten Versand; ausschalten, um keine personenbezogenen Daten zu speichern
        retention_days: 90      # 0 behält Einträge dauerhaft
        max_attempts: 3
        retry_failed: true
```

## Auslösen aus eigenem Code

```php
use VTInnovations\CentralizedNotificationSuite\CentralizedNotificationSuite;

public function __construct(private readonly CentralizedNotificationSuite $notify) {}

$result = $this->notify->send('bestellbestaetigung', [
    'kunde_name'  => $order->name,
    'summe'       => $order->formattedTotal,
], 'de');

$result->isSuccessful();
$result->hasFailures();
$result->getErrors();
```

**Zustellfehler lösen keine Ausnahme aus.** Eine Benachrichtigung wird in der Regel ausgelöst,
nachdem etwas bereits gespeichert wurde. Würde ein SMTP-Zeitüberschreitungsfehler
durchgereicht, sähe der Besucher eine Fehlermeldung für einen erfolgreichen Vorgang. Fehler
werden je Nachricht abgefangen und protokolliert; die übrigen Nachrichten werden weiterhin
versendet. Ein unbekannter Alias löst hingegen eine Ausnahme aus, weil das ein Programmierfehler
ist.

Auf der Kommandozeile:

```bash
vendor/bin/contao-console notification:send bestellbestaetigung -t kunde_name=Jane --dry-run
```

Über die Ereignisse `PreSendEvent` und `PostSendEvent` lässt sich der Versand abbrechen,
verändern oder auswerten.

### Migration aus Notification Center

```bash
vendor/bin/contao-console notification:import-nc --dry-run
vendor/bin/contao-console notification:import-nc
```

Liest die bestehenden Tabellen und legt Benachrichtigungen, Nachrichten und Absender neu an.
Die vorhandene Installation bleibt unangetastet, beide können parallel laufen. Tokennamen
werden übersetzt; alles Unbekannte bleibt erhalten und wird am Ende aufgeführt – daher stets
zuerst mit `--dry-run` starten.

## Berechtigungen

- Die sechs Backend-Module folgen der normalen Contao-Rechteverwaltung und lassen sich je
  Benutzergruppe freigeben.
- Die Lizenzverwaltung liegt in **Einstellungen** und steht damit nur Benutzern zur Verfügung,
  die auf dieses Modul zugreifen dürfen. Jede lizenzverändernde Aktion prüft diese Berechtigung
  zusätzlich serverseitig.
- Vorschau und Testversand sind an das jeweilige Backend-Modul gebunden.

## Funktionsstatus

| Funktion | Status |
|---|---|
| Benachrichtigungen, Nachrichten, Mehrsprachigkeit | Verfügbar |
| Formular-, Mitglieder-, Kommentar- und Newsletter-Auslöser | Verfügbar |
| Auslösen aus eigenem Code und per Kommandozeile | Verfügbar |
| Tokens inklusive Formularfeldern und Hilfe-Assistent | Verfügbar |
| Blockbasierter Nachrichteninhalt | Verfügbar |
| 20 Designs, Branding, eigene Layouts | Verfügbar |
| CSS-Inlining und eingebettete Bilder | Verfügbar |
| Automatische Textfassung | Verfügbar |
| Anhänge aus Formularuploads und aus der Dateiverwaltung | Verfügbar |
| Vorschau und Testversand | Verfügbar |
| Versandprotokoll, erneuter Versand, automatische Wiederholung | Verfügbar |
| Absender E-Mail, Webhook/JSON, Datei | Verfügbar |
| SMTP-Konfiguration im Backend | Verfügbar |
| Import aus Notification Center | Verfügbar |
| Versand ohne aktivierte Lizenz | Nicht verfügbar |
| Frontend-Ausgabe durch das Paket | Nicht zutreffend |

## Sicherheitsmodell

- **Zugriffskontrolle** – alle Backend-Module unterliegen der Rechteverwaltung von Contao.
  Lizenzverändernde Aktionen prüfen die Berechtigung zusätzlich serverseitig und laufen über
  Contao-eigene Formulare, sodass der Anfrage-Token greift.
- **Serverseitige Durchsetzung** – die Berechtigung wird an mehreren voneinander unabhängigen
  Stellen im Versandweg geprüft, nicht allein in der Oberfläche.
- **Maskierung** – Token-Werte werden im HTML-Inhalt maskiert. Der Ausnahmefall ist ausdrücklich
  über die Namenskonvention `_html` gekennzeichnet.
- **Authentizität und Integrität** – Lizenzdaten werden authentifiziert und gegen Veränderung
  geprüft, bevor sie verwendet werden. Eine nachträglich veränderte Datei wird abgelehnt.
- **Private Ablage** – Lizenzdaten liegen außerhalb des öffentlichen Web-Wurzelverzeichnisses
  mit restriktiven Dateirechten.
- **Vertrauenswürdige Verbindungen** – ausgehende Lizenzkommunikation erfolgt ausschließlich per
  HTTPS an einen fest im Programmcode hinterlegten Dienst, mit Zertifikatsprüfung und ohne
  Weiterleitungen.
- **Sicheres Scheitern** – schlägt eine Prüfung fehl, wird die geschützte Funktion deaktiviert.
  Es gibt keinen Ersatzpfad, der bei einem Fehler zusätzliche Rechte gewährt.
- **Geheimnisse** – vollständige Schlüssel und Authentifizierungsdaten erscheinen weder in
  Browser-Ausgaben noch in gewöhnlichen Protokollen. Dies wird automatisiert getestet.

Diese Angaben beschreiben umgesetzte Kontrollen. Kein Schutzmechanismus ist unüberwindbar; die
Suite erhebt diesen Anspruch nicht.

## Betriebssicherheit

- **Zugesichert:** Ein Ausfall des Lizenzdienstes entzieht einer bereits aktivierten
  Installation nicht die Lizenz. Ein fehlgeschlagener Schreibvorgang lässt den vorherigen
  Stand unverändert. Ein Zustand, der nur zur Hälfte geschrieben wurde, wird nicht verwendet.
- **Zugesichert:** Zustellfehler werden je Nachricht abgefangen; die übrigen Nachrichten werden
  weiterhin versendet.
- **Umgebungsabhängig:** Ob eine E-Mail tatsächlich zugestellt wird, hängt vom Mailserver und
  vom Betrieb der Messenger-Warteschlange ab. Ohne laufenden Worker verbleiben Nachrichten in
  der Warteschlange.
- **Nach bestem Bemühen:** Die stündliche Wiederholung versucht fehlgeschlagene Nachrichten
  erneut, bis die eingestellte Anzahl an Versuchen erreicht ist.

## Laufzeitverzeichnisse

| Verzeichnis | Inhalt |
|---|---|
| `var/` | Private Daten der Suite, darunter der Lizenzzustand |
| `var/notification-mail` | Vom Datei-Absender abgelegte Nachrichten |

Beide liegen außerhalb des öffentlichen Web-Wurzelverzeichnisses und gehören in die Sicherung,
nicht in die Versionsverwaltung.

## Externe Kommunikation

Ohne ausdrückliche Konfiguration nimmt die Suite folgende ausgehende Verbindungen auf:

| Anlass | Ziel |
|---|---|
| Lizenz aktivieren, aktualisieren, prüfen | Fest hinterlegter Lizenzdienst von V-T.ONE, ausschließlich per HTTPS |
| Nutzungsmeldung | Derselbe Dienst; wird nach dem Ausliefern der Antwort gesendet und beeinflusst weder Darstellung noch Versand |
| Webhook-Absender | Ausschließlich die im jeweiligen Absender eingetragene Adresse |
| E-Mail-Versand | Der konfigurierte Mailserver |

Die Suite nimmt darüber hinaus eine Anfrage entgegen: Der Lizenzdienst kann einen aktualisierten
Lizenzstand an
`/rest/api/v1/centralized-notification-suite-license-updater` übermitteln. Der Endpunkt nimmt
ausschließlich authentifizierte Anfragen an und muss erreichbar bleiben, damit Aktualisierungen
ankommen.

## Protokollierung

Betriebsmeldungen gehen in das Contao-Systemprotokoll. Festgehalten werden Vorgang, Ergebnis
und – beim Versand – die betroffene Nachricht.

Nicht protokolliert werden vollständige Lizenzschlüssel, Authentifizierungsdaten, übertragene
Datenpakete sowie deren Prüfwerte. Das Versandprotokoll speichert Betreff und Inhalt nur, wenn
`store_body` aktiviert ist; auf Installationen ohne Speicherung personenbezogener Daten sollte
diese Option ausgeschaltet werden.

## Deployment

```bash
composer install --no-dev --optimize-autoloader
vendor/bin/contao-console contao:migrate --no-interaction
vendor/bin/contao-console cache:clear --env=prod
vendor/bin/contao-console cache:warmup --env=prod
```

Zu beachten:

- `var/` muss für den Webserver-Benutzer beschreibbar sein.
- Die Lizenz ist an Hostnamen gebunden. Test-, Staging- und Produktivsysteme benötigen jeweils
  eine für ihren Hostnamen gültige Lizenz.
- Ausgehende HTTPS-Verbindungen und der oben genannte Endpunkt dürfen nicht blockiert werden.
- Für den Mailversand muss ein Messenger-Worker laufen oder der Web-Worker von Contao aktiv sein.

## Cache leeren

```bash
vendor/bin/contao-console cache:clear --env=prod
vendor/bin/contao-console cache:clear --env=dev
```

Nach Änderungen an der SMTP-Konfiguration im Backend erneuert die Suite den Cache selbstständig.

## Tests

```bash
composer install
vendor/bin/phpunit
vendor/bin/phpstan analyse
vendor/bin/php-cs-fixer fix --dry-run --diff
composer release:check
```

Die Testsuite läuft ohne Contao-Bootstrap und ohne Netzwerkzugriff. `composer release:check`
prüft vor dem Paketieren, ob das ausgelieferte Paket überhaupt in der Lage ist, Lizenzdaten zu
prüfen, und bricht andernfalls ab.

## Fehlerbehebung

| Beobachtung | Ursache und Abhilfe |
|---|---|
| „Es wurde nichts versendet: Diese Installation ist nicht aktiviert.“ | Keine gültige Lizenz. Unter Einstellungen aktivieren. |
| Lizenz lässt sich nicht aktivieren | Prüfen, ob auf der Startseite eine Domain hinterlegt ist und ob ausgehendes HTTPS möglich ist. Der Schlüssel muss für diesen Hostnamen ausgestellt sein. |
| E-Mails bleiben „In Warteschlange“ | Es läuft kein Messenger-Worker. Warteschlange abarbeiten oder Worker einrichten. |
| Formular versendet nichts | Im Formular muss die Benachrichtigung ausgewählt sein, die Nachricht veröffentlicht und für die Sprache vorhanden sein. |
| Design wird in Outlook falsch dargestellt | Prüfen, ob das Layout veröffentlicht und der Nachricht zugewiesen ist. |
| Webhook meldet einen Fehler | Der Fehlertext des Zielsystems steht im Versandprotokoll. |

## Bekannte Einschränkungen

- Der Versand setzt eine aktivierte Lizenz voraus. Ohne Lizenz wird nichts zugestellt.
- Eine Lizenz gilt nur für die Hostnamen, für die sie ausgestellt wurde; verwandte Schreibweisen
  gelten nicht automatisch mit.
- Die Versionierung erfasst die Nachricht selbst, nicht ihre Inhaltsblöcke. Das Zurückholen
  einer älteren Nachrichtenversion stellt deren Blöcke nicht wieder her.
- Der Datei-Absender ist für Entwicklung und Prüfung gedacht und stellt nicht zu.
- Für den Webhook-Absender liefert die Suite Vorlagen; das Format des Zielsystems bestimmt der
  jeweilige Dienst.

## Lizenz und Urheberrecht

Softwarelizenz: proprietär (`proprietary`, siehe `composer.json`). Alle Rechte vorbehalten.
Weitergabe, Veränderung und Weiterverbreitung sind nur im Rahmen des mit V&T Innovations
geschlossenen Vertrags zulässig.

Copyright: V&T Innovations Team, [https://www.v-t.one](https://www.v-t.one).

Der Betrieb der Suite erfordert zusätzlich eine aktivierte Produktlizenz; siehe
[Lizenzierung](#lizenzierung).

Support und Fehlermeldungen:
[github.com/vtinnovations/centralized-notification-suite](https://github.com/vtinnovations/centralized-notification-suite/issues)

---

*[English version](README.en.md)*
