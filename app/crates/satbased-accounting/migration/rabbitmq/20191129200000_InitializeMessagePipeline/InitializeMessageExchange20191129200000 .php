<?php declare(strict_types=1);

namespace Satbased\Accounting\Migration\RabbitMq;

use Daikon\RabbitMq3\Migration\RabbitMq3Migration;
use PhpAmqpLib\Exchange\AMQPExchangeType;

final class InitializeMessageExchange20191129200000 extends RabbitMq3Migration
{
    public function getDescription(string $direction = self::MIGRATE_UP): string
    {
        return $direction === self::MIGRATE_UP
            ? 'Create a RabbitMQ message exchange for the Satbased-Accounting context.'
            : 'Delete the RabbitMQ message exchange for the Satbased-Accounting context.';
    }

    public function isReversible(): bool
    {
        return true;
    }

    protected function up(): void
    {
        $this->createMigrationList('satbased.accounting.migration_list');
        $this->declareExchange(
            'satbased.accounting.exchange',
            'x-delayed-message',
            false,
            true,
            false,
            false,
            false,
            ['x-delayed-type' => AMQPExchangeType::TOPIC]
        );
    }

    protected function down(): void
    {
        $this->deleteExchange('satbased.accounting.exchange');
        $this->deleteExchange('satbased.accounting.migration_list');
    }
}
