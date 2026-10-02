<?php

declare(strict_types=1);

namespace Manuxi\SuluBlockValidationBundle\Tests\DependencyInjection;

use Manuxi\SuluBlockValidationBundle\DependencyInjection\Compiler\ConditionalSchemaPass;
use Manuxi\SuluBlockValidationBundle\Metadata\ConditionalSchemaMetadataProvider;
use Manuxi\SuluBlockValidationBundle\SuluBlockValidationBundle;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SchemaMetadataProvider;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ConditionalSchemaPassTest extends TestCase
{
    public function testTheClassOfTheProviderIsReplaced(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('sulu_admin.schema_metadata_provider', new Definition(SchemaMetadataProvider::class));

        (new ConditionalSchemaPass())->process($container);

        $this->assertSame(ConditionalSchemaMetadataProvider::class, $container->getDefinition('sulu_admin.schema_metadata_provider')->getClass());
    }

    public function testNothingHappensWithoutTheAdmin(): void
    {
        $container = new ContainerBuilder();

        (new ConditionalSchemaPass())->process($container);

        $this->assertFalse($container->has('sulu_admin.schema_metadata_provider'));
    }

    public function testTheBundleRegistersThePass(): void
    {
        $container = new ContainerBuilder();
        (new SuluBlockValidationBundle())->build($container);

        $passes = array_map(static fn ($pass): string => $pass::class, $container->getCompilerPassConfig()->getBeforeOptimizationPasses());

        $this->assertContains(ConditionalSchemaPass::class, $passes);
    }
}
