<?php
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

use Codeception\Util\JsonArray;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
*/
class ApiTester extends \Codeception\Actor
{
    use _generated\ApiTesterActions;

    public function seeResponseContainsJsonCount(int $count): void
    {
        $jsonResponseArray = new JsonArray($this->grabResponse());
        $this->assertCount($count, $jsonResponseArray->toArray());
    }

    public function dontSeeResponseContainsJsonKey(string $key): void
    {
        $jsonResponseArray = (new JsonArray($this->grabResponse()));
        $this->assertArrayNotHasKey($key, $jsonResponseArray->toArray());
    }
}
