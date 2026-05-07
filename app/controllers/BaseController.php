<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\SessionHelper;
use Throwable;

abstract class BaseController extends Controller
{
    protected $session;

    public function __construct($request = [], $view = null, $session = null)
    {
        parent::__construct($request, $view);
        $this->session = $session ?? new SessionHelper();
        $this->session->start();
    }

    protected function respond($view, $data = [], $layout = null)
    {
        if ($this->wants_json() || !$this->view->exists($view)) {
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
        return $this->json(['success' => true, 'data' => $data], $status);
    }

    protected function error($message, $status = 400, $errors = [])
    {
        return $this->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
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
            'csrf_token' => $this->session->csrf_token(),
        ];
    }

    protected function handle_exception(Throwable $exception)
    {
        return $this->error($exception->getMessage(), 500);
    }
}
