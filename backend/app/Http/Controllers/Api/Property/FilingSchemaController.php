<?php

namespace App\Http\Controllers\Api\Property;

use App\Http\Controllers\Controller;
use App\Services\Filing\FilingSchemaRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FilingSchemaController extends Controller
{
    public function __construct(
        private readonly FilingSchemaRegistry $registry,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->registry->fullSchema()]);
    }

    public function fields(Request $request): JsonResponse
    {
        $request->validate([
            'property_category' => ['required', 'string'],
            'transaction_type' => ['required', 'string'],
        ]);

        return response()->json([
            'data' => $this->registry->fieldsFor(
                $request->input('property_category'),
                $request->input('transaction_type'),
            ),
        ]);
    }
}
