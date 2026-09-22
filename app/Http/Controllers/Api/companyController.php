<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\company;
use Illuminate\Http\Request;
use App\Http\Resources\companyResource;

class companyController extends Controller
{
    // 1. ดึงรายการบริษัททั้งหมด (รองรับการค้นหาตามชื่อ)
    public function index(Request $request)
    {
        $query = company::query()->with('industry');

        if ($request->filled('search')) {
            $query->where('company_name', 'like', '%' . $request->string('search') . '%');
        }

        $companies = $query->latest('company_id')->paginate(15);

        return companyResource::collection($companies);
    }

    // 2. สร้างข้อมูลบริษัทใหม่
    public function store(Request $request)
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'industry_id' => 'required|exists:industries,industry_id',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'benefits' => 'nullable|string',
            'culture' => 'nullable|string',
            'logo_image' => 'nullable|string',
        ]);

        $company = company::create($validated)->load('industry');

        return response()->json([
            'message' => 'Company created successfully.',
            'data' => new companyResource($company)
        ], 201);
    }

    // 3. ดูข้อมูลบริษัทรายบริษัท
    public function show(company $company)
    {
        return new companyResource($company->load(['industry', 'reviews.user', 'jobs']));
    }

    // 4. แก้ไขข้อมูลบริษัท
    public function update(Request $request, company $company)
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $validated = $request->validate([
            'company_name' => 'sometimes|string|max:255',
            'industry_id' => 'sometimes|exists:industries,industry_id',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'benefits' => 'nullable|string',
            'culture' => 'nullable|string',
            'logo_image' => 'nullable|string',
        ]);

        $company->update($validated);
        $company->load('industry');

        return response()->json([
            'message' => 'Company updated successfully.',
            'data' => new companyResource($company)
        ], 200);
    }

    // 5. ลบข้อมูลบริษัท (Soft Delete)
    public function destroy(company $company)
    {
        abort_unless(request()->user()?->role === 'admin', 403);

        $company->delete();

        return response()->json([
            'message' => 'Company deleted successfully.'
        ], 200);
    }
}
