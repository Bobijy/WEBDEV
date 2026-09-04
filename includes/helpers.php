<?php
/**
 * Maison Ungod — Shared Helper Functions
 *
 * Centralizes: sanitization, validation, JSON responses, and auth guards.
 * Required by all API files — include AFTER db.php.
 */

// ══════════════════════════════════════════════
// SANITIZATION HELPERS
// ══════════════════════════════════════════════

/**
 * Sanitize a plain string input.
 * Trims whitespace and encodes HTML special characters.
 *
 * @param  string $value Raw user input
 * @return string        Sanitized value
 */
function sanitize_string(string $value): string {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize a raw string without HTML-encoding.
 * Use when the value is stored then later output-escaped separately.
 *
 * @param  string $value Raw user input
 * @return string        Trimmed value
 */
function sanitize_raw(string $value): string {
    return trim($value);
}

// ══════════════════════════════════════════════
// VALIDATION HELPERS
// ══════════════════════════════════════════════

/**
 * Validate an email address using PHP's built-in filter.
 *
 * @param  string $email Value to test
 * @return bool
 */
function validate_email(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate a full name.
 * Rules: 2–100 chars, letters, spaces, hyphens, apostrophes only.
 *
 * @param  string $name
 * @return bool
 */
function validate_name(string $name): bool {
    $len = mb_strlen($name);
    if ($len < 2 || $len > 100) return false;
    // Allow letters (Unicode), spaces, hyphens, apostrophes
    return (bool) preg_match("/^[\p{L}\s'\-]+$/u", $name);
}

/**
 * Validate a Philippine/international phone number.
 * Accepts: +63 9XX, 09XX, or plain 10-digit formats.
 * Allows spaces, dashes, parentheses as separators.
 *
 * @param  string $phone
 * @return bool
 */
function validate_phone(string $phone): bool {
    // Strip separators for length check
    $digits = preg_replace('/[\s\-\(\)\+]/', '', $phone);
    $len = strlen($digits);
    if ($len < 10 || $len > 15) return false;
    // Must contain only digit-related characters
    return (bool) preg_match('/^[\+\d\s\-\(\)]+$/', $phone);
}

/**
 * Validate a password meets the minimum strength requirement.
 * Rule: at least 6 characters.
 *
 * @param  string $password
 * @return bool
 */
function validate_password(string $password): bool {
    return strlen($password) >= 6;
}

/**
 * Validate a positive integer ID (e.g., product_id, order_id).
 *
 * @param  mixed $id
 * @return bool
 */
function validate_id($id): bool {
    return is_numeric($id) && (int)$id > 0;
}

/**
 * Validate a quantity value (must be a positive integer >= 1).
 *
 * @param  mixed $qty
 * @return bool
 */
function validate_quantity($qty): bool {
    return is_numeric($qty) && (int)$qty >= 1;
}

/**
 * Validate a price value (must be a positive number).
 *
 * @param  mixed $price
 * @return bool
 */
function validate_price($price): bool {
    return is_numeric($price) && (float)$price > 0;
}

/**
 * Validate a stock value (must be a non-negative integer).
 *
 * @param  mixed $stock
 * @return bool
 */
function validate_stock($stock): bool {
    return is_numeric($stock) && (int)$stock >= 0;
}

/**
 * Validate that a value exists in an allowed whitelist array.
 *
 * @param  mixed  $value
 * @param  array  $allowed
 * @return bool
 */
function validate_in_list($value, array $allowed): bool {
    return in_array($value, $allowed, true);
}

// ══════════════════════════════════════════════
// RESPONSE HELPERS
// ══════════════════════════════════════════════

/**
 * Emit a JSON response and terminate execution.
 *
 * @param  bool   $success
 * @param  string $message
 * @param  array  $extra   Additional keys to merge into the response
 * @return void
 */
function json_response(bool $success, string $message = '', array $extra = []): void {
    $payload = ['success' => $success];
    if ($message !== '') {
        $payload['message'] = $message;
    }
    echo json_encode(array_merge($payload, $extra));
    exit;
}

// ══════════════════════════════════════════════
// AUTH / MIDDLEWARE HELPERS
// ══════════════════════════════════════════════

/**
 * Require the user to be logged in.
 * Sends a 401 JSON error and exits if no session is active.
 *
 * @return void
 */
function require_auth(): void {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        json_response(false, 'Unauthorized. Please log in.');
    }
}

/**
 * Require the logged-in user to have the 'admin' role.
 * Sends a 403 JSON error and exits otherwise.
 *
 * @return void
 */
function require_admin(): void {
    require_auth();
    if (($_SESSION['user_role'] ?? '') !== 'admin') {
        http_response_code(403);
        json_response(false, 'Forbidden: Admins only.');
    }
}

// ══════════════════════════════════════════════
// DATABASE HELPERS
// ══════════════════════════════════════════════

/**
 * Fetch a single row from the database using a prepared statement.
 *
 * @param  PDO    $pdo
 * @param  string $sql    SQL with named placeholders (e.g., :id)
 * @param  array  $params Associative array of named parameters
 * @return array|null     Associative row or null if not found
 */
function db_fetch(PDO $pdo, string $sql, array $params = []): ?array {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/**
 * Fetch all rows from the database using a prepared statement.
 *
 * @param  PDO    $pdo
 * @param  string $sql
 * @param  array  $params
 * @return array
 */
function db_fetch_all(PDO $pdo, string $sql, array $params = []): array {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Execute an INSERT/UPDATE/DELETE statement.
 * Returns the number of affected rows on success.
 *
 * @param  PDO    $pdo
 * @param  string $sql
 * @param  array  $params
 * @return int    Affected row count
 */
function db_execute(PDO $pdo, string $sql, array $params = []): int {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}
