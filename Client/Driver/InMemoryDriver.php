<?php

namespace Gos\Bundle\WebSocketBundle\Client\Driver;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class InMemoryDriver implements DriverInterface
{
    /**
     * @var array
     */
    protected $elements = [];

    /**
     * @return mixed
     */
    public function fetch(string $id)
    {
        if (!$this->contains($id)) {
            return false;
        }

        return $this->elements[$id];
    }

    public function contains(string $id): bool
    {
        return isset($this->elements[$id]);
    }

    /**
     * @param mixed $data
     */
    public function save(string $id, $data, int $lifeTime = 0): bool
    {
        $this->elements[$id] = $data;

        return true;
    }

    public function delete(string $id): bool
    {
        unset($this->elements[$id]);

        return true;
    }
}
