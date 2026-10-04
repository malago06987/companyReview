<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\job;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use App\Http\Resources\JobResource;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class jobController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = job::with(['company', 'jobFunction']);

        if ($request->user()?->role === 'admin') {
            if ($request->filled('approval_status')) {
                $query->where('approval_status', $request->input('approval_status'));
            }
        } else {
            $query->where('approval_status', 'approved')
                ->where('status', 'open')
                ->whereHas('company', fn ($companyQuery) => $companyQuery->where('approval_status', 'approved'));
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->integer('company_id'));
        }

        $jobs = $query->latest('job_id')->paginate(15);

        return JobResource::collection($jobs);
    }

    public function mine(Request $request)
    {
        $jobs = job::with(['company', 'jobFunction'])
            ->withCount('applications')
            ->where('user_id', $request->user()->user_id)
            ->latest('job_id')
            ->get();

        return JobResource::collection($jobs);
    }

    public function adminIndex(Request $request)
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $query = job::with(['company', 'jobFunction']);
        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->input('approval_status'));
        }

        return JobResource::collection($query->latest('job_id')->paginate(15));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $companyExists = $request->user()->role === 'admin'
            ? Rule::exists('companies', 'company_id')
            : Rule::exists('companies', 'company_id')->where('approval_status', 'approved');
        $documentRules = $request->user()->role === 'admin'
            ? ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120']
            : ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];

        $validated = $request->validate([
            'company_id' => ['required', $companyExists],
            'function_id' => ['required', 'exists:job_functions,function_id'],
            'job_title' => ['required', 'string', 'max:255'],
            'job_description' => ['required', 'string'],
            'salary' => ['nullable', 'string', 'max:255'],
            'work_location' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'in:open,closed'],
            'approval_status' => ['prohibited'],
            'user_id' => ['prohibited'],
            'role' => ['prohibited'],
            'authorization_document' => $documentRules,
            'document' => ['prohibited'],
            'authorization_document_path' => ['prohibited'],
        ]);

        $authorizationDocumentPath = null;
        if ($request->hasFile('authorization_document')) {
            $authorizationDocumentPath = $this->storeAuthorizationDocument(
                $request->file('authorization_document')
            );
        }

        $validated['approval_status'] = 'pending';
        $validated['user_id'] = $request->user()->user_id;
        $validated['document'] = $authorizationDocumentPath;
        unset($validated['authorization_document']);

        try {
            $job = job::create($validated);
        } catch (\Throwable $exception) {
            if ($authorizationDocumentPath !== null) {
                $this->deleteAuthorizationDocument($authorizationDocumentPath);
            }

            throw $exception;
        }

        return response()->json([
            'message' => 'ส่งประกาศงานแล้ว รออนุมัติ',
            'data' => new JobResource(
                $job->load(['company', 'jobFunction'])
            )
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(job $job)
    {
        $isAdmin = request()->user()?->role === 'admin';
        $companyIsApproved = $job->company?->approval_status === 'approved';

        abort_unless(
            $isAdmin || (
                $job->approval_status === 'approved'
                && $job->status === 'open'
                && $companyIsApproved
            ),
            404
        );

        return new JobResource(
            $job->load(['company', 'jobFunction'])
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, job $job)
    {
        abort_unless(
            $request->user()?->role === 'admin' || $job->user_id === $request->user()?->user_id,
            403
        );

        $validated = $request->validate([
            'company_id' => ['sometimes', 'exists:companies,company_id'],
            'function_id' => ['sometimes', 'exists:job_functions,function_id'],
            'job_title' => ['sometimes', 'string', 'max:255'],
            'job_description' => ['sometimes', 'string'],
            'salary' => ['nullable', 'string', 'max:255'],
            'work_location' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'in:open,closed'],
            'approval_status' => ['prohibited'],
            'rejection_reason' => ['prohibited'],
            'user_id' => ['prohibited'],
            'role' => ['prohibited'],
            'authorization_document' => ['prohibited'],
            'document' => ['prohibited'],
            'authorization_document_path' => ['prohibited'],
        ]);

        $hasJobDetails = collect([
            'company_id',
            'function_id',
            'job_title',
            'job_description',
            'salary',
            'work_location',
            'employment_type',
        ])->contains(fn (string $field) => array_key_exists($field, $validated));

        if (! $hasJobDetails && array_key_exists('status', $validated)) {
            abort_if(
                $request->user()->role !== 'admin'
                    && $job->approval_status !== 'approved',
                422,
                'เปลี่ยนสถานะรับสมัครได้หลังจากประกาศผ่านการอนุมัติแล้วเท่านั้น'
            );

            $job->update(['status' => $validated['status']]);

            return response()->json([
                'message' => 'เปลี่ยนสถานะการรับสมัครเรียบร้อยแล้ว',
                'data' => new JobResource(
                    $job->load(['company', 'jobFunction'])
                )
            ]);
        }

        if ($request->user()->role !== 'admin' && $hasJobDetails) {
            $validated['approval_status'] = 'pending';
            $validated['rejection_reason'] = null;
        }

        $job->update($validated);

        return response()->json([
            'message' => 'แก้ไขประกาศงานเรียบร้อยแล้ว',
            'data' => new JobResource(
                $job->load(['company', 'jobFunction'])
            )
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(job $job)
    {
        abort_unless(
            request()->user()?->role === 'admin' || $job->user_id === request()->user()?->user_id,
            403
        );

        $job->delete();

        return response()->noContent();
    }

    public function authorizationDocument(Request $request, job $job)
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $documentPath = $job->document;
        abort_unless($documentPath !== null, 404, 'ไม่พบเอกสารประกอบการสมัครงาน');

        if (str_starts_with($documentPath, 'uploads/documents_job/')) {
            $path = public_path($documentPath);
            abort_unless(
                basename($documentPath) === substr($documentPath, strlen('uploads/documents_job/'))
                    && File::exists($path),
                404,
                'ไม่พบเอกสารประกอบการสมัครงาน'
            );

            return response()->file($path, ['Content-Disposition' => 'inline']);
        }

        $storage = Storage::disk('local');
        abort_unless(
            str_starts_with($documentPath, 'job-authorization-documents/')
                && $storage->exists($documentPath),
            404,
            'ไม่พบเอกสารประกอบการสมัครงาน'
        );

        return response()->file($storage->path($documentPath), ['Content-Disposition' => 'inline']);
    }

    private function storeAuthorizationDocument(UploadedFile $file): string
    {
        $directory = public_path('uploads/documents_job');
        File::ensureDirectoryExists($directory);

        $filename = $file->hashName();
        $file->move($directory, $filename);

        return 'uploads/documents_job/' . $filename;
    }

    private function deleteAuthorizationDocument(?string $documentPath): void
    {
        if ($documentPath === null) {
            return;
        }

        if (str_starts_with($documentPath, 'uploads/documents_job/')) {
            if (basename($documentPath) !== substr($documentPath, strlen('uploads/documents_job/'))) {
                throw new RuntimeException('Invalid authorization document path.');
            }

            $path = public_path($documentPath);
            if (File::exists($path) && ! File::delete($path)) {
                throw new RuntimeException('Unable to delete authorization document.');
            }

            return;
        }

        $storage = Storage::disk('local');
        if (
            str_starts_with($documentPath, 'job-authorization-documents/')
            && $storage->exists($documentPath)
            && ! $storage->delete($documentPath)
        ) {
            throw new RuntimeException('Unable to delete authorization document.');
        }
    }

    public function updateApproval(Request $request, job $job)
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $validated = $request->validate([
            'approval_status' => ['required', 'in:approved,rejected'],
            'rejection_reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $job->update([
            'approval_status' => $validated['approval_status'],
            'rejection_reason' => $validated['approval_status'] === 'rejected'
                ? ($validated['rejection_reason'] ?? null)
                : null,
        ]);

        return response()->json([
            'message' => $validated['approval_status'] === 'approved'
                ? 'อนุมัติประกาศงานเรียบร้อยแล้ว'
                : 'ไม่อนุมัติประกาศงานเรียบร้อยแล้ว',
            'data' => new JobResource($job->load(['company', 'jobFunction'])),
        ]);
    }
}
