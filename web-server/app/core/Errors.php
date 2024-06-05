<?php

namespace app\core;

trait Errors
{
    private array $errors  = [
        '400' => 'Bad Request!',
        '401' => 'Unauthorized!',
        '404' => 'Not Found!',
        '405' => 'Method Not Allowed',
        '415' => 'Unsupported Media Type',
        '',
        '501' => 'Not Implemented'
    ];

    public function createErrorMessage(string $error): array
    {


        return [];
    }

    public function userGet($users, $id)
    {

        http_response_code(401);
        $result = [
            'status' => false,
            'message' => 'Access denied!',
        ];

        http_response_code(404);
        $result = [
            "status" => false,
            "message" => "User ID=" . $id . " not found!",
        ];

        http_response_code(200);
        $result = [
            "status" => true,
            "message" => "Request completed!",
            'users' => $users,
        ];
    }

    public function userPost($data, $newUser)
    {
        http_response_code(400);
        $result = [
            "status" => false,
            "message" => 'Data is undefined!',
        ];

        http_response_code(400);
        $result = [
            'status' => false,
            'message' => 'User ' . strtoupper($data['email']) . ' is already registered!',
        ];

        http_response_code(400);
        $result = [
            'status' => false,
            'message' => 'User ' . strtoupper($data['email']) . ' was not create!',
        ];

        http_response_code(201);
        $result = [
            'status' => true,
            'message' => "User " . strtoupper($newUser['email']) . ' ID=' . $newUser['id'] . " is registered!",
            'user' => json_decode($this->get($newUser['id']), true)['users'],
        ];
    }

    public function userPut($data, $newUser)
    {
        http_response_code(401);
        $result = [
            'status' => false,
            'message' => 'Access denied!',
        ];

        http_response_code(400);
        $result = [
            "status" => false,
            "message" => 'ID is undefined!',
        ];

        http_response_code(400);
        $result = [
            "status" => false,
            "message" => 'Data is undefined or email already exist!',
        ];

        http_response_code(404);
        $result = [
            "status" => false,
            "message" => 'User ID=' . $data['id'] . ' is not found or you have not access!',
        ];

        http_response_code(200);
        $result = [
            "status" => true,
            "message" => 'User ID=' . $data['id'] . ' is updated!',
            "user" => json_decode($this->get($data['id']), true)['users'],
        ];
    }

    public function userDelete($id)
    {
        http_response_code(401);
        $result = [
            'status' => false,
            'message' => 'Access denied!',
        ];

        http_response_code(400);
        $result = [
            "status" => false,
            "message" => 'ID is undefined!',
        ];

        http_response_code(404);
        $result = [
            "status" => false,
            "message" => 'ID=' . $id . ' is not found or you have not access!',
        ];

        http_response_code(200);
        $result = [
            "status" => true,
            "message" => 'User ID=' . $id . ' is deleted!',
        ];
    }

    public function userLogin()
    {
        http_response_code(400);
        $result = [
            'status' => false,
            'message' => 'Data is undefined!',
        ];

        http_response_code(400);
        $result = [
            'status' => false,
            'message' => 'Invalid username or password!',
        ];

        http_response_code(200);
        $result = [
            'status' => true,
            'message' => 'User login!',
            'session' => $_SESSION['user'],
        ];
    }

    public function userLogout()
    {
        http_response_code(400);
        $result = [
            'status' => false,
            'message' => 'You are not logged!',
        ];

        http_response_code(200);
        $result = [
            "status" => true,
            "message" => 'You are logout!',
            "session_name" => session_name(),
            "session" => session_id(),
        ];
    }

    public function userReset($sent, $userWithCurrentId)
    {
        http_response_code(400);
        $result = [
            "status" => false,
            "message" => 'Data is undefined!',
        ];

        http_response_code(404);
        $result = [
            "status" => false,
            "message" => 'Email not found!',
        ];

        http_response_code(200);
        $result = [
            "status" => $sent,
            "message" => 'Password ID=' . $userWithCurrentId['id'] . ' reset!',
            "link" => 'link.ru/link',
        ];
    }
}
