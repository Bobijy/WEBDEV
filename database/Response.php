<?php
class Response {
    public static function json(bool $success, string $message = '', array $extra = []): void {
        $payload = ['success' => $success];
        if ($message !== '') {
            $payload['message'] = $message;
        }
        echo json_encode(array_merge($payload, $extra));
        exit;
    }
}
