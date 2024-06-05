<?php

namespace app\core;

use app\controllers\UserController;
use PDO as PDO;

class DbConnect
{
    public PDO $pdo;
    private array $dsn;
    private array $adminData;

    public function __construct()
    {
        $this->dsn = (include dirname(__FILE__, 2) . '/config/config.php')['dsn'];
        $this->adminData = (include dirname(__FILE__, 2) . '/config/config.php')['admin'];

        $this->pdo = new PDO(
            'mysql:host=' . $this->dsn['host'] . ';dbname=' . $this->dsn['dbname'] . ';port=' . $this->dsn['port'] . ';charset=' . $this->dsn['charset'],
            $this->dsn['user'],
            $this->dsn['password']
        );
    }

    /**
     * @param string $table
     * @return string
     */
    public function getTableStatus(string $table): string
    {
        $statement = $this->pdo->query("CHECK TABLE $table");

        return $statement->fetch(PDO::FETCH_ASSOC)['Msg_text'];
    }

    /**
     * @param string $sql
     * @return void
     */
    public function createTable(string $sql): void
    {
        $this->pdo->query($sql);
    }

    /**
     * @return void
     */
    public function createAdmin(): void
    {
        $UserModel = new UserController();
        $UserModel->post(['email' => $this->adminData['email'], 'password' => $this->adminData['password'], 'role' => $this->adminData['role']]);
    }
}