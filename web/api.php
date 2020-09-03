<?php declare(strict_types=1);

use Auryn\Injector;
use Daikon\Boot\Bootstrap\WebBootstrap;
use Daikon\Boot\Middleware\PipelineBuilderInterface;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiStreamEmitter;
use Psr\Container\ContainerInterface;

$baseDir = dirname(__DIR__);
/** @psalm-suppress UnresolvableInclude */
require_once "$baseDir/vendor/autoload.php";

$appDir = "$baseDir/app";
$appDebug = getenv('APP_DEBUG') ?: true;

/** @var ContainerInterface $container */
$container = (new WebBootstrap)(new Injector, [
    'context' => 'api',
    'version' => getenv('APP_VERSION') ?: 'master',
    'env' => getenv('APP_ENV') ?: 'dev',
    'debug' => filter_var($appDebug, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
    'base_dir' => $baseDir,
    'boot_dir' => getenv('APP_BOOT_DIR') ?: "$baseDir/vendor/daikon/boot",
    'crates_dir' => getenv('APP_CRATES_DIR') ?: "$appDir/crates",
    'config_dir' => getenv('APP_CONFIG_DIR') ?: "$appDir/config",
    'secrets_dir' => getenv('APP_SECRETS_DIR') ?: '/usr/local/env',
    'log_dir' => getenv('APP_LOG_DIR') ?: "$baseDir/var/logs",
    'cache_dir' => getenv('APP_CACHE_DIR') ?: "$baseDir/var/cache",
    'scheme' => getenv('APP_SCHEME') ?: 'http',
    'host' => getenv('APP_HOST') ?: 'localhost',
    'port' => getenv('APP_PORT') ?: 80
]);

(new SapiStreamEmitter)->emit(
    $container
        ->get(PipelineBuilderInterface::class)()
        ->handle(ServerRequestFactory::fromGlobals())
);
