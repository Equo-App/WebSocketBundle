<?php

namespace Gos\Bundle\WebSocketBundle\Pusher\Amqp;

use Gos\Bundle\WebSocketBundle\Pusher\AbstractPusher;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AmqpPusher extends AbstractPusher
{
    /**
     * @var \AMQPConnection
     */
    protected $connection;

    /**
     * @var \AMQPExchange
     */
    protected $exchange;

    /**
     * @var AmqpConnectionFactory
     */
    protected $connectionFactory;

    public function __construct(AmqpConnectionFactory $connectionFactory)
    {
        $this->connectionFactory = $connectionFactory;
    }

    /**
     * @param string|array $data
     */
    protected function doPush($data, array $context): void
    {
        if (false === $this->connected) {
            $this->connection = $this->connectionFactory->createConnection();
            $this->exchange = $this->connectionFactory->createExchange($this->connection);

            $this->connection->connect();
            $this->setConnected();
        }

        $resolver = new OptionsResolver();

        $resolver->setDefaults(
            [
                'routing_key' => null,
                'publish_flags' => AMQP_NOPARAM,
                'attributes' => [],
            ]
        );

        $context = $resolver->resolve($context);

        $this->exchange->publish(
            $data,
            $context['routing_key'],
            $context['publish_flags'],
            $context['attributes']
        );
    }

    public function close()
    {
        if (false === $this->isConnected()) {
            return;
        }

        $this->connection->disconnect();
    }
}
