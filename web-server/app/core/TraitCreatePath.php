<?php

namespace app\core;

trait TraitCreatePath
{
    /**
     * @param int $directoryParentId
     * @return string
     */
    private function createPath(int $directoryParentId): string
    {
        $pathToDirectory = '';

        do {
            if (!$parentDirectory = $this->directoryModel->get(['id' => $directoryParentId])) {
                break;
            }

            $pathToDirectory  = $parentDirectory['name'] . '/' . $pathToDirectory;
            $directoryParentId = $parentDirectory['parent'];
        } while ($directoryParentId !== '0');

        return $pathToDirectory;
    }
}