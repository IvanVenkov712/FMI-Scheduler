<?php

declare(strict_types=1);

class AsciiPlanParser
{
    public function parse(string $content): array
    {
        $content = str_replace(["\r\n", "\r"], "\n", trim($content, "\n"));
        $lines = $content === '' ? [] : explode("\n", $content);
        $height = count($lines);
        $width = $height > 0 ? max(array_map('strlen', $lines)) : 0;

        $grid = [];
        $legend = [];

        foreach ($lines as $y => $line) {
            $line = str_pad($line, $width, ' ');
            $chars = str_split($line);
            $grid[$y] = $chars;

            foreach ($chars as $x => $char) {
                if (trim($char) === '') {
                    continue;
                }

                $legend[$char][] = ['x' => $x, 'y' => $y];
            }
        }

        return [
            'width' => $width,
            'height' => $height,
            'lines' => $lines,
            'grid' => $grid,
            'legend' => $legend,
        ];
    }

    public function parseFile(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('ASCII plan file not found or not readable.');
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException('ASCII plan file could not be read.');
        }

        return $this->parse($content);
    }

    public function render(array $grid): string
    {
        $lines = [];

        foreach ($grid as $row) {
            if (is_string($row)) {
                $lines[] = $row;
                continue;
            }

            if (!is_array($row)) {
                continue;
            }

            $lines[] = implode('', array_map(static fn (mixed $cell): string => (string) $cell, $row));
        }

        return implode(PHP_EOL, $lines);
    }

    public function exportToFile(string $path, array|string $grid): void
    {
        $content = is_array($grid) ? $this->render($grid) : $grid;

        if (file_put_contents($path, $content) === false) {
            throw new RuntimeException('ASCII plan file could not be written.');
        }
    }

    public function symbolsSummary(array $parsedPlan): array
    {
        $legend = $parsedPlan['legend'] ?? [];
        $summary = [];

        foreach ($legend as $symbol => $positions) {
            $summary[$symbol] = is_array($positions) ? count($positions) : 0;
        }

        ksort($summary);

        return $summary;
    }
}
