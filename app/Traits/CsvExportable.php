<?php

namespace App\Traits;

use Symfony\Component\HttpFoundation\StreamedResponse;

trait CsvExportable
{
    /**
     * Export an array of data to a CSV file.
     *
     * @param array $headers
     * @param array $rows
     * @param string $filename
     * @return StreamedResponse
     */
    public function exportCsv(array $headers, array $rows, string $filename): StreamedResponse
    {
        $callback = function () use ($headers, $rows) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, $headers);
            foreach ($rows as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
