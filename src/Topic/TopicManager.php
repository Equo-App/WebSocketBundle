<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Topic;

use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Ratchet\Wamp\WampServerInterface;
use Ratchet\WebSocket\WsServerInterface;

/**
 * @author Edu Salguero <edusalguero@gmail.com>
 */
class TopicManager implements WsServerInterface, WampServerInterface
{
    protected WampServerInterface $app;

    /**
     * @var array<string, Topic>
     */
    protected array $topicLookup = [];

    public function __construct(WampServerInterface $app)
    {
        $this->app = $app;
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        $conn->WAMP->subscriptions = new \SplObjectStorage();
        $this->app->onOpen($conn);
    }

    /**
     * @param string       $id The unique ID of the RPC, required to respond to
     * @param string|Topic $topic
     */
    public function onCall(ConnectionInterface $conn, $id, $topic, array $params): void
    {
        $this->app->onCall($conn, $id, $this->getTopic($topic), $params);
    }

    /**
     * @param string|Topic $topic
     */
    public function onSubscribe(ConnectionInterface $conn, $topic): void
    {
        $topicObj = $this->getTopic($topic);

        if ($conn->WAMP->subscriptions->contains($topicObj)) {
            return;
        }

        $this->topicLookup[(string) $topic]->add($conn);
        $conn->WAMP->subscriptions->attach($topicObj);
        $this->app->onSubscribe($conn, $topicObj);
    }

    /**
     * @param string|Topic $topic
     */
    public function onUnsubscribe(ConnectionInterface $conn, $topic): void
    {
        $topicObj = $this->getTopic($topic);

        if (!$conn->WAMP->subscriptions->contains($topicObj)) {
            return;
        }

        $this->cleanTopic($topicObj, $conn);

        $this->app->onUnsubscribe($conn, $topicObj);
    }

    /**
     * @param string|Topic $topic
     * @param string       $event
     */
    public function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude, array $eligible): void
    {
        $this->app->onPublish($conn, $this->getTopic($topic), $event, $exclude, $eligible);
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $this->app->onClose($conn);

        foreach ($this->topicLookup as $topic) {
            $this->cleanTopic($topic, $conn);
        }
    }

    public function onError(ConnectionInterface $conn, \Throwable $e): void
    {
        $this->app->onError($conn, $e);
    }

    public function getSubProtocols(): array
    {
        if ($this->app instanceof WsServerInterface) {
            return $this->app->getSubProtocols();
        }

        return [];
    }

    /**
     * @param Topic|string $topic
     *
     * @throws \InvalidArgumentException if the $topic argument is not a supported type
     */
    public function getTopic($topic): Topic
    {
        if (!($topic instanceof Topic) && !\is_string($topic)) {
            throw new \InvalidArgumentException(sprintf('The $topic argument of %s() must be an instance of %s or a string, %s was given.', __METHOD__, Topic::class, ('object' === \gettype($topic) ? 'an instance of '.\get_class($topic) : 'a '.\gettype($topic))));
        }

        $key = $topic instanceof Topic ? $topic->getId() : $topic;

        if (!\array_key_exists($key, $this->topicLookup)) {
            if ($topic instanceof Topic) {
                $this->topicLookup[$key] = $topic;
            } else {
                $this->topicLookup[$key] = new Topic($topic);
            }
        }

        return $this->topicLookup[$key];
    }

    protected function cleanTopic(Topic $topic, ConnectionInterface $conn): void
    {
        if ($conn->WAMP->subscriptions->contains($topic)) {
            $conn->WAMP->subscriptions->detach($topic);
        }

        $this->topicLookup[$topic->getId()]->remove($conn);

        if (0 === $topic->count()) {
            unset($this->topicLookup[$topic->getId()]);
        }
    }
}
