<?php

namespace ModelsAlpha;

use ModelsAlpha\Core\CurlOptionsDto;
use ModelsAlpha\Helpers\ExceptionLog;
use ModelsAlpha\Helpers\Str;

abstract class BaseApiModel extends BaseModel
{
    public const string CONTENT_TYPE_JSON = 'application/json'; // default for requestFormatMode and responseFormatMode
    public const string CONTENT_TYPE_FORM_DATA = 'multipart/form-data';

    protected const array HEADERS_DEFAULT = [
        'Cache-Control' => 'no-cache, must-revalidate',
        'Pragma' => 'no-cache',
    ];

    protected const string M_GET = 'GET';
    protected const string M_POST = 'POST';
    protected const string M_PUT = 'PUT';
    protected const string M_DELETE = 'DELETE';
    protected const string M_PATCH = 'PATCH';
    protected const string M_OPTIONS = 'OPTIONS';
    protected const string M_HEAD = 'HEAD';

    protected const string LOG_ERR = 'ERROR';
    protected const string LOG_CRITIC = 'CRITICAL';

    protected string $method = self::M_GET;

    private int $lastResponseCode;
    private string $lastErrorMessage;

    abstract public function apiServer(): string;
    abstract public function apiEndPoint(): string;
    abstract public function forcedQuery(): array;

    protected function apiEndPointLogMsg(): string {
        return $this->apiEndPoint();
    }

    protected bool $isQuiteModeOnceForGetNotFoundApiError = false;

    private float $timeoutDefault = 42.0;
    private ?float $timeoutOnce = null;
    protected function withTimeout(float $timeout): static
    {
        $this->timeoutOnce = $timeout > 0.0 ? $timeout : null;
        return $this;
    }

    private ?int $retriesOnceCount = null;
    private ?int $retriesOnceAwaitSecs = null;
    protected function withRetries(int $count, int $await = 2): static
    {
        if ($count <= 0 || $await <= 0.0) {
            $this->retriesOnceCount     = null;
            $this->retriesOnceAwaitSecs = null;
        } else {
            $this->retriesOnceCount     = $count;
            $this->retriesOnceAwaitSecs = $await;
        }
        return $this;
    }

    protected function logAnError(string $level, string $message, array $logArr): void
    {
        // LogHelper::userError($message, $logArr, $this->crmID);
    }

    public function isLastResponseSuccess(): bool
    {
        return !empty($this->lastResponseCode) && $this->lastResponseCode >= 200 && $this->lastResponseCode < 300;
    }

    public function errorMsg(): string
    {
        return !empty($this->lastErrorMessage) ? $this->lastErrorMessage : '';
    }

    public function sendGet(): ?array
    {
        $this->method = self::M_GET;
        $return = $this->getResponse();
        $this->isQuiteModeOnceForGetNotFoundApiError = false;
        return $return;
    }

    public function allowGetNotFountQuietOnce(): static
    {
        $this->isQuiteModeOnceForGetNotFoundApiError = true;
        return $this;
    }

    public function sendPost(array $body): ?array
    {
        $this->method = self::M_POST;
        return $this->getResponse($this->curlOptions($body));
    }

    public function sendPostVsCode(array $body): bool
    {
        $this->sendPost($body);
        return $this->isLastResponseSuccess();
    }

    public function sendPut(array $body): ?array
    {
        $this->method = self::M_PUT;
        return $this->getResponse($this->curlOptions($body));
    }

    public function sendPatch(array $body): ?array
    {
        $this->method = self::M_PATCH;
        return $this->getResponse($this->curlOptions($body));
    }

    public function sendPatchVsCode(array $body): bool
    {
        $this->sendPatch($body);
        return $this->isLastResponseSuccess();
    }

    protected function getResponse(?CurlOptionsDto $guzzleOptions = null): ?array
    {
        $responseStr = $this->getResponseString($guzzleOptions);

        if (is_null($responseStr)) {
            return null;
        }

        try {
            $response = null;
            if (self::CONTENT_TYPE_JSON == $this->responseFormatMode()) {
                $response = json_decode($responseStr, true, 512, JSON_THROW_ON_ERROR);
            }

            return $response;
        }
        catch (\JsonException) {
            $this->logAnError(self::LOG_ERR, 'Bad JSON in ' . Str::aClass($this),  ['response' => $responseStr, 'endpoint' => $this->apiEndPoint()]);
        }
        catch (\Throwable $ex) {
            $this->logAnError(self::LOG_CRITIC, 'BaseApiModel parse-json ' . Str::aClass($this),  ['ex' => ExceptionLog::toArray($ex)]);
        }
        return null;
    }

    private function getResponseString(CurlOptionsDto $opts): ?string
    {
        try {
            $url = $this->apiUri();
            $url .= $opts->query ? (str_contains($url, '?') ? '&' : '?') . $opts->query : '';

            $curl = curl_init();
            curl_setopt_array($curl, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => $opts->headers,
                    CURLOPT_CUSTOMREQUEST => $this->method,
                ] + ($opts->timeout > 0.01 ? [CURLOPT_TIMEOUT_MS => (int)(1000.0 * $opts->timeout)] : [])
                + ($opts->body ? [CURLOPT_POSTFIELDS => $opts->body] : [])
            );

            $responseStr = curl_exec($curl);

            $curlErrNo = curl_errno($curl);
            $curlErrMsg = curl_error($curl);
            $httpCode = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);

