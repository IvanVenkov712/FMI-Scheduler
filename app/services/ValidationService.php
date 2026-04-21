<?php

declare(strict_types=1);

class ValidationService
{
    public function validate(array $data, array $rules, array $messages = []): array
    {
        $validator = Validator::make($data, $rules, $messages);

        return $validator->passes()
            ? ['valid' => true, 'errors' => [], 'data' => $validator->validated()]
            : ['valid' => false, 'errors' => $validator->errors(), 'data' => []];
    }

    public function assert(array $data, array $rules, array $messages = []): array
    {
        $result = $this->validate($data, $rules, $messages);

        if (!$result['valid']) {
            throw new InvalidArgumentException($this->firstErrorMessage($result['errors']) ?? 'Validation failed.');
        }

        return $result['data'];
    }

    public function validateDateRange(string $startsAt, string $endsAt, string $startField = 'starts_at', string $endField = 'ends_at'): array
    {
        $errors = [];

        if (strtotime($startsAt) === false) {
            $errors[$startField][] = 'The start date must be valid.';
        }

        if (strtotime($endsAt) === false) {
            $errors[$endField][] = 'The end date must be valid.';
        }

        if ($errors === [] && strtotime($endsAt) <= strtotime($startsAt)) {
            $errors[$endField][] = 'The end date must be after the start date.';
        }

        return $errors;
    }

    public function firstErrorMessage(array $errors): ?string
    {
        foreach ($errors as $messages) {
            if (is_array($messages) && isset($messages[0])) {
                return (string) $messages[0];
            }

            if (is_string($messages) && $messages !== '') {
                return $messages;
            }
        }

        return null;
    }

    public function normalizeBooleans(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = !empty($data[$field]) ? 1 : 0;
            }
        }

        return $data;
    }

    public function normalizeIntegers(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            if (!array_key_exists($field, $data) || $data[$field] === '' || $data[$field] === null) {
                continue;
            }

            $data[$field] = (int) $data[$field];
        }

        return $data;
    }

    public function normalizeJson(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            if (is_array($data[$field])) {
                $data[$field] = json_encode($data[$field], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }

        return $data;
    }
}
