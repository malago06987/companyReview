<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\job;
use Illuminate\Http\Request;

class jobController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = job::with(['company', 'jobFunction'])->where('status', 'open');

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->integer('company_id'));
        }

        return response()->json($query->latest('job_id')->paginate(15));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,company_id'],
            'function_id' => ['required', 'exists:job_functions,function_id'],
            'job_title' => ['required', 'string', 'max:255'],
            'job_description' => ['required', 'string'],
            'salary' => ['nullable', 'string', 'max:255'],
            'work_location' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'in:open,closed'],
        ]);

        return response()->json(job::create($validated)->load(['company', 'jobFunction']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(job $job)
    {
        return response()->json($job->load(['company', 'jobFunction']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, job $job)
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $validated = $request->validate([
            'company_id' => ['sometimes', 'exists:companies,company_id'],
            'function_id' => ['sometimes', 'exists:job_functions,function_id'],
            'job_title' => ['sometimes', 'string', 'max:255'],
            'job_description' => ['sometimes', 'string'],
            'salary' => ['nullable', 'string', 'max:255'],
            'work_location' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'in:open,closed'],
        ]);

        $job->update($validated);

        return response()->json($job->load(['company', 'jobFunction']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(job $job)
    {
        abort_unless(request()->user()?->role === 'admin', 403);

        $job->delete();

        return response()->noContent();
    }
}
