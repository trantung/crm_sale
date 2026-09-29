<?php

namespace App\Services;

use App\Models\LeadSource;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LeadImportService
{
    public const SESSION_KEY = 'lead_import_token';

    public const PER_PAGE = 20;

    /**
     * @return list<array{stt: int|string, name: string, phone: string, email: string, utm: string}>
     */
    public function parseFile(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $path = $file->getRealPath();

        $matrix = in_array($extension, ['xlsx', 'xls', 'ods'], true)
            ? $this->readSpreadsheet($path)
            : $this->readCsv($path);

        if ($matrix === []) {
            return [];
        }

        $header = array_map(fn ($column) => $this->normalizeHeader((string) $column), array_shift($matrix));
        $rows = [];
        $index = 1;

        foreach ($matrix as $line) {
            if (! is_array($line) || $this->isEmptyLine($line)) {
                continue;
            }

            $mapped = [];
            foreach ($header as $i => $key) {
                $mapped[$key] = isset($line[$i]) ? trim((string) $line[$i]) : '';
            }

            $name = $mapped['name'] ?? $mapped['ten'] ?? '';
            if ($name === '') {
                continue;
            }

            $rows[] = [
                'stt' => ($mapped['stt'] ?? '') !== '' ? $mapped['stt'] : $index,
                'name' => $name,
                'phone' => $mapped['phone'] ?? $mapped['sdt'] ?? '',
                'email' => $mapped['email'] ?? '',
                'utm' => $mapped['utm'] ?? $mapped['utm_campaign'] ?? '',
            ];
            $index++;
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function storeDraft(array $rows): string
    {
        $token = Str::uuid()->toString();
        Storage::disk('local')->put($this->path($token), json_encode([
            'rows' => array_values($rows),
            'created_at' => now()->toIso8601String(),
        ], JSON_UNESCAPED_UNICODE));

        return $token;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function loadDraft(string $token): array
    {
        if (! Storage::disk('local')->exists($this->path($token))) {
            return [];
        }

        $payload = json_decode(Storage::disk('local')->get($this->path($token)), true);

        return is_array($payload['rows'] ?? null) ? $payload['rows'] : [];
    }

    /**
     * @param  array<int, array<string, mixed>>  $updates
     */
    public function mergeDraft(string $token, array $updates): array
    {
        $rows = $this->loadDraft($token);
        foreach ($updates as $index => $row) {
            $i = (int) $index;
            if (! isset($rows[$i]) || ! is_array($row)) {
                continue;
            }
            $rows[$i]['name'] = trim((string) ($row['name'] ?? $rows[$i]['name']));
            $rows[$i]['phone'] = trim((string) ($row['phone'] ?? $rows[$i]['phone']));
            $rows[$i]['email'] = trim((string) ($row['email'] ?? $rows[$i]['email']));
            $rows[$i]['utm'] = trim((string) ($row['utm'] ?? $rows[$i]['utm']));
        }
        $this->replaceDraft($token, $rows);

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function replaceDraft(string $token, array $rows): void
    {
        Storage::disk('local')->put($this->path($token), json_encode([
            'rows' => array_values($rows),
            'created_at' => now()->toIso8601String(),
        ], JSON_UNESCAPED_UNICODE));
    }

    public function forgetDraft(string $token): void
    {
        Storage::disk('local')->delete($this->path($token));
    }

    /**
     * @return array{source_code: string, utm_campaign: ?string, utm_source: ?string}
     */
    public function resolveUtm(?string $utm): array
    {
        $utm = trim((string) $utm);
        if ($utm === '') {
            return ['source_code' => 'other', 'utm_campaign' => null, 'utm_source' => null];
        }

        $aliases = [
            'facebook' => 'facebook_ads',
            'fb' => 'facebook_ads',
            'google' => 'google_ads',
            'tiktok' => 'tiktok_ads',
        ];
        $mapped = $aliases[mb_strtolower($utm)] ?? $utm;

        $match = LeadSource::query()
            ->where('is_active', true)
            ->where(function ($query) use ($utm, $mapped) {
                $query->where('code', $utm)
                    ->orWhere('code', $mapped)
                    ->orWhereRaw('LOWER(name) = ?', [mb_strtolower($utm)]);
            })
            ->first();

        if ($match) {
            return [
                'source_code' => $match->code,
                'utm_campaign' => $match->code,
                'utm_source' => $match->code,
            ];
        }

        return [
            'source_code' => 'other',
            'utm_campaign' => $utm,
            'utm_source' => $utm,
        ];
    }

    public function sampleSpreadsheet(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['STT', 'Name', 'Phone', 'Email', 'UTM'],
            [1, 'Trần Tùng', '912957368', 'trantunghn196@gmail.com', 'facebook'],
            [2, 'Gia Hưng', '12345667', 'giahung@gmail.com', ''],
            [3, 'gia minh', '5413213', 'giaminh@gmail.com', 'abc'],
        ]);

        $temp = storage_path('app/lead-sample.xlsx');
        (new Xlsx($spreadsheet))->save($temp);

        return $temp;
    }

    /**
     * @return list<list<mixed>>
     */
    private function readSpreadsheet(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();

        return $sheet->toArray(null, true, true, false);
    }

    /**
     * @return list<list<mixed>>
     */
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            return [];
        }

        $rows = [];
        while (($line = fgetcsv($handle)) !== false) {
            $rows[] = $line;
        }
        fclose($handle);

        return $rows;
    }

    private function normalizeHeader(string $column): string
    {
        $column = preg_replace('/^\xEF\xBB\xBF/', '', $column) ?? $column;
        $column = mb_strtolower(trim($column));

        return match ($column) {
            'tên', 'ten' => 'name',
            'số điện thoại', 'so dien thoai', 'sdt' => 'phone',
            'thư điện tử', 'thu dien tu' => 'email',
            'utm campaign', 'utm_campaign' => 'utm',
            default => $column,
        };
    }

    /**
     * @param  list<mixed>  $line
     */
    private function isEmptyLine(array $line): bool
    {
        foreach ($line as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    private function path(string $token): string
    {
        return 'imports/'.$token.'.json';
    }
}
