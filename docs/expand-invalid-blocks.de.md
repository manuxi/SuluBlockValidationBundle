# Blöcke mit ungültigen Feldern öffnen

Nach einem fehlgeschlagenen Speichern markiert der Admin die ungültigen Felder rot. Ein **zugeklappter** Block versteckt seine Felder, der Redakteur muss also Block für Block öffnen, um das Feld zu finden. Bei einer Seite mit vielen (verschachtelten) Blöcken dauert das lange. Sulu zeigt nur die Meldung „Das Formular beinhaltet ungültige Werte“.

Mit diesem Teil des Bundles **öffnet ein fehlgeschlagenes Speichern die Blöcke, die einen Fehler enthalten**, und nur diese. Alles andere bleibt zugeklappt.

---

## Verhalten

| Situation | Ergebnis |
|---|---|
| Ein Block mit ungültigem Feld ist zugeklappt, der Redakteur speichert | der Block öffnet sich |
| Der Block steckt in einem anderen zugeklappten Block | zuerst öffnet sich der äußere, dann (Ebene für Ebene) der innere |
| Der Redakteur klappt den Block wieder zu und speichert erneut | er öffnet sich wieder (jedes Speichern prüft das Formular neu) |
| Der Redakteur klappt den Block wieder zu und ändert ein anderes Feld | er bleibt zugeklappt |
| Die Seite ist gerade geladen, Fehler existieren, aber es wurde nicht gespeichert | nichts öffnet sich (der Admin zeigt die Fehler auch noch nicht an) |
| Gültige Blöcke | bleiben, wie der Redakteur sie verlassen hat |

Geöffnet werden nur die Blocklisten (`block`-Felder), keine anderen einklappbaren Teile eines Formulars. Die Seite scrollt nicht zum ersten Fehler.

---

## Installation

Das JavaScript ist ein optionaler Teil des Bundles (der PHP-Teil funktioniert auch ohne). In `assets/admin/package.json` ergänzen:

```json
"sulu-block-validation-bundle": "file:../../vendor/manuxi/sulu-block-validation-bundle/src/Resources/js"
```

und in `assets/admin/app.js`:

```js
import 'sulu-block-validation-bundle';
```

Dann den Admin bauen:

```bash
cd assets/admin
npm install --force
npm run build
```

---

## So funktioniert es

Der Admin merkt sich, welcher Block offen ist, in der Blockliste (`BlockCollection`). Das Formularfeld darum (`FieldBlocks`) bekommt die Fehler seiner Blöcke. Das Bundle hängt sich in `componentDidMount` und `componentDidUpdate` von `FieldBlocks` ein:

1. Eine Blockliste meldet sich beim Einhängen an (gefunden über die Render-Funktion ihres Feldes, eine pro Feld).
2. Ändern sich die Fehler eines Feldes, während das Formular sie anzeigt (ein Speichern wurde versucht), öffnet das Feld jeden Block mit Fehler.
3. Eine verschachtelte Blockliste hängt sich ein, wenn ihr Block öffnet, und macht dasselbe für ihre eigenen Blöcke.

Die Fehler werden bei jeder Validierung ersetzt (beim Laden des Formulars und bei jedem Speichern), daran erkennt das Bundle eine neue Prüfung im Unterschied zu einer Änderung.

Der Code nutzt Interna des Sulu-Admins (`FieldBlocks`, `BlockCollection` und die Art, wie `mobx-react` 5 Lifecycle-Methoden patcht). Ein Sulu-Update kann sie ändern, dann braucht das Bundle ein Update. Die Logik deckt ein Test ab, der ohne Admin läuft:

```bash
node src/Resources/js/expandInvalidBlocks/patch.test.mjs
```

---

## Komponenten

| Datei | Aufgabe |
|---|---|
| `src/Resources/js/index.js` | installiert die Hooks |
| `src/Resources/js/expandInvalidBlocks/patch.js` | die Logik (welche Blöcke, wann, die Hooks) |
| `src/Resources/js/expandInvalidBlocks/patch.test.mjs` | der Test mit Stand-ins für die Admin-Klassen |
