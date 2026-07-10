<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Contract\ContractService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    public function __construct(private readonly ContractService $contracts) {}

    public function templates(): JsonResponse
    {
        return response()->json(['data' => $this->contracts->templates()]);
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
        ]);

        $contract = $this->contracts->generate($request->user(), $data);

        return response()->json([
            'data' => $contract,
            'pdf_url' => $contract->pdf_path ? url('storage/'.$contract->pdf_path) : null,
        ], 201);
    }
}
