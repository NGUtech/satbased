################################################################################
# Base image
################################################################################

FROM rabbitmq:3.8-management

################################################################################
# Build instructions
################################################################################

ADD --chown=rabbitmq:rabbitmq https://github.com/rabbitmq/rabbitmq-message-timestamp/releases/download/v3.8.0/rabbitmq_message_timestamp-3.8.0.ez \
    /opt/rabbitmq/plugins
ADD --chown=rabbitmq:rabbitmq https://github.com/rabbitmq/rabbitmq-delayed-message-exchange/releases/download/v3.8.0/rabbitmq_delayed_message_exchange-3.8.0.ez \
    /opt/rabbitmq/plugins

RUN rabbitmq-plugins enable \
    rabbitmq_shovel \
    rabbitmq_shovel_management \
    rabbitmq_message_timestamp \
    rabbitmq_delayed_message_exchange