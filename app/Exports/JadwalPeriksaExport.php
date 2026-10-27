<?php

namespace App\Exports;

use App\Models\JadwalPeriksa;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/** Export Jadwal Periksa milik SATU dokter (id-nya diberikan oleh controller dari Auth::id()). */
class JadwalPeriksaExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private int $idDokter)
    {
    }

    public function collection(): Collection
    {
        return JadwalPeriksa::where('id_dokter', $this->idDokter)
            ->orderByRaw("CASE hari WHEN 'Senin' THEN 1 WHEN 'Selasa' THEN 2 WHEN 'Rabu' THEN 3 WHEN 'Kamis' THEN 4 WHEN 'Jumat' THEN 5 WHEN 'Sabtu' THEN 6 ELSE 7 END")
            ->orderBy('jam_mulai')
            ->get();
    }

    public function headings(): array
    {
        return ['No', 'Hari', 'Jam Mulai', 'Jam Selesai', 'Status'];
    }

    public function map(mixed $row): array
    {
        static $no = 0;

        return [++$no, $row->hari, substr($row->jam_mulai, 0, 5), substr($row->jam_selesai, 0, 5), $row->status];
    }
}
