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

        if ($request->user()?->role !== 'admin') {
            $query->where('approval_status', 'approved');
        }

        if ($request->has('search')) {
            $query->where('company_name', 'like', '%' . $request->search . '%');
        }

        $companies = $query->get();

        return CompanyResource::collection($companies);
    }

    public function mine(Request $request)
    {
        $companies = Company::with('industry')
            ->where('user_id', $request->user()->user_id)
            ->latest('company_id')
            ->get();

        return CompanyResource::collection($companies);
    }

    public function adminIndex(Request $request)
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $query = Company::with('industry');
        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->input('approval_status'));
        }

        return CompanyResource::collection($query->latest('company_id')->get());
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
        'approval_status' => 'prohibited',
        'user_id' => 'prohibited',
        'role' => 'prohibited',
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
    $validated['approval_status'] = 'pending';
    $validated['user_id'] = $request->user()->user_id;

    $company = Company::create($validated);

    return response()->json([
        'message' => 'ส่งข้อมูลบริษัทแล้ว รออนุมัติ',
        'data' => new CompanyResource($company)
    ], 201);
}

    public function show(Company $company)
    {
        abort_unless(
            $company->approval_status === 'approved' || request()->user()?->role === 'admin',
            404
        );

        return new CompanyResource($company->load(['industry', 'reviews.user', 'jobs']));
    }

    public function update(Request $request, Company $company)
{
        abort_unless(
            $request->user()?->role === 'admin' || $company->user_id === $request->user()?->user_id,
            403
        );

        $validated = $request->validate([
        'company_name' => 'sometimes|string|max:255',
        'industry_id' => 'sometimes|exists:industries,industry_id',
        'description' => 'nullable|string',
        'address' => 'nullable|string',
        'benefits' => 'nullable|string',
        'culture' => 'nullable|string',
        'logo_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        'approval_status' => 'prohibited',
        'user_id' => 'prohibited',
        'role' => 'prohibited',
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

    if ($request->user()->role !== 'admin') {
        $validated['approval_status'] = 'pending';
        $validated['rejection_reason'] = null;
    }

    $company->update($validated);

    return response()->json([
        'message' => 'Company updated successfully.',
        'data' => new CompanyResource($company)
    ], 200);
}

    public function destroy(Company $company)
    {
        abort_unless(
            request()->user()?->role === 'admin' || $company->user_id === request()->user()?->user_id,
            403
        );

        $company->delete();

        return response()->json([
            'message' => 'Company deleted successfully.'
        ], 200);
    }

        public function updateApproval(Request $request, Company $company)
        {
            abort_unless($request->user()?->role === 'admin', 403);

            $validated = $request->validate([
                'approval_status' => ['required', 'in:approved,rejected'],
                'rejection_reason' => ['nullable', 'string', 'max:2000'],
            ]);

            $company->update([
                'approval_status' => $validated['approval_status'],
                'rejection_reason' => $validated['approval_status'] === 'rejected'
                    ? ($validated['rejection_reason'] ?? null)
                    : null,
            ]);

            return response()->json([
                'message' => $validated['approval_status'] === 'approved'
                    ? 'Company approved successfully.'
                    : 'Company rejected successfully.',
                'data' => new CompanyResource($company->load('industry')),
            ]);
        }
    }
