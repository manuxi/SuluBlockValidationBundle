<?php

declare(strict_types=1);

namespace Manuxi\SuluBlockValidationBundle\Metadata;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\ItemMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SchemaMetadataProvider;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\IfThenElseMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapperRegistry;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata;

/**
 * Sulu validates a form against a JSON schema in which every mandatory field is simply "required", even when the
 * field is hidden by its visibleCondition (or sits in a hidden section). A mandatory field of another display of a block
 * then stops the form from being saved.
 *
 * This provider turns such a field into a conditional requirement ("if the condition holds, then the field is required").
 * The conditions that Sulu's forms use are small expressions on the sibling fields of the same object:
 *
 *   __parent.view == 'table'    __parent.view != 'plans'    __parent.view in ['a', 'b']
 *   __parent.toggle             !__parent.toggle            ... joined with AND (or &&)
 *
 * A condition that is not understood leaves the field required as before, so nothing becomes more lax by accident.
 *
 * Registered by the compiler pass of the bundle, which replaces the class of sulu_admin.schema_metadata_provider.
 */
class ConditionalSchemaMetadataProvider extends SchemaMetadataProvider
{
    private int $depth = 0;

    public function __construct(private readonly PropertyMetadataMapperRegistry $mapperRegistry)
    {
        parent::__construct($mapperRegistry);
    }

    /**
     * @param ItemMetadata[] $itemsMetadata
     */
    public function getMetadata(array $itemsMetadata): SchemaMetadata
    {
        // The first call is for the form itself (the root object), the nested calls come from the types of its blocks while
        // their schema is built. A property name without "__parent." in a condition means a property of the root object.
        $root = 0 === $this->depth;
        ++$this->depth;

        try {
            $properties = [];
            $conditionals = [];
            $this->collect($itemsMetadata, [], $properties, $conditionals, $root);
        } finally {
            --$this->depth;
        }

        return new SchemaMetadata($properties, [], $conditionals);
    }

    /**
     * @param ItemMetadata[] $items
     * @param list<string> $inherited visible conditions of the sections around the items
     * @param list<PropertyMetadata> $properties
     * @param list<IfThenElseMetadata> $conditionals
     */
    private function collect(array $items, array $inherited, array &$properties, array &$conditionals, bool $root): void
    {
        foreach ($items as $item) {
            $conditions = $inherited;
            if (null !== $item->getVisibleCondition()) {
                $conditions[] = $item->getVisibleCondition();
            }

            if ($item instanceof SectionMetadata) {
                $this->collect($item->getItems(), $conditions, $properties, $conditionals, $root);

                continue;
            }

            \assert($item instanceof FieldMetadata, 'ItemMetadata is expected to be FieldMetadata');

            $property = $this->propertyOf($item);
            // a block is a list of entries with mandatory fields of their own
            $isBlock = [] !== $item->getTypes();
            $condition = ($property->isMandatory() || $isBlock) && [] !== $conditions ? self::conditionSchema($conditions, $root) : null;
            if (null === $condition) {
                $properties[] = $property;

                continue;
            }

            if ($isBlock) {
                // A hidden block is not checked at all: the admin may have created entries while it was shown (minOccurs), and
                // their mandatory fields would block saving although nobody can see them any more.
                $properties[] = new PropertyMetadata($property->getName(), false, new RawSchemaMetadata(['type' => ['array', 'null']]));
                $conditionals[] = new IfThenElseMetadata(
                    new SchemaMetadata([], [], [new RawSchemaMetadata($condition)]),
                    new SchemaMetadata([$property]),
                );

                continue;
            }

            // Not visible: the field may be missing or empty (a mandatory text carries "minLength: 1" in its own schema, so the
            // plain schema is that of the field without "mandatory"). Visible: the strict schema with "required" applies.
            $optional = clone $item;
            $optional->setRequired(false);
            $properties[] = $this->propertyOf($optional);
            $conditionals[] = new IfThenElseMetadata(
                new SchemaMetadata([], [], [new RawSchemaMetadata($condition)]),
                new SchemaMetadata([$property]),
            );
        }
    }

