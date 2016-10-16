PHP RabbitMQ Microservices
===========

Playing around with event driven microservices in PHP.

It's not well thought code by any means. Just prototype to play with the concept.

# Before

### Run composer

```
composer install
```

### Get Rabbitmq
I've used docker one as the simplest solution.

```
docker run -d --hostname rabbit --name rabbit0 -p 5671:15672 -p 5672:5672 rabbitmq:3-management
```

# How to run it

### Item service
```
bin/console app:item-service
```

### Order service
```
bin/console app:order-service
```

### Interface
```
bin/console app:interface
```

Type ```order:5``` and see in different shells how process is executed.

Type ```order:9``` and see ItemService failing. Add row to the table, restart service and it's picking up where it finished.
