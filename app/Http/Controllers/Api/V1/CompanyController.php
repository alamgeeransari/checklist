<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $companies = Company::query()->latest()->paginate(20);

        return response()->json($companies);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:companies,slug'],
        ]);

        $company = Company::create($validated);

        return response()->json($company, 201);
    }

    public function approve(Company $company): JsonResponse
    {
        $company->update([
            'status' => \App\Enums\CompanyStatus::Approved,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return response()->json(['message' => 'Company approved']);
    }

    public function suspend(Company $company): JsonResponse
    {
        $company->update(['status' => \App\Enums\CompanyStatus::Suspended]);

        return response()->json(['message' => 'Company suspended']);
    }
}
