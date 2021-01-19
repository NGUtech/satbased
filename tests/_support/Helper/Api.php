<?php

namespace Helper;

use Codeception\Module\REST;
use Codeception\Util\HttpCode;
use DateTimeImmutable;
use Dflydev\FigCookies\Cookie;

final class Api extends REST
{
    public function login(array $params = []): array
    {
        $this->haveHttpHeader('Accept', 'application/json');
        $this->sendPOST('/login', $params);
        $this->seeResponseCodeIs(HttpCode::OK);
        $headers = $this->grabHttpHeader('Set-Cookie', false);
        $jwt = Cookie::listFromCookieString($headers[0]);
        $xsrf = Cookie::listFromCookieString($headers[1]);
        return ['jwt' => $jwt[0]->getValue(), 'xsrf' => $xsrf[0]->getValue()];
    }

    public function seeValidAuthenticationCookies(): void
    {
        $headers = $this->grabHttpHeader('Set-Cookie', false);
        $this->assertCount(2, $headers);

        $jwt = Cookie::listFromCookieString($headers[0]);
        $this->assertEquals($this->config['cookies']['jwt'], $jwt[0]->getName());
        $this->assertNotEmpty($jwt[0]->getValue());
        $this->assertEquals('Domain', $jwt[1]->getName());
        $this->assertEquals($this->config['cookies']['domain'], $jwt[1]->getValue());
        $this->assertEquals('Path', $jwt[2]->getName());
        $this->assertEquals('/', $jwt[2]->getValue());
        $this->assertEquals('Expires', $jwt[3]->getName());
        $this->assertTrue(new DateTimeImmutable < new DateTimeImmutable($jwt[3]->getValue()));
        $this->assertEquals('HttpOnly', $jwt[4]->getName());
        $this->assertEquals('SameSite', $jwt[5]->getName());
        $this->assertEquals('Lax', $jwt[5]->getValue());

        $xsrf = Cookie::listFromCookieString($headers[1]);
        $this->assertEquals($this->config['cookies']['xsrf'], $xsrf[0]->getName());
        $this->assertNotEmpty($xsrf[0]->getValue());
        $this->assertEquals('Domain', $xsrf[1]->getName());
        $this->assertEquals($this->config['cookies']['domain'], $xsrf[1]->getValue());
        $this->assertEquals('Path', $xsrf[2]->getName());
        $this->assertEquals('/', $xsrf[2]->getValue());
        $this->assertEquals('Expires', $xsrf[3]->getName());
        $this->assertTrue(new DateTimeImmutable() < new DateTimeImmutable($xsrf[3]->getValue()));
        $this->assertEquals('SameSite', $xsrf[4]->getName());
        $this->assertEquals('Lax', $xsrf[4]->getValue());
    }

    public function seeExpiredAuthenticationCookies(): void
    {
        $headers = $this->grabHttpHeader('Set-Cookie', false);
        $this->assertCount(2, $headers);

        $jwt = Cookie::listFromCookieString($headers[0]);
        $this->assertEquals($this->config['cookies']['jwt'], $jwt[0]->getName());
        $this->assertEmpty($jwt[0]->getValue());
        $this->assertEquals('Expires', $jwt[1]->getName());
        $this->assertTrue(new DateTimeImmutable > new DateTimeImmutable($jwt[1]->getValue()));

        $xsrf = Cookie::listFromCookieString($headers[1]);
        $this->assertEquals($this->config['cookies']['xsrf'], $xsrf[0]->getName());
        $this->assertEmpty($xsrf[0]->getValue());
        $this->assertEquals('Expires', $xsrf[1]->getName());
        $this->assertTrue(new DateTimeImmutable > new DateTimeImmutable($xsrf[1]->getValue()));
    }

    public function sendPOST($url, $params = [], $files = []): void
    {
        $this->haveHttpHeader('Content-Type', 'application/json');
        parent::sendPOST($url, $params, $files);
    }

    public function getProfile(string $profileId): void
    {
        $this->getDocument('daikon.satbased-security.profile.standard', $profileId);
    }

    public function getPayment(string $paymentId): void
    {
        $this->getDocument('daikon.satbased-accounting.payment.standard', $paymentId);
    }

    public function getAccount(string $accountId): void
    {
        $this->getDocument('daikon.satbased-accounting.account.standard', $accountId);
    }

    public function searchAccounts(array $query = []): void
    {
        $this->searchDocuments('daikon.satbased-accounting.account.standard', $query);
    }

    public function searchProfiles(array $query = []): void
    {
        $this->searchDocuments('daikon.satbased-security.profile.standard', $query);
    }

    private function getDocument(string $index, string $identifier): void
    {
        $this->amHttpAuthenticated(
            $this->config['elasticsearch']['username'],
            $this->config['elasticsearch']['password']
        );
        $this->sendGET($this->config['elasticsearch']['url']."/$index/_doc/$identifier");
    }

    private function searchDocuments(string $index, array $query = []): void
    {
        $this->amHttpAuthenticated(
            $this->config['elasticsearch']['username'],
            $this->config['elasticsearch']['password']
        );
        $this->sendGET($this->config['elasticsearch']['url']."/$index/_search", array_merge(['size' => 50], $query));
    }
}
