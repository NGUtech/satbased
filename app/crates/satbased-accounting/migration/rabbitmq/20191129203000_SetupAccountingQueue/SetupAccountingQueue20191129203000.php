<?php declare(strict_types=1);

namespace Satbased\Accounting\Migration\RabbitMq;

use Daikon\RabbitMq3\Migration\RabbitMq3Migration;

final class SetupAccountingQueue20191129203000 extends RabbitMq3Migration
{
    public function getDescription(string $direction = self::MIGRATE_UP): string
    {
        return $direction === self::MIGRATE_UP
            ? 'Create RabbitMQ queue for Accounting messages.'
            : 'Delete RabbitMQ queue for Accounting messages.';
    }

    public function isReversible(): bool
    {
        return true;
    }

    protected function up(): void
    {
        $this->declareQueue('satbased.accounting.messages', false, true, false, false);
        $this->bindQueue(
            'satbased.accounting.messages',
            'satbased.accounting.exchange',
            'satbased.accounting.#'
        );
    }

    protected function down(): void
    {
        $this->deleteQueue('satbased.accounting.messages');
    }
}
