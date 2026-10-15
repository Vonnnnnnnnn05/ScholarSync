<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class GranteeFileParserService
{
    /**
     * Send an uploaded file to the Python microservice /parse-file endpoint.
     *
     * @param UploadedFile $file
     * @return array{
     *     success: bool,
     *     file_type: string,
     *     original_headers: array<int, string>,
     *     normalized_headers: array<int, string>,
     *     unmapped_headers: array<int, string>,
     *     missing_fields: array<int, string>,
     *     rows: array<int, array<string, mixed>>,
     *     total_rows: int,
     *     parse_warnings: array<int, string>
     * }
     * @throws ValidationException
     */
    public function parse(UploadedFile $file): array
    {
        $baseUrl = rtrim((string) config('services.masterlist_verifier.url', 'http://127.0.0.1:8001'), '/');
        $timeout = (int) config('services.masterlist_verifier.timeout', 30);

        try {
            $response = Http::timeout($timeout)
                ->retry(2, 250, throw: false)
                ->attach(
                    'file',
                    file_get_contents($file->getRealPath()),
                    $file->getClientOriginalName()
                )
                ->post("{$baseUrl}/parse-file");
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'file' => 'Failed to connect to the parsing microservice: ' . $e->getMessage(),
            ]);
        }

        if ($response->status() === 422) {
            $data = $response->json();
            throw ValidationException::withMessages([
                'file' => $data['error'] ?? 'The uploaded file does not contain valid tabular grantee data.',
            ]);
        }

        if ($response->failed()) {
            $data = $response->json();
            $msg = $data['error'] ?? 'An error occurred while parsing the grantee file.';
            throw ValidationException::withMessages(['file' => $msg]);
        }

        $json = $response->json();
        if (!($json['success'] ?? false) || empty($json['rows'])) {
            throw ValidationException::withMessages([
                'file' => 'No valid grantee records could be extracted from this file.',
            ]);
        }

        return $json;
    }

    /**
     * Send multiple uploaded files to the Python microservice /parse-files endpoint.
     *
     * @param array<int, UploadedFile> $files
     * @return array<string, mixed>
     * @throws ValidationException
     */
    public function parseFiles(array $files): array
    {
        if (count($files) === 1) {
            return $this->parse($files[0]);
        }

        $baseUrl = rtrim((string) config('services.masterlist_verifier.url', 'http://127.0.0.1:8001'), '/');
        $timeout = (int) config('services.masterlist_verifier.timeout', 60);

        try {
            $req = Http::timeout($timeout)->retry(2, 250, throw: false);
            foreach ($files as $file) {
                $req->attach('files', file_get_contents($file->getRealPath()), $file->getClientOriginalName());
            }
            $response = $req->post("{$baseUrl}/parse-files");
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'files' => 'Failed to connect to the parsing microservice: ' . $e->getMessage(),
            ]);
        }

        if ($response->status() === 422) {
            $data = $response->json();
            throw ValidationException::withMessages([
                'files' => $data['error'] ?? 'The uploaded files do not contain valid tabular grantee data.',
            ]);
        }

        if ($response->failed()) {
            $data = $response->json();
            $msg = $data['error'] ?? 'An error occurred while parsing the grantee files.';
            throw ValidationException::withMessages(['files' => $msg]);
        }

        $json = $response->json();
        if (!($json['success'] ?? false) || empty($json['rows'])) {
            throw ValidationException::withMessages([
                'files' => 'No valid grantee records could be extracted from these files.',
            ]);
        }

        return $json;
    }
}
