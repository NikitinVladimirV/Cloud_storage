<?php

namespace app\models;

use app\core\DbConnect;
use PDO;
use PDOException;

class DirectoryModel
{
    private PDO $connection;

    public function __construct()
    {
        $pdo = new DbConnect();

        try {
            $this->connection = $pdo->pdo;
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        if ($pdo->getTableStatus('directories') !== 'OK') {
            var_dump('ErrorDir');
            $sql = "CREATE TABLE directories(
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                user INT NOT NULL,
                parent INT NOT NULL, 
                status VARCHAR(255) NOT NULL DEFAULT 'created',
                date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )";
            $pdo->createTable($sql);
        }
    }

    /**
     * @param array $data
     * @return array|null
     */
    public function get(array $data): ?array
    {
        if (isset($data['id'])) {
            $requestDirectoryById = $this->connection->prepare("SELECT * FROM directories WHERE id = :id");
            $requestDirectoryById->execute(['id' => $data['id']]);

            $directories = $requestDirectoryById->fetch(PDO::FETCH_ASSOC);

            return $directories === false ? null : $directories;
        } elseif (isset($data['userId']) && isset($data['parent'])) {
            $requestDirectoryById = $this->connection->prepare("SELECT * FROM directories WHERE user = :user AND parent = :parent");
            $requestDirectoryById->execute(['user' => $data['userId'], 'parent' => $data['parent']]);
        } elseif (isset($data['userId'])) {
            $requestDirectoryById = $this->connection->prepare("SELECT * FROM directories WHERE user = :user");
            $requestDirectoryById->execute(['user' => $data['userId']]);
        } else {
            $requestDirectoryById = $this->connection->prepare("SELECT name FROM directories WHERE parent = :parent");
            $requestDirectoryById->execute(['parent' => $data['parent']]);
        }
        $directories = $requestDirectoryById->fetchAll(PDO::FETCH_ASSOC);

        return $directories === false ? null : $directories;
    }

    /**
     * @param array $data
     * @return bool
     */
    public function post(array $data): bool
    {
        if (isset($data['id'])) {
            $requestCreateDirectory = $this->connection->prepare(
                "INSERT INTO directories (id, name, user, parent, status) VALUES (:id, :name, :user, :parent, :status)"
            );
        } else {
            $requestCreateDirectory = $this->connection->prepare(
                "INSERT INTO directories (name, user, parent, status) VALUES (:name, :user, :parent, :status)"
            );
        }

        return $requestCreateDirectory->execute($data);
    }

    /**
     * @param array $data
     * @return void
     */
    public function put(array $data): void
    {
        $statement = $this->connection->prepare("UPDATE directories SET name = :name, parent = :parent WHERE id = :id");
        $statement->execute($data);
    }

    /**
     * @param int $id
     * @return void
     */
    public function delete(int $id): void
    {
        $statement = $this->connection->prepare("DELETE FROM directories WHERE id = :id");
        $statement->execute(['id' => $id]);
    }
}