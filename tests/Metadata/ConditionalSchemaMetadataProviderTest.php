<?php

declare(strict_types=1);

namespace Manuxi\SuluBlockValidationBundle\Tests\Metadata;

use Manuxi\SuluBlockValidationBundle\Metadata\ConditionalSchemaMetadataProvider;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapperRegistry;

/**
 * The schema is checked with a real JSON schema validator, the way the admin checks a form.
 */
class ConditionalSchemaMetadataProviderTest extends TestCase
{
    private ConditionalSchemaMetadataProvider $provider;

    protected function setUp(): void
    {
        // no property mappers: every field is a plain property (mandatory = required)
        $locator = new class() implements ContainerInterface {
            public function get(string $id): never
            {
                throw new \RuntimeException($id);
            }

            public function has(string $id): bool
            {
                return false;
            }
        };
        $this->provider = new ConditionalSchemaMetadataProvider(new PropertyMetadataMapperRegistry($locator));
    }

    private function field(string $name, bool $required, ?string $condition = null): FieldMetadata
    {
        $field = new FieldMetadata($name);
        $field->setType('text_line');
        $field->setRequired($required);
        $field->setVisibleCondition($condition);

        return $field;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function isValid(object $schema, array $data): bool
    {
        return (new Validator())->validate((object) $data, $schema)->isValid();
    }

    private function schemaOf(array $items): object
    {
        return json_decode((string) json_encode($this->provider->getMetadata($items)->toJsonSchema()));
    }

    /**
     * @return iterable<string, array{array<string, mixed>, bool}>
     */
    public static function dataCases(): iterable
    {
        yield 'title missing is always an error' => [['view' => 'plans', 'plans' => 'x', 'weird' => 'w'], false];
        yield 'unknown condition stays required' => [['title' => 't', 'view' => 'plans', 'plans' => 'x'], false];
        yield 'table view needs columns' => [['title' => 't', 'view' => 'table', 'weird' => 'w'], false];
        yield 'table view with columns' => [['title' => 't', 'view' => 'table', 'columns' => 'c', 'weird' => 'w'], true];
        yield 'plans view does not need columns' => [['title' => 't', 'view' => 'plans', 'plans' => 'x', 'weird' => 'w'], true];
        yield 'plans view needs plans' => [['title' => 't', 'view' => 'plans', 'weird' => 'w'], false];
        yield 'table view does not need plans' => [['title' => 't', 'view' => 'table', 'columns' => 'c', 'weird' => 'w'], true];
        yield 'in-list and flag need extra' => [['title' => 't', 'view' => 'a', 'flag' => true, 'plans' => 'x', 'weird' => 'w'], false];
        yield 'in-list without flag does not' => [['title' => 't', 'view' => 'a', 'flag' => false, 'plans' => 'x', 'weird' => 'w'], true];
        yield 'in-list and flag with extra' => [['title' => 't', 'view' => 'b', 'flag' => true, 'extra' => 'e', 'plans' => 'x', 'weird' => 'w'], true];
        yield 'empty hidden field is valid' => [['title' => 't', 'view' => 'plans', 'plans' => 'x', 'weird' => 'w', 'columns' => ''], true];
        yield 'hidden section field is not required' => [['title' => 't', 'view' => 'plans', 'plans' => 'x', 'weird' => 'w'], true];
        yield 'visible section field is required' => [['title' => 't', 'view' => 'section', 'plans' => 'x', 'weird' => 'w'], false];
        yield 'visible section field filled' => [['title' => 't', 'view' => 'section', 'plans' => 'x', 'weird' => 'w', 'inSection' => 'i'], true];
    }

    /**
     * @dataProvider dataCases
     *
     * @param array<string, mixed> $data
     */
    public function testMandatoryFieldsAreOnlyRequiredWhereTheyAreVisible(array $data, bool $expected): void
    {
        $hidden = new SectionMetadata('hiddenSection');
        $hidden->setVisibleCondition("__parent.view == 'section'");
        $hidden->addItem($this->field('inSection', true));

        $schema = $this->schemaOf([
            $this->field('title', true),
            $this->field('view', false),
            $this->field('columns', true, "__parent.view == 'table'"),
            $this->field('plans', true, "__parent.view != 'table'"),
            $this->field('extra', true, "__parent.view in ['a', 'b'] AND __parent.flag"),
            $this->field('weird', true, '__parent.view | somethingElse'),
            $hidden,
        ]);

        $this->assertSame($expected, $this->isValid($schema, $data));
    }

    /**
     * @return iterable<string, array{array<string, mixed>, bool}>
     */
    public static function orCases(): iterable
    {
        yield 'OR: neither side holds' => [['view' => 'z', 'flag' => false], true];
        yield 'OR: left side holds' => [['view' => 'a', 'flag' => false], false];
        yield 'OR: right side holds' => [['view' => 'z', 'flag' => true], false];
        yield 'OR: filled' => [['view' => 'a', 'flag' => true, 'orField' => 'x'], true];
        yield 'never visible is never required' => [['view' => 'z', 'flag' => false], true];
        yield 'root level name: holds' => [['view' => 'r', 'flag' => false], false];
        yield 'root level name: filled' => [['view' => 'r', 'flag' => false, 'rootField' => 'x'], true];
    }

    /**
     * @dataProvider orCases
     *
     * @param array<string, mixed> $data
     */
    public function testOrNeverVisibleAndRootLevelNames(array $data, bool $expected): void
    {
        $schema = $this->schemaOf([
            $this->field('view', false),
            $this->field('flag', false),
            $this->field('orField', true, "__parent.view == 'a' OR __parent.flag"),
            $this->field('never', true, 'false'),
            $this->field('rootField', true, "view == 'r'"),
        ]);

        $this->assertSame($expected, $this->isValid($schema, $data));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function expressions(): iterable
    {
        yield 'equals' => ["__parent.a == 'x'", true];
        yield 'negated property' => ['!__parent.a', true];
        yield 'and with number' => ["__parent.a == 'x' && __parent.b != 3", true];
        yield 'or' => ["__parent.a == 'x' OR __parent.b", true];
        yield 'or and precedence' => ['__parent.a or __parent.b AND __parent.c', true];
        yield 'negated group' => ["!(__parent.a in ['x', 'y'])", true];
        yield 'never' => ['false', true];
        yield 'root name outside of the root is not understood' => ["a == 'x'", false];
        yield 'service call is not understood' => ["service('x').y", false];
    }

    /**
     * @dataProvider expressions
     */
    public function testWhichExpressionsAreUnderstood(string $expression, bool $understood): void
    {
        $this->assertSame($understood, null !== ConditionalSchemaMetadataProvider::conditionSchema([$expression]));
    }
}
