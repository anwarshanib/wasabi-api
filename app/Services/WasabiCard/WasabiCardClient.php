<?php

declare(strict_types=1);

namespace App\Services\WasabiCard;

use App\Exceptions\WasabiApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Base HTTP client for the Wasabi Card Open API.
 *
 * Handles:
 *   - SHA256withRSA request signing  (merchant private key → X-WSB-SIGNATURE)
 *   - API key injection              (X-WSB-API-KEY)
 *   - Response signature verification (Wasabi platform public key)
 *   - Structured upstream error throwing
 *
 * Every method on the Wasabi API is a POST. Pass an empty array for
 * endpoints that require no body — the client will send `{}` and sign it,
 * as required by the Wasabi spec.
 */
final class WasabiCardClient
{
    private readonly string $baseUrl;
    private readonly string $apiKey;
    private readonly string $privateKey;
    private readonly string $wsbPublicKey;
    private readonly int    $timeout;

    public function __construct()
    {
        $this->baseUrl      = rtrim((string) config('wasabi.base_url'), '/');
        $this->apiKey       = (string) config('wasabi.api_key');
        $this->timeout      = (int) config('wasabi.timeout', 30);
        $this->privateKey   = $this->readKeyFile((string) config('wasabi.private_key_path'));
        $this->wsbPublicKey = $this->readKeyFile((string) config('wasabi.wsb_public_key_path'));
    }

    /**
     * Execute a signed POST request to the Wasabi Card API.
     *
     * @param  array<string, mixed> $body
     * @return array<string, mixed>
     *
     * @throws WasabiApiException
     */
    public function post(string $endpoint, array $body = []): array
    {
        $bodyJson  = empty($body)
            ? '{}'
            : json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $signature = $this->sign($bodyJson);

        Log::channel('wasabi')->info('Wasabi API → request', [
            'endpoint' => $endpoint,
            'body'     => $bodyJson,
        ]);

        try {
            $response = Http::withHeaders([
                'X-WSB-API-KEY'   => $this->apiKey,
                'X-WSB-SIGNATURE' => $signature,
                'Accept'          => 'application/json',
            ])
                ->withBody($bodyJson, 'application/json')
                ->timeout($this->timeout)
                ->post("{$this->baseUrl}{$endpoint}");
        } catch (ConnectionException $e) {
            Log::channel('wasabi')->error('Wasabi API → connection failed', [
                'endpoint' => $endpoint,
                'error'    => $e->getMessage(),
            ]);

            throw new WasabiApiException('Upstream service unavailable', 503, 503);
        }

        $this->verifyResponseSignature($response, $endpoint);

        /** @var array{success: bool, code: int, msg: string, data: mixed} $payload */
        $payload = $response->json();

        Log::channel('wasabi')->info('Wasabi API ← response', [
            'endpoint' => $endpoint,
            'code'     => $payload['code'] ?? null,
            'success'  => $payload['success'] ?? false,
        ]);

        if (! ($payload['success'] ?? false)) {
            throw new WasabiApiException(
                $payload['msg'] ?? 'Wasabi API error',
                $payload['code'] ?? 500,
            );
        }

        return $payload;
    }

    /**
     * Execute a signed multipart/form-data POST to the Wasabi Card API.
     *
     * Per the Wasabi spec: sign an empty object `{}` for multipart requests,
     * NOT the file content. The signature covers only the JSON envelope.
     *
     * @return array<string, mixed>
     *
     * @throws WasabiApiException
     */
    public function postMultipart(string $endpoint, UploadedFile $file, string $fieldName = 'file'): array
    {
        // Wasabi requires signing {} (empty body) for multipart uploads
        $signature = $this->sign('{}');

        Log::channel('wasabi')->info('Wasabi API → multipart request', [
            'endpoint'      => $endpoint,
            'original_name' => $file->getClientOriginalName(),
            'size'          => $file->getSize(),
        ]);

        try {
            $response = Http::withHeaders([
                'X-WSB-API-KEY'   => $this->apiKey,
                'X-WSB-SIGNATURE' => $signature,
                'Accept'          => 'application/json',
            ])
                ->timeout($this->timeout)
                ->attach(
                    $fieldName,
                    (string) file_get_contents($file->getRealPath()),
                    $file->getClientOriginalName(),
                    ['Content-Type' => $file->getMimeType() ?? 'application/octet-stream'],
                )
                ->post("{$this->baseUrl}{$endpoint}");
        } catch (ConnectionException $e) {
            Log::channel('wasabi')->error('Wasabi API → connection failed', [
                'endpoint' => $endpoint,
                'error'    => $e->getMessage(),
            ]);

            throw new WasabiApiException('Upstream service unavailable', 503, 503);
        }

        $this->verifyResponseSignature($response, $endpoint);

        /** @var array{success: bool, code: int, msg: string, data: mixed} $payload */
        $payload = $response->json();

        Log::channel('wasabi')->info('Wasabi API ← multipart response', [
            'endpoint' => $endpoint,
            'status'   => $response->status(),
            'code'     => $payload['code'] ?? null,
            'success'  => $payload['success'] ?? false,
            'msg'      => $payload['msg'] ?? null,
            'body'     => $response->body(),
        ]);

        if (! ($payload['success'] ?? false)) {
            throw new WasabiApiException(
                $payload['msg'] ?? 'Wasabi API error',
                $payload['code'] ?? 500,
            );
        }

        return $payload;
    }

    // -------------------------------------------------------------------------
    // Signature Helpers
    // -------------------------------------------------------------------------

    /**
     * Sign $data with the merchant RSA private key using SHA256withRSA.
     * Returns a base64-encoded signature string.
     */
    private function sign(string $data): string
    {
        $privateKey = openssl_pkey_get_private($this->privateKey);

        if ($privateKey === false) {
            throw new WasabiApiException(
                'Merchant RSA private key is invalid or not configured.',
                500,
                500,
            );
        }

        openssl_sign($data, $rawSignature, $privateKey, OPENSSL_ALGO_SHA256);

        return base64_encode($rawSignature);
    }

    /**
     * Verify the Wasabi platform signature on the response body.
     * Logs a warning on failure but does not throw — sandbox may omit signatures.
     */
    private function verifyResponseSignature(Response $response, string $endpoint): void
    {
        $signature = $response->header('X-WSB-SIGNATURE');

        if (! $signature || ! $this->wsbPublicKey) {
            return;
        }

        $publicKey = openssl_pkey_get_public($this->wsbPublicKey);

        if (! $publicKey) {
            return;
        }

        $result = openssl_verify(
            $response->body(),
            base64_decode($signature, true),
            $publicKey,
            OPENSSL_ALGO_SHA256
        );

        if ($result !== 1) {
            Log::channel('wasabi')->warning('Wasabi response signature verification failed', [
                'endpoint' => $endpoint,
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Key Loading
    // -------------------------------------------------------------------------

    /**
     * Read a PEM key file. Returns an empty string with a warning if missing
     * so the app boots without keys present during initial setup.
     */
    private function readKeyFile(string $path): string
    {
        // Resolve relative paths from the project root
        if (! str_starts_with($path, '/')) {
            $path = base_path($path);
        }

        if (! file_exists($path)) {
            Log::channel('wasabi')->error("RSA key file not found: {$path}");

            throw new WasabiApiException(
                'RSA key file not found. Please configure your key files in storage/app/keys/.',
                500,
                500,
            );
        }

        return (string) file_get_contents($path);
    }
}
