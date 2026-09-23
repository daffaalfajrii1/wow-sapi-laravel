<?php

namespace App\Exceptions;

use RuntimeException;

class AiServiceException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $codeKey = 'AI_ERROR',
        public readonly int $httpStatus = 422,
        public readonly ?array $payload = null,
    ) {
        parent::__construct($message);
    }

    public static function unreachable(): self
    {
        return new self(
            'Tidak dapat terhubung ke layanan AI. Pastikan FastAPI berjalan di port 8003.',
            'UNREACHABLE',
            503,
        );
    }

    public static function timeout(): self
    {
        return new self(
            'Layanan AI tidak merespons. Silakan coba lagi beberapa saat.',
            'TIMEOUT',
            504,
        );
    }

    public static function invalidJson(): self
    {
        return new self('Respons layanan AI tidak valid.', 'INVALID_JSON', 502);
    }

    public static function fromFastApi(string $code, ?string $fallback = null, int $status = 422, ?array $payload = null): self
    {
        $messages = [
            'NO_COW' => 'Tidak ada sapi terdeteksi pada foto. Unggah foto yang menampilkan sapi dengan jelas.',
            'MULTIPLE_COWS' => 'Foto harus berisi tepat 1 sapi untuk estimasi bobot. Terdeteksi lebih dari satu sapi.',
            'UNSUPPORTED_TYPE' => 'Format gambar tidak didukung. Gunakan JPG atau PNG.',
            'INVALID_IMAGE' => 'Berkas gambar tidak valid atau rusak.',
            'FILE_TOO_LARGE' => 'Ukuran berkas terlalu besar. Maksimal sekitar 10 MB.',
            'MODEL_NOT_LOADED' => 'Layanan AI belum siap. Coba beberapa saat lagi.',
        ];

        return new self(
            $messages[$code] ?? $fallback ?? 'Pemeriksaan AI gagal diproses.',
            $code,
            $status,
            $payload,
        );
    }
}
