<?php

namespace App\Services\Property;

use App\Exports\PropertiesExport;
use App\Exports\PropertiesImportTemplateExport;
use App\Imports\PropertiesImport;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PropertyExportService
{
    public function export(User $user): BinaryFileResponse
    {
        return Excel::download(
            new PropertiesExport($user->office_id),
            'properties-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function sampleTemplate(): BinaryFileResponse
    {
        return Excel::download(
            new PropertiesImportTemplateExport,
            'نمونه-ایمپورت-املاک.xlsx'
        );
    }

    public function import(User $user, $file): array
    {
        $import = new PropertiesImport($user);
        Excel::import($import, $file);

        return [
            'imported' => $import->getRowCount(),
            'message' => 'ایمپورت اکسل با موفقیت انجام شد. '.$import->getRowCount().' ردیف پردازش شد.',
        ];
    }
}
