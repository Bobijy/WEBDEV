<?php
class Sanitizer {
    public static function string(string $value): string {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    public static function raw(string $value): string {
        return trim($value);
    }
}
