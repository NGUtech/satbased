<?php declare(strict_types=1);

namespace Satbased\Test;

use DG\BypassFinals;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

class TestCase extends PHPUnitTestCase
{
    public static function setUpBeforeClass(): void
    {
        BypassFinals::enable();
    }
}
