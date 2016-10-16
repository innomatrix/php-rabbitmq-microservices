<?php

namespace AppBundle\Command;

use AppBundle\AMQPConfig;
use AppBundle\Model\Item;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OrderServiceCommand extends Command
{
    protected function configure()
    {
        $this->setName('app:order-service');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $connection = new AMQPStreamConnection(AMQPConfig::HOST, AMQPConfig::POST, AMQPConfig::USER, AMQPConfig::PASS);

        $channel = $connection->channel();

        $channel->exchange_declare(AMQPConfig::EXCHANGE, 'fanout', false, false, false);

        list($queueName, , ) = $channel->queue_declare('order_service', false, true, false, false);

        $channel->queue_bind($queueName, AMQPConfig::EXCHANGE);

        $output->writeln('Order Service :: Waiting for msgs');

        $callback = function (AMQPMessage $msg) use ($output, $channel) {
            $content = explode(':', $msg->getBody());

            if ($content[0] === 'order') {
                $channel->basic_publish(new AMQPMessage('item-need:' . $content[1]), AMQPConfig::EXCHANGE);

                $output->writeln($this->microtime_float() . ' :: Requested details of item ' . $content[1]);
            }
            if ($content[0] === 'item-available') {
                $output->writeln($this->microtime_float() . ' :: Bought item' . $content[2]);
            }

            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        };

        $channel->basic_consume($queueName, 'order_service', false, false, false, false, $callback);

        while (count($channel->callbacks)) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();


    }

    private function microtime_float()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }
}
