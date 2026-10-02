<?php

declare(strict_types=1);

namespace Manuxi\SuluBlockValidationBundle\DependencyInjection\Compiler;

use Manuxi\SuluBlockValidationBundle\Metadata\ConditionalSchemaMetadataProvider;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Swaps the class of Sulu's schema metadata provider for the one that understands visibleCondition. Only the admin
 * context has the service, the website context is left alone.
 */
final class ConditionalSchemaPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('sulu_admin.schema_metadata_provider')) {
            return;
        }

        $container->getDefinition('sulu_admin.schema_metadata_provider')->setClass(ConditionalSchemaMetadataProvider::class);
    }
}
