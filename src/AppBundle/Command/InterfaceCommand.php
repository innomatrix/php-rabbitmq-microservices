<?php

namespace AppBundle\Command;

use AppBundle\AMQPConfig;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class InterfaceCommand extends Command
{
    protected function configure()
    {
        $this->setName('app:interface');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $connection = new AMQPStreamConnection(AMQPConfig::HOST, AMQPConfig::POST, AMQPConfig::USER, AMQPConfig::PASS);

        $channel = $connection->channel();

        $channel->exchange_declare(AMQPConfig::EXCHANGE, 'fanout', false, false, false);

        $questionHelper = $this->getHelper('question');

        $question = new Question('What do you wnat to do?' . PHP_EOL);

        while (true) {
            $task = $questionHelper->ask($input, $output, $question);

            if ($task === 'exit') {
                break;
            }

            $channel->basic_publish(new AMQPMessage($task), AMQPConfig::EXCHANGE);
        }

        $channel->close();
        $connection->close();
    }
}
