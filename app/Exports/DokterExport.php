<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/** Export data Dokter (khusus Admin). */
class DokterExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function collection(): Collection
    {
        // with('poli') = eager loading, jadi tidak ada N+1 saat memetakan baris
        return User::where('role', 'dokter')->with('poli')->orderBy('nama')->get();
    }

    public function headings(): array
    {
        return ['No', 'Nama', 'Poli', 'No KTP', 'No HP', 'Email', 'Alamat'];
    }

    public function map(mixed $row): array
    {
        static $no = 0;

        return [++$no, $row->nama, $row->poli->nama_poli ?? '-', $row->no_ktp, $row->no_hp, $row->email, $row->alamat];
    }
}
