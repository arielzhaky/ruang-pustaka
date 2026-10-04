<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CsvFile
{
    public static function read(Request $request, array $headers, array $optionalHeaders = []): array
    {
        $request->validate(['file' => ['required', 'file', 'max:2048', 'extensions:csv']], [
            'file.required' => 'Pilih file CSV.', 'file.max' => 'File maksimal 2 MB.',
            'file.extensions' => 'Gunakan file berakhiran .csv.',
        ]);
        $content = file_get_contents($request->file('file')->getRealPath());
        if (! mb_check_encoding($content, 'UTF-8') || str_contains($content, "\0")) {
            throw ValidationException::withMessages(['file' => 'Simpan file sebagai CSV UTF-8 sebelum mengimpor.']);
        }
        $handle = fopen('php://temp', 'r+');
        try {
            fwrite($handle, preg_replace('/^\xEF\xBB\xBF/', '', $content));
            rewind($handle);
            $firstLine = fgets($handle);
            $delimiter = count(str_getcsv($firstLine ?: '', ';', '"', '')) > count(str_getcsv($firstLine ?: '', ',', '"', '')) ? ';' : ',';
            rewind($handle);
            $actual = fgetcsv($handle, 0, $delimiter, '"', '');
            $actual = $actual ? array_map(fn ($value) => strtolower(trim((string) $value)), $actual) : [];
            if (count($actual) !== count(array_unique($actual)) || array_diff($headers, $actual) || array_diff($actual, [...$headers, ...$optionalHeaders])) {
                throw ValidationException::withMessages(['file' => 'Kolom CSV tidak sesuai. Unduh template dan pertahankan semua nama kolomnya.']);
            }
            $rows = [];
            $number = 1;
            while (($values = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
                $number++;
                if (count($values) === 1 && ($values[0] === null || trim($values[0]) === '')) {
                    continue;
                }
                if (count($values) !== count($actual)) {
                    throw ValidationException::withMessages(['file' => "Baris {$number}: jumlah kolom tidak sesuai template."]);
                }
                $record = array_combine($actual, $values);
                foreach ($record as $key => $value) {
                    $value = $key === 'password' ? (string) $value : trim((string) $value);
                    $record[$key] = $value === '' ? null : $value;
                }
                $rows[$number] = $record;
                if (count($rows) > 1000) {
                    throw ValidationException::withMessages(['file' => 'Maksimal 1000 baris data per impor.']);
                }
            }
            if (! $rows) {
                throw ValidationException::withMessages(['file' => 'CSV belum berisi data.']);
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    public static function download(string $filename, array $headers, iterable $rows)
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $headers, ',', '"', '');
            foreach ($rows as $row) {
                $safe = array_map(function ($value) {
                    $text = (string) ($value ?? '');

                    // Excel tidak boleh mengeksekusi isi sel sebagai formula.
                    return preg_match('/^[\s]*[=+@\-]/u', $text) ? "\t".$text : $text;
                }, $row);
                fputcsv($output, $safe, ',', '"', '');
            }
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store']);
    }
}
