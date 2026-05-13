<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\SessionHelper;
use App\Logger\Logger;
use Throwable;

abstract class BaseController extends Controller
{
    protected SessionHelper $session;

    public function __construct($request = [], $view = null, $session = null)
    {
        parent::__construct($request, $view);
        $this->session = $session ?? new SessionHelper();
        $this->session->start();
    }

    protected function respond($view, $data = [], $layout = null)
    {
        if ($this->wants_json()) {
            return $this->json($data);
        }

        return $this->render($view, $data + $this->shared_view_data(), $layout);
    }

    protected function json($data = [], $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        return json_encode($this->sanitize($data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function success($data = [], $status = 200)
    {
        Logger::operation('Controller operation succeeded', [
            'controller' => static::class,
            'method' => $this->request['method'] ?? null,
            'path' => $this->request['path'] ?? null,
            'status' => $status,
            'user_id' => $this->session->get_user_id(),
            'username' => $this->session->get_username(),
        ]);

        if (!$this->wants_json()) {
            $this->session->flash('success', $this->success_message($data));
            return $this->redirect_back();
        }

        return $this->json(['success' => true, 'data' => $data], $status);
    }

    protected function error($message, $status = 400, $errors = [])
    {
        Logger::warning('Controller operation failed', [
            'controller' => static::class,
            'method' => $this->request['method'] ?? null,
            'path' => $this->request['path'] ?? null,
            'status' => $status,
            'message' => $message,
            'errors' => $errors,
            'user_id' => $this->session->get_user_id(),
            'username' => $this->session->get_username(),
        ]);

        if (!$this->wants_json()) {
            $this->session->flash('error', $this->format_error_message($message, $errors));
            return $this->redirect_back();
        }

        return $this->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    protected function ok($ok)
    {
        return $this->json(['success' => (bool) $ok], $ok ? 200 : 422);
    }

    protected function result($value, $key, $message, $success_status = 200)
    {
        return $value === null
            ? $this->error($message, 422)
            : $this->success([$key => $value], $success_status);
    }

    protected function guarded_response($roles, $callback)
    {
        if ($guard = $this->guarded($roles)) {
            return $guard;
        }

        return $callback();
    }

    protected function json_result($result)
    {
        return $this->json($result, $result['success'] ? 200 : 422);
    }

    protected function found_result($value, $key, $message = 'Not found.')
    {
        return $value === null ? $this->not_found($message) : $this->success([$key => $value]);
    }

    protected function guarded($roles = null)
    {
        if (!$this->session->is_authenticated()) {
            return $this->unauthenticated();
        }

        if ($roles !== null && !$this->session->has_role($roles)) {
            return $this->forbidden();
        }

        return null;
    }

    protected function unauthenticated()
    {
        if ($this->wants_json()) {
            return $this->error('Authentication required.', 401);
        }

        $this->session->flash('error', 'Please log in first.');
        return $this->redirect('/login');
    }

    protected function forbidden()
    {
        if ($this->wants_json() || !$this->view->exists('errors/403')) {
            return $this->error('Forbidden.', 403);
        }

        http_response_code(403);
        return $this->render('errors/403', $this->shared_view_data());
    }

    protected function not_found($message = 'Not found.')
    {
        if ($this->wants_json() || !$this->view->exists('errors/404')) {
            return $this->error($message, 404);
        }

        http_response_code(404);
        return $this->render('errors/404', $this->shared_view_data() + ['message' => $message]);
    }

    protected function payload()
    {
        return array_merge($this->input(), $this->body());
    }

    protected function int_param($key, $default = null)
    {
        $value = $this->input($key, $default);

        return is_numeric($value) ? (int) $value : $default;
    }

    protected function resolve_id($id, $key = 'id', $default = 0)
    {
        if ($id !== null && is_numeric($id)) {
            return (int) $id;
        }

        return $this->int_param($key, $this->payload()[$key] ?? $default);
    }

    protected function request_user_id()
    {
        return $this->session->get_user_id();
    }

    protected function shared_view_data()
    {
        return [
            'current_user' => $this->session->get_user(),
            'current_user_id' => $this->session->get_user_id(),
            'current_user_role' => $this->session->get_user_role(),
            'flash' => $this->session->flashes(),
        ];
    }

    protected function handle_exception(Throwable $exception)
    {
        Logger::exception($exception, [
            'controller' => static::class,
            'method' => $this->request['method'] ?? null,
            'path' => $this->request['path'] ?? null,
            'user_id' => $this->session->get_user_id(),
            'username' => $this->session->get_username(),
        ]);

        return $this->error('Internal Server Error', 500);
    }

    protected function redirect_back($fallback = '/dashboard')
    {
        return $this->redirect($this->safe_redirect_target($fallback));
    }

    private function safe_redirect_target($fallback)
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if ($referer === '') {
            return $fallback;
        }

        $parts = parse_url($referer);
        if (!is_array($parts)) {
            return $fallback;
        }

        $current = parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''));
        if (isset($parts['host'], $current['host']) && strcasecmp($parts['host'], $current['host']) !== 0) {
            return $fallback;
        }

        if (isset($parts['port']) || isset($current['port'])) {
            if (($parts['port'] ?? null) !== ($current['port'] ?? null)) {
                return $fallback;
            }
        }

        $path = $parts['path'] ?? $fallback;
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';

        return '/' . ltrim($path, '/') . $query;
    }

    private function success_message($data)
    {
        if (isset($data['deleted'])) {
            return 'Deleted successfully.';
        }

        return 'Operation completed successfully.';
    }

    private function format_error_message($message, $errors)
    {
        if (empty($errors) || !is_array($errors)) {
            return $message;
        }

        $details = [];
        foreach ($errors as $field => $error) {
            $details[] = is_string($field) ? $field . ': ' . $error : (string) $error;
        }

        return $message . ' ' . implode(' ', $details);
    }
}
