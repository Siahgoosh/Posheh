<?php

namespace App\Services\Contract;

use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\Property;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Morilog\Jalali\Jalalian;

class ContractService
{
    public function templateFields(?int $templateId = null): array
    {
        $config = config('mubayaeh_contract.fields', []);

        return $config;
    }

    public function templates()
    {
        return ContractTemplate::where('is_active', true)->orderBy('name')->get()->map(function ($t) {
            $t->setAttribute('fields', $t->slug === 'mubayaeh-125' ? $this->templateFields() : []);

            return $t;
        });
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

        $fieldValues = $data['fields'] ?? [];
        $auto = $this->autoFields($user, $property);
        $merged = array_merge($auto, array_filter($fieldValues, fn ($v) => $v !== null && $v !== ''));

        $body = $template?->body ?? ($data['content'] ?? '');
        $content = $this->fillTemplate($body, $merged);

        $contract = Contract::create([
            'office_id' => $user->office_id,
            'property_id' => $property?->id,
            'created_by' => $user->id,
            'template_id' => $template?->id,
            'title' => $data['title'] ?? ($template?->name ?? 'قرارداد'),
            'content' => $content,
            'field_values' => $merged,
            'party_a_name' => $merged['seller_name'] ?? $data['party_a_name'] ?? null,
            'party_b_name' => $merged['buyer_name'] ?? $data['party_b_name'] ?? null,
            'status' => 'draft',
        ]);

        $this->generatePdf($contract);
        $this->generateWord($contract);

        return $contract->fresh();
    }

    /** @return array<string, string> */
    private function autoFields(User $user, ?Property $property): array
    {
        $office = $user->office;
        $jalali = Jalalian::now();

        return [
            'office_name' => $office?->name ?? '',
            'consultant_office_address' => trim(($office?->city ?? '').' '.($office?->address ?? '')),
            'consultant_license' => (string) ($office?->getSetting('consultant_license', '')),
            'property_code' => $property?->code ?? '',
            'property_price' => $property?->price ? number_format($property->price) : '',
            'property_address' => trim(implode(' ', array_filter([
                $property?->province, $property?->city, $property?->district,
                $property?->neighborhood, $property?->address,
            ]))),
            'property_area' => $property?->area ? (string) $property->area : '',
            'total_price_number' => $property?->price ? number_format($property->price) : '',
            'contract_date' => $jalali->format('Y/m/d'),
            'today_jalali' => $jalali->format('l، j F Y'),
            'deed_date' => $jalali->format('Y/m/d'),
            'delivery_date' => $jalali->format('Y/m/d'),
            '{{party_a}}' => '',
            '{{party_b}}' => '',
        ];
    }

    /** @param array<string, string> $values */
    private function fillTemplate(string $body, array $values): string
    {
        $replacements = [];
        foreach ($values as $key => $value) {
            $replacements['{{'.$key.'}}'] = e((string) $value);
        }

        // legacy placeholders
        $replacements['{{party_a}}'] = e((string) ($values['seller_name'] ?? $values['{{party_a}}'] ?? ''));
        $replacements['{{party_b}}'] = e((string) ($values['buyer_name'] ?? $values['{{party_b}}'] ?? ''));
        $replacements['{{property_code}}'] = e((string) ($values['property_code'] ?? ''));
        $replacements['{{property_address}}'] = e((string) ($values['property_address'] ?? ''));
        $replacements['{{price}}'] = e((string) ($values['property_price'] ?? $values['total_price_number'] ?? ''));
        $replacements['{{date}}'] = e((string) ($values['contract_date'] ?? ''));
        $replacements['{{office_name}}'] = e((string) ($values['office_name'] ?? ''));

        return str_replace(array_keys($replacements), array_values($replacements), $body);
    }

    public function generatePdf(Contract $contract): Contract
    {
        $html = $this->wrapHtml($contract->content);
        $pdf = Pdf::loadHTML($html);
        $path = 'contracts/'.Str::uuid().'.pdf';
        Storage::disk('public')->put($path, $pdf->output());
        $contract->update(['pdf_path' => $path, 'status' => 'generated']);

        return $contract;
    }

    public function generateWord(Contract $contract): Contract
    {
        $html = $this->wrapHtml($contract->content, forWord: true);
        $path = 'contracts/'.Str::uuid().'.doc';
        Storage::disk('public')->put($path, "\xEF\xBB\xBF".$html);
        $contract->update(['docx_path' => $path]);

        return $contract;
    }

    private function wrapHtml(string $content, bool $forWord = false): string
    {
        $meta = $forWord
            ? '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">'
            : '';

        return '<!DOCTYPE html><html dir="rtl" lang="fa"><head>'.$meta
            .'<style>body{font-family:Tahoma,B Nazanin,DejaVu Sans,sans-serif;font-size:12pt;line-height:1.8;direction:rtl;text-align:justify}'
            .'h2{text-align:center}p{margin:6px 0}.field{border-bottom:1px dotted #333;display:inline-block;min-width:80px}</style></head><body>'
            .$content.'</body></html>';
    }
}
