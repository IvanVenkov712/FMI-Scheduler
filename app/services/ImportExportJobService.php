<?php

declare(strict_types=1);

class ImportExportJobService
{
    public function __construct(
        private readonly ImportExportJobRepository $jobs = new ImportExportJobRepository()
    ) {
    }

    public function history(int $limit = 100): array
    {
        return $this->jobs->recent($limit);
    }
}
