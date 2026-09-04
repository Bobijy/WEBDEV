<?php
/**
 * Maison Ungod — Shared Helper Functions
 *
 * Centralizes: sanitization, validation, JSON responses, and auth guards.
 * Required by all API files — include AFTER db.php.
 */

require_once __DIR__ . '/Sanitizer.php';
require_once __DIR__ . '/Validation.php';
require_once __DIR__ . '/Response.php';
require_once __DIR__ . '/CSRF.php';

// ══════════════════════════════════════════════
// SANITIZATION HELPERS
// ══════════════════════════════════════════════
function sanitize_string(string $value): string {
    return Sanitizer::string($value);
}

function sanitize_raw(string $value): string {
    return Sanitizer::raw($value);
}

// ══════════════════════════════════════════════
// VALIDATION HELPERS
// ══════════════════════════════════════════════
function validate_email(string $email): bool { return Validation::email($email); }
function validate_name(string $name): bool { return Validation::name($name); }
function validate_phone(string $phone): bool { return Validation::phone($phone); }
function validate_password(string $password): bool { return Validation::password($password); }
function validate_id($id): bool { return Validation::id($id); }
function validate_quantity($qty): bool { return Validation::quantity($qty); }
function validate_price($price): bool { return Validation::price($price); }
function validate_stock($stock): bool { return Validation::stock($stock); }
function validate_in_list($value, array $allowed): bool { return Validation::inList($value, $allowed); }

// ══════════════════════════════════════════════
// RESPONSE HELPERS
// ══════════════════════════════════════════════
function json_response(bool $success, string $message = '', array $extra = []): void {
    Response::json($success, $message, $extra);
}

// ══════════════════════════════════════════════
// AUTH / MIDDLEWARE HELPERS
// ══════════════════════════════════════════════
function require_auth(): void {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        json_response(false, 'Unauthorized. Please log in.');
    }
}

function require_admin(): void {
    require_auth();
    if (($_SESSION['user_role'] ?? '') !== 'admin') {
        http_response_code(403);
        json_response(false, 'Forbidden: Admins only.');
    }
}

function require_csrf(): void {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!CSRF::verify($token)) {
        $sessionToken = $_SESSION['csrf_token'] ?? 'MISSING_IN_SESSION';
        error_log("[CSRF FAILURE] Submitted Token: '{$token}' | Session Token: '{$sessionToken}' | Session ID: " . session_id());
        http_response_code(403);
        $debugMsg = "Invalid or missing CSRF token. Submitted: '{$token}', Session: '{$sessionToken}'. Please refresh the page and try again.";
        json_response(false, $debugMsg);
    }
}

// ══════════════════════════════════════════════
// DATABASE HELPERS
// ══════════════════════════════════════════════
function db_fetch(PDO $pdo, string $sql, array $params = []): ?array {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function db_fetch_all(PDO $pdo, string $sql, array $params = []): array {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function db_execute(PDO $pdo, string $sql, array $params = []): int {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}
