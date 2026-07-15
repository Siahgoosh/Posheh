<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Contract\ContractService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ContractController extends Controller
{
    public function __construct(private readonly ContractService $contracts) {}

    public function templates(): JsonResponse
    {
        return response()->json(['data' => $this->contracts->templates()]);
    }

    public function fields(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->contracts->templateFields()]);
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->contracts->list($request->user()));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'template_id' => ['nullable', 'integer'],
            'property_id' => ['nullable', 'integer'],
            'title' => ['nullable', 'string'],
            'party_a_name' => ['nullable', 'string'],
            'party_b_name' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'fields' => ['nullable', 'array'],
        ]);

        $contract = $this->contracts->generate($request->user(), $data);

        return response()->json([
            'data' => $contract,
            'pdf_url' => $contract->pdf_path ? url('storage/'.$contract->pdf_path) : null,
            'word_url' => $contract->docx_path ? url('storage/'.$contract->docx_path) : null,
        ], 201);
    }

    public function download(Request $request, int $id, string $format)
    {
        $contract = \App\Models\Contract::where('office_id', $request->user()->office_id)->findOrFail($id);

        $path = $format === 'word' ? $contract->docx_path : $contract->pdf_path;
        abort_unless($path && Storage::disk('public')->exists($path), 404);

        $filename = $format === 'word' ? 'mubayaeh-'.$contract->id.'.doc' : 'mubayaeh-'.$contract->id.'.pdf';

        return Storage::disk('public')->download($path, $filename);
    }
}
