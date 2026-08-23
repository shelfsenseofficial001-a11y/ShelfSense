<?php
namespace App\Core;

class Response
{
    public static function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }

    public static function success($data = [], $message = 'Success', $statusCode = 200)
    {
        self::json([
            'success' => true,
            'data' => $data,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ], $statusCode);
    }

    public static function error($message = 'Error', $statusCode = 400, $errors = [])
    {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        self::json($response, $statusCode);
    }

    public static function unauthorized($message = 'Unauthorized')
    {
        self::error($message, 401);
    }

    public static function forbidden($message = 'Forbidden')
    {
        self::error($message, 403);
    }

    public static function notFound($message = 'Not Found')
    {
        self::error($message, 404);
    }

    public static function validationError($errors)
    {
        self::error('Validation failed', 422, $errors);
    }

    public static function redirect($url, $message = null, $type = 'info')
    {
        if ($message) {
            $_SESSION['flash_message'] = $message;
            $_SESSION['flash_type'] = $type;
        }

        if (!headers_sent()) {
            header("Location: {$url}");
            exit;
        }

        echo "<script>window.location.href='{$url}';</script>";
        exit;
    }

    public static function view($view, $data = [])
    {
        extract($data);
        $viewPath = __DIR__ . '/../../views/pages/' . $view . '.php';

        if (!file_exists($viewPath)) {
            throw new \Exception("View not found: {$view}");
        }

        require_once $viewPath;
        exit;
    }
}