import {} from 'dotenv';
import amqplib from 'amqplib';
import lnd from './lnd.js';

const STATE_OPEN = 0;
const STATE_SETTLED = 1;
const STATE_CANCELLED = 2;
const STATE_ACCEPTED = 3;
const EVENT_SEND = 1;
const EVENT_RECEIVE = 2;
const EVENT_FORWARD = 3;

let prefix = 'lnd.message.';
let delay = process.env.LIGHTNING_RELAY_DELAY || 0;
const lndHost = process.env.ALICE_HOST+':'+process.env.ALICE_RPC_PORT;

// lnd setup
(async () => {
  await lnd.init({
    server: lndHost,
    tls: '/lnd/tls.cert',
    macaroonPath: '/lnd/data/chain/bitcoin/regtest/admin.macaroon',
    includeDefaults: true
  });

  let amqp;
  let connectionInterval = setInterval(connectExchange, 10000);
  const exchange = 'lnd.adapter.exchange';

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

  function subscribeInvoice(invoice) {
    const invoiceSubscriber = lnd.invoicesrpc.subscribeSingleInvoice({rHash: invoice.rHash});
    invoiceSubscriber
    .on('data', (invoice) => {
      if ([STATE_CANCELLED, STATE_ACCEPTED].indexOf(invoice.state) >= 0) {
        publishInvoice(invoice);
      }
      if ([STATE_CANCELLED, STATE_SETTLED].indexOf(invoice.state) >= 0) {
        invoiceSubscriber.cancel();
      }
    })
    .on('error', (error) => {
      if (error.code === 1) {
        console.log('Invoice '+invoice.rHash.toString('hex')+' subscriber stopped');
      } else {
        console.log(error);
      }
    });
    console.log('Invoice '+invoice.rHash.toString('hex')+' subscriber started');
  }

  function publishInvoice(invoice) {
    if (amqp && !connectionInterval) {
      // convert byte buffers to hex strings
      let copy = {...invoice};
      copy.receipt = '';
      copy.rHash = copy.rHash.toString('hex');
      copy.rPreimage = copy.rPreimage.toString('hex');
      copy.descriptionHash = copy.descriptionHash.toString('hex');
      const invoiceJson = JSON.stringify(copy);
      const headers = delay > 0 ? {headers: {'x-delay': delay}} : {};
      amqp.publish(exchange, prefix + 'invoice', Buffer.from(invoiceJson), headers);
      console.log('Invoice '+copy.rHash+' state '+copy.state+' relayed');
    }
  }

  // function publishEvent(event) {
  //   let copy = {...event};
  //   const eventJson = JSON.stringify(copy);
  //   rabbitmq.publish(exchange, prefix + 'htlc_event', Buffer.from(eventJson));
  //   console.log('Event '+event.event+' of type '+copy.eventType+' relayed.');
  // }

  // subscribe to LND invoice events
  const invoicesSubscriber = lnd.lnrpc.subscribeInvoices({});
  invoicesSubscriber
  .on('data', (invoice) => {
    publishInvoice(invoice);
    if (invoice.state === STATE_OPEN && !invoice.rPreimage.toString('hex')) {
      subscribeInvoice(invoice);
    }
  });

  // subscribe to LND htlc events
  // const htlcSubscriber = lnd.routerrpc.subscribeHtlcEvents({});
  // htlcSubscriber
  // .on('data', (event) => {
  //   // hack for missing event field
  //   if (event.hasOwnProperty('forwardEvent')) {
  //     event.event = 7;
  //   } else if (event.hasOwnProperty('forwardFailEvent')) {
  //     event.event = 8;
  //   } else if (event.hasOwnProperty('settleEvent')) {
  //     event.event = 9;
  //   } else if (event.hasOwnProperty('linkFailEvent')) {
  //     event.event = 10;
  //   }

  //   publishEvent(event);
  // });
})();