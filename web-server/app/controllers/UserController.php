<?php

namespace app\controllers;

use app\core\AbstractHttpController;
use app\models\DirectoryModel;
use app\models\UserModel;

class UserController extends AbstractHttpController
{
    private UserModel $userModel;
    private DirectoryModel $directoryModel;
    private DirectoryController $directoryController;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->directoryModel = new DirectoryModel();
        $this->directoryController = new DirectoryController();

        if (!is_dir('files')) {
            mkdir('files');
        }

        if (!$this->directoryModel->get(['id' => '1'])) {
            $this->directoryModel->post([
                'id' => 1,
                'name' => 'files',
                'user' => 0,
                'parent' => 0,
                'status' => 'created',
            ]);
        }
    }

    /**
     * @param int|null $id
     * @return string
     */
    public function get(int $id = null): string
    {
        $user = $_SESSION['user'] ?? null;

        if(!$user) {
            http_response_code(403);
            $result = [
                'status' => false,
                'message' => 'Access denied!',
            ];

            return json_encode($result);
        }

        if (!$users = $this->userModel->getUsers(['id' => $id])) {
            http_response_code(404);
            $result = [
                "status" => false,
                "message" => "User ID=" . $id . " not found!",
            ];

            return json_encode($result);
        }

        http_response_code(200);
        $result = [
            "status" => true,
            "message" => "Request completed!",
            'users' => $users,
        ];

        return json_encode($result);
    }

    /**
     * @param array|null $data
     * @return string
     */
    public function post(array $data = null): string
    {
        $data = $data ?? $_POST;

        if (!isset($data['email']) || !isset($data['password'])) {
            http_response_code(400);
            $result = [
                "status" => false,
                "message" => 'Data is undefined!',
            ];

            return json_encode($result);
        }

        if ($this->userModel->getUsers(['email' => $data['email']])) {
            http_response_code(400);
            $result = [
                'status' => false,
                'message' => 'User ' . strtoupper($data['email']) . ' is already registered!',
            ];

            return json_encode($result);
        }

        if (!$this->userModel->postUser($data)) {
            http_response_code(400);
            $result = [
                'status' => false,
                'message' => 'User ' . strtoupper($data['email']) . ' was not create!',
            ];

            return json_encode($result);
        }

        $this->login(['email' => $data['email'], 'password' => $data['password']]);

        $newUser = $this->userModel->getUsers(['email' => $data['email']]);
        $userDirectory = [
            'name' => strstr($data['email'], '@', true),
            'user' => $newUser['id'],
            'parent' => 1,
        ];
        $this->directoryController->post($userDirectory);

        http_response_code(201);
        $result = [
            'status' => true,
            'message' => "User " . strtoupper($newUser['email']) . ' ID=' . $newUser['id'] . " is registered!",
            'user' => json_decode($this->get($newUser['id']), true)['users'],
        ];

        return json_encode($result);
    }

    /**
     * @param array|null $data
     * @return string
     */
    public function put(array $data = null): string
    {
        $user = $_SESSION['user'] ?? null;

        $data = json_decode(file_get_contents('php://input'), true);

        if (!$user) {
            http_response_code(403);
            $result = [
                'status' => false,
                'message' => 'Access denied!',
            ];

            return json_encode($result);
        }

        if (!isset($data['id'])) {
            http_response_code(400);
            $result = [
                "status" => false,
                "message" => 'ID is undefined!',
            ];

            return json_encode($result);
        }

        if ((isset($data['email']) && $this->userModel->getUsers(['email' => $data['email']])) || (!isset($data['password']) && !isset($data['email']))) {
            http_response_code(400);
            $result = [
                "status" => false,
                "message" => 'Data is undefined or email already exist!',
            ];

            return json_encode($result);
        }

        $userWithCurrentId = $this->userModel->getUsers(['id' => $data['id']]);

        if (!$userWithCurrentId || ($userWithCurrentId['id'] !== $user['id'] && $user['role'] !== 'admin')) {
            http_response_code(404);
            $result = [
                "status" => false,
                "message" => 'User ID=' . $data['id'] . ' is not found or you have not access!',
            ];

            return json_encode($result);
        }

        $directoryUserRoot = $this->directoryModel->get(['userId' => $userWithCurrentId['id'], 'parent' => '1']);
        $directoryNewName = isset($data['email']) ? strstr($data['email'], '@', true) : null;
        $this->directoryController->put(['id' => $directoryUserRoot[0]['id'], 'name' => $directoryNewName, 'parent' => '1']);
        $this->userModel->putUser($data, $userWithCurrentId);

        http_response_code(200);
        $result = [
            "status" => true,
            "message" => 'User ID=' . $data['id'] . ' is updated!',
            "user" => json_decode($this->get($data['id']), true)['users'],
        ];

        return json_encode($result);
    }

    /**
     * @param int|null $id
     * @return string
     */
    public function delete(int $id = null): string
    {
        $user = $_SESSION['user'] ?? null;

        if (!$user) {
            http_response_code(403);
            $result = [
                'status' => false,
                'message' => 'Access denied!',
            ];

            return json_encode($result);
        }

        if (!$id) {
            http_response_code(400);
            $result = [
                "status" => false,
                "message" => 'ID is undefined!',
            ];

            return json_encode($result);
        }

        $userDeletable = $this->userModel->getUsers(['id' => $id]);

        if (!$userDeletable || ($userDeletable['id'] !== $user['id'] && $user['role'] !== 'admin')) {
            http_response_code(404);
            $result = [
                "status" => false,
                "message" => 'ID=' . $id . ' is not found or you have not access!',
            ];

            return json_encode($result);
        }

        $directoryUserRoot = $this->directoryModel->get(['userId' => $userDeletable['id'], 'parent' => '1']);
        $this->userModel->deleteUser($id);
        $this->directoryController->delete($directoryUserRoot[0]['id']);

        http_response_code(200);
        $result = [
            "status" => true,
            "message" => 'User ID=' . $id . ' is deleted!',
        ];

        return json_encode($result);
    }

    /**
     * @param array|null $data
     * @return string
     */
    public function login(array $data = null): string
    {
        $data = $data ?? $_GET;

        if (!isset($data['email']) || !isset($data['password'])) {
            http_response_code(400);
            $result = [
                'status' => false,
                'message' => 'Data is undefined!',
            ];

            return json_encode($result);
        }

        if (!$userWithCurrentId = $this->userModel->getUsers(['email' => $data['email'], 'password' => $data['password']])) {
            http_response_code(400);
            $result = [
                'status' => false,
                'message' => 'Invalid username or password!',
            ];

            return json_encode($result);
        }

        setcookie('sessionId', session_id());

        $_SESSION['user'] = [
            'id' => $userWithCurrentId['id'],
            'email' => $userWithCurrentId['email'],
            'password' => $userWithCurrentId['password'],
            'message' => 'User ID=' . $userWithCurrentId['id'] . ' authorized!',
            'session_name' => session_name(),
            'session_id' => session_id(),
            'role' => $userWithCurrentId['role']
        ];

        http_response_code(200);
        $result = [
            'status' => true,
            'message' => 'User authorized!',
            'session' => $_SESSION['user'],
        ];

        return json_encode($result);
    }

    /**
     * @noinspection PhpUnused
     * @return string
     */
    public function logout(): string
    {
        $user = $_SESSION['user'] ?? null;

        if (!$user) {
            http_response_code(400);
            $result = [
                'status' => false,
                'message' => 'You are not logged!',
            ];

            return json_encode($result);
        }

        unset($user);
        session_destroy();

        http_response_code(200);
        $result = [
            "status" => true,
            "message" => 'You are logout!',
            "session_name" => session_name(),
            "session" => session_id(),
        ];

        return json_encode($result);
    }

    /**
     * @noinspection PhpUnused
     * @return string
     */
    public function reset(): string
    {
        $data = $_GET;

        if (!isset($data['email'])) {
            http_response_code(400);
            $result = [
                "status" => false,
                "message" => 'Data is undefined!',
            ];

            return json_encode($result);
        }

        if (!$userWithCurrentId = $this->userModel->getUsers(['email' => $data['email']])) {
            http_response_code(403);
            $result = [
                "status" => false,
                "message" => 'Invalid email!',
            ];

            return json_encode($result);
        }

        $sent = mail(
            $data['email'],
            'Link on restore password',
            'Remove to link for reset password: link.ru/link'
        );

        http_response_code(200);
        $result = [
            "status" => $sent,
            "message" => 'Password ID=' . $userWithCurrentId['id'] . ' reset!',
            "link" => 'link.ru/link',
        ];

        return json_encode($result);
    }
}
