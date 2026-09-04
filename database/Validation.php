<?php
class Validation {
    public static function email(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function name(string $name): bool {
        $len = mb_strlen($name);
        if ($len < 2 || $len > 100) return false;
        return (bool) preg_match("/^[\p{L}\s'\-]+$/u", $name);
    }

    public static function phone(string $phone): bool {
        $digits = preg_replace('/[\s\-\(\)\+]/', '', $phone);
        $len = strlen($digits);
        if ($len < 10 || $len > 15) return false;
        return (bool) preg_match('/^[\+\d\s\-\(\)]+$/', $phone);
    }

    public static function password(string $password): bool {
        return strlen($password) >= 6;
    }

    public static function id($id): bool {
        return is_numeric($id) && (int)$id > 0;
    }

    public static function quantity($qty): bool {
        return is_numeric($qty) && (int)$qty >= 1;
    }

    public static function price($price): bool {
        return is_numeric($price) && (float)$price > 0;
    }

    public static function stock($stock): bool {
        return is_numeric($stock) && (int)$stock >= 0;
    }

    public static function inList($value, array $allowed): bool {
        return in_array($value, $allowed, true);
    }
}
