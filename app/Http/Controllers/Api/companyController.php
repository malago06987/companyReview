<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use App\Http\Resources\CompanyResource;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $query = Company::with('industry');

        if ($request->has('search')) {
            $query->where('company_name', 'like', '%' . $request->search . '%');
        }

        $companies = $query->get();

        return CompanyResource::collection($companies);
    }

  public function store(Request $request)
{
    $validated = $request->validate([
        'company_name' => 'required|string|max:255',
        'industry_id' => 'required|exists:industries,industry_id',
        'description' => 'nullable|string',
        'address' => 'nullable|string',
        'benefits' => 'nullable|string',
        'culture' => 'nullable|string',
        'logo_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
    ]);

    $logoImage = null;

    if ($request->hasFile('logo_image')) {
        $file = $request->file('logo_image');

        $filename = time() . '_' . $file->getClientOriginalName();

        $file->move(
            public_path('uploads/company'),
            $filename
        );

        $logoImage = 'uploads/company/' . $filename;
    }

    $validated['logo_image'] = $logoImage;

    $company = Company::create($validated);

    return response()->json([
        'message' => 'Company created successfully.',
        'data' => new CompanyResource($company)
    ], 201);
}

    public function show(Company $company)
    {
        return new CompanyResource($company->load(['industry', 'reviews.user', 'jobs']));
    }

    public function update(Request $request, Company $company)
{
    $validated = $request->validate([
        'company_name' => 'sometimes|string|max:255',
        'industry_id' => 'sometimes|exists:industries,industry_id',
        'description' => 'nullable|string',
        'address' => 'nullable|string',
        'benefits' => 'nullable|string',
        'culture' => 'nullable|string',
        'logo_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
    ]);

    if ($request->hasFile('logo_image')) {
        $file = $request->file('logo_image');

        $filename = time() . '_' . $file->getClientOriginalName();

        $file->move(
            public_path('uploads/company'),
            $filename
        );

        $validated['logo_image'] = 'uploads/company/' . $filename;
    }

    $company->update($validated);

    return response()->json([
        'message' => 'Company updated successfully.',
        'data' => new CompanyResource($company)
    ], 200);
}

    public function destroy(Company $company)
    {
        $company->delete();

        return response()->json([
            'message' => 'Company deleted successfully.'
        ], 200);
    }
}
