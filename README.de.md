# SuluBlockValidationBundle
![php workflow](https://github.com/manuxi/SuluBlockValidationBundle/actions/workflows/php.yml/badge.svg)
![symfony workflow](https://github.com/manuxi/SuluBlockValidationBundle/actions/workflows/symfony.yml/badge.svg)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://github.com/manuxi/SuluBlockValidationBundle/blob/main/LICENSE)
![GitHub Tag](https://img.shields.io/github/v/tag/manuxi/SuluBlockValidationBundle)
![Supports Sulu 3.0 or later](https://img.shields.io/badge/%20Sulu->=3.0-0088cc?color=00b2df)

[🇬🇧 English Version](README.md)

Pflichtfelder und Blockeinträge in Sulu-Admin-Formularen werden nur dort geprüft, **wo ihre `visibleCondition` sie anzeigt**.

## Das Problem

Ein Formularfeld lässt sich mit einer `visibleCondition` verstecken, zum Beispiel das Feld „Spalten“ eines Blocks, das nur in der Darstellung „Tabelle“ sichtbar ist. Sulu führt trotzdem jedes Pflichtfeld als `required` im JSON-Schema, gegen das der Admin das Formular prüft. Die Folgen:

- ein **Pflichtfeld einer anderen Darstellung** verhindert das Speichern, obwohl es niemand sieht,
- ein **leerer Text** in so einem Feld verletzt die Regel `minLength: 1`, die Sulu an jedes Pflicht-Textfeld hängt,
- **Einträge eines versteckten Blocks** (der Admin legt sie beim Anzeigen wegen `minOccurs` an) bleiben versteckt zurück, und ihre Pflichtfelder blockieren das Speichern, nachdem der Redakteur zurückgeschaltet hat.

Der Admin meldet nur „Das Formular beinhaltet ungültige Werte“, und der Redakteur findet das Feld nicht.

## Was das Bundle macht

Es ersetzt die Klasse von `sulu_admin.schema_metadata_provider` (nur im Admin-Kontext) durch eine, die solche Felder in bedingte Regeln übersetzt, `wenn <Bedingung>, dann <Pflicht>`:

- ein **verstecktes Feld** darf fehlen oder leer sein,
- ein **versteckter Block** wird gar nicht geprüft (weder seine Einträge noch deren Felder),
- ein Feld in einem **versteckten Abschnitt** folgt der Bedingung des Abschnitts,
- ein **sichtbares** Feld wird so streng geprüft wie bisher.

Eine Bedingung, die das Bundle nicht versteht, lässt das Feld wie bisher Pflicht sein. Nichts wird versehentlich lockerer. Siehe [die unterstützten Bedingungen](docs/conditions.de.md).

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

Es gibt nichts zu konfigurieren. Admin-Cache leeren (`bin/adminconsole cache:clear`) und den Admin neu laden.

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
```

## Lizenz

MIT
