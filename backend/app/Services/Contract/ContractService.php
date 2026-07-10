<?php

namespace App\Services\Contract;

use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\Property;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ContractService
{
    public function templates()
    {
        return ContractTemplate::where('is_active', true)->orderBy('name')->get();
    }

    public function list(User $user)
    {
        return Contract::with(['template', 'property'])
            ->where('office_id', $user->office_id)
            ->latest()
            ->paginate(20);
    }

    public function generate(User $user, array $data): Contract
    {
        $template = isset($data['template_id'])
            ? ContractTemplate::findOrFail($data['template_id'])
            : null;

        $property = isset($data['property_id'])
            ? Property::where('office_id', $user->office_id)->findOrFail($data['property_id'])
            : null;

        $replacements = [
            '{{office_name}}' => $user->office?->name ?? '',
            '{{party_a}}' => $data['party_a_name'] ?? '',
            '{{party_b}}' => $data['party_b_name'] ?? '',
            '{{property_code}}' => $property?->code ?? '',
            '{{property_address}}' => $property?->address ?? '',
            '{{price}}' => $property?->price ? number_format($property->price) : '',
            '{{date}}' => now()->format('Y/m/d'),
        ];

        $body = $template?->body ?? ($data['content'] ?? '');
        $content = str_replace(array_keys($replacements), array_values($replacements), $body);

        $contract = Contract::create([
            'office_id' => $user->office_id,
            'property_id' => $property?->id,
            'created_by' => $user->id,
            'template_id' => $template?->id,
            'title' => $data['title'] ?? ($template?->name ?? 'قرارداد'),
            'content' => $content,
            'party_a_name' => $data['party_a_name'] ?? null,
            'party_b_name' => $data['party_b_name'] ?? null,
            'status' => 'draft',
        ]);

        return $this->generatePdf($contract);
    }

    public function generatePdf(Contract $contract): Contract
    {
        $pdf = Pdf::loadHTML('<html dir="rtl"><body style="font-family:DejaVu Sans">'.$contract->content.'</body></html>');
        $path = 'contracts/'.Str::uuid().'.pdf';
        Storage::disk('public')->put($path, $pdf->output());
        $contract->update(['pdf_path' => $path, 'status' => 'generated']);

        return $contract->fresh();
    }
}
