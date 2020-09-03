<?php declare(strict_types=1);

namespace Satbased\Job;

use Daikon\AsyncJob\Strategy\JobStrategyInterface;
use Daikon\Interop\Assert;
use Daikon\MessageBus\EnvelopeInterface;
use Daikon\Metadata\MetadataInterface;
use Daikon\RabbitMq3\Transport\RabbitMq3Transport;

final class DelayAndRetryWithExponentialBackoff implements JobStrategyInterface
{
    public const RETRIES = 'retries';

    private array $settings = [];

    public function __construct(array $settings = [])
    {
        $this->settings = $settings;
        Assert::that($this->settings['limit'], 'Invalid Retry limit.')->nullOr()->notBlank()->integerish()->min(0);
        Assert::that($this->settings['initial'], 'Invalid initial delay.')->notBlank()->integerish()->min(0);
        Assert::that($this->settings['backoff'], 'Invalid backoff.')->notBlank()->integerish()->min(0);
        Assert::that($this->settings['backoff_limit'], 'Invalid backoff limit.')->notBlank()->integerish()->min(0);
    }

    public function canRetry(EnvelopeInterface $envelope): bool
    {
        return isset($this->settings['limit'])
            ? $envelope->getMetadata()->get(self::RETRIES, 0) < $this->settings['limit']
            : true;
    }

    public function enrich(MetadataInterface $metadata): MetadataInterface
    {
        $headers = $metadata->get(RabbitMq3Transport::APPLICATION_HEADERS, []);

        if (!$metadata->has(self::RETRIES)) {
            if ($this->settings['initial'] > 0) {
                $headers['x-delay'] = $this->settings['initial'];
            }
            return $metadata
                ->with(self::RETRIES, 0)
                ->with(RabbitMq3Transport::APPLICATION_HEADERS, $headers);
        }

        $retries = $metadata->get(self::RETRIES) + 1;
        //@todo improve the backoff calculation
        $headers['x-delay'] = min(2**$retries * $this->settings['backoff'], $this->settings['backoff_limit']);

        return $metadata
            ->with(self::RETRIES, $retries)
            ->with(RabbitMq3Transport::APPLICATION_HEADERS, $headers);
    }
}
