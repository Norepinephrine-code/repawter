<?php
declare(strict_types=1);

function sanitize_string(?string $v): string
{
    return trim((string)$v);
}

function validate(array $input, array $rules): array
{
    $clean  = [];
    $errors = [];

    foreach ($rules as $field => $ruleStr) {
        $parts = explode('|', $ruleStr);
        $value = $input[$field] ?? null;
        $strVal = is_array($value) ? '' : trim((string)$value);

        $isRequired = in_array('required', $parts, true);
        $isEmpty    = ($strVal === '' || $value === null);

        if ($isEmpty && !$isRequired) {
            $clean[$field] = null;
            continue;
        }

        if ($isRequired && $isEmpty) {
            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
            $clean[$field]  = null;
            continue;
        }

        $fieldErrors = [];

        foreach ($parts as $rule) {
            if ($rule === 'required') {
                continue;
            }

            if ($rule === 'email') {
                if (filter_var($strVal, FILTER_VALIDATE_EMAIL) === false) {
                    $fieldErrors[] = 'Must be a valid email address.';
                }
                continue;
            }

            if ($rule === 'int') {
                if (filter_var($strVal, FILTER_VALIDATE_INT) === false) {
                    $fieldErrors[] = 'Must be an integer.';
                } else {
                    $strVal = (string)(int)$strVal;
                }
                continue;
            }

            if ($rule === 'float') {
                if (filter_var($strVal, FILTER_VALIDATE_FLOAT) === false) {
                    $fieldErrors[] = 'Must be a number.';
                }
                continue;
            }

            if (str_starts_with($rule, 'max:')) {
                $max = (int)substr($rule, 4);
                if (mb_strlen($strVal) > $max) {
                    $fieldErrors[] = 'Must be at most ' . $max . ' characters.';
                }
                continue;
            }

            if (str_starts_with($rule, 'min:')) {
                $min = (int)substr($rule, 4);
                if (is_numeric($strVal) && (float)$strVal < $min) {
                    $fieldErrors[] = 'Must be at least ' . $min . '.';
                } elseif (!is_numeric($strVal) && mb_strlen($strVal) < $min) {
                    $fieldErrors[] = 'Must be at least ' . $min . ' characters.';
                }
                continue;
            }

            if (str_starts_with($rule, 'in:')) {
                $options = explode(',', substr($rule, 3));
                if (!in_array($strVal, $options, true)) {
                    $fieldErrors[] = 'Invalid selection.';
                }
                continue;
            }

            if ($rule === 'url') {
                if (filter_var($strVal, FILTER_VALIDATE_URL) === false) {
                    $fieldErrors[] = 'Must be a valid URL.';
                }
                continue;
            }

            if ($rule === 'nullable') {
                continue;
            }
        }

        if (!empty($fieldErrors)) {
            $errors[$field] = implode(' ', $fieldErrors);
            $clean[$field]  = null;
        } else {
            $clean[$field] = $strVal;
        }
    }

    return [$clean, $errors];
}

function old(string $key, string $default = ''): string
{
    $old = peek_old();
    return isset($old[$key]) ? (string)$old[$key] : $default;
}
