<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\job;
use Illuminate\Http\Request;
use App\Http\Resources\JobResource;
use Illuminate\Validation\Rule;

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
        ]);

        $validated['approval_status'] = 'pending';
        $validated['user_id'] = $request->user()->user_id;

        $job = job::create($validated);

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
        abort_unless(
            $job->approval_status === 'approved' || request()->user()?->role === 'admin',
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
            'user_id' => ['prohibited'],
            'role' => ['prohibited'],
        ]);

        if ($request->user()->role !== 'admin') {
            $validated['approval_status'] = 'pending';
            $validated['rejection_reason'] = null;
        }

        $job->update($validated);

        return response()->json([
            'message' => 'Job updated successfully.',
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
                ? 'Job approved successfully.'
                : 'Job rejected successfully.',
            'data' => new JobResource($job->load(['company', 'jobFunction'])),
        ]);
    }
}
