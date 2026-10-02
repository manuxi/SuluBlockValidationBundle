# Unterstützte Bedingungen

Das Bundle versteht die kleinen Ausdrücke, die üblicherweise für `visibleCondition` und `disabledCondition` in einem Formular-XML benutzt werden. Sie beziehen sich auf die Daten des Formulars, `__parent.` ist das Objekt um das Feld (der Block, oder auf oberster Ebene das Formular selbst).

```xml
<property name="columns" type="text_area" mandatory="true"
          visibleCondition="__parent.select_view == 'table'">
    ...
</property>

<block name="rows" default-type="row" minOccurs="1"
       visibleCondition="__parent.select_view == 'table'">
    ...
</block>
```

---

## Ausdrücke

| Ausdruck | Bedeutung |
|---|---|
| `__parent.x == 'a'` | `x` ist `a` (Text, Zahl, `true`, `false`) |
| `__parent.x != 'a'` | `x` ist nicht `a` (auch wenn `x` fehlt) |
| `__parent.x in ['a', 'b']` | `x` ist einer der Werte |
| `__parent.x` | `x` ist gesetzt und nicht `false`, `0` oder leer |
| `!__parent.x` | das Gegenteil |
| `!(...)` | Verneinung einer Gruppe |
| `A AND B`, `A && B` | beide gelten |
| `A OR B`, `A \|\| B` | eines gilt (`AND` bindet stärker als `OR`) |
| `false` | das Feld oder der Block ist nie sichtbar (bzw. nie deaktiviert) |

Auf der **obersten Ebene eines Formulars** bedeutet ein Name ohne `__parent.` (`select_view == 'table'`) ein Feld desselben Objekts, wie im Admin. In einem Block meint er ein Feld des Formulars und wird **nicht** verstanden.

Alles andere (ein Dienstaufruf, eine Bedingung auf Benutzer oder Sprache, eine Bedingung auf ein Feld einer anderen Ebene) wird nicht verstanden: Ein solches Feld bleibt Pflicht, genau wie ohne das Bundle.

---

## Was geprüft wird

| Das Feld oder der Block ist | Prüfung |
|---|---|
| sichtbar und aktiv | wie bei Sulu: Pflicht, `minLength`, Einträge mit ihren Pflichtfeldern |
| versteckt (`visibleCondition`) | fehlend, leer oder unvollständig ist in Ordnung |
| deaktiviert (`disabledCondition`) | fehlend, leer oder unvollständig ist in Ordnung: der Redakteur kann es nicht ändern |
| in einem versteckten oder deaktivierten Abschnitt | folgt der Bedingung des Abschnitts |

Ein Feld wird nur geprüft, wenn es sichtbar **und** nicht deaktiviert ist. Eine `disabledCondition`, die nicht verstanden wird, lässt das Feld wie bisher Pflicht sein, genau wie bei der `visibleCondition`.

```xml
<property name="footer_text" type="text_area" mandatory="true"
          disabledCondition="!__parent.toggle_footer">
    ...
</property>
```

Ist `toggle_footer` aus, ist das Feld im Admin ausgegraut und darf leer bleiben, ist es an, ist es Pflicht.

---

## Komponenten

| Klasse | Aufgabe |
|---|---|
| `Metadata\ConditionalSchemaMetadataProvider` | baut das Schema mit den bedingten Regeln (erweitert Sulus `SchemaMetadataProvider`) |
| `Metadata\RawSchemaMetadata` | ein fertiges Stück JSON-Schema |
| `DependencyInjection\Compiler\ConditionalSchemaPass` | ersetzt die Klasse von `sulu_admin.schema_metadata_provider` im Admin-Kontext |
| `Command\FormSchemaCommand` | `sulu:block-validation:schema`, gibt ein Schema aus oder prüft es |
