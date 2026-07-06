<?php

namespace App\Http\Controllers\Api\Contact;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $contacts = Contact::where('office_id', $request->user()->office_id)
            ->with('creator:id,name')
            ->latest()
            ->paginate(20);

        return response()->json($contacts);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:15'],
            'email' => ['nullable', 'email', 'max:255'],
            'type' => ['nullable', 'in:buyer,seller,lead,owner'],
            'status' => ['nullable', 'in:new,contacted,qualified,closed,lost'],
            'source' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'budget_min' => ['nullable', 'integer', 'min:0'],
            'budget_max' => ['nullable', 'integer', 'min:0'],
            'property_interest' => ['nullable', 'string', 'max:100'],
            'rooms_min' => ['nullable', 'integer', 'min:0'],
            'area_min' => ['nullable', 'integer', 'min:0'],
        ]);

        $contact = Contact::create([
            ...$data,
            'office_id' => $request->user()->office_id,
            'created_by' => $request->user()->id,
            'type' => $data['type'] ?? 'lead',
            'status' => $data['status'] ?? 'new',
        ]);

        return response()->json(['data' => $contact->load('creator:id,name')], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $contact = Contact::where('office_id', $request->user()->office_id)->findOrFail($id);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:15'],
            'type' => ['sometimes', 'in:buyer,seller,lead,owner'],
            'status' => ['sometimes', 'in:new,contacted,qualified,closed,lost'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'last_contact_at' => ['nullable', 'date'],
        ]);

        $contact->update($data);

        return response()->json(['data' => $contact->fresh('creator:id,name')]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $contact = Contact::where('office_id', $request->user()->office_id)->findOrFail($id);
        $contact->delete();

        return response()->json(['message' => 'مخاطب حذف شد.']);
    }
}
