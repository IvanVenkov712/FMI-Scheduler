<?php

declare(strict_types=1);

class FileUploader
{
    public function upload(array $file, string $destinationDir, array $allowedExtensions = [], ?int $maxFileSize = null, ?string $targetName = null): array
    {
        $this->assertUpload($file);

        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if ($allowedExtensions !== [] && !in_array($extension, array_map('strtolower', $allowedExtensions), true)) {
            throw new RuntimeException('Uploaded file type is not allowed.');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($maxFileSize !== null && $size > $maxFileSize) {
            throw new RuntimeException('Uploaded file exceeds the allowed size.');
        }

        if (!is_dir($destinationDir) && !mkdir($destinationDir, 0775, true) && !is_dir($destinationDir)) {
            throw new RuntimeException('Upload directory could not be created.');
        }

        $safeName = $targetName !== null && $targetName !== ''
            ? $this->sanitizeFileName($targetName)
            : $this->generateFileName(pathinfo((string) ($file['name'] ?? 'upload'), PATHINFO_FILENAME), $extension);

        $targetPath = rtrim($destinationDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName;

        if (!move_uploaded_file((string) $file['tmp_name'], $targetPath)) {
            throw new RuntimeException('Uploaded file could not be moved to the destination.');
        }

        return [
            'original_name' => (string) ($file['name'] ?? ''),
            'stored_name' => $safeName,
            'path' => $targetPath,
            'extension' => $extension,
            'size' => $size,
            'mime_type' => (string) ($file['type'] ?? ''),
        ];
    }

    public function uploadMany(array $files, string $destinationDir, array $allowedExtensions = [], ?int $maxFileSize = null): array
    {
        $normalized = $this->normalizeFiles($files);
        $uploaded = [];

        foreach ($normalized as $file) {
            $uploaded[] = $this->upload($file, $destinationDir, $allowedExtensions, $maxFileSize);
        }

        return $uploaded;
    }

    private function normalizeFiles(array $files): array
    {
        if (!isset($files['name']) || !is_array($files['name'])) {
            return [$files];
        }

        $normalized = [];
        $count = count($files['name']);

        for ($index = 0; $index < $count; $index++) {
            $normalized[] = [
                'name' => $files['name'][$index] ?? null,
                'type' => $files['type'][$index] ?? null,
                'tmp_name' => $files['tmp_name'][$index] ?? null,
                'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $files['size'][$index] ?? 0,
            ];
        }

        return $normalized;
    }

    private function assertUpload(array $file): void
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException($this->uploadErrorMessage((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE)));
        }

        if (!isset($file['tmp_name']) || !is_string($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('Invalid uploaded file.');
        }
    }

    private function generateFileName(string $baseName, string $extension): string
    {
        $baseName = $this->sanitizeFileName($baseName);
        $unique = $baseName . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4));

        return $extension !== '' ? $unique . '.' . $extension : $unique;
    }

    private function sanitizeFileName(string $name): string
    {
        $name = preg_replace('/[^A-Za-z0-9_\-\.]+/', '-', $name) ?? 'file';
        $name = trim($name, '-.');

        return $name !== '' ? $name : 'file';
    }

    private function uploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Uploaded file is too large.',
            UPLOAD_ERR_PARTIAL => 'Uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary upload directory.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write the uploaded file to disk.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
            default => 'Unknown upload error.',
        };
    }
}
