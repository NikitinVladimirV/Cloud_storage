<?php

namespace app\controllers;

use app\core\AbstractHttpController;
use app\core\TraitCreatePath;
use app\models\DirectoryModel;
use app\models\FileModel;

class DirectoryController extends AbstractHttpController
{
    private string $commonFilesDirectory;
    private DirectoryModel $directoryModel;
    private FileModel $fileModel;
    private array $user;

    public function __construct()
    {
        $this->user = $_SESSION['user'] ?? null;
        $this->commonFilesDirectory = 'files';
        $this->directoryModel = new DirectoryModel();
        $this->fileModel = new FileModel();
    }

    use TraitCreatePath;

    /**
     * @param int|null $id
     * @return string
     */
    public function get(int $id = null): string
    {
        if (!$this->user) {
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
                'status' => false,
                'message' => 'Id undefined!',
            ];

            return json_encode($result);
        }
            if (!$this->directoryModel->get(['id' => $id]) || $this->directoryModel->get(['id' => $id])['user'] !== $this->user['id'] && $this->user['role'] !== 'admin') {
            http_response_code(400);
            $result = [
                'status' => false,
                'message' => 'Directory ID=' . $id . ' not found or you have not access!',
            ];

            return json_encode($result);
        }

        $pathToDirectory = $this->createPath($id);

        $resFromDirectoryName = $this->directoryModel->get(['parent' => $id]);
        $resFromFilesDirectoryName = $this->fileModel->get(['parent' => $id]);

        $filesListDatabase = [];
        foreach ($resFromDirectoryName as $name) {
            $filesListDatabase[] = $name['name'];
        }
        foreach ($resFromFilesDirectoryName as $name) {
            $filesListDatabase[] = $name['name'];
        }

        $filesListServer = array_slice(scandir($pathToDirectory), 2);

        sort($filesListServer);
        sort($filesListDatabase);

        if (json_encode($filesListServer) !== json_encode($filesListDatabase)) {
            http_response_code(400);
            $result = [
                'status' => false,
                'message' => 'File list from server does`t match database!',
                'directoryDataFromServer' => $filesListServer,
                'directoryDataFromDatabase' => $filesListDatabase,
            ];

            return json_encode($result);
        }

        http_response_code(200);
        $result = [
            'status' => true,
            'message' => 'Request executed!',
            'directoryDataFromServer' => $filesListServer,
            'directoryDataFromDatabase' => $filesListDatabase,
        ];

