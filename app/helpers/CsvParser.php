<?php

declare(strict_types=1);

class CsvParser
{
    public function parseFile(string $path, string $delimiter = ',', string $enclosure = '"', string $escape = '\\'): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('CSV file not found or not readable.');
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('CSV file could not be opened.');
        }

        $headers = fgetcsv($handle, 0, $delimiter, $enclosure, $escape);
        if ($headers === false) {
            fclose($handle);
            return [];
        }

        $rows = [];

        while (($row = fgetcsv($handle, 0, $delimiter, $enclosure, $escape)) !== false) {
            if ($row === [null] || $row === []) {
                continue;
            }

            $row = array_pad($row, count($headers), null);
            $rows[] = array_combine($headers, array_slice($row, 0, count($headers))) ?: [];
        }

        fclose($handle);

        return $rows;
    }

    public function parseString(string $content, string $delimiter = ',', string $enclosure = '"', string $escape = '\\'): array
    {
        $temp = fopen('php://temp', 'r+');
        if ($temp === false) {
            throw new RuntimeException('Temporary CSV stream could not be created.');
        }

        fwrite($temp, $content);
        rewind($temp);

        $headers = fgetcsv($temp, 0, $delimiter, $enclosure, $escape);
        if ($headers === false) {
            fclose($temp);
            return [];
        }

        $rows = [];

        while (($row = fgetcsv($temp, 0, $delimiter, $enclosure, $escape)) !== false) {
            $row = array_pad($row, count($headers), null);
            $rows[] = array_combine($headers, array_slice($row, 0, count($headers))) ?: [];
        }

        fclose($temp);

        return $rows;
    }

    public function exportToFile(string $path, array $rows, bool $includeHeader = true, string $delimiter = ',', string $enclosure = '"', string $escape = '\\'): void
    {
        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new RuntimeException('CSV export file could not be created.');
        }

        $this->writeRows($handle, $rows, $includeHeader, $delimiter, $enclosure, $escape);
        fclose($handle);
    }

    public function toString(array $rows, bool $includeHeader = true, string $delimiter = ',', string $enclosure = '"', string $escape = '\\'): string
    {
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            throw new RuntimeException('Temporary CSV stream could not be created.');
        }

        $this->writeRows($handle, $rows, $includeHeader, $delimiter, $enclosure, $escape);
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return $content !== false ? $content : '';
    }

    private function writeRows($handle, array $rows, bool $includeHeader, string $delimiter, string $enclosure, string $escape): void
    {
        if ($rows === []) {
            return;
        }

        $firstRow = reset($rows);
        if (!is_array($firstRow)) {
            throw new RuntimeException('CSV rows must be arrays.');
        }

        $headers = array_keys($firstRow);

        if ($includeHeader) {
            fputcsv($handle, $headers, $delimiter, $enclosure, $escape);
        }

        foreach ($rows as $row) {
            $ordered = [];

            foreach ($headers as $header) {
                $ordered[] = $row[$header] ?? null;
            }

            fputcsv($handle, $ordered, $delimiter, $enclosure, $escape);
        }
    }
}
