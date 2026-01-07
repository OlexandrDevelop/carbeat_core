<?php

namespace App\Http\Services\Import;

use InvalidArgumentException;

class ImportServiceFactory
{
    /**
     * @var ImportServiceInterface[]
     */
    private array $importers;

    public function __construct(ImportServiceInterface ...$importers)
    {
        $this->importers = $importers;
    }

    public function getImporter(string $url): ImportServiceInterface
    {
        foreach ($this->importers as $importer) {
            if ($importer->canHandle($url)) {
                return $importer;
            }
        }

        throw new InvalidArgumentException('No importer found for the given URL.');
    }
}

