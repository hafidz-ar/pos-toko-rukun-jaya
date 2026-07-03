<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class SalesHistoryExport implements FromQuery, WithCustomStartCell, WithHeadings, WithMapping, WithColumnWidths, WithEvents
{
    protected int $rowNumber = 0;

    public function __construct(
        protected Builder $query,
        protected array $summary,
        protected array $filters,
        protected string $selectedCashierName
    ) {}

    /**
     * Return the query builder instance.
     */
    public function query()
    {
        return $this->query;
    }

    /**
     * Start cells for query data.
     */
    public function startCell(): string
    {
        return 'A12';
    }

    /**
     * Define the headings.
     */
    public function headings(): array
    {
        return [
            'No',
            'ID Transaksi',
            'Tanggal',
            'Waktu',
            'Barang Dibeli',
            'Jumlah Item',
            'Total Harga',
            'Metode Pembayaran',
            'Kasir',
        ];
    }

    /**
     * Map transaction data.
     */
    public function map($transaction): array
    {
        $this->rowNumber++;

        $itemsSummary = $transaction->items
            ->map(fn ($item) => $item->product?->name ?? 'Produk Dihapus')
            ->implode(', ');

        return [
            $this->rowNumber,
            'TX-' . str_pad($transaction->id, 6, '0', STR_PAD_LEFT),
            $transaction->transaction_datetime ? $transaction->transaction_datetime->format('d/m/Y') : '-',
            $transaction->transaction_datetime ? $transaction->transaction_datetime->format('H:i') . ' WIB' : '-',
            $itemsSummary,
            (float) $transaction->items->sum('qty_in_selected_unit'),
            (float) $transaction->total_amount,
            strtoupper($transaction->payment_method),
            $transaction->cashier?->name ?? '-',
        ];
    }

    /**
     * Column widths definition.
     */
    public function columnWidths(): array
    {
        return [
            'A' => 6,   // No
            'B' => 18,  // ID Transaksi
            'C' => 14,  // Tanggal
            'D' => 14,  // Waktu
            'E' => 45,  // Barang Dibeli
            'F' => 14,  // Jumlah Item
            'G' => 18,  // Total Harga
            'H' => 20,  // Metode Pembayaran
            'I' => 18,  // Kasir
        ];
    }

    /**
     * Handle styles and custom layouts using events.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;

                // 1. Write Part A: Summary Metrik
                $sheet->setCellValue('A1', 'LAPORAN RIWAYAT PENJUALAN');
                $sheet->mergeCells('A1:I1');
                
                $sheet->setCellValue('A3', 'Periode');
                $dateFrom = $this->filters['date_from'] ?? null;
                $dateTo = $this->filters['date_to'] ?? null;
                $periodeText = ($dateFrom && $dateTo) 
                    ? \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') . ' - ' . \Carbon\Carbon::parse($dateTo)->format('d/m/Y')
                    : 'Semua Periode';
                $sheet->setCellValue('B3', $periodeText);

                $sheet->setCellValue('A4', 'Metode Pembayaran');
                $methodText = 'Semua Pembayaran';
                if (!empty($this->filters['payment_method'])) {
                    $methodText = strtoupper($this->filters['payment_method']);
                }
                $sheet->setCellValue('B4', $methodText);

                $sheet->setCellValue('A5', 'Kasir');
                $sheet->setCellValue('B5', $this->selectedCashierName);

                $sheet->setCellValue('A6', 'Total Transaksi');
                $sheet->setCellValue('B6', (int)$this->summary['total_count']);

                $sheet->setCellValue('A7', 'Total Omset');
                $sheet->setCellValue('B7', (float)$this->summary['total_revenue']);

                $sheet->setCellValue('A8', 'Total Tunai');
                $sheet->setCellValue('B8', (float)$this->summary['cash_total']);

                $sheet->setCellValue('A9', 'Total QRIS');
                $sheet->setCellValue('B9', (float)$this->summary['qris_total']);

                $sheet->setCellValue('A10', 'Diekspor Pada');
                $sheet->setCellValue('B10', now()->format('d/m/Y H:i') . ' WIB');

                // Summary Styling
                $sheet->getStyle('A1')->getFont()->setSize(16)->setBold(true);
                $sheet->getStyle('A3:A10')->getFont()->setBold(true);
                $sheet->getStyle('B7:B9')->getNumberFormat()->setFormatCode('"Rp" #,##0');

                // 2. Table Detail Styling (Starting Row 12)
                $lastRow = $sheet->getHighestRow();

                // Column headers style at Row 12
                $sheet->getStyle('A12:I12')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1E293B'], // Slate-800
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ]
                ]);

                // Apply borders dynamically based on the highest row
                if ($lastRow >= 12) {
                    $sheet->getStyle("A12:I{$lastRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                'color' => ['rgb' => 'CBD5E1'], // Slate-300
                            ],
                        ],
                    ]);

                    // Format Total Harga (G) & Qty (F) and alignments
                    $sheet->getStyle("G13:G{$lastRow}")->getNumberFormat()->setFormatCode('"Rp" #,##0');
                    $sheet->getStyle("G13:G{$lastRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("F13:F{$lastRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("A13:A{$lastRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("C13:D{$lastRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("H13:H{$lastRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                }

                // Autofilter for transaction headers
                $sheet->setAutoFilter('A12:I12');

                // Freeze panes under headers
                $sheet->freezePane('A13');
            }
        ];
    }
}
