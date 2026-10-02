# SuluBlockValidationBundle
![php workflow](https://github.com/manuxi/SuluBlockValidationBundle/actions/workflows/php.yml/badge.svg)
![symfony workflow](https://github.com/manuxi/SuluBlockValidationBundle/actions/workflows/symfony.yml/badge.svg)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://github.com/manuxi/SuluBlockValidationBundle/blob/main/LICENSE)
![GitHub Tag](https://img.shields.io/github/v/tag/manuxi/SuluBlockValidationBundle)
![Supports Sulu 3.0 or later](https://img.shields.io/badge/%20Sulu->=3.0-0088cc?color=00b2df)

[🇩🇪 Deutsche Version](README.de.md)

Two fixes for the "The form contains invalid values" experience in the Sulu admin:

1. Mandatory fields and block entries are only checked **where their `visibleCondition` shows them** (PHP).
2. After a failed save, **blocks that contain an invalid field open up**, so the red marks are visible (admin JavaScript, optional).

## 1. Conditional validation

### The problem

A form field can be hidden with a `visibleCondition`, for example the "columns" field of a block that is only shown for the display "table". Sulu still lists every mandatory field as `required` in the JSON schema the admin validates the form against. So:

- a **mandatory field of another display** stops the form from being saved, although nobody can see it, and
- an **empty text** in such a field violates the `minLength: 1` rule that Sulu attaches to every mandatory text field, and
- **entries of a hidden block** (the admin creates them while the block is shown, because of `minOccurs`) stay behind, hidden, and their mandatory fields block saving after the editor switched back.

The admin only says "The form contains invalid values", and the editor cannot find the field.

### What the bundle does

It replaces the class of `sulu_admin.schema_metadata_provider` (admin context only) with one that turns such fields into conditional rules, `if <condition> then <required>`:

- a **hidden field** may be missing or empty,
- a **hidden block** is not checked at all (neither its entries nor their fields),
- a field in a **hidden section** follows the condition of the section,
- a **visible** field is checked as strictly as before.

A condition that the bundle does not understand leaves the field required as before. Nothing becomes more lax by accident. See [the supported conditions](docs/conditions.en.md).

## 2. Opening blocks with invalid fields

A block that is collapsed hides its fields. After a failed save the editor has to open block after block to find the red field, and Sulu only shows a toast. With the (optional) JavaScript of the bundle, a failed save **opens exactly the blocks that contain an error**, one nesting level after the other. Valid blocks stay as they are. See [Opening blocks with invalid fields](docs/expand-invalid-blocks.en.md) for the behaviour, the installation of the JavaScript and how it works.

## Requirements

- PHP 8.2+, Sulu 3.x

## Installation

```bash
composer require manuxi/sulu-block-validation-bundle
```

Register the bundle in `config/bundles.php` (Symfony Flex does this for you):

```php
Manuxi\SuluBlockValidationBundle\SuluBlockValidationBundle::class => ['all' => true],
```

There is nothing to configure. Clear the admin cache (`bin/adminconsole cache:clear`) and reload the admin. The conditional validation works with this. To also get the opening of blocks with errors, add the JavaScript to your admin build, see [the installation](docs/expand-invalid-blocks.en.md#installation).

## Checking a form

```bash
composer require --dev opis/json-schema
bin/adminconsole sulu:block-validation:schema page default                    # the schema of the page template "default"
bin/adminconsole sulu:block-validation:schema page default --check=page.json # validate the data of a page against it
```

The check uses the same schema the admin sends to the browser, including the global blocks. This is how a form can be tested without a browser, for example in a PHPUnit test of your own project.

## Limits

- Only conditions on the data of the form are understood, not conditions on the user, the locale or services.
- The MCP bundle of Sulu validates mandatory fields on its own and does not use this schema.
- `minOccurs` is no JSON schema rule: the admin only uses it to create empty entries when a block is shown.

## Tests

```bash
composer install
vendor/bin/phpunit
node src/Resources/js/expandInvalidBlocks/patch.test.mjs
```

## License

MIT
