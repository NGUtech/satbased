<?php declare(strict_types=1);

namespace Satbased\Api;

use Daikon\Boot\Middleware\Action\DaikonRequest;
use Daikon\Security\Middleware\Action\SecureAction;
use Laminas\Permissions\Acl\Resource\ResourceInterface;

abstract class SecureResourceAction extends SecureAction implements ResourceInterface
{
    public function getResourceId(): string
    {
        return static::class;
    }

    public function handleError(DaikonRequest $request): DaikonRequest
    {
        return $request->withResponder(ErrorResponder::class);
    }
}
