<?php

namespace ModelsAlpha;

use ModelsAlpha\Core\CurlOptionsDto;
use ModelsAlpha\Exceptions\ApiException;
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
    protected const string M_PATCH = 'PATCH';
    protected const string M_DELETE = 'DELETE';
    protected const string M_OPTIONS = 'OPTIONS';
    protected const string M_HEAD = 'HEAD';

    protected const string LOG_ERROR = 'ERROR';
    protected const string LOG_CRITICAL = 'CRITICAL';

    private string $method = self::M_GET;
    protected float $timeoutDefault = 42.0;

    private int $lastResponseCode;
    private string $lastErrorMessage;

    /** MAIN settings */

    abstract public function apiServer(): string;
    abstract public function apiEndPoint(): string;
    abstract public function forcedQuery(): array;
    abstract protected function logAnError(string $level, string $message, array $logArr): void;

    /** SECONDARY settings */

    public function apiHeaders(): array { return []; }
    public function apiBearer(): ?string { return null; }
    public function apiBasic(): ?string { return null; }

    protected function requestFormatMode(): string
    {
        return self::CONTENT_TYPE_JSON;
    }

    protected function responseFormatMode(): string
    {
        return self::CONTENT_TYPE_JSON;
    }

    /** LOG settings */

    protected function apiEndPointForLog(): string {
        return $this->apiEndPoint();
    }

    /** REQUEST ONCE settings */

    private ?float $timeoutOnce = null;
    protected final function withTimeout(float $timeout): static
    {
        $this->timeoutOnce = $timeout > 0.0 ? $timeout : null;
        return $this;
    }

    private ?int $retriesOnceCount = null;
    private ?int $retriesOnceAwaitSecs = null;
    protected final function withRetries(int $count, int $await = 2): static
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

    /** RESPONSE status */

    public final function isLastResponseSuccess(): bool
    {
        return !empty($this->lastResponseCode) && $this->lastResponseCode >= 200 && $this->lastResponseCode < 300;
    }

    public final function errorCode(): int
    {
        return $this->lastResponseCode ?? 700;
    }

    public final function errorMsg(): string
    {
        return $this->lastErrorMessage ?? '';
    }

    /** REQUEST send */

    public static final function apiGet(): static
    {
        $model = new static();
        $response = $model->sendGet();
        if (is_null($response)) {
            throw ApiException::api(Str::aClass($model) . ' GET', $model->errorMsg(), $model->errorCode());
        }
        return static::fromArray($response);
    }

    public static final function justGet(): ?static
    {
        $model = new static();
        $response = $model->sendGet();
        return is_null($response) ? null : static::fromArray($response);
    }

    public final function sendGet(): ?array
    {
        $this->method = self::M_GET;
        return $this->getResponse();
    }

    public final function successGet(): bool
    {
        $this->sendGet();
        return $this->isLastResponseSuccess();
    }

    public static final function apiPost(array $body): static
    {
        $model = new static();
        $response = $model->sendPost($body);
        if (is_null($response)) {
            throw ApiException::api(Str::aClass($model) . ' POST', $model->errorMsg(), $model->errorCode());
        }
        return static::fromArray($response);
    }

    public static final function justPost(array $body): ?static
    {
        $model = new static();
        $response = $model->sendPost($body);
        return is_null($response) ? null : static::fromArray($response);
    }

    public final function sendPost(array $body): ?array
    {
        $this->method = self::M_POST;
        return $this->getResponse($this->curlOptions($body));
    }

    public final function successPost(array $body): bool
    {
        $this->sendPost($body);
        return $this->isLastResponseSuccess();
    }

    public static final function apiPut(array $body): static
    {
        $model = new static();
        $response = $model->sendPut($body);
        if (is_null($response)) {
            throw ApiException::api(Str::aClass($model) . ' PUT', $model->errorMsg(), $model->errorCode());
        }
        return static::fromArray($response);
    }

    public static final function justPut(array $body): ?static
    {
        $model = new static();
        $response = $model->sendPut($body);
        return is_null($response) ? null : static::fromArray($response);
    }

    public final function sendPut(array $body): ?array
    {
        $this->method = self::M_PUT;
        return $this->getResponse($this->curlOptions($body));
    }

    public final function successPut(array $body): bool
    {
        $this->sendPut($body);
        return $this->isLastResponseSuccess();
    }

    public static final function apiPatch(array $body): static
    {
        $model = new static();
        $response = $model->sendPatch($body);
        if (is_null($response)) {
            throw ApiException::api(Str::aClass($model) . ' PATCH', $model->errorMsg(), $model->errorCode());
        }
        return static::fromArray($response);
    }

    public static final function justPatch(array $body): ?static
    {
        $model = new static();
        $response = $model->sendPatch($body);
        return is_null($response) ? null : static::fromArray($response);
    }

    public final function sendPatch(array $body): ?array
    {
        $this->method = self::M_PATCH;
        return $this->getResponse($this->curlOptions($body));
    }

    public final function successPatch(array $body): bool
    {
        $this->sendPatch($body);
        return $this->isLastResponseSuccess();
    }

    /** REQUEST send TECH */

    public function apiUri(): string { return $this->apiServer() . $this->apiEndPoint(); }

    protected final function getResponse(?CurlOptionsDto $opts = null): ?array
    {
        $responseStr = $this->sendRequestTakeResponse($opts ?? $this->curlOptions());
        if (is_null($responseStr)) {
            return null;
        }

        return match ($this->responseFormatMode()) {
            self::CONTENT_TYPE_JSON => $this->parseResponseStringJson($responseStr),
            default => null,
        };
    }

    private function parseResponseStringJson(string $responseStr): ?array
    {
        try {
            return json_decode($responseStr, true, 512, JSON_THROW_ON_ERROR);
        }
        catch (\JsonException) {
            $this->logAnError(self::LOG_ERROR, static::LOG_PREFIX_JSON . ' ' . Str::aClass($this) . ' ' . $this->apiServer() . $this->apiEndPointForLog(),  ['response' => $responseStr, 'code' => $this->errorCode()]);
        }
        catch (\Throwable $ex) {
            $this->logAnError(self::LOG_CRITICAL, static::LOG_PREFIX_JSON . ' ' . Str::aClass($this) . ' ' . $this->apiServer() . $this->apiEndPointForLog(),  ['ex' => ExceptionLog::toArray($ex), 'response' => $responseStr, 'code' => $this->errorCode()]);
        }
        return null;
    }

    private function sendRequestTakeResponse(CurlOptionsDto $opts): ?string
    {
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
        $this->lastResponseCode = $httpCode = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);

        $curlErrNo = curl_errno($curl);
        $this->lastErrorMessage = $curlErrMsg = curl_error($curl);

        if ($httpCode >= 200 && $httpCode < 300 && !$curlErrNo) {
            $this->timeoutOnce = null;
            $this->retriesOnceCount = null;
            $this->retriesOnceAwaitSecs = null;

            return is_string($responseStr) ? $responseStr : null;
        }

        if (is_string($responseStr)) {
            $this->lastErrorMessage .= trim($this->lastErrorMessage) ? ' ## RESPONSE ## ' . $responseStr : $responseStr;
        }

        $this->logAnError(self::LOG_ERROR, $this->logMsg($httpCode, $curlErrNo), $this->logBody($responseStr, $curlErrNo, $curlErrMsg));

        if ($this->retriesOnceCount > 0) {
            sleep($this->retriesOnceAwaitSecs);
            $this->retriesOnceCount--;
            return $this->sendRequestTakeResponse($opts);
        }
        return null;
    }

    /** CURL */

    protected final function curlOptions(?array $body = null): CurlOptionsDto
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

    protected function finalQuery(): ?string
    {
        return $this->forcedQuery() ? http_build_query($this->forcedQuery()) : null;
    }

    protected final function finalHeaders(): array
    {
        $headers = [];
        foreach ($this->finalUnformattedHeaders() as $name => $val) {
            $headers[] = $name . ': ' . $val;
        }
        return $headers;
    }

    protected final function finalUnformattedHeaders(): array
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

    /** LOGs */

    protected function logMsg(int $httpCode, int $curlErrNo): string
    {
        return $this->logPrefix($httpCode, $curlErrNo) . ' ' .
            Str::aClass($this) . ' ' .
            $httpCode . ' ' .
            $this->apiServer() . $this->apiEndPointForLog();
    }

    protected function logBody(mixed $responseStr, int $curlErrNo, string $curlErrMsg): array
    {
        $logArr = [] + ($curlErrNo ? ['curl_errno' => $curlErrNo] : []) + ($curlErrMsg ? ['curl_error' => $curlErrMsg] : []);
        if (is_string($responseStr)) {

            if (!$responseStr && !$logArr) {
                return ['response' => 'EMPTY'];
            }
            else if (!$responseStr) {
                $logArr['response'] = 'EMPTY';
                return $logArr;
            }

            try {
                $responseArr = json_decode($responseStr, true, 512, JSON_THROW_ON_ERROR);
                if ($logArr) {
                    $logArr['response'] = $responseArr;
                } else {
                    $logArr = $responseArr;
                }
            }
            catch (\Throwable) {
                $logArr['response'] = $responseStr;
            }
        }
        return $logArr;
    }

    protected const string LOG_PREFIX_CURL = 'Network-Fail-API';
    protected const string LOG_PREFIX_404 = 'Not-Found-API';
    protected const string LOG_PREFIX_500S = 'Server-Fail-API';
    protected const string LOG_PREFIX_400S = 'Fail-API';
    protected const string LOG_PREFIX_ELSE = 'Unexpected-Fail-API';
    protected const string LOG_PREFIX_JSON = 'JSON-Parse-Fail-API';
    protected function logPrefix(int $httpCode, int $curlErrNo): string
    {
        return match (true) {
            !!$curlErrNo     => static::LOG_PREFIX_CURL,
            $httpCode == 404 => static::LOG_PREFIX_404,
            $httpCode >= 500 => static::LOG_PREFIX_500S,
            $httpCode >= 400 => static::LOG_PREFIX_400S,
            default          => static::LOG_PREFIX_ELSE,
        };
    }
}
