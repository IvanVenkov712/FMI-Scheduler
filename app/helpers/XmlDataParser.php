<?php

declare(strict_types=1);

class XmlDataParser
{
    public function parseFile(string $path, string $rowTag = 'row'): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('XML file not found or not readable.');
        }

        $xml = simplexml_load_file($path, SimpleXMLElement::class, LIBXML_NOCDATA);
        if ($xml === false) {
            throw new RuntimeException('XML file could not be parsed.');
        }

        return $this->extractRows($xml, $rowTag);
    }

    public function parseString(string $xmlContent, string $rowTag = 'row'): array
    {
        $xml = simplexml_load_string($xmlContent, SimpleXMLElement::class, LIBXML_NOCDATA);
        if ($xml === false) {
            throw new RuntimeException('XML content could not be parsed.');
        }

        return $this->extractRows($xml, $rowTag);
    }

    public function exportToFile(string $path, array $rows, string $rootTag = 'rows', string $rowTag = 'row'): void
    {
        $content = $this->toString($rows, $rootTag, $rowTag);

        if (file_put_contents($path, $content) === false) {
            throw new RuntimeException('XML export file could not be written.');
        }
    }

    public function toString(array $rows, string $rootTag = 'rows', string $rowTag = 'row'): string
    {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        $root = $xml->createElement($rootTag);
        $xml->appendChild($root);

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $rowNode = $xml->createElement($rowTag);
            $this->appendArray($xml, $rowNode, $row);
            $root->appendChild($rowNode);
        }

        return $xml->saveXML() ?: '';
    }

    private function extractRows(SimpleXMLElement $xml, string $rowTag): array
    {
        $rows = [];
        $nodes = $xml->xpath('.//' . $rowTag);

        if ($nodes === false || $nodes === []) {
            $array = $this->xmlNodeToArray($xml);

            if ($array !== []) {
                $rows[] = $array;
            }

            return $rows;
        }

        foreach ($nodes as $node) {
            $rows[] = $this->xmlNodeToArray($node);
        }

        return $rows;
    }

    private function xmlNodeToArray(SimpleXMLElement $node): array
    {
        $result = [];

        foreach ($node->attributes() as $name => $value) {
            $result['@' . $name] = (string) $value;
        }

        foreach ($node->children() as $childName => $child) {
            $value = $child->count() > 0 ? $this->xmlNodeToArray($child) : trim((string) $child);

            if (array_key_exists($childName, $result)) {
                if (!is_array($result[$childName]) || !array_is_list($result[$childName])) {
                    $result[$childName] = [$result[$childName]];
                }

                $result[$childName][] = $value;
            } else {
                $result[$childName] = $value;
            }
        }

        return $result;
    }

    private function appendArray(DOMDocument $xml, DOMElement $parent, array $data): void
    {
        foreach ($data as $key => $value) {
            if (is_int($key)) {
                $key = 'item';
            }

            if (str_starts_with((string) $key, '@')) {
                $parent->setAttribute(substr((string) $key, 1), (string) $value);
                continue;
            }

            if (is_array($value) && array_is_list($value)) {
                foreach ($value as $listValue) {
                    $child = $xml->createElement((string) $key);

                    if (is_array($listValue)) {
                        $this->appendArray($xml, $child, $listValue);
                    } else {
                        $child->appendChild($xml->createTextNode((string) $listValue));
                    }

                    $parent->appendChild($child);
                }

                continue;
            }

            $child = $xml->createElement((string) $key);

            if (is_array($value)) {
                $this->appendArray($xml, $child, $value);
            } else {
                $child->appendChild($xml->createTextNode((string) $value));
            }

            $parent->appendChild($child);
        }
    }
}
