<?php
/**
 * ResQFood — Validation Helpers
 * ──────────────────────────────
 * All functions accept an $errors array by reference and append to it.
 * Pattern: validate*($input, $field, $errors) — returns bool (true = passes).
 *
 * Usage example:
 *   $errors = [];
 *   validateRequired($data, ['title', 'quantity'], $errors);
 *   validateEmail($data['email'], 'email', $errors);
 *   if (empty($errors)) { // proceed }
 */

// ── Required & Presence ───────────────────────────────────────────────────

/**
 * Ensure all listed fields are present and non-empty.
 */
function validateRequired(array $data, array $fields, array &$errors): void
{
    foreach ($fields as $field) {
        $value = $data[$field] ?? '';
        if (trim((string) $value) === '') {
            $errors[$field] = fieldLabel($field) . ' is required.';
        }
    }
}

// ── Email ─────────────────────────────────────────────────────────────────

function validateEmail(string $email, string $field, array &$errors): bool
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[$field] = 'Please enter a valid email address.';
        return false;
    }
    return true;
}

/**
 * Check whether an email already exists in the users table.
 */
function emailExists(string $email, ?int $excludeUserId = null): bool
{
    $sql  = 'SELECT id FROM users WHERE email = ?';
    $args = [strtolower(trim($email))];

    if ($excludeUserId !== null) {
        $sql .= ' AND id != ?';
        $args[] = $excludeUserId;
    }

    $stmt = db()->prepare($sql . ' LIMIT 1');
    $stmt->execute($args);
    return (bool) $stmt->fetch();
}

// ── Password ──────────────────────────────────────────────────────────────

/**
 * Validate password strength: min 8 chars, at least 1 letter and 1 digit.
 */
function validatePassword(string $password, string $field, array &$errors): bool
{
    if (strlen($password) < 8) {
        $errors[$field] = 'Password must be at least 8 characters long.';
        return false;
    }
    if (!preg_match('/[A-Za-z]/', $password)) {
        $errors[$field] = 'Password must contain at least one letter.';
        return false;
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[$field] = 'Password must contain at least one number.';
        return false;
    }
    return true;
}

/**
 * Validate that two password fields match.
 */
function validatePasswordMatch(string $password, string $confirm, array &$errors): bool
{
    if ($password !== $confirm) {
        $errors['password_confirm'] = 'Passwords do not match.';
        return false;
    }
    return true;
}

// ── Numeric ───────────────────────────────────────────────────────────────

/**
 * Validate a value is numeric and within an optional min/max range.
 */
function validateNumeric(
    mixed  $value,
    string $field,
    array  &$errors,
    float  $min = 0,
    ?float $max = null
): bool {
    if (!is_numeric($value)) {
        $errors[$field] = fieldLabel($field) . ' must be a number.';
        return false;
    }
    $num = (float) $value;
    if ($num < $min) {
        $errors[$field] = fieldLabel($field) . " must be at least {$min}.";
        return false;
    }
    if ($max !== null && $num > $max) {
        $errors[$field] = fieldLabel($field) . " must not exceed {$max}.";
        return false;
    }
    return true;
}

// ── Date & Time ───────────────────────────────────────────────────────────

/**
 * Validate a date string matches a specific format.
 */
function validateDate(string $date, string $field, array &$errors, string $format = 'Y-m-d'): bool
{
    $d = DateTime::createFromFormat($format, $date);
    if (!$d || $d->format($format) !== $date) {
        $errors[$field] = fieldLabel($field) . " must be a valid date ({$format}).";
        return false;
    }
    return true;
}

/**
 * Validate a datetime is in the future.
 */
function validateFutureDate(string $datetime, string $field, array &$errors): bool
{
    if (strtotime($datetime) <= time()) {
        $errors[$field] = fieldLabel($field) . ' must be a future date and time.';
        return false;
    }
    return true;
}

/**
 * Validate that $end is after $start.
 */
function validateDateOrder(string $start, string $end, string $endField, array &$errors): bool
{
    if (strtotime($end) <= strtotime($start)) {
        $errors[$endField] = fieldLabel($endField) . ' must be after the start time.';
        return false;
    }
    return true;
}

// ── Enum / Allowed Values ─────────────────────────────────────────────────

/**
 * Validate a value is in an explicit allowed set.
 */
function validateEnum(mixed $value, array $allowed, string $field, array &$errors): bool
{
    if (!in_array($value, $allowed, true)) {
        $errors[$field] = fieldLabel($field) . ' has an invalid value.';
        return false;
    }
    return true;
}

// ── String Constraints ────────────────────────────────────────────────────

function validateMaxLength(string $value, int $max, string $field, array &$errors): bool
{
    if (mb_strlen($value) > $max) {
        $errors[$field] = fieldLabel($field) . " must not exceed {$max} characters.";
        return false;
    }
    return true;
}

function validateMinLength(string $value, int $min, string $field, array &$errors): bool
{
    if (mb_strlen($value) < $min) {
        $errors[$field] = fieldLabel($field) . " must be at least {$min} characters.";
        return false;
    }
    return true;
}

// ── Phone ─────────────────────────────────────────────────────────────────

function validatePhone(string $phone, string $field, array &$errors): bool
{
    if (!preg_match('/^[\d\s\+\-\(\)]{7,20}$/', $phone)) {
        $errors[$field] = 'Please enter a valid phone number.';
        return false;
    }
    return true;
}

// ── Internal Helper ───────────────────────────────────────────────────────

/**
 * Convert a snake_case field key to a human-readable label.
 */
function fieldLabel(string $field): string
{
    return ucwords(str_replace('_', ' ', $field));
}
