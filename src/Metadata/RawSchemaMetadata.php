<?php

declare(strict_types=1);

namespace Manuxi\SuluBlockValidationBundle\Metadata;

use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadataInterface;

/**
 * A piece of JSON schema that is already complete (used for the conditions of ConditionalSchemaMetadataProvider).
 */
final class RawSchemaMetadata implements SchemaMetadataInterface
{
    /**
     * @param array<string, mixed> $schema
     */
    public function __construct(private readonly array $schema)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function toJsonSchema(): array
    {
        return $this->schema;
    }
}
