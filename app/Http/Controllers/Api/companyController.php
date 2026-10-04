<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use App\Http\Resources\CompanyResource;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

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
    $documentRules = $request->user()->role === 'admin'
        ? ['nullable', 'file', 'mimes:pdf', 'max:5120']
        : ['required', 'file', 'mimes:pdf', 'max:5120'];

    $validated = $request->validate([
        'company_name' => 'required|string|max:255',
        'industry_id' => 'required|exists:industries,industry_id',
        'description' => 'nullable|string',
        'address' => 'nullable|string',
        'benefits' => 'nullable|string',
        'culture' => 'nullable|string',
        'logo_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        'document' => $documentRules,
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

    $registrationDocumentPath = null;
    if ($request->hasFile('document')) {
        $registrationDocumentPath = $this->storeRegistrationDocument(
            $request->file('document')
        );
    }

    $validated['logo_image'] = $logoImage;
    $validated['document'] = $registrationDocumentPath;
    $validated['approval_status'] = 'pending';
    $validated['user_id'] = $request->user()->user_id;

    try {
        $company = Company::create($validated);
    } catch (\Throwable $exception) {
        if ($registrationDocumentPath !== null) {
            $this->deleteRegistrationDocument($registrationDocumentPath);
        }

        throw $exception;
    }

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
        'document' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
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

    $previousRegistrationDocumentPath = $company->document;
    $registrationDocumentPath = null;
    if ($request->hasFile('document')) {
        $registrationDocumentPath = $this->storeRegistrationDocument(
            $request->file('document')
        );

        $validated['document'] = $registrationDocumentPath;
    }

    if ($request->user()->role !== 'admin') {
        $validated['approval_status'] = 'pending';
        $validated['rejection_reason'] = null;
    }

    try {
        $company->update($validated);
    } catch (\Throwable $exception) {
        if ($registrationDocumentPath !== null) {
            $this->deleteRegistrationDocument($registrationDocumentPath);
        }

        throw $exception;
    }

    if ($registrationDocumentPath !== null) {
        $this->deleteRegistrationDocument($previousRegistrationDocumentPath);
    }

    return response()->json([
        'message' => 'แก้ไขข้อมูลบริษัทเรียบร้อยแล้ว',
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
            'message' => 'ลบข้อมูลบริษัทเรียบร้อยแล้ว'
        ], 200);
    }

    public function registrationDocument(Request $request, Company $company)
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $documentPath = $company->document;
        abort_unless($documentPath !== null, 404, 'ไม่พบเอกสารจดทะเบียนของบริษัทนี้');

        if (
            str_starts_with($documentPath, 'uploads/documents/')
            || str_starts_with($documentPath, 'documents/')
        ) {
            $prefix = str_starts_with($documentPath, 'uploads/documents/')
                ? 'uploads/documents/'
                : 'documents/';
            $path = public_path($documentPath);
            abort_unless(
                basename($documentPath) === substr($documentPath, strlen($prefix))
                    && File::exists($path),
                404,
                'ไม่พบเอกสารจดทะเบียนของบริษัทนี้'
            );

            return response()->file($path, ['Content-Disposition' => 'inline']);
        }

        $storage = Storage::disk('local');
        abort_unless(
            str_starts_with($documentPath, 'company-registration-documents/')
                && $storage->exists($documentPath),
            404,
            'ไม่พบเอกสารจดทะเบียนของบริษัทนี้'
        );

        return response()->file($storage->path($documentPath), ['Content-Disposition' => 'inline']);
    }

    private function storeRegistrationDocument(UploadedFile $file): string
    {
        $directory = public_path('uploads/documents');
        File::ensureDirectoryExists($directory);

        $filename = $file->hashName();
        $file->move($directory, $filename);

        return 'uploads/documents/' . $filename;
    }

    private function deleteRegistrationDocument(?string $documentPath): void
    {
        if ($documentPath === null) {
            return;
        }

        if (
            str_starts_with($documentPath, 'uploads/documents/')
            || str_starts_with($documentPath, 'documents/')
        ) {
            $prefix = str_starts_with($documentPath, 'uploads/documents/')
                ? 'uploads/documents/'
                : 'documents/';
            if (basename($documentPath) !== substr($documentPath, strlen($prefix))) {
                throw new RuntimeException('Invalid company registration document path.');
            }

            $path = public_path($documentPath);
            if (File::exists($path) && ! File::delete($path)) {
                throw new RuntimeException('Unable to delete company registration document.');
            }

            return;
        }

        $storage = Storage::disk('local');
        if (
            str_starts_with($documentPath, 'company-registration-documents/')
            && $storage->exists($documentPath)
            && ! $storage->delete($documentPath)
        ) {
            throw new RuntimeException('Unable to delete company registration document.');
        }
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
                    ? 'อนุมัติบริษัทเรียบร้อยแล้ว'
                    : 'ไม่อนุมัติบริษัทเรียบร้อยแล้ว',
                'data' => new CompanyResource($company->load('industry')),
            ]);
        }
    }
