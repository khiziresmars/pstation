<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Input Validator
 * Validates and sanitizes input data
 */
class Validator
{
    private array $data;
    private array $errors = [];
    private array $validated = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Create validator from request data
     */
    public static function make(array $data): self
    {
        return new self($data);
    }

    /**
     * Validate data against rules
     */
    public function validate(array $rules): self
    {
        foreach ($rules as $field => $ruleSet) {
            $value = $this->getValue($field);
            $rulesArray = is_string($ruleSet) ? explode('|', $ruleSet) : $ruleSet;

            foreach ($rulesArray as $rule) {
                $this->applyRule($field, $value, $rule);
            }

            if (!isset($this->errors[$field])) {
                $this->validated[$field] = $value;
            }
        }

        return $this;
    }

    /**
     * Check if validation passed
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Check if validation failed
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get validation errors
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error message
     */
    public function firstError(): ?string
    {
        foreach ($this->errors as $fieldErrors) {
            return $fieldErrors[0] ?? null;
        }
        return null;
    }

    /**
     * Get validated data
     */
    public function validated(): array
    {
        return $this->validated;
    }

    /**
     * Get a specific validated value
     */
    public function get(string $field, mixed $default = null): mixed
    {
        return $this->validated[$field] ?? $default;
    }

    /**
     * Get nested value using dot notation
     */
    private function getValue(string $field): mixed
    {
        $keys = explode('.', $field);
        $value = $this->data;

        foreach ($keys as $key) {
            if (is_array($value) && array_key_exists($key, $value)) {
                $value = $value[$key];
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * Apply a single validation rule
     */
    private function applyRule(string $field, mixed $value, string $rule): void
    {
        $params = [];
        if (str_contains($rule, ':')) {
            [$rule, $paramStr] = explode(':', $rule, 2);
            $params = explode(',', $paramStr);
        }

        $method = 'validate' . str_replace('_', '', ucwords($rule, '_'));

        if (method_exists($this, $method)) {
            $this->$method($field, $value, $params);
        }
    }

    /**
     * Add an error
     */
    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    // ==================== Validation Rules ====================

    private function validateRequired(string $field, mixed $value, array $params): void
    {
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            $this->addError($field, "The {$field} field is required");
        }
    }

    private function validateNullable(string $field, mixed $value, array $params): void
    {
        // Nullable allows null values, no error needed
    }

    private function validateString(string $field, mixed $value, array $params): void
    {
        if ($value !== null && !is_string($value)) {
            $this->addError($field, "The {$field} must be a string");
        }
    }

    private function validateInteger(string $field, mixed $value, array $params): void
    {
        if ($value !== null && !filter_var($value, FILTER_VALIDATE_INT)) {
            $this->addError($field, "The {$field} must be an integer");
        } else if ($value !== null) {
            $this->validated[$field] = (int) $value;
        }
    }

    private function validateNumeric(string $field, mixed $value, array $params): void
    {
        if ($value !== null && !is_numeric($value)) {
            $this->addError($field, "The {$field} must be numeric");
        }
    }

    private function validateFloat(string $field, mixed $value, array $params): void
    {
        if ($value !== null && !is_numeric($value)) {
            $this->addError($field, "The {$field} must be a number");
        } else if ($value !== null) {
            $this->validated[$field] = (float) $value;
        }
    }

    private function validateBoolean(string $field, mixed $value, array $params): void
    {
        if ($value !== null) {
            $valid = [true, false, 1, 0, '1', '0', 'true', 'false', 'yes', 'no'];
            if (!in_array($value, $valid, true)) {
                $this->addError($field, "The {$field} must be a boolean");
            } else {
                $this->validated[$field] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }
        }
    }

    private function validateArray(string $field, mixed $value, array $params): void
    {
        if ($value !== null && !is_array($value)) {
            $this->addError($field, "The {$field} must be an array");
        }
    }

    private function validateEmail(string $field, mixed $value, array $params): void
    {
        if ($value !== null && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, "The {$field} must be a valid email address");
        }
    }

    private function validateUrl(string $field, mixed $value, array $params): void
    {
        if ($value !== null && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->addError($field, "The {$field} must be a valid URL");
        }
    }

    private function validateMin(string $field, mixed $value, array $params): void
    {
        $min = (float) ($params[0] ?? 0);

        if (is_string($value) && mb_strlen($value) < $min) {
            $this->addError($field, "The {$field} must be at least {$min} characters");
        } elseif (is_numeric($value) && $value < $min) {
            $this->addError($field, "The {$field} must be at least {$min}");
        } elseif (is_array($value) && count($value) < $min) {
            $this->addError($field, "The {$field} must have at least {$min} items");
        }
    }

    private function validateMax(string $field, mixed $value, array $params): void
    {
        $max = (float) ($params[0] ?? PHP_INT_MAX);

        if (is_string($value) && mb_strlen($value) > $max) {
            $this->addError($field, "The {$field} must not exceed {$max} characters");
        } elseif (is_numeric($value) && $value > $max) {
            $this->addError($field, "The {$field} must not exceed {$max}");
        } elseif (is_array($value) && count($value) > $max) {
            $this->addError($field, "The {$field} must not have more than {$max} items");
        }
    }

    private function validateBetween(string $field, mixed $value, array $params): void
    {
        $min = (float) ($params[0] ?? 0);
        $max = (float) ($params[1] ?? PHP_INT_MAX);

        if (is_numeric($value) && ($value < $min || $value > $max)) {
            $this->addError($field, "The {$field} must be between {$min} and {$max}");
        }
    }

    private function validateIn(string $field, mixed $value, array $params): void
    {
        if ($value !== null && !in_array($value, $params, true)) {
            $allowed = implode(', ', $params);
            $this->addError($field, "The {$field} must be one of: {$allowed}");
        }
    }

    private function validateNotIn(string $field, mixed $value, array $params): void
    {
        if ($value !== null && in_array($value, $params, true)) {
            $this->addError($field, "The {$field} contains an invalid value");
        }
    }

    private function validateDate(string $field, mixed $value, array $params): void
    {
        if ($value !== null) {
            $format = $params[0] ?? 'Y-m-d';
            $date = \DateTime::createFromFormat($format, $value);
            if (!$date || $date->format($format) !== $value) {
                $this->addError($field, "The {$field} must be a valid date in format {$format}");
            }
        }
    }

    private function validateDateAfter(string $field, mixed $value, array $params): void
    {
        if ($value !== null) {
            $compareDate = $params[0] ?? 'today';
            $valueTime = strtotime($value);
            $compareTime = strtotime($compareDate);

            if ($valueTime === false || $compareTime === false) {
                $this->addError($field, "The {$field} must be a valid date");
            } elseif ($valueTime <= $compareTime) {
                $this->addError($field, "The {$field} must be after {$compareDate}");
            }
        }
    }

    private function validateDateBefore(string $field, mixed $value, array $params): void
    {
        if ($value !== null) {
            $compareDate = $params[0] ?? 'today';
            $valueTime = strtotime($value);
            $compareTime = strtotime($compareDate);

            if ($valueTime === false || $compareTime === false) {
                $this->addError($field, "The {$field} must be a valid date");
            } elseif ($valueTime >= $compareTime) {
                $this->addError($field, "The {$field} must be before {$compareDate}");
            }
        }
    }

    private function validateTime(string $field, mixed $value, array $params): void
    {
        if ($value !== null) {
            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $value)) {
                $this->addError($field, "The {$field} must be a valid time (HH:MM or HH:MM:SS)");
            }
        }
    }

    private function validateRegex(string $field, mixed $value, array $params): void
    {
        $pattern = $params[0] ?? '';
        if ($value !== null && !preg_match($pattern, $value)) {
            $this->addError($field, "The {$field} format is invalid");
        }
    }

    private function validatePhone(string $field, mixed $value, array $params): void
    {
        if ($value !== null) {
            $cleaned = preg_replace('/[^0-9+]/', '', $value);
            if (!preg_match('/^\+?[0-9]{8,15}$/', $cleaned)) {
                $this->addError($field, "The {$field} must be a valid phone number");
            } else {
                $this->validated[$field] = $cleaned;
            }
        }
    }

    private function validateSlug(string $field, mixed $value, array $params): void
    {
        if ($value !== null && !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value)) {
            $this->addError($field, "The {$field} must be a valid slug (lowercase letters, numbers, and hyphens)");
        }
    }

    private function validateAlpha(string $field, mixed $value, array $params): void
    {
        if ($value !== null && !preg_match('/^[\pL]+$/u', $value)) {
            $this->addError($field, "The {$field} must only contain letters");
        }
    }

    private function validateAlphaNum(string $field, mixed $value, array $params): void
    {
        if ($value !== null && !preg_match('/^[\pL\pN]+$/u', $value)) {
            $this->addError($field, "The {$field} must only contain letters and numbers");
        }
    }

    private function validateAlphaDash(string $field, mixed $value, array $params): void
    {
        if ($value !== null && !preg_match('/^[\pL\pN_-]+$/u', $value)) {
            $this->addError($field, "The {$field} must only contain letters, numbers, dashes, and underscores");
        }
    }

    private function validateJson(string $field, mixed $value, array $params): void
    {
        if ($value !== null) {
            if (is_string($value)) {
                json_decode($value);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->addError($field, "The {$field} must be valid JSON");
                }
            } elseif (!is_array($value)) {
                $this->addError($field, "The {$field} must be valid JSON");
            }
        }
    }

    private function validateUuid(string $field, mixed $value, array $params): void
    {
        if ($value !== null) {
            $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
            if (!preg_match($pattern, $value)) {
                $this->addError($field, "The {$field} must be a valid UUID");
            }
        }
    }

    private function validateConfirmed(string $field, mixed $value, array $params): void
    {
        $confirmField = $field . '_confirmation';
        $confirmValue = $this->getValue($confirmField);

        if ($value !== $confirmValue) {
            $this->addError($field, "The {$field} confirmation does not match");
        }
    }

    private function validateSame(string $field, mixed $value, array $params): void
    {
        $otherField = $params[0] ?? '';
        $otherValue = $this->getValue($otherField);

        if ($value !== $otherValue) {
            $this->addError($field, "The {$field} must match {$otherField}");
        }
    }

    private function validateDifferent(string $field, mixed $value, array $params): void
    {
        $otherField = $params[0] ?? '';
        $otherValue = $this->getValue($otherField);

        if ($value === $otherValue) {
            $this->addError($field, "The {$field} must be different from {$otherField}");
        }
    }

    private function validateRequiredIf(string $field, mixed $value, array $params): void
    {
        $otherField = $params[0] ?? '';
        $otherValue = $this->getValue($otherField);
        $expectedValues = array_slice($params, 1);

        if (in_array($otherValue, $expectedValues) && ($value === null || $value === '')) {
            $this->addError($field, "The {$field} field is required when {$otherField} is " . implode('/', $expectedValues));
        }
    }

    private function validateRequiredWith(string $field, mixed $value, array $params): void
    {
        foreach ($params as $otherField) {
            $otherValue = $this->getValue($otherField);
            if ($otherValue !== null && $otherValue !== '') {
                if ($value === null || $value === '') {
                    $this->addError($field, "The {$field} field is required when {$otherField} is present");
                }
                break;
            }
        }
    }

    private function validateImage(string $field, mixed $value, array $params): void
    {
        if ($value !== null && isset($value['type'])) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($value['type'], $allowedTypes)) {
                $this->addError($field, "The {$field} must be an image (JPEG, PNG, GIF, WEBP)");
            }
        }
    }

    private function validateFile(string $field, mixed $value, array $params): void
    {
        if ($value !== null) {
            if (!is_array($value) || !isset($value['tmp_name']) || !is_uploaded_file($value['tmp_name'])) {
                $this->addError($field, "The {$field} must be a valid uploaded file");
            }
        }
    }

    private function validateMimes(string $field, mixed $value, array $params): void
    {
        if ($value !== null && isset($value['name'])) {
            $extension = strtolower(pathinfo($value['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, $params)) {
                $allowed = implode(', ', $params);
                $this->addError($field, "The {$field} must be a file of type: {$allowed}");
            }
        }
    }

    private function validateMaxFileSize(string $field, mixed $value, array $params): void
    {
        $maxKb = (int) ($params[0] ?? 2048);
        if ($value !== null && isset($value['size'])) {
            $sizeKb = $value['size'] / 1024;
            if ($sizeKb > $maxKb) {
                $this->addError($field, "The {$field} must not be larger than {$maxKb}KB");
            }
        }
    }

    // ==================== Sanitization Helpers ====================

    /**
     * Sanitize string for safe HTML output
     */
    public static function sanitizeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Sanitize for SQL LIKE queries
     */
    public static function sanitizeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
    }

    /**
     * Strip all HTML tags
     */
    public static function stripTags(string $value): string
    {
        return strip_tags($value);
    }

    /**
     * Trim and normalize whitespace
     */
    public static function normalizeWhitespace(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', $value));
    }

    /**
     * Generate a safe filename
     */
    public static function sanitizeFilename(string $filename): string
    {
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        return preg_replace('/_+/', '_', $filename);
    }
}
