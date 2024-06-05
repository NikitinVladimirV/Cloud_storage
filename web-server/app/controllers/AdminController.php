<?php

namespace app\controllers;

use app\core\AbstractHttpController;

/**
 * @noinspection PhpUnused
 */
class AdminController extends AbstractHttpController
{
    private UserController $userController;

    public function __construct()
    {
        $this->userController = new UserController();
    }

    /**
     * @param int|null $id
     * @return string
     */
    public function get(int $id = null): string
    {
        $user = $_SESSION['user'] ?? null;

        if (!isset($user['role']) || $user['role'] !== 'admin') {
            http_response_code(403);
            $result = [
                'status' => false,
                'message' => 'Access denied!'
            ];

            return json_encode($result);
        }

        $result = json_decode($this->userController->get($id), true);

        if ($result['status']) {
            http_response_code(200);
        } else {
            http_response_code(400);
        }

        return json_encode($result);
    }

    public function post(array $data = null): string
    {
        $user = $_SESSION['user'] ?? null;

        if (!isset($user['role']) || $user['role'] !== 'admin') {
            http_response_code(403);
            $result = [
                'status' => false,
                'message' => 'Access denied!'
            ];

            return json_encode($result);
        }

        $result = json_decode($this->userController->post($_POST), true);

        if ($result['status']) {
            http_response_code(200);
        } else {
            http_response_code(400);
        }

        return json_encode($result);
    }

    /**
     * @param array|null $data
     * @return string
     */
    public function put(array $data = null): string
    {
        $user = $_SESSION['user'] ?? null;

        if (!isset($user['role']) || $user['role'] !== 'admin') {
            http_response_code(403);
            $result = [
                'status' => false,
                'message' => 'Access denied!',
            ];

            return json_encode($result);
        }

        $result = json_decode($this->userController->put(), true);

        if ($result['status']) {
            http_response_code(200);
        } else {
            http_response_code(400);
        }

        return json_encode($result);
    }

    /**
     * @param int|null $id
     * @return string
     */
    public function delete(int $id = null): string
    {
        $user = $_SESSION['user'] ?? null;

        if (!isset($user['role']) || $user['role'] !== 'admin') {
            http_response_code(403);
            $result = [
                'status' => false,
                'message' => 'Access denied!',
            ];

            return json_encode($result);
        }

        $result = json_decode($this->userController->delete($id), true);

        if ($result['status']) {
            http_response_code(200);
        } else {
            http_response_code(400);
        }

        return json_encode($result);
    }
}
