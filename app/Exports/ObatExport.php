<?php

namespace App\Exports;

use App\Models\Obat;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/** Export data Obat termasuk stok dan harga (khusus Admin). */
class ObatExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function collection(): Collection
    {
        return Obat::orderBy('nama_obat')->get();
    }

    public function headings(): array
    {
        return ['No', 'Nama Obat', 'Kemasan', 'Harga (Rp)', 'Stok'];
    }

    public function map(mixed $row): array
    {
        static $no = 0;

        // harga & stok dikirim sebagai angka (bukan teks) supaya bisa dijumlah di Excel
        return [++$no, $row->nama_obat, $row->kemasan, (int) $row->harga, (int) $row->stok];
    }
}
