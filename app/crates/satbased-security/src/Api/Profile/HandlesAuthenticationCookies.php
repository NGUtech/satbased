<?php declare(strict_types=1);

namespace Satbased\Security\Api\Profile;

use Daikon\Security\Middleware\JwtDecoder;
use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\Modifier\SameSite;
use Dflydev\FigCookies\SetCookie;
use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface;

trait HandlesAuthenticationCookies
{
    private function setAuthenticationCookies(ResponseInterface $response): ResponseInterface
    {
        $cookiesConfig = $this->config->get('project.authentication.cookies', []);
        $cookieDomain = $cookiesConfig['domain'] ?? '.'.$this->config->get('app.host');
        $jwtAttribute = $cookiesConfig['jwt']['attribute'] ?? JwtDecoder::DEFAULT_ATTR_JWT;
        $xsrfAttribute = $cookiesConfig['xsrf']['attribute'] ?? JwtDecoder::DEFAULT_ATTR_XSRF;
        $jwtPayload = explode('.', $this->jwt)[1];
        $decodedToken = JWT::jsonDecode(JWT::urlsafeB64Decode($jwtPayload));

        $response = FigResponseCookies::set(
            $response,
            $this->createSetCookie($jwtAttribute, $this->jwt, $cookieDomain, $decodedToken->exp, true)
        );

        // Use an xsrf cookie so the lifetime of the token matches the jwt.
        return FigResponseCookies::set(
            $response,
            $this->createSetCookie($xsrfAttribute, $decodedToken->xsrf, $cookieDomain, $decodedToken->exp, false)
        );
    }

    private function expireAuthenticationCookies(ResponseInterface $response): ResponseInterface
    {
        $cookieConfig = $this->config->get('project.authentication.cookies', []);
        $jwtAttribute = $cookieConfig['jwt']['attribute'] ?? JwtDecoder::DEFAULT_ATTR_JWT;
        $xsrfAttribute = $cookieConfig['xsrf']['attribute'] ?? JwtDecoder::DEFAULT_ATTR_XSRF;

        $response = FigResponseCookies::expire($response, $jwtAttribute);
        $response = FigResponseCookies::expire($response, $xsrfAttribute);

        return $response;
    }

    private function createSetCookie(
        string $name,
        string $value,
        string $domain,
        int $expires,
        bool $httpOnly
    ): SetCookie {
        $isSecure = $this->config->get('project.cors.scheme') === 'https';
        $isHostDirective = strpos($name, '__Host-') === 0;

        $setCookie = SetCookie::create($name)
            ->withValue($value)
            ->withDomain($domain)
            ->withExpires(gmdate('D, d M Y H:i:s T', $expires))
            ->withPath('/')
            ->withHttpOnly($httpOnly)
            ->withSecure($isSecure)
            ->withSameSite(SameSite::lax());

        // The __Host- cookie directive requires that domain be excluded, secure is true,
        // and path be '/'. It is not sent to subdomains.
        if ($isHostDirective === true) {
            $setCookie = $setCookie
                ->withDomain(null)
                ->withSecure(true)
                ->withSameSite(SameSite::strict());
        }

        return $setCookie;
    }
}
