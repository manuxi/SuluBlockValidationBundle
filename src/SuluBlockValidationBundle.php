<?php

declare(strict_types=1);

namespace Manuxi\SuluBlockValidationBundle;

use Manuxi\SuluBlockValidationBundle\DependencyInjection\Compiler\ConditionalSchemaPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SuluBlockValidationBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new ConditionalSchemaPass());
    }
}
