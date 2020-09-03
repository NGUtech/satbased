require('dotenv');
const zeromq = require('zeromq');
const amqplib = require('amqplib');

let amqp;
let prefix = 'bitcoind.message.';
let connectionInterval = setInterval(connectExchange, 10000);
const bitcoind_txs = 'tcp://' + process.env.BITCOIN_HOST + ':' + process.env.BITCOIN_ZMQ_TX_PORT;
const bitcoind_blocks = 'tcp://' + process.env.BITCOIN_HOST + ':' + process.env.BITCOIN_ZMQ_BLOCK_PORT;
const exchange = 'bitcoind.adapter.exchange';

function connectExchange() {
  console.log('Connecting to AMQP service at ' + process.env.RABBITMQ_HOST + ':' + process.env.RABBITMQ_PORT);
  amqplib.connect({
    protocol: 'amqp',
    hostname: process.env.RABBITMQ_HOST,
    port: process.env.RABBITMQ_PORT,
    username: process.env.RABBITMQ_DEFAULT_USER,
    password: process.env.RABBITMQ_DEFAULT_PASS
  })
  .then(conn => conn.createChannel())
  .then(ch => {
    ch.on('error', err => {
      amqp = null;
      console.log('AMQP channel error: ' + err.code);
      if (!connectionInterval) {
        connectionInterval = setInterval(connectExchange, 10000);
      }
    });
    ch.checkExchange(exchange).then(() => {
      amqp = ch;
      clearInterval(connectionInterval);
      connectionInterval = false;
      console.log('AMQP channel established');
    }).catch(err => {});
  })
  .catch(err => {
    console.log('AMQP connection error: ' + err.code);
  });
}

//@todo zmq reconnection handling

// Bitcoin Txs
zeromq.socket('sub')
.on('message', function(topic, buffer) {
  topic = topic.toString();
  if (topic === 'hashtx') {
    if (amqp && !connectionInterval) {
      amqp.publish(exchange, prefix + topic, buffer);
    }
  }
})
.setsockopt(zeromq.ZMQ_TCP_KEEPALIVE, 1)
.setsockopt(zeromq.ZMQ_SUBSCRIBE, Buffer.alloc(0))
.connect(bitcoind_txs);

// Bitcoin Blocks
zeromq.socket('sub')
.on('message', function(topic, buffer) {
  topic = topic.toString();
  if (topic === 'hashblock') {
    if (amqp && !connectionInterval) {
      amqp.publish(exchange, prefix + topic, buffer);
    }
  }
})
.setsockopt(zeromq.ZMQ_TCP_KEEPALIVE, 1)
.setsockopt(zeromq.ZMQ_SUBSCRIBE, Buffer.alloc(0))
.connect(bitcoind_blocks);

console.log('Subscribers connected to Bitcoind');