# SuluBlockValidationBundle
![php workflow](https://github.com/manuxi/SuluBlockValidationBundle/actions/workflows/php.yml/badge.svg)
![symfony workflow](https://github.com/manuxi/SuluBlockValidationBundle/actions/workflows/symfony.yml/badge.svg)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://github.com/manuxi/SuluBlockValidationBundle/blob/main/LICENSE)
![GitHub Tag](https://img.shields.io/github/v/tag/manuxi/SuluBlockValidationBundle)
![Supports Sulu 3.0 or later](https://img.shields.io/badge/%20Sulu->=3.0-0088cc?color=00b2df)

[🇬🇧 English Version](README.md)

Zwei Korrekturen für das „Das Formular beinhaltet ungültige Werte“ im Sulu-Admin:

1. Pflichtfelder und Blockeinträge werden nur dort geprüft, **wo ihre `visibleCondition` sie anzeigt und ihre `disabledCondition` sie nicht deaktiviert** (PHP).
2. Nach einem fehlgeschlagenen Speichern **öffnen sich die Blöcke mit ungültigen Feldern**, damit die roten Markierungen sichtbar sind (Admin-JavaScript, optional).

## 1. Bedingte Validierung

### Das Problem

Ein Formularfeld lässt sich mit einer `visibleCondition` verstecken, zum Beispiel das Feld „Spalten“ eines Blocks, das nur in der Darstellung „Tabelle“ sichtbar ist, oder es mit einer `disabledCondition` ausgrauen. Sulu führt trotzdem jedes Pflichtfeld als `required` im JSON-Schema, gegen das der Admin das Formular prüft. Die Folgen:

- ein **Pflichtfeld einer anderen Darstellung** oder ein **deaktiviertes**, das der Redakteur nicht ändern kann, verhindert das Speichern,
- ein **leerer Text** in so einem Feld verletzt die Regel `minLength: 1`, die Sulu an jedes Pflicht-Textfeld hängt,
- **Einträge eines versteckten Blocks** (der Admin legt sie beim Anzeigen wegen `minOccurs` an) bleiben versteckt zurück, und ihre Pflichtfelder blockieren das Speichern, nachdem der Redakteur zurückgeschaltet hat.

Der Admin meldet nur „Das Formular beinhaltet ungültige Werte“, und der Redakteur findet das Feld nicht.

### Was das Bundle macht

Es ersetzt die Klasse von `sulu_admin.schema_metadata_provider` (nur im Admin-Kontext) durch eine, die solche Felder in bedingte Regeln übersetzt, `wenn <Bedingung>, dann <Pflicht>`:

- ein **verstecktes oder deaktiviertes Feld** darf fehlen oder leer sein,
- ein **versteckter oder deaktivierter Block** wird gar nicht geprüft (weder seine Einträge noch deren Felder),
- ein Feld in einem **versteckten oder deaktivierten Abschnitt** folgt den Bedingungen des Abschnitts,
- ein **sichtbares und aktives** Feld wird so streng geprüft wie bisher.

Eine Bedingung, die das Bundle nicht versteht, lässt das Feld wie bisher Pflicht sein. Nichts wird versehentlich lockerer. Siehe [die unterstützten Bedingungen](docs/conditions.de.md).

### Beispiel

Ein Block, der je nach Auswahl Pakete oder eine Vergleichstabelle zeigt. Die Felder der Tabelle sind Pflicht und nur für die Tabelle sichtbar:

```xml
<property name="select_view" type="single_select">
    <params>
        <param name="default_value" value="plans"/>
        <param name="values" type="collection">
            <param name="plans"><meta><title lang="de">Pakete</title></meta></param>
            <param name="table"><meta><title lang="de">Vergleichstabelle</title></meta></param>
        </param>
    </params>
</property>

<block name="plans" default-type="plan" minOccurs="1"
       visibleCondition="__parent.select_view == 'plans'">
    <types>
        <type name="plan">
            <properties>
                <property name="name" type="text_line" mandatory="true"/>
            </properties>
        </type>
    </types>
</block>

<property name="columns" type="text_area" mandatory="true"
          visibleCondition="__parent.select_view == 'table'"/>

<block name="rows" default-type="row" minOccurs="1"
       visibleCondition="__parent.select_view == 'table'">
    <types>
        <type name="row">
            <properties>
                <property name="label" type="text_line" mandatory="true"/>
            </properties>
        </type>
    </types>
</block>
```

| Was der Redakteur tut | Ohne das Bundle | Mit dem Bundle |
|---|---|---|
| Darstellung „Pakete“, die (versteckten) `columns` sind leer, speichert | „Das Formular beinhaltet ungültige Werte“ | gespeichert |
| Schaltet auf „Vergleichstabelle“: der Admin legt eine leere Zeile an (`minOccurs`). Schaltet zurück auf „Pakete“, speichert | „Das Formular beinhaltet ungültige Werte“: die versteckte Zeile hat kein `label` | gespeichert |
| Darstellung „Vergleichstabelle“, `columns` leer, speichert | Fehler am Feld | Fehler am Feld (sichtbare Felder bleiben Pflicht) |
| Darstellung „Vergleichstabelle“, eine Zeile ohne `label`, speichert | Fehler am Feld | Fehler am Feld |

## 2. Blöcke mit ungültigen Feldern öffnen

Ein zugeklappter Block versteckt seine Felder. Nach einem fehlgeschlagenen Speichern muss der Redakteur Block für Block öffnen, um das rote Feld zu finden, und Sulu zeigt nur eine Meldung. Mit dem (optionalen) JavaScript des Bundles **öffnet ein fehlgeschlagenes Speichern genau die Blöcke, die einen Fehler enthalten**, Ebene für Ebene. Gültige Blöcke bleiben, wie sie sind. Siehe [Blöcke mit ungültigen Feldern öffnen](docs/expand-invalid-blocks.de.md) für das Verhalten, die Installation des JavaScripts und die Funktionsweise.

## Kompatibilität

| Bundle | Sulu | PHP | Symfony | Admin-JavaScript (Sulus eigene Versionen) |
|---|---|---|---|---|
| 1.2.x | 3.0.x (getestet mit 3.0.10) | 8.2+ (getestet mit 8.3) | 6.4, 7.x (getestet mit 7.4) | React 17, MobX 4, mobx-react 5 |
| 1.1.x | 3.0.x (getestet mit 3.0.10) | 8.2+ (getestet mit 8.3) | 6.4, 7.x (getestet mit 7.4) | React 17, MobX 4, mobx-react 5 |
| 1.0.x | 3.0.x (getestet mit 3.0.10) | 8.2+ (getestet mit 8.3) | 6.4, 7.x (getestet mit 7.4) | nicht Teil dieser Version |

Die bedingte Validierung (PHP) baut auf dem `SchemaMetadataProvider` des Sulu-Admins auf, das Öffnen der Blöcke (JavaScript) auf `FieldBlocks` und `BlockCollection`. Das sind Interna von Sulu: Nach einem Sulu-Update die Tests des Bundles und die Prüfung der eigenen Formulare laufen lassen (siehe „Ein Formular prüfen“).

## Voraussetzungen

- PHP 8.2+, Sulu 3.x

## Installation

```bash
composer require manuxi/sulu-block-validation-bundle
```

Bundle in `config/bundles.php` eintragen (Symfony Flex macht das für dich):

```php
Manuxi\SuluBlockValidationBundle\SuluBlockValidationBundle::class => ['all' => true],
```

Es gibt nichts zu konfigurieren. Admin-Cache leeren (`bin/adminconsole cache:clear`) und den Admin neu laden. Damit funktioniert die bedingte Validierung. Für das Öffnen der Blöcke mit Fehlern das JavaScript in den Admin-Build aufnehmen, siehe [die Installation](docs/expand-invalid-blocks.de.md#installation).

## Ein Formular prüfen

```bash
composer require --dev opis/json-schema
bin/adminconsole sulu:block-validation:schema page default                    # das Schema des Seiten-Templates "default"
bin/adminconsole sulu:block-validation:schema page default --check=page.json # Daten einer Seite dagegen prüfen
```

Die Prüfung nutzt dasselbe Schema, das der Admin an den Browser schickt, einschließlich der globalen Blöcke. So lässt sich ein Formular ohne Browser testen, zum Beispiel in einem PHPUnit-Test des eigenen Projekts.

## Grenzen

- Verstanden werden nur Bedingungen auf den Daten des Formulars, nicht auf Benutzer, Sprache oder Dienste.
- Das MCP-Bundle von Sulu prüft Pflichtfelder selbst und nutzt dieses Schema nicht.
- `minOccurs` ist keine JSON-Schema-Regel: Der Admin legt damit nur leere Einträge an, wenn ein Block angezeigt wird.

## Tests

```bash
composer install
vendor/bin/phpunit
node src/Resources/js/expandInvalidBlocks/patch.test.mjs
```

## Changelog

Siehe [CHANGELOG.md](CHANGELOG.md) (auf Englisch).

## Lizenz

MIT
