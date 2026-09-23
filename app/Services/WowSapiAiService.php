<?php

namespace App\Services;

use App\Exceptions\AiServiceException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class WowSapiAiService
{
    public function health(): array
    {
        try {
            $response = Http::acceptJson()
                ->timeout(8)
                ->get($this->url('health'));

            if (! $response->successful()) {
                return ['ok' => false, 'reachable' => true, 'status' => $response->status()];
            }

            return array_merge(['reachable' => true], $response->json() ?? ['ok' => true]);
        } catch (Throwable) {
            return ['ok' => false, 'reachable' => false];
        }
    }

    public function predictWeight(string $absolutePath, string $filename): array
    {
        return $this->postFile('weight', $absolutePath, $filename);
    }

    public function predictLumpy(string $absolutePath, string $filename): array
    {
        return $this->postFile('lumpy', $absolutePath, $filename);
    }

    public function analyzeCombined(string $absolutePath, string $filename): array
    {
        return $this->postFile('analyze', $absolutePath, $filename, allowPartial: true);
    }

    public function storeUpload(UploadedFile $file, string $directory): array
    {
        $path = $file->store($directory, 'public');

        return [
            'path' => $path,
            'absolute' => Storage::disk('public')->path($path),
            'filename' => $file->getClientOriginalName() ?: 'upload.jpg',
        ];
    }

    protected function postFile(string $key, string $absolutePath, string $filename, bool $allowPartial = false): array
    {
        if (! is_readable($absolutePath)) {
            throw new AiServiceException('Berkas gambar tidak dapat dibaca.', 'INVALID_IMAGE', 422);
        }

        $contents = file_get_contents($absolutePath);
        $timeout = (int) config('wowsapi.ai.timeout', 60);

        try {
            $response = Http::acceptJson()
                ->timeout($timeout)
                ->attach('file', $contents, $filename)
                ->post($this->url($key));
        } catch (ConnectionException $e) {
            $message = strtolower($e->getMessage());
            Log::warning('WOW SAPI AI connection', ['key' => $key, 'error' => $e->getMessage()]);
            if (str_contains($message, 'timed out') || str_contains($message, 'timeout')) {
                throw AiServiceException::timeout();
            }
            throw AiServiceException::unreachable();
        } catch (RequestException $e) {
            if (str_contains(strtolower($e->getMessage()), 'timeout')) {
                throw AiServiceException::timeout();
            }
            throw AiServiceException::unreachable();
        } catch (Throwable $e) {
            $message = strtolower($e->getMessage());
            if (str_contains($message, 'timed out') || str_contains($message, 'timeout')) {
                throw AiServiceException::timeout();
            }
            Log::warning('WOW SAPI AI error', ['key' => $key, 'error' => $e->getMessage()]);
            throw AiServiceException::unreachable();
        }

        $json = $response->json();
        if (! is_array($json)) {
            if ($response->status() >= 500) {
                throw new AiServiceException('Terjadi gangguan pada layanan AI.', 'SERVER_ERROR', 502);
            }
            throw AiServiceException::invalidJson();
        }

        if ($response->successful() && ($json['ok'] ?? true)) {
            return $json;
        }

        $code = (string) ($json['code'] ?? 'AI_ERROR');
        if ($allowPartial && isset($json['ok']) && $json['ok'] === true) {
            return $json;
        }

        throw AiServiceException::fromFastApi(
            $code,
            is_string($json['message'] ?? null) ? $json['message'] : null,
            $response->status() >= 400 ? $response->status() : 422,
            $json,
        );
    }

    protected function url(string $key): string
    {
        $base = rtrim((string) config('wowsapi.ai.url'), '/');
        $path = (string) config("wowsapi.ai.paths.{$key}");

        return $base.(str_starts_with($path, '/') ? $path : '/'.$path);
    }
}
