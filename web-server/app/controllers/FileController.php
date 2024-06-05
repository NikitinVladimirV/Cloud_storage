<?php

namespace app\controllers;

use app\core\AbstractHttpController;
use app\core\TraitCreatePath;
use app\models\DirectoryModel;
use app\models\FileModel;

/**
 * @noinspection PhpUnused
 */
class FileController extends AbstractHttpController
{
    private DirectoryModel $directoryModel;
    private FileModel $fileModel;

    public function __construct()
    {
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
        $user = $_SESSION['user'] ?? null;

        if (!$user) {
            http_response_code(403);
            $result = [
                'status' => false,
                'message' => 'Access denied!',
            ];

            return json_encode($result);
        }

        if (isset($user['role']) && $user['role'] === 'admin') {
            $fileList = $this->fileModel->get(['id' => $id]);
        } else {
            $fileList = $this->fileModel->get(['id' => $id, 'userId' => $user['id']]);
        }

        if (!$fileList) {
            http_response_code(404);
            $result = [
                "status" => false,
                "message" => "File ID=" . $id . " not found or you have not access!",
            ];

            return json_encode($result);
        }

        http_response_code(200);
        $result = [
            "status" => true,
            "message" => "Request completed!",
            "files" => $fileList,
        ];

        return json_encode($result);
    }

    /**
     * @param array|null $data
     * @return string
     */
    public function post(array $data = null): string
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

        $data = $_POST;

        if (!isset($data['parent']) || $data['parent'] === '') {
            http_response_code(400);
            $result = [
                "status" => false,
                "message" => 'Directory is undefined!',
                "user" => $user,
                "file" => $_FILES,
            ];

            return json_encode($result);
        }

        if (!$this->directoryModel->get(['id' => $data['parent']]) || $this->directoryModel->get(['id' => $data['parent']])['user'] !== $user['id'] && $user['role'] !== 'admin') {
            http_response_code(400);
            $result = [
                "status" => false,
                "message" => 'Directory ID=' . $data['parent'] . ' not found or you have not access!',
                "session" => $_SESSION,
            ];

            return json_encode($result);
        }

        if (!isset($_FILES['file'])) {
            http_response_code(400);
            $result = [
                "status" => false,
                "message" => 'File data is undefined!',
                "user" => $user,
                "file" => $_FILES,
            ];

            return json_encode($result);
        }

        if ($_FILES['file']['error'] !== 0) {
            http_response_code(400);
            $result = [
                "status" => false,
                "message" => 'File loading error № ' . $_FILES['file']['error'] . '!',
                "user" => $user,
                "error" => $_FILES['file']['error'],
            ];

            return json_encode($result);
        }

        $fileType = strstr($_FILES['file']['type'], '/', true);

        if ($fileType !== 'image') {
            http_response_code(400);
            $result = [
                "status" => false,
                "message" => 'File type ' . strtoupper($fileType) . ' is not supported!',
                "user" => $user,
                "file" => $_FILES['file'],
            ];

            return json_encode($result);
        }

        if ($_FILES['file']['size'] > 2000000000) {
            http_response_code(400);
            $result = [
                "status" => false,
                "message" => 'File size ' . strtoupper($_FILES['file']['name']) . ' is more than 2Gb!',
                "user" => $user,
                "file" => $_FILES['file'],
            ];

            return json_encode($result);
        }

        $fileName = $_FILES['file']['name'];
        $fileExtension = trim(strstr($_FILES['file']['name'], '.'), '.');
        $fileUniqName = strstr($fileName, '.', true) . time() . '.' . $fileExtension;
        $fileData = addslashes(file_get_contents($_FILES['file']['tmp_name']));
        $fileSize = $_FILES['file']['size'];
        $pathToDirectory = $this->createPath($data['parent']);

        if (file_exists($pathToDirectory . $fileName)) {
            http_response_code(400);
            $result = [
                "status" => false,
                "message" => 'File ' . strtoupper($fileName) . ' already exists!',
                "user" => $user,
                "file" => $_FILES['file'],
            ];

            return json_encode($result);
        }

        if (!move_uploaded_file($_FILES['file']['tmp_name'], $pathToDirectory . $fileName)) {
            http_response_code(400);
            $result = [
                "status" => false,
                "message" => 'File ' . strtoupper($fileName) . ' not uploaded!',
                "user" => $user,
                "file" => $_FILES['file'],
            ];

            return json_encode($result);
        }

        $this->fileModel->post([
            'user' => $user['id'],
            'name' => $fileName,
            'uniq_name' => $fileUniqName,
            'data' => $fileData,
            'size' => $fileSize,
            'parent' => $data['parent'],
        ]);

