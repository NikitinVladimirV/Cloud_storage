<?php

namespace app\models;

use app\core\DbConnect;
use PDO;
use PDOException;

class FileModel
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

        if ($pdo->getTableStatus('files') !== 'OK') {
            var_dump('ErrorFile');
            $sql = "CREATE TABLE files(
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                uniq_name VARCHAR(255) NOT NULL,
                parent INT NOT NULL, 
                size INT NOT NULL,
                data BLOB NOT NULL,
                user INT NOT NULL,
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
        if (isset($data['id']) && isset($data['userId'])) {
            $requestFromFilesDirectoryNameById = $this->connection->prepare("SELECT id, user, name, uniq_name, size, parent FROM files WHERE id = :id AND user = :user");
            $requestFromFilesDirectoryNameById->execute(['id' => $data['id'], 'user' => $data['userId']]);
            $files = $requestFromFilesDirectoryNameById->fetch(PDO::FETCH_ASSOC);

            return $files === false ? null : $files;
        }
        if (isset($data['id'])) {
            $requestFromFilesDirectoryNameById = $this->connection->prepare("SELECT id, user, name, uniq_name, size, parent FROM files WHERE id = :id");
            $requestFromFilesDirectoryNameById->execute(['id' => $data['id']]);

            $files = $requestFromFilesDirectoryNameById->fetch(PDO::FETCH_ASSOC);

            return $files === false ? null : $files;
        } elseif (isset($data['userId']) && isset($data['parent'])) {
            $requestFromFilesDirectoryNameById = $this->connection->prepare("SELECT id, user, name, uniq_name, size, parent FROM files WHERE user = :user AND parent = :parent");
            $requestFromFilesDirectoryNameById->execute(['user' => $data['userId'], 'parent' => $data['parent']]);
        } elseif (isset($data['userId'])) {
            $requestFromFilesDirectoryNameById = $this->connection->prepare("SELECT id, user, name, uniq_name, size, parent FROM files WHERE user = :user");
            $requestFromFilesDirectoryNameById->execute(['user' => $data['userId']]);
        } elseif (isset($data['parent'])) {
            $requestFromFilesDirectoryNameById = $this->connection->prepare("SELECT id, user, name, uniq_name, size, parent FROM files WHERE parent = :parent");
            $requestFromFilesDirectoryNameById->execute(['parent' => $data['parent']]);
        } else {
            $requestFromFilesDirectoryNameById = $this->connection->prepare("SELECT id, user, name, uniq_name, size, parent FROM files");
            $requestFromFilesDirectoryNameById->execute(['id' => $data['id']]);
        }
        $files = $requestFromFilesDirectoryNameById->fetchAll(PDO::FETCH_ASSOC);

        return $files === false ? null : $files;
    }

    /**
     * @param array $data
     * @return void
     */
    public function post(array $data): void
    {
        $statement = $this->connection->prepare(
            "INSERT INTO files (user, name, uniq_name, data, size, parent) VALUES (:user, :name, :uniq_name, :data, :size, :parent)"
        );
        $statement->execute($data);
    }

    /**
     * @param array $data
     * @return void
     */
    public function put(array $data): void
    {
        $statement = $this->connection->prepare(
            "UPDATE files SET name = :fileName, parent = :parent WHERE id = :id"
        );
        $statement->execute($data);
    }

    /**
     * @param int $id
     * @return void
     */
    public function delete(int $id): void
    {
        $statement = $this->connection->prepare("DELETE FROM files WHERE id = :id");
        $statement->execute(['id' => $id]);
    }
}