# Supported conditions

The bundle understands the small expressions that are typically used for `visibleCondition` and `disabledCondition` in a form XML. They refer to the data of the form, `__parent.` is the object around the field (the block, or the form itself at the top level).

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

## Expressions

| Expression | Meaning |
|---|---|
| `__parent.x == 'a'` | `x` is `a` (text, number, `true`, `false`) |
| `__parent.x != 'a'` | `x` is not `a` (also when `x` is missing) |
| `__parent.x in ['a', 'b']` | `x` is one of the values |
| `__parent.x` | `x` is set and not `false`, `0` or empty |
| `!__parent.x` | the opposite |
| `!(...)` | negation of a group |
| `A AND B`, `A && B` | both hold |
| `A OR B`, `A \|\| B` | one holds (`AND` binds stronger than `OR`) |
| `false` | the field or block is never visible (or never disabled) |

At the **top level of a form** a name without `__parent.` (`select_view == 'table'`) means a field of the same object, as in the admin. In a block it means a field of the form, and is **not** understood.

Anything else (a service call, a condition on the user or the locale, a condition on a field of another level) is not understood: such a field stays required exactly as without the bundle.

---

## What is checked

| The field or block is | Check |
|---|---|
| visible and enabled | like Sulu does: mandatory, `minLength`, entries with their mandatory fields |
| hidden (`visibleCondition`) | missing, empty or incomplete is fine |
| disabled (`disabledCondition`) | missing, empty or incomplete is fine: the editor cannot change it |
| in a hidden or disabled section | follows the condition of the section |

A field is only checked when it is visible **and** not disabled. A disabled condition that is not understood leaves the field required as before, just like a visible condition.

```xml
<property name="footer_text" type="text_area" mandatory="true"
          disabledCondition="!__parent.toggle_footer">
    ...
</property>
```

With `toggle_footer` switched off the field is greyed out in the admin and may stay empty, with it switched on it is mandatory.

---

## Components

| Class | Task |
|---|---|
| `Metadata\ConditionalSchemaMetadataProvider` | builds the schema with the conditional rules (extends Sulu's `SchemaMetadataProvider`) |
| `Metadata\RawSchemaMetadata` | a finished piece of JSON schema |
| `DependencyInjection\Compiler\ConditionalSchemaPass` | replaces the class of `sulu_admin.schema_metadata_provider` in the admin context |
| `Command\FormSchemaCommand` | `sulu:block-validation:schema`, prints or checks a schema |
