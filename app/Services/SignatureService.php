<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SignatureService
{
    /**
     * Save a base64 data URI (from HTML5 Canvas signature pad) as a PNG signature file for the user.
     */
    public function saveSignatureFromDataUri(User $user, string $dataUri): string
    {
        $dataUri = trim($dataUri);

        if (empty($dataUri)) {
            throw new InvalidArgumentException('Signature data cannot be empty.');
        }

        // Parse data URI scheme if present (e.g. data:image/png;base64,iVBORw0KGgo...)
        if (preg_match('/^data:image\/(\w+);base64,(.+)$/s', $dataUri, $matches)) {
            $extension = strtolower($matches[1]) === 'jpeg' ? 'jpg' : strtolower($matches[1]);
            $base64Data = $matches[2];
        } else {
            $extension = 'png';
            $base64Data = $dataUri;
        }

        $binary = base64_decode(str_replace(' ', '+', $base64Data), true);

        if ($binary === false || strlen($binary) < 50) {
            throw new InvalidArgumentException('Invalid signature image data provided.');
        }

        // Delete previous signature file if it exists
        $this->cleanupExistingFile($user);

        $filename = 'signatures/user_'.$user->id.'_'.time().'_'.Str::random(6).'.'.$extension;
        Storage::disk('local')->put($filename, $binary);

        $user->forceFill([
            'signature_path' => $filename,
            'signature_updated_at' => now(),
        ])->save();

        return $filename;
    }

    /**
     * Save an uploaded file as the user's digital signature.
     */
    public function saveSignatureFromUploadedFile(User $user, UploadedFile $file): string
    {
        $this->cleanupExistingFile($user);

        $path = $file->store('signatures', 'local');

        $user->forceFill([
            'signature_path' => $path,
            'signature_updated_at' => now(),
        ])->save();

        return $path;
    }

    /**
     * Delete the user's stored digital signature file and reset attributes.
     */
    public function deleteSignature(User $user): void
    {
        $this->cleanupExistingFile($user);

        $user->forceFill([
            'signature_path' => null,
            'signature_updated_at' => null,
        ])->save();
    }

    /**
     * Get the signature data URI for a given user.
     */
    public function getSignatureDataUri(User $user): ?string
    {
        return $user->signatureDataUri();
    }

    /**
     * Safely delete the user's existing signature file from storage if present.
     */
    protected function cleanupExistingFile(User $user): void
    {
        if (! empty($user->signature_path) && Storage::disk('local')->exists($user->signature_path)) {
            Storage::disk('local')->delete($user->signature_path);
        }
    }
}
