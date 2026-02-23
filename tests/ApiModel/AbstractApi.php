<?php

declare(strict_types=1);

namespace ModelsAlpha\Tests\ApiModel;

use ModelsAlpha\Examples\Tests\ApiAa;
use PHPUnit\Framework\TestCase;

abstract class AbstractApi extends TestCase
{
    private const string API_TO_CHECK_SERVER = 'alpha-a-api-ready';

    private static $fakeServer = null;
    private static ?string $fakeServerFile = null;
    protected static string $fakeServerMethod = 'GET';
    protected static string $fakeServerApi = 'alpha-a';
    protected static string $fakeServerResponse = '{"create":"protected static string $fakeServerResponse"}';
    protected static string $fakeCodeSuccess = '200';
    protected static string $fakeCodeError = '404';
    protected static string $fakeRequestBody = '{"create":"protected static string $fakeRequestBody"}';
    protected static string $fakeQueryParams = '';
    protected static float $fakeTimeout = 0.0;
    protected static float $fakeRetries = 0;

    public static function setUpBeforeClass(): void
    {
        self::$fakeServerFile = self::createFakeServer();

        $cmd = ['php', '-S', ApiAa::FAKE_SERVER, self::$fakeServerFile];
        self::$fakeServer = proc_open($cmd, self::fakeServerDescriptors(), $pipes);

        self::waitUntilServerReady('http://' . ApiAa::FAKE_SERVER . '/' . self::API_TO_CHECK_SERVER, self::API_TO_CHECK_SERVER);
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$fakeServer)) {
            proc_terminate(self::$fakeServer);
            proc_close(self::$fakeServer);
            self::$fakeServer = null;
        }

        if (self::$fakeServerFile && file_exists(self::$fakeServerFile)) {
            @unlink(self::$fakeServerFile);
            self::$fakeServerFile = null;
        }
    }

    private static function createFakeServer(): string
    {
        static::clearRetries();

        $serverCode = str_replace('__FAKE_METHOD__', static::$fakeServerMethod, self::FAKE_SERVER_PHP);
        $serverCode = str_replace('__FAKE_API__', static::$fakeServerApi, $serverCode);
        $serverCode = str_replace('__FAKE_RESPONSE__', static::$fakeServerResponse, $serverCode);
        $serverCode = str_replace('__FAKE_CODE_SUCCESS__', static::$fakeCodeSuccess, $serverCode);
        $serverCode = str_replace('__FAKE_CODE_ERROR__', static::$fakeCodeError, $serverCode);
        $serverCode = str_replace('__FAKE_REQUEST_BODY__', static::$fakeRequestBody, $serverCode);
        $serverCode = str_replace('__FAKE_QUERY__', static::$fakeQueryParams, $serverCode);
        $serverCode = str_replace('__FAKE_TIMEOUT__', (string)static::$fakeTimeout, $serverCode);
        $serverCode = str_replace('__RETRIES_FILE_CACHE_PATH__', static::retriesPath(), $serverCode);
        $filePath = tempnam(sys_get_temp_dir(), 'phpunit_stub_');
        file_put_contents($filePath, $serverCode);
        return $filePath;
    }

    protected static function clearRetries(): void
    {
        file_put_contents(static::retriesPath(), static::$fakeRetries);
    }

    private static function retriesPath(): string { return __DIR__ . '/../../logs/count_retries.log'; }

    private static function fakeServerDescriptors(): array
    {
        return [
            0 => ['pipe', 'r'],
            1 => ['file', PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null', 'a'],
            2 => ['file', PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null', 'a'],
        ];
    }

    private static function waitUntilServerReady(string $url, string $response): void
    {
        $deadline = microtime(true) + 1.0;
        do {
            usleep(100000);
            $fakeServerResponse = @file_get_contents($url, false, stream_context_create([
                'http' => [
                    'method' => "GET",
                    'timeout' => 0.2,
                    'ignore_errors' => true,
                ],
            ]));
        } while ($fakeServerResponse !== $response && microtime(true) < $deadline);
    }

private const string FAKE_SERVER_PHP = <<<'TEMP_FILE_CONTENT'
<?php
$targetApi = '/__FAKE_API__';
$targetMethod = '__FAKE_METHOD__';
$targetQuery = '__FAKE_QUERY__';
$targetTimeout = __FAKE_TIMEOUT__;
$retriesFile = '__RETRIES_FILE_CACHE_PATH__';
$targetRetries = (int)file_get_contents($retriesFile);
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$body = file_get_contents('php://input') ?: '';

if ($path === '/alpha-a-api-ready') {
    http_response_code(200);
    echo 'alpha-a-api-ready';
    exit;
}

$isSuccess = $method === $targetMethod && $path === $targetApi;

if (in_array($method, ['POST', 'PUT', 'PATCH']) && !str_contains($body, '__FAKE_REQUEST_BODY__')) {
    $isSuccess = false;
}

if ($targetQuery) {
    $query = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_QUERY) ?? '';
    parse_str($query, $queryParams);
    parse_str($targetQuery, $targetQueryParams);

    foreach ($targetQueryParams as $K => $value) {
        if (!isset($queryParams[$K]) || (string)$queryParams[$K] != (string)$value) {
            $isSuccess = false;
            break;
        }
    }
}

if ($targetTimeout > 0.1) {
    usleep(1_000_000 * $targetTimeout);
}

if ($targetRetries > 0) {
    $targetRetries--;
    $isSuccess = false;
    file_put_contents($retriesFile, $targetRetries);
}

http_response_code($isSuccess ? __FAKE_CODE_SUCCESS__ : __FAKE_CODE_ERROR__);
header('Content-Type: application/json; charset=utf-8');
echo $isSuccess ? '__FAKE_RESPONSE__' : '{"action":"error"}';

TEMP_FILE_CONTENT;

}