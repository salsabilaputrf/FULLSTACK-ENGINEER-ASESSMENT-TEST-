<?php
// Helper untuk mengirim response JSON 

namespace App\Helpers;

class Response
{
    /// Kirim response sukses 
    public static function success($data = null, string $message = 'Berhasil', int $code = 200): void
    {
        self::send([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    // Kirim response error
    public static function error(string $message, int $code = 400, $errors = null): void
    {
        $body = [
            'success' => false,
            'message' => $message,
        ];

        // Kalau ada detail error tambahan, ikutkan juga
        if ($errors !== null) {
            $body['errors'] = $errors;
        }

        self::send($body, $code);
    }

    private static function send(array $body, int $code): void
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($code);

        echo json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit; 
    }
}
