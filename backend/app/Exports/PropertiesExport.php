<?php

namespace App\Exports;

use App\Models\Property;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PropertiesExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly int $officeId) {}

    public function collection()
    {
        return Property::where('office_id', $this->officeId)
            ->get()
            ->map(fn ($p) => [
                $p->code,
                $p->type,
                $p->status,
                $p->price,
                $p->deposit,
                $p->rent,
                $p->area,
                $p->rooms,
                $p->city,
                $p->district,
                $p->address,
                $p->owner_name,
                $p->owner_mobile,
                $p->description,
            ]);
    }

    public function headings(): array
    {
        return ['code', 'type', 'status', 'price', 'deposit', 'rent', 'area', 'rooms', 'city', 'district', 'address', 'owner_name', 'owner_mobile', 'description'];
    }
}
