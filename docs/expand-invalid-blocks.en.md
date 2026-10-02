# Opening blocks with invalid fields

After a failed save the admin marks the invalid fields red. A block that is **collapsed** hides its fields, so the editor has to open block after block to find the field, in a page with many (nested) blocks this takes a long time. Sulu only shows the toast "The form contains invalid values".

With this part of the bundle, a failed save **opens the blocks that contain an error**, and only those. Everything else stays collapsed.

---

## Behaviour

| Situation | Result |
|---|---|
| A block with an invalid field is collapsed, the editor saves | the block opens |
| The block is inside another collapsed block | the outer block opens first, then (one level after the other) the inner one |
| The editor collapses the block again and saves again | it opens again (every save checks the form anew) |
| The editor collapses the block again and edits another field | it stays collapsed |
| The page is just loaded, errors exist but nothing was saved yet | nothing opens (the admin does not show the errors yet either) |
| Valid blocks | stay as the editor left them |

Only the lists of blocks (`block` fields) are opened, not other collapsible parts of a form. The page does not scroll to the first error.

---

## Installation

The JavaScript is an optional part of the bundle (the PHP part works without it). Add it to `assets/admin/package.json`:

```json
"sulu-block-validation-bundle": "file:../../vendor/manuxi/sulu-block-validation-bundle/src/Resources/js"
```

and to `assets/admin/app.js`:

```js
import 'sulu-block-validation-bundle';
```

Then build the admin:

```bash
cd assets/admin
npm install --force
npm run build
```

---

## How it works

The admin keeps which block is open in the list of blocks (`BlockCollection`), and the form field around it (`FieldBlocks`) receives the errors of its blocks. The bundle hooks into the `componentDidMount` and `componentDidUpdate` of `FieldBlocks`:

1. A list of blocks reports itself when it mounts (found by the render function of its field, one per field).
2. When the errors of a field change while the form shows them (a save was tried), the field opens every block that has an error.
3. A nested list of blocks mounts when its block opens and does the same for its own blocks.

The errors are replaced on every validation (loading the form and every save), this is what tells a new check from an edit.

The code uses internals of the Sulu admin (`FieldBlocks`, `BlockCollection`, and the way `mobx-react` 5 patches lifecycle methods). A Sulu update may change them, then the bundle needs an update. The logic is covered by a test that runs without the admin:

```bash
node src/Resources/js/expandInvalidBlocks/patch.test.mjs
```

---

## Components

| File | Task |
|---|---|
| `src/Resources/js/index.js` | installs the hooks |
| `src/Resources/js/expandInvalidBlocks/patch.js` | the logic (which blocks, when, the hooks) |
| `src/Resources/js/expandInvalidBlocks/patch.test.mjs` | the test with stand-ins for the admin classes |
