<?php

declare(strict_types=1);

namespace ModelsAlpha\Tests\ApiModel;

use ModelsAlpha\Examples\Tests\ApiAa;
use PHPUnit\Framework\TestCase;

abstract class AbstractApi extends TestCase
{
    private static $fakeServer = null;
    private static ?string $fakeServerFile = null;
    protected static string $fakeServerMethod = 'GET';
    protected static string $fakeServerApi = 'alpha-a';
    protected static string $fakeServerResponse = '{"create":"public const string MODEL_A"}';

    public static function setUpBeforeClass(): void
    {
        self::$fakeServerFile = self::createFakeServer();

        $cmd = ['php', '-S', ApiAa::FAKE_SERVER, self::$fakeServerFile];
        self::$fakeServer = proc_open($cmd, self::fakeServerDescriptors(), $pipes);

        self::waitUntilServerReady('http://' . ApiAa::FAKE_SERVER . '/' . static::$fakeServerApi, static::$fakeServerResponse);
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
        $serverCode = str_replace('__FAKE_METHOD__', static::$fakeServerMethod, self::FAKE_SERVER_PHP);
        $serverCode = str_replace('__FAKE_API__', static::$fakeServerApi, $serverCode);
        $serverCode = str_replace('__FAKE_RESPONSE__', static::$fakeServerResponse, $serverCode);
        $filePath = tempnam(sys_get_temp_dir(), 'phpunit_stub_');
        file_put_contents($filePath, $serverCode);
        return $filePath;
    }

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
        $deadline = microtime(true) + 4.0;
        do {
            usleep(100000);
            $fakeServerResponse = @file_get_contents($url, false, stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 0.2,
                    'ignore_errors' => true,
                ],
            ]));
        } while ($fakeServerResponse !== $response && microtime(true) < $deadline);
    }

    private const string FAKE_SERVER_PHP = <<<'TEMP_FILE_CONTENT'
<?php
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$isSuccess = $method === '__FAKE_METHOD__' && $path === '/__FAKE_API__';

http_response_code($isSuccess ? 200 : 404);
header('Content-Type: application/json; charset=utf-8');
echo $isSuccess ? '__FAKE_RESPONSE__' : '{"action":"error"}';

TEMP_FILE_CONTENT;
}