    private function propertyOf(FieldMetadata $field): PropertyMetadata
    {
        $type = $field->getType();
        if ($this->mapperRegistry->has($type)) {
            return $this->mapperRegistry->get($type)->mapPropertyMetadata($field);
        }

        return new PropertyMetadata($field->getName(), $field->isRequired());
    }

    /**
     * JSON schema that holds when all conditions hold, null when one of them is not understood.
     *
     * @param list<string> $conditions
     * @param bool $root the items are those of the form itself: a property name without "__parent." is a property of the same object
     *
     * @return array<string, mixed>|null
     */
    public static function conditionSchema(array $conditions, bool $root = false): ?array
    {
        $schemas = [];
        foreach ($conditions as $condition) {
            // AND binds stronger than OR: "a OR b AND c" is "a OR (b AND c)"
            $alternatives = [];
            foreach (preg_split('/\s+(?:OR|\|\|)\s+/i', trim($condition)) ?: [] as $alternative) {
                $all = [];
                foreach (preg_split('/\s+(?:AND|&&)\s+/i', trim($alternative)) ?: [] as $part) {
                    $schema = self::partSchema(trim($part), $root);
                    if (null === $schema) {
                        return null;
                    }
                    $all[] = $schema;
                }
                $alternatives[] = ['allOf' => $all];
            }
            $schemas[] = 1 === \count($alternatives) ? $alternatives[0] : ['anyOf' => $alternatives];
        }

        return [] === $schemas ? null : ['allOf' => $schemas];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function partSchema(string $part, bool $root): ?array
    {
        if ('false' === $part) {
            return ['not' => new \stdClass()]; // never visible
        }

        if (1 === preg_match('/^!\((.*)\)$/', $part, $m)) {
            $inner = self::conditionSchema([$m[1]], $root);

            return null === $inner ? null : ['not' => $inner];
        }

        $prefix = $root ? '(?:__parent\.)?' : '__parent\.';

        if (1 === preg_match('/^' . $prefix . '(\w+)\s*(==|!=)\s*(\'[^\']*\'|"[^"]*"|true|false|-?\d+(?:\.\d+)?)$/', $part, $m)) {
            $equals = self::hasValue($m[1], self::literal($m[3]));

            return '==' === $m[2] ? $equals : ['not' => $equals];
        }

        if (1 === preg_match('/^' . $prefix . '(\w+)\s+in\s+\[(.*)\]$/', $part, $m)) {
            $values = [];
            foreach (preg_split('/\s*,\s*/', trim($m[2])) ?: [] as $literal) {
                if (!preg_match('/^(\'[^\']*\'|"[^"]*"|true|false|-?\d+(?:\.\d+)?)$/', $literal)) {
                    return null;
                }
                $values[] = self::literal($literal);
            }

            return [] === $values ? null : ['properties' => [$m[1] => ['enum' => $values]], 'required' => [$m[1]]];
        }

        if (1 === preg_match('/^(!?)' . $prefix . '(\w+)$/', $part, $m)) {
            // truthy: present and not false, null, 0 or an empty string
            $truthy = ['properties' => [$m[2] => ['not' => ['enum' => [false, null, 0, '']]]], 'required' => [$m[2]]];

            return '!' === $m[1] ? ['not' => $truthy] : $truthy;
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private static function hasValue(string $property, mixed $value): array
    {
        return ['properties' => [$property => ['const' => $value]], 'required' => [$property]];
    }

    private static function literal(string $literal): string|int|float|bool
    {
        return match (true) {
            'true' === $literal => true,
            'false' === $literal => false,
            is_numeric($literal) => str_contains($literal, '.') ? (float) $literal : (int) $literal,
            default => substr($literal, 1, -1),
        };
    }
}
