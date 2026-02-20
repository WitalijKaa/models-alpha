<?php

namespace ModelsAlpha\Examples\Tests;

use ModelsAlpha\BaseApiModel;

abstract class ApiAa extends BaseApiModel
{
    public const string FAKE_SERVER = '127.0.0.1:22022';

    public function apiServer(): string
    {
        return 'http://' . self::FAKE_SERVER . '/';
    }

    public function forcedQuery(): array
    {
        return [];
    }

    protected function logAnError(string $level, string $message, array $logArr): void
    {
        $filePath = '/logs/Tests-' . date('Y-m-d') . '.log';
        $line = sprintf('[%s] %s: %s #### %s',
            date('Y-m-d H:i:s'),
            $level,
            $message,
            json_encode($logArr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
        file_put_contents($filePath, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}