        return json_encode($result);
    }

    /**
     * @param array|null $data
     * @return string
     */
    public function post(array $data = null): string
    {
        if (!$this->user) {
            http_response_code(403);
            $result = [
                'status' => false,
                'message' => 'Access denied!',
            ];

            return json_encode($result);
        }

        $data = $data ?? $_POST;

        if (!is_dir($this->commonFilesDirectory)) {
            http_response_code(400);
            $result = [
                'status' => false,
                'message' => 'Common directory ' . strtoupper($this->commonFilesDirectory) . ' not exists!',
            ];

            return json_encode($result);
        }

        if (!isset($data['parent']) || !isset($data['name'])) {
            http_response_code(400);
            $result = [
                'status' => false,
                'message' => 'Data undefined!',
            ];

            return json_encode($result);
        }

        if (!$this->directoryModel->get(['id' => $data['parent']]) || ($this->directoryModel->get(['id' => $data['parent']])['user'] !== $this->user['id'] && $this->user['role'] !== 'admin' && ($this->directoryModel->get(['id' => $data['parent']])['user'] !== '0' || $this->directoryModel->get(['userId' => $this->user['id']])))) {
            http_response_code(400);
            $result = [
                'status' => false,
                'message' => 'Directory ID=' . $data['parent'] . ' not found or you have not access!',
            ];

            return json_encode($result);
        }

        $pathToDirectory = $this->createPath($data['parent']);

        if (file_exists($pathToDirectory . $data['name'])) {
            http_response_code(400);
            $result = [
                'status' => false,
                'message' => 'Directory ' . strtoupper($data['name']) . ' already exists!',
            ];

            return json_encode($result);
        }

        mkdir( $pathToDirectory . $data['name'], 0777, true);

        $this->directoryModel->post([
            'name' => $data['name'],
            'user' => $this->user['id'],
            'parent' => $data['parent'],
            'status' => 'created'
        ]);

        http_response_code(200);
        $result = [
            'status' => true,
            'message' => 'Directory ' . strtoupper($data['name']) . ' created!',
        ];

        return json_encode($result);
    }

    /**
     * @param array|null $data
     * @return string
     */
    public function put(array $data = null): string
    {
        if (!$this->user) {
            http_response_code(403);
            $result = [
                'status' => false,
                'message' => 'Access denied!',
            ];

            return json_encode($result);
        }

        $data = $data ?? json_decode(file_get_contents('php://input'), true);

        if (!isset($data['id']) || !isset($data['name']) && !isset($data['parent'])) {
            http_response_code(400);
            $result = [
                'status' => false,
                'message' => 'Data is undefined!',
            ];

            return json_encode($result);
        }

        $directoryDataCurrent = $this->directoryModel->get(['id' => $data['id']]);

         if (!$directoryDataCurrent || $directoryDataCurrent['user'] !== $this->user['id'] && $this->user['role'] !== 'admin') {
            http_response_code(400);
            $result = [
                'status' => false,
                'message' => 'Directory ID=' . $data['id'] . ' not found or you have not access!',
            ];

            return json_encode($result);
        }

        $directoryToRemove = $this->directoryModel->get(['id' => $data['parent'] ?? $directoryDataCurrent['parent']]);

        if (!$directoryToRemove || $directoryToRemove['user'] !== $this->user['id'] && $this->user['role'] !== 'admin' && ($directoryToRemove['user'] !== '0' || $directoryDataCurrent['parent'] !== '1')) {
            http_response_code(400);
            $result = [
                'status' => false,
                'message' => 'Directory to remove ID=' . $data['parent'] . ' not found or you have not access!',
            ];

            return json_encode($result);
        }

        $pathToDirectory = $this->createPath($directoryDataCurrent['parent']);

        if (!is_dir($pathToDirectory . $directoryDataCurrent['name'])) {
            http_response_code(400);
            $result = [
                'status' => false,
                'message' => 'Directory ' . strtoupper($pathToDirectory . $directoryDataCurrent['name']) . ' not found!',
            ];

            return json_encode($result);
        }

        $newPath = $this->createPath($data['parent'] ?? $directoryDataCurrent['parent']);
        $directoryNewName = $data['name'] ?? $directoryDataCurrent['name'];

        if (is_dir($newPath . $directoryNewName)) {
            http_response_code(400);
            $result = [
                'status' => false,
                'message' => 'Directory ' . strtoupper($newPath . $directoryNewName) . ' already exist!',
            ];

            return json_encode($result);
        }

        if (!rename($pathToDirectory . $directoryDataCurrent['name'], $newPath . $data['name'])) {
            http_response_code(400);
            $result = [
                'status' => false,
                'message' => 'Renaming ' . strtoupper($pathToDirectory . $directoryDataCurrent['name']) . ' to ' . strtoupper($newPath . $data['name']) . ' is failed!',
            ];

            return json_encode($result);
        }

        $this->directoryModel->put(['id' => $data['id'], 'name' => $data['name'] ?? $directoryDataCurrent['name'], 'parent' => $data['parent'] ?? $directoryDataCurrent['parent']]);

        http_response_code(200);
        $result = [
            'status' => true,
            'message' => 'Directory ' . strtoupper($directoryDataCurrent['name']) . ' rename to ' . strtoupper($data['name']) . '!',
        ];

        return json_encode($result);
    }

    /**
     * @param int|null $id
     * @return string
     */
    public function delete(int $id = null): string
    {
        if (!$this->user) {
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
                'status' => false,
                'message' => 'Id undefined!',
            ];

            return json_encode($result);
        }

        $directoryData = $this->directoryModel->get(['id' => $id]);

        if (!$directoryData || $directoryData['user'] !== $this->user['id'] && $this->user['role'] !== 'admin') {
            http_response_code(400);
            $result = [
                'status' => false,
                'message' => 'Directory ID=' . $id . ' not found or you have not access!',
            ];

            return json_encode($result);
        }

        $directoryDeleteName = $directoryData['name'];
        $pathToDirectory = $this->createPath($directoryData['parent']);

        if (!is_dir($pathToDirectory . $directoryDeleteName)) {
            http_response_code(400);
            $result = [
                'status' => false,
                'message' => 'Directory ' . $directoryDeleteName . ' is not exists!',
            ];

            return json_encode($result);
        }

        if (array_slice(scandir($pathToDirectory . $directoryDeleteName), 2) !== []) {
            http_response_code(400);
            $result = [
                'status' => false,
                'message' => 'Directory ' . strtoupper($directoryDeleteName) . ' is not empty!',
            ];

            return json_encode($result);
        }

        if (!rmdir($pathToDirectory . $directoryDeleteName)) {
            http_response_code(400);
            $result = [
                'status' => false,
                'message' => 'Directory ' . strtoupper($directoryDeleteName) . ' is not deleted!',
            ];

            return json_encode($result);
        }

        $this->directoryModel->delete($id);

        http_response_code(200);
        $result = [
            'status' => true,
            'message' => 'Directory ' . strtoupper($directoryDeleteName) . ' is deleted!',
        ];

        return json_encode($result);
    }
}