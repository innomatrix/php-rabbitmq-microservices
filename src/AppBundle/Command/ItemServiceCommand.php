<?php

namespace AppBundle\Command;

use AppBundle\AMQPConfig;
use AppBundle\Model\Item;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ItemServiceCommand extends Command
{
    protected function configure()
    {
        $this->setName('app:item-service');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $connection = new AMQPStreamConnection(AMQPConfig::HOST, AMQPConfig::POST, AMQPConfig::USER, AMQPConfig::PASS);

        $channel = $connection->channel();

        $channel->exchange_declare(AMQPConfig::EXCHANGE, 'fanout', false, false, false);

        list($queueName, , ) = $channel->queue_declare('item_service', false, true, false, false);

        $channel->queue_bind($queueName, AMQPConfig::EXCHANGE);

        $output->writeln('Item Service :: Waiting for msgs');

        $callback = function (AMQPMessage $msg) use ($output, $channel) {
            $content = explode(':', $msg->getBody());

            if ($content[0] === 'item-need') {
                $item = $this->getItemById($content[1]);

                $msgBody = 'item-available:' . $item->getId() . ':' . $item->getName();

                $channel->basic_publish(new AMQPMessage($msgBody), AMQPConfig::EXCHANGE);
                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);

                $output->writeln($this->microtime_float() . ' :: Sent back deails of item ' . $item->getId());
            }
        };

        $channel->basic_consume($queueName, 'item_service', false, false, false, false, $callback);

        while (count($channel->callbacks)) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();


    }

    private function getItems()
    {
        return [
            1  => new Item(1, 'Item 1'),
            2  => new Item(2, 'Item 2'),
            3  => new Item(3, 'Item 3'),
            4  => new Item(4, 'Item 4'),
            5  => new Item(5, 'Item 5'),
            6  => new Item(6, 'Item 6'),
            7  => new Item(7, 'Item 7'),
            8  => new Item(7, 'Item 8'),
        ];
    }


    private function microtime_float()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    /**
     * @param $id
     * @return Item
     */
    private function getItemById($id)
    {
        return $this->getItems()[$id];
    }
}
