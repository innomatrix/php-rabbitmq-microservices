<?php

namespace AppBundle;

class AMQPConfig
{
    const HOST = 'localhost';
    const POST = '5672';
    const USER = 'guest';
    const PASS = 'guest';
    const VHOST = '/';

    const EXCHANGE = 'rmqus';
    const QUEUE = 'bus';

}
