<?php
namespace Modules\WorkCenter\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\WorkCenter\Models\{Report, ReportVersion};

/** Corporate PDF: logo header, branded footer w/ page numbers, classification. */
class PdfService
{
    public function render(Report $r, ReportVersion $v, string $theme = 'corporate'): string
    {
        $company = \Modules\Organization\Models\Company::find($r->company_id);
        $branch = $r->branch_id ? \Modules\Organization\Models\Branch::find($r->branch_id) : null;
        $html = view('workcenter::pdf.report', [
            'report' => $r, 'version' => $v, 'company' => $company,
            'branch' => $branch, 'theme' => $theme,
        ])->render();
        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
        $path = 'reports/pdf/'.Str::ulid().'.pdf';
        Storage::disk('local')->put($path, $pdf->output());
        return $path;
    }

    public function preview(Report $r, ReportVersion $v): \Illuminate\Http\Response
    {
        $company = \Modules\Organization\Models\Company::find($r->company_id);
        return Pdf::loadHTML(view('workcenter::pdf.report', [
            'report' => $r, 'version' => $v, 'company' => $company,
            'branch' => $r->branch_id ? \Modules\Organization\Models\Branch::find($r->branch_id) : null,
            'theme' => $r->template?->theme ?? 'corporate',
        ])->render())->setPaper('a4')->stream("{$r->ref}-v{$v->version}.pdf");
    }
}
