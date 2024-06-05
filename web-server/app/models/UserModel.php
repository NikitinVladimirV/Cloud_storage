<?php

namespace app\models;

use app\core\DbConnect;
use PDO;
use PDOException;

class UserModel
{
    private DbConnect $pdo;
    private PDO $connection;

    public function __construct()
    {
        $this->pdo = new DbConnect();

        try {
            $this->connection = $this->pdo->pdo;
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        if ($this->pdo->getTableStatus('users') !== 'OK') {
            var_dump('ErrorUser');
            $sql = "CREATE TABLE users(
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(255) NOT NULL DEFAULT 'user', 
                date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )";
            $this->pdo->createTable($sql);

            $this->pdo->createAdmin();
        }
    }

    /**
     * @param array $data
     * @return array|null
     */
    public function getUsers(array $data): ?array
    {
        if (isset($data['password']) && isset($data['email'])) {
            $statement = $this->connection->prepare('SELECT * FROM users WHERE email = :email AND password = :password');
            $statement->execute(['email' => $data['email'], 'password' => md5($data['password'])]);
        } elseif (isset($data['email'])) {
            $statement = $this->connection->prepare("SELECT * FROM users WHERE email = :email");
            $statement->execute(['email' => $data['email']]);
        } elseif (isset($data['id'])) {
            $statement = $this->connection->prepare("SELECT * FROM users WHERE id = :id");
            $statement->execute(['id' => $data['id']]);
        } else {
            $statement = $this->connection->query('SELECT * FROM users');
            $users = $statement->fetchAll(PDO::FETCH_ASSOC);

            return $users === false ? null : $users;
        }
        $users = $statement->fetch(PDO::FETCH_ASSOC);

        return $users === false ? null : $users;
    }

    /**
     * @param array $data
     * @return bool
     */
    public function postUser(array $data): bool
    {
        $statement = $this->connection->prepare("INSERT INTO users (email, password, role) VALUES (:email, :password, :role)");

        if(isset($data['role'])) {
            return $statement->execute(['password' => md5($data['password']), 'email' => $data['email'], 'role' => $data['role']]);
        }

        return $statement->execute(['password' => md5($data['password']), 'email' => $data['email'], 'role' => 'user']);
    }

    /**
     * @param array $data
     * @param array $currentData
     * @return void
     */
    public function putUser(array $data, array $currentData): void
    {
        $statement = $this->connection->prepare("UPDATE users SET id = :id, role = :role, email = :email, password = :password WHERE id = :id");
        $statement->execute([
            'id' => $data['id'],
            'role' => $data['role'] ?? $currentData['role'],
            'email' => $data['email'] ?? $currentData['email'],
            'password' => isset($data['password']) ? md5($data['password']) : $currentData['password'],
        ]);
    }

    /**
     * @param int $id
     * @return void
     */
    public function deleteUser(int $id): void
    {
        $statement = $this->connection->prepare("DELETE FROM users WHERE id = :id");
        $statement->execute(['id' => $id]);
    }
}