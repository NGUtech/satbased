# Satbased
Satbased Infrastructure

```
$ composer --ignore-platform-reqs install
$ docker-compose up -d
# wait a few moments while cluster initialises
```

Boostrap infrastructure if required.
```
$ bin/bootstrap
```

Build project and fixtures.
```
$ bin/daikon -vv migrate:up
$ bin/daikon -vv fixture:import
# migrate down later
$ bin/daikon -vv migrate:down
```

# Docs

Buildings and serving docs
```
$ pip install mkdocs mkdocs-material pymdown-extensions
$ cd doc && mkdocs serve
```

## Endpoints for testing:

- Webserver: http://local.satbased.com
- CouchDB Admin: http://local.satbased.com:5984/_utils (couch/couch)
- RabbitMQ Admin: http://local.satbased.com:15672 (rabbit/rabbit)
- Kibana: http://local.satbased.com:5601
- Workers:
  - bin/daikon -vv worker:run bitcoind.adapter.messages bitcoind.adapter.message_queue
  - bin/daikon -vv worker:run lightningd.adapter.messages lightningd.adapter.message_queue
  - bin/daikon -vv worker:run lnd.adapter.messages lnd.adapter.message_queue
  - bin/daikon -vv worker:run satbased.accounting.messages daikon.message_queue
