<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ImportExportControllerTest extends TestCase
{
    public function testImportAndExportEntityListsCoverSpecificationObjects(): void
    {
        $controller = new ImportExportController();
        $reflection = new ReflectionClass($controller);

        $importEntities = $reflection->getProperty('importEntities');
        $importEntities->setAccessible(true);

        $exportEntities = $reflection->getProperty('exportEntities');
        $exportEntities->setAccessible(true);

        $expected = ['exam_requests', 'resource_requests', 'online_sessions', 'movement_constraints'];

        foreach ($expected as $entity) {
            $this->assertContains($entity, $importEntities->getValue($controller));
            $this->assertContains($entity, $exportEntities->getValue($controller));
        }
    }
}
