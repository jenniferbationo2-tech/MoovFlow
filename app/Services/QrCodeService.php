<?php

namespace App\Services;

use App\Models\Inscription;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
    public function generate(Inscription $inscription): string
    {
        $code = $inscription->qr_code ?: Str::upper((string) Str::ulid());

        Storage::disk('public')->put(
            $this->path($code),
            QrCode::format('svg')->size(320)->margin(1)->generate($code)
        );

        if ($inscription->qr_code !== $code) {
            $inscription->update([
                'qr_code' => $code,
            ]);
        }

        return $code;
    }

    public function verify(string $code): ?Inscription
    {
        $payload = trim($code);
        $decoded = json_decode($payload, true);

        if (is_array($decoded)) {
            $payload = (string) ($decoded['code'] ?? $decoded['qr_code'] ?? $decoded['token'] ?? $payload);
        }

        return Inscription::query()
            ->with(['user', 'evenement', 'tarif', 'paiement.facture', 'presence'])
            ->where('qr_code', $payload)
            ->first();
    }

    public function publicUrl(?string $code): ?string
    {
        if (! $code || ! Storage::disk('public')->exists($this->path($code))) {
            return null;
        }

        return Storage::url($this->path($code));
    }

    private function path(string $code): string
    {
        return 'qrcodes/'.$code.'.svg';
    }
}