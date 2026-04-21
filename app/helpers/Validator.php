<?php

declare(strict_types=1);

class Validator
{
    private array $data;
    private array $rules;
    private array $messages;
    private array $errors = [];

    public function __construct(array $data, array $rules, array $messages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->messages = $messages;
    }

    public static function make(array $data, array $rules, array $messages = []): self
    {
        return new self($data, $rules, $messages);
    }

    public function passes(): bool
    {
        $this->errors = [];

        foreach ($this->rules as $field => $fieldRules) {
            $value = $this->data[$field] ?? null;
            $fieldRules = is_array($fieldRules) ? $fieldRules : explode('|', (string) $fieldRules);

            $isNullable = in_array('nullable', $fieldRules, true);
            $isEmpty = $value === null || (is_string($value) && trim($value) === '');

            foreach ($fieldRules as $rule) {
                [$name, $parameter] = array_pad(explode(':', (string) $rule, 2), 2, null);

                if ($name === 'nullable' && $isEmpty) {
                    break;
                }

                if ($isNullable && $isEmpty && $name !== 'required') {
                    continue;
                }

                $this->applyRule($field, $value, $name, $parameter);
            }
        }

        return $this->errors === [];
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function first(string $field, ?string $default = null): ?string
    {
        return $this->errors[$field][0] ?? $default;
    }

    public function validated(): array
    {
        return $this->passes()
            ? array_intersect_key($this->data, $this->rules)
            : [];
    }

    private function applyRule(string $field, mixed $value, string $rule, ?string $parameter): void
    {
        $valid = match ($rule) {
            'required' => !($value === null || (is_string($value) && trim($value) === '')),
            'string' => $value === null || is_scalar($value),
            'integer', 'int' => $value === null || filter_var($value, FILTER_VALIDATE_INT) !== false,
            'numeric' => $value === null || is_numeric($value),
            'boolean' => $value === null || in_array($value, [true, false, 0, 1, '0', '1'], true),
            'array' => $value === null || is_array($value),
            'email' => $value === null || filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'url' => $value === null || filter_var($value, FILTER_VALIDATE_URL) !== false,
            'date' => $value === null || strtotime((string) $value) !== false,
            'min' => $this->checkMin($value, $parameter),
            'max' => $this->checkMax($value, $parameter),
            'in' => $this->checkIn($value, $parameter),
            'same' => $this->checkSame($value, $parameter),
            default => true,
        };

        if (!$valid) {
            $this->addError($field, $rule, $parameter);
        }
    }

    private function checkMin(mixed $value, ?string $parameter): bool
    {
        $min = (int) $parameter;

        if (is_array($value)) {
            return count($value) >= $min;
        }

        if (is_numeric($value)) {
            return (float) $value >= $min;
        }

        return mb_strlen(trim((string) $value)) >= $min;
    }

    private function checkMax(mixed $value, ?string $parameter): bool
    {
        $max = (int) $parameter;

        if (is_array($value)) {
            return count($value) <= $max;
        }

        if (is_numeric($value)) {
            return (float) $value <= $max;
        }

        return mb_strlen(trim((string) $value)) <= $max;
    }

    private function checkIn(mixed $value, ?string $parameter): bool
    {
        if ($parameter === null) {
            return true;
        }

        $allowed = array_map('trim', explode(',', $parameter));

        return $value === null || in_array((string) $value, $allowed, true);
    }

    private function checkSame(mixed $value, ?string $parameter): bool
    {
        if ($parameter === null) {
            return true;
        }

        return $value === ($this->data[$parameter] ?? null);
    }

    private function addError(string $field, string $rule, ?string $parameter): void
    {
        $key = $field . '.' . $rule;
        $message = $this->messages[$key] ?? $this->defaultMessage($field, $rule, $parameter);
        $this->errors[$field][] = $message;
    }

    private function defaultMessage(string $field, string $rule, ?string $parameter): string
    {
        $label = str_replace('_', ' ', $field);

        return match ($rule) {
            'required' => "The {$label} field is required.",
            'string' => "The {$label} field must be a string.",
            'integer', 'int' => "The {$label} field must be an integer.",
            'numeric' => "The {$label} field must be numeric.",
            'boolean' => "The {$label} field must be boolean.",
            'array' => "The {$label} field must be an array.",
            'email' => "The {$label} field must be a valid email address.",
            'url' => "The {$label} field must be a valid URL.",
            'date' => "The {$label} field must be a valid date.",
            'min' => "The {$label} field must be at least {$parameter}.",
            'max' => "The {$label} field may not be greater than {$parameter}.",
            'in' => "The {$label} field contains an invalid value.",
            'same' => "The {$label} field must match {$parameter}.",
            default => "The {$label} field is invalid.",
        };
    }
}