        http_response_code(200);
        $result = [
            "status" => true,
            "message" => 'File was loaded!',
            "user" => $user,
            "file" => $_FILES['file'],
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

        if (!$user) {
            http_response_code(403);
            $result = [
                'status' => false,
                'message' => 'Access denied!',
            ];

            return json_encode($result);
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['id']) || !isset($data['name']) && !isset($data['parent'])) {
            http_response_code(400);
            $result = [
                "status" => false,
                "message" => "Data is undefined!",
                "data" => $data,
            ];

            return json_encode($result);
        }

        $fileWithCurrentId = $this->fileModel->get(['id' => $data['id']]);

        if (!$fileWithCurrentId || $fileWithCurrentId['user'] !== $user['id'] && $user['role'] !== 'admin') {
            http_response_code(400);
            $result = [
                "status" => false,
                "message" => 'File ID=' . $data['id'] . ' not found in database or you have not access!',
                "session" => $_SESSION,
            ];

            return json_encode($result);
        }

        $dataDirectoryToRemove = $this->directoryModel->get(['id' => $data['parent'] ?? $fileWithCurrentId['parent']]);

        if (!$dataDirectoryToRemove || $dataDirectoryToRemove['user'] !== $user['id'] && $user['role'] !== 'admin') {
            http_response_code(400);
            $result = [
                "status" => false,
                "message" => 'Directory ID=' . $data['parent'] . ' not found in database or you have not access!',
                "session" => $_SESSION,
            ];

            return json_encode($result);
        }

        $pathToCurrentDirectory = $this->createPath($fileWithCurrentId['parent']);
        $pathToMove = $this->createPath($dataDirectoryToRemove['id']);

        $fileNewName = $data['name'] ?? $fileWithCurrentId['name'];
        $pathToFile = $pathToMove . $fileNewName;

        if (!file_exists($pathToCurrentDirectory . $fileWithCurrentId['name'])) {
            http_response_code(400);
            $result = [
                "status" => false,
                "message" => "File " . strtoupper($pathToCurrentDirectory . $fileWithCurrentId['name']) . " not found on server!",
                "data" => $data,
            ];

            return json_encode($result);
        }

        $dataFiles = $this->fileModel->get(['parent' => $dataDirectoryToRemove['id']]);
        $fileList = [];
        foreach ($dataFiles as $file) {
            $fileList[] = $file['name'];
        }

        if (in_array($fileNewName, $fileList) || file_exists($pathToMove . $fileNewName)) {
            http_response_code(400);
            $result = [
                "status" => false,
                "message" => 'File ' . strtoupper($fileNewName) . ' already exist!',
                "data" => $data,
            ];

            return json_encode($result);
        }

        if (!rename($pathToCurrentDirectory . $fileWithCurrentId['name'], $pathToFile)) {
            http_response_code(400);
            $result = [
                "status" => false,
                "message" => "Saving file ID=" . $data['id'] . " on the server failed!",
                "data" => $data,
            ];

            return json_encode($result);
        }

        $this->fileModel->put(
            [
                'id' => $data['id'],
                'fileName' => $data['name'] ?? $fileWithCurrentId['name'],
                'parent' => $data['parent'] ?? $fileWithCurrentId['parent'],
            ]
        );

        http_response_code(200);
        $result = [
            "status" => true,
            "message" => "File ID=" . strtoupper($data['id']) . " was removed!",
            "data" => $data,
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
                "message" => "ID is undefined!",
                "id" => $id,
            ];

            return json_encode($result);
        }

        $fileWithCurrentId = $this->fileModel->get(['id' => $id]);

        if (!$fileWithCurrentId || $fileWithCurrentId['user'] !== $user['id'] && $user['role'] !== 'admin') {
            http_response_code(400);
            $result = [
                "status" => false,
                "message" => "File ID " . $id . " is not found in database or you have not access!",
                "id" => $id,
            ];

            return json_encode($result);
        }

        $pathToDirectory = $this->createPath($fileWithCurrentId['parent']);

        if (!file_exists($pathToDirectory . $fileWithCurrentId['name'])) {
            http_response_code(400);
            $result = [
                "status" => false,
                "message" => "File " . strtoupper($pathToDirectory . $fileWithCurrentId['name']) . " is not found on server!",
                "id" => $id,
            ];

            return json_encode($result);
        }

        if (!unlink($pathToDirectory . $fileWithCurrentId['name'])) {
            http_response_code(400);
            $result = [
                "status" => false,
                "message" => "Deleting file ID=" . $id . " on the server failed!",
                "id" => $id,
            ];

            return json_encode($result);
        }

        $this->fileModel->delete($id);

        http_response_code(200);
        $result = [
            "status" => true,
            "message" => "File ID " . $id . " is deleted!",
            "id" => $id,
        ];

        return json_encode($result);
    }
}