<?php

declare(strict_types=1);

namespace Manuxi\SuluBlockValidationBundle\Tests\Command;

use Manuxi\SuluBlockValidationBundle\Command\FormSchemaCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class FormSchemaCommandTest extends TestCase
{
    public function testItNeedsTheAdminContext(): void
    {
        $tester = new CommandTester(new FormSchemaCommand(null));

        $this->assertSame(Command::FAILURE, $tester->execute([]));
        $this->assertStringContainsString('admin context', $tester->getDisplay());
    }
}
