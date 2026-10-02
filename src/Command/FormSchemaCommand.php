<?php

declare(strict_types=1);

namespace Manuxi\SuluBlockValidationBundle\Command;

use Opis\JsonSchema\Validator;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\XmlTemplateFormMetadataLoader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Prints the JSON schema the admin validates a template form against, or checks data against it (bin/adminconsole).
 *
 *   sulu:block-validation:schema page default                       the schema of the page template "default"
 *   sulu:block-validation:schema page default --check=data.json     validate the JSON file (the content of a page)
 *
 * The check needs opis/json-schema (the validator is not a requirement of the bundle).
 */
#[AsCommand(name: 'sulu:block-validation:schema', description: 'Print the JSON schema of a template form, or validate data against it')]
final class FormSchemaCommand extends Command
{
    public function __construct(
        private readonly ?XmlTemplateFormMetadataLoader $loader = null,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('type', InputArgument::OPTIONAL, 'Kind of template (page, snippet, article ...)', 'page')
            ->addArgument('template', InputArgument::OPTIONAL, 'Template key', 'default')
            ->addOption('check', null, InputOption::VALUE_REQUIRED, 'JSON file with the form data to validate');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (null === $this->loader) {
            $io->error('The admin context is needed: run bin/adminconsole.');

            return Command::FAILURE;
        }

        $typed = $this->loader->getMetadata((string) $input->getArgument('type'));
        $forms = $typed?->getForms() ?? [];
        $form = $forms[(string) $input->getArgument('template')] ?? null;
        if (null === $form) {
            $io->error(\sprintf('No template "%s" of type "%s". Known: %s', $input->getArgument('template'), $input->getArgument('type'), implode(', ', array_keys($forms))));

            return Command::FAILURE;
        }

        $schema = json_decode((string) json_encode($form->getSchema()->toJsonSchema()));
        // the global blocks (type="main-...") are definitions of the schema; the admin adds them when it sends the metadata
        $schema->definitions = new \stdClass();
        foreach ($this->loader->getMetadata('block')?->getForms() ?? [] as $blockKey => $blockForm) {
            $schema->definitions->{$blockKey} = json_decode((string) json_encode($blockForm->getSchema()->toJsonSchema()));
        }
        $check = $input->getOption('check');
        if (null === $check) {
            $output->writeln((string) json_encode($schema, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE));

            return Command::SUCCESS;
        }

        if (!class_exists(Validator::class)) {
            $io->error('The check needs a JSON schema validator: composer require --dev opis/json-schema');

            return Command::FAILURE;
        }

        $data = json_decode((string) file_get_contents((string) $check));
        $result =(new Validator(null, 20))->validate($data, $schema);
        if ($result->isValid()) {
            $io->success('The data is valid.');

            return Command::SUCCESS;
        }

        $error = $result->error();
        $io->error('The data is not valid: ' . ($error?->message() ?? 'unknown'));
        foreach ($error?->subErrors() ?? [] as $sub) {
            $io->writeln(' - /' . implode('/', $sub->data()->fullPath()) . ': ' . $sub->message());
        }

        return Command::FAILURE;
    }
}
