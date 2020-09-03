#!/usr/bin/env node
const amqplib = require('amqplib');
const Plugin = require('clightningjs');

const relayPlugin = new Plugin();
let amqp, auth, host, exchange, delay, connectionInterval;
let prefix = 'lightningd.message.';
let enabledNotifications = {};

relayPlugin.onInit = function(params) {
  if (params.options['amqp-host'] !== 'off') {
    host = params.options['amqp-host'];
  }

  if (params.options['amqp-auth'] !== 'off') {
    auth = params.options['amqp-auth'];
  }

  if (params.options['amqp-exchange'] !== 'off') {
    exchange = params.options['amqp-exchange'];
  }

  if (params.options['amqp-delay'] !== 'off') {
     delay = params.options['amqp-delay'];
  }

  if (params.options['amqp-prefix'] !== 'off') {
    prefix = params.options['amqp-prefix'];
  }

  if (params.options['amqp-notifications'] !== 'off') {
    params.options['amqp-notifications'].split(',').forEach(
      event => {
        enabledNotifications[event] = true;
      }
    );
  }

  if (host && exchange && Object.keys(enabledNotifications).length > 0) {
    connectExchange();
    connectionInterval = setInterval(connectExchange, 10000);
  } else {
    relayPlugin.log('AMQP plugin is installed but not enabled');
  }

  return true;
};

function connectExchange() {
  relayPlugin.log('Connecting to AMQP service at ' + host);
  const endpoint = auth ? auth+'@'+host : host;
  amqplib.connect('amqp://' + endpoint)
  .then(conn => conn.createChannel())
  .then(ch => {
    ch.on('error', err => {
      amqp = null;
      relayPlugin.log('AMQP channel error: ' + err.code);
      if (!connectionInterval) {
        connectionInterval = setInterval(connectExchange, 10000);
      }
    });
    ch.checkExchange(exchange).then(() => {
      amqp = ch;
      clearInterval(connectionInterval);
      connectionInterval = false;
      relayPlugin.log('AMQP channel established');
    }).catch(err => {});
  })
  .catch(err => {
    relayPlugin.log('AMQP connection error: ' + err.code);
  });
}

function publish(event, message) {
  if (amqp && !connectionInterval && enabledNotifications[event] === true) {
    const eventJson = JSON.stringify(message);
    const headers = delay > 0 ? {headers: {'x-delay': delay}} : {};
    amqp.publish(exchange, prefix + event, Buffer.from(eventJson), headers);
  }
}

relayPlugin.addOption('amqp-auth', 'off', 'AMQP service user:pass credentials', 'string');
relayPlugin.addOption('amqp-host', 'off', 'AMQP service host:port address', 'string');
relayPlugin.addOption('amqp-exchange', 'off', 'AMQP service target exchange', 'string');
relayPlugin.addOption('amqp-prefix', 'off', 'AMQP message routing prefix', 'string');
relayPlugin.addOption('amqp-delay', 'off', 'AMQP message relay delay (in ms)', 'int');
relayPlugin.addOption('amqp-notifications', 'off', 'AMQP notification relay list', 'string');

relayPlugin.subscribe('channel_opened');
relayPlugin.subscribe('connect');
relayPlugin.subscribe('disconnect');
relayPlugin.subscribe('invoice_payment');
relayPlugin.subscribe('invoice_creation');
relayPlugin.subscribe('forward_event');
relayPlugin.subscribe('sendpay_success');
relayPlugin.subscribe('sendpay_failure');
relayPlugin.subscribe('coin_movement');
relayPlugin.notifications.channel_opened.on('channel_opened', message => {publish('channel_opened', message)});
relayPlugin.notifications.connect.on('connect', message => {publish('connect', message)});
relayPlugin.notifications.disconnect.on('disconnect', message => {publish('disconnect', message)});
relayPlugin.notifications.invoice_creation.on('invoice_creation', message => {publish('invoice_creation', message)});
relayPlugin.notifications.invoice_payment.on('invoice_payment', message => {publish('invoice_payment', message)});
relayPlugin.notifications.forward_event.on('forward_event', message => {publish('forward_event', message)});
relayPlugin.notifications.sendpay_success.on('sendpay_success', message => {publish('sendpay_success', message)});
relayPlugin.notifications.sendpay_failure.on('sendpay_failure', message => {publish('sendpay_failure', message)});
relayPlugin.notifications.coin_movement.on('coin_movement', message => {publish('coin_movement', message)});

relayPlugin.start();