<?php

namespace App\Services;

use App\Models\Cattle;
use App\Support\DatePeriod;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CattleReportPdfService
{
    public function __construct(private CattleTimelineService $timeline) {}

    public function download(Cattle $cattle, ?DatePeriod $period = null): Response|StreamedResponse|BinaryFileResponse
    {
        $report = $this->timeline->report($cattle, $period, excludeFailed: true);
        $filename = $cattle->code.'-laporan-'.now()->format('Ymd-His').'.pdf';

        return Pdf::loadView('pdf.cattle-report', [
            'cattle' => $cattle,
            'report' => $report,
            'period' => $period,
            'generatedAt' => now(),
        ])
            ->setPaper('a4')
            ->setOption(['isRemoteEnabled' => false, 'defaultFont' => 'DejaVu Sans'])
            ->download($filename);
    }
}
