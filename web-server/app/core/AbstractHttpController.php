<?php

namespace app\core;

abstract class AbstractHttpController
{
    /**
     * @param int|null $id
     * @return string
     */
    abstract public function get(int $id = null): string;

    /**
     * @param array|null $data
     * @return string
     */
    abstract public function post(array $data = null): string;

    /**
     * @param array|null $data
     * @return string
     */
    abstract public function put(array $data = null): string;

    /**
     * @param int|null $id
     * @return string
     */
    abstract public function delete(int $id = null): string;
}
