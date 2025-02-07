<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Topic;

use Ratchet\ConnectionInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;

class ConnectionPeriodicTimer implements \IteratorAggregate, \Countable
{
    /**
     * @var array<string, TimerInterface>
     */
    protected array $registry = [];
    protected ConnectionInterface $connection;
    protected LoopInterface $loop;

    public function __construct(ConnectionInterface $connection, LoopInterface $loop)
    {
        $this->connection = $connection;
        $this->loop = $loop;
    }

    /**
     * @return TimerInterface|bool
     */
    public function getPeriodicTimer(string $name)
    {
        if (!$this->isPeriodicTimerActive($name)) {
            return false;
        }

        return $this->registry[$this->getTid($name)];
    }

    protected function getTid(string $name): string
    {
        return sha1($this->connection->resourceId.$this->connection->WAMP->sessionId.$name);
    }

    /**
     * @param int|float $timeout
     * @param mixed     $callback
     */
    public function addPeriodicTimer(string $name, $timeout, $callback): void
    {
        $this->registry[$this->getTid($name)] = $this->loop->addPeriodicTimer($timeout, $callback);
    }

    public function isPeriodicTimerActive(string $name): bool
    {
        return isset($this->registry[$this->getTid($name)]);
    }

    public function cancelPeriodicTimer(string $tidOrname): void
    {
        if (!isset($this->registry[$tidOrname])) {
            $tid = $this->getTid($tidOrname);
        } else {
            $tid = $tidOrname;
        }

        $timer = $this->registry[$tid];
        $this->loop->cancelTimer($timer);
        unset($this->registry[$tid]);
    }

    /**
     * @return \ArrayIterator<string, TimerInterface>
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->registry);
    }

    /**
     * @return int
     */
    public function count()
    {
        return \count($this->registry);
    }
}
