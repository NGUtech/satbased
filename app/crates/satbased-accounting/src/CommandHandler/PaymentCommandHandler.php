<?php declare(strict_types=1);

namespace Satbased\Accounting\CommandHandler;

use Daikon\EventSourcing\Aggregate\Command\CommandHandler;
use Daikon\Metadata\MetadataInterface;
use Satbased\Accounting\Payment\Cancel\CancelPayment;
use Satbased\Accounting\Payment\Complete\CompletePayment;
use Satbased\Accounting\Payment\Fail\FailPayment;
use Satbased\Accounting\Payment\Make\MakePayment;
use Satbased\Accounting\Payment\Payment;
use Satbased\Accounting\Payment\Receive\ReceivePayment;
use Satbased\Accounting\Payment\Request\RequestPayment;
use Satbased\Accounting\Payment\Select\SelectPayment;
use Satbased\Accounting\Payment\Send\SendPayment;
use Satbased\Accounting\Payment\Settle\SettlePayment;

final class PaymentCommandHandler extends CommandHandler
{
    protected function handleRequestPayment(RequestPayment $requestPayment, MetadataInterface $metadata): array
    {
        return [Payment::request($requestPayment), $metadata];
    }

    protected function handleMakePayment(MakePayment $makePayment, MetadataInterface $metadata): array
    {
        return [Payment::make($makePayment), $metadata];
    }

    protected function handleReceivePayment(ReceivePayment $receivePayment, MetadataInterface $metadata): array
    {
        /** @var Payment $payment */
        $payment = $this->checkout($receivePayment->getPaymentId(), $receivePayment->getKnownAggregateRevision());
        return [$payment->receive($receivePayment), $metadata];
    }

    protected function handleSelectPayment(SelectPayment $selectPayment, MetadataInterface $metadata): array
    {
        /** @var Payment $payment */
        $payment = $this->checkout($selectPayment->getPaymentId(), $selectPayment->getKnownAggregateRevision());
        return [$payment->select($selectPayment), $metadata];
    }

    protected function handleSettlePayment(SettlePayment $settlePayment, MetadataInterface $metadata): array
    {
        /** @var Payment $payment */
        $payment = $this->checkout($settlePayment->getPaymentId(), $settlePayment->getKnownAggregateRevision());
        return [$payment->settle($settlePayment), $metadata];
    }

    protected function handleCompletePayment(CompletePayment $completePayment, MetadataInterface $metadata): array
    {
        /** @var Payment $payment */
        $payment = $this->checkout($completePayment->getPaymentId(), $completePayment->getKnownAggregateRevision());
        return [$payment->complete($completePayment), $metadata];
    }

    protected function handleCancelPayment(CancelPayment $cancelPayment, MetadataInterface $metadata): array
    {
        /** @var Payment $payment */
        $payment = $this->checkout($cancelPayment->getPaymentId(), $cancelPayment->getKnownAggregateRevision());
        return [$payment->cancel($cancelPayment), $metadata];
    }

    protected function handleSendPayment(SendPayment $sendPayment, MetadataInterface $metadata): array
    {
        /** @var Payment $payment */
        $payment = $this->checkout($sendPayment->getPaymentId(), $sendPayment->getKnownAggregateRevision());
        return [$payment->send($sendPayment), $metadata];
    }

    protected function handleFailPayment(FailPayment $failPayment, MetadataInterface $metadata): array
    {
        /** @var Payment $payment */
        $payment = $this->checkout($failPayment->getPaymentId(), $failPayment->getKnownAggregateRevision());
        return [$payment->fail($failPayment), $metadata];
    }
}