            curl_close($curl);

            $this->timeoutOnce = null;
            $this->retriesOnceCount = null;
            $this->retriesOnceAwaitSecs = null;

            $this->lastResponseCode = $httpCode;

            if ($curlErrNo !== 0) {
                $this->lastErrorMessage = $curlErrMsg;
                throw new \RuntimeException('cURL error: ' . $curlErrMsg, $curlErrNo);
            }

            if ($this->isQuiteModeOnceForGetNotFoundApiError && 404 === $httpCode) {
                $this->logAnError(self::LOG_ERR, Str::aClass($this), [
                    'api' => $this->apiUri(),
                    'queryForced' => $this->forcedQuery(),
                ]);
                return null;
            }

            if ($httpCode >= 400) {
                $this->lastErrorMessage = is_string($responseStr) ? $responseStr : '';
                throw new \RuntimeException('HTTP error: ' . $httpCode, $httpCode);
            }

            return is_string($responseStr) ? $responseStr : null;
        }
        catch (\Throwable $ex) {
            $logArr = [
                'endpoint' => $this->apiEndPoint(),
                'ex' => ExceptionLog::toArray($ex),
                'response' => is_string($responseStr ?? null) ? $responseStr : null,
                'http_code' => $httpCode ?? null,
                'curl_errno' => $curlErrNo ?? null,
                'curl_error' => $curlErrMsg ?? null,
            ];
            $msg = static::logMsgForGuzzleException($httpCode, $curlErrNo, $curlErrMsg, $ex);
            $this->logAnError(self::LOG_ERR, $msg, $logArr);

            if ($this->retriesOnceCount > 0 && $this->retriesOnceAwaitSecs) {
                sleep($this->retriesOnceAwaitSecs);
                $this->retriesOnceCount--;
                return $this->getResponseString($opts);
            }
        }

        return null;
    }

    public function apiUri(): string
    {
        return $this->apiServer() . $this->apiEndPoint();
    }

    public function apiHeaders(): array
    {
        return [];
    }

    public function apiBearer(): ?string
    {
        return null;
    }

    public function apiBasic(): ?string
    {
        return null;
    }

    protected function requestFormatMode(): string
    {
        return self::CONTENT_TYPE_JSON;
    }

    private function responseFormatMode(): string // make protected when support other formats
    {
        return self::CONTENT_TYPE_JSON;
    }

    protected function finalQuery(): ?string
    {
        return $this->forcedQuery() ? http_build_query($this->forcedQuery()) : null;
    }

    protected function curlOptions(?array $body = null): CurlOptionsDto
    {
        if ($body && self::CONTENT_TYPE_JSON == $this->requestFormatMode()) {
            $postBody = json_encode($body);
        }
        else if ($body && self::CONTENT_TYPE_FORM_DATA == $this->requestFormatMode()) {
            $postBody = $body;
        }

        return new CurlOptionsDto(
            headers: $this->finalHeaders(),
            query: $this->finalQuery(),
            body: $postBody ?? null,
            timeout: $this->timeoutOnce ?? $this->timeoutDefault ?? 0.0,
        );
    }

    protected function finalUnformattedHeaders(): array
    {
        $base = [
            'Accept' => $this->responseFormatMode(),
        ];
        if (self::CONTENT_TYPE_JSON == $this->requestFormatMode()) {
            $base['Content-Type'] = self::CONTENT_TYPE_JSON;
        }
        // IMPORTANT: for multipart/form-data do NOT set Content-Type manually (boundary is needed)
        if (self::CONTENT_TYPE_FORM_DATA == $this->requestFormatMode()) {
            unset($base['Content-Type']);
        }

        if ($token = $this->apiBearer()) {
            $base['Authorization'] = 'Bearer ' . $token;
        } else if ($token = $this->apiBasic()) {
            $base['Authorization'] = 'Basic ' . $token;
        }

        return array_merge(static::HEADERS_DEFAULT, $base, $this->apiHeaders());
    }

    protected function finalHeaders(): array
    {
        $headers = [];
        foreach ($this->finalUnformattedHeaders() as $name => $val) {
            $headers[] = $name . ': ' . $val;
        }
        return $headers;
    }

    private function logMsgForGuzzleException(int $httpCode, int $curlErrNo, string $curlErrMsg, \Throwable $exception): string
    {
        $code = $httpCode ?: $exception->getCode();
        $prefix = static::logPrefixForGuzzleException($code, $curlErrNo);

        if ($httpCode > 0) {
            return $prefix . $this->apiEndPointLogMsg() . ' ' . $httpCode . ' ' . $this->apiServer();
        }

        $msg = $curlErrMsg !== '' ? $curlErrMsg : $exception->getMessage();
        return $prefix . $this->apiEndPointLogMsg() . ' ' . Str::aClass($exception) . ' - ' . $exception->getCode() . ' ' . $msg;
    }

    private static function logPrefixForGuzzleException(?int $code, int $curlErrNo): string
    {
        if (404 == $code) {
            return 'Not-Found-API ';
        }
        if ($curlErrNo !== 0) {
            return 'Connection-Fail-API ';
        }
        if (!empty($code) && $code >= 500) {
            return 'Critical-Fail-API ';
        }
        if (!empty($code) && $code >= 400) {
            return 'Fail-API ';
        }
        return 'Unexpected-Fail-API ';
    }
}
