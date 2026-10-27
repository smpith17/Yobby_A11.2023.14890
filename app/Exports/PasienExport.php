<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/** Export data Pasien (khusus Admin). */
class PasienExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function collection(): Collection
    {
        return User::where('role', 'pasien')->orderBy('nama')->get();
    }

    public function headings(): array
    {
        return ['No', 'Nama', 'No RM', 'No KTP', 'No HP', 'Email', 'Alamat'];
    }

    public function map(mixed $row): array
    {
        static $no = 0;

        return [++$no, $row->nama, $row->no_rm, $row->no_ktp, $row->no_hp, $row->email, $row->alamat];
    }
}
