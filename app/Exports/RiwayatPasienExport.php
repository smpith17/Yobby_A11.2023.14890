<?php

namespace App\Exports;

use App\Models\Periksa;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/** Export Riwayat Pasien yang diperiksa SATU dokter (id dari Auth::id() di controller). */
class RiwayatPasienExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private int $idDokter)
    {
    }

    public function collection(): Collection
    {
        return Periksa::whereHas('daftarPoli.jadwalPeriksa', fn ($q) => $q->where('id_dokter', $this->idDokter))
            ->with(['daftarPoli.pasien', 'detailPeriksas.obat'])
            ->orderBy('tgl_periksa', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return ['No', 'Tanggal Periksa', 'No Antrian', 'Nama Pasien', 'Keluhan', 'Catatan', 'Obat', 'Biaya (Rp)'];
    }

    public function map(mixed $row): array
    {
        static $no = 0;

        return [
            ++$no,
            $row->tgl_periksa,
            $row->daftarPoli->no_antrian,
            $row->daftarPoli->pasien->nama ?? '-',
            $row->daftarPoli->keluhan,
            $row->catatan,
            $row->detailPeriksas->map(fn ($d) => $d->obat->nama_obat ?? '-')->implode(', '),
            (int) $row->biaya_periksa,
        ];
    }
}
