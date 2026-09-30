<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\jobFunction;
use Illuminate\Http\Request;

class jobFunctionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(jobFunction::withCount('jobs')->latest('function_id')->get());
    }

    /**
     * Store a newly created resource in storage.
     */
 public function store(Request $request)
{
    abort_unless($request->user()?->role === 'admin', 403);

    $validated = $request->validate([
        'function_name' => ['required', 'string', 'max:255', 'unique:job_functions,function_name'],
    ]);

    return response()->json(jobFunction::create($validated), 201);
}

    /**
     * Display the specified resource.
     */
    public function show(jobFunction $jobFunction)
    {
        return response()->json($jobFunction->load('jobs'));
    }

    /**
     * Update the specified resource in storage.
     */
public function update(Request $request, jobFunction $jobFunction)
{
    abort_unless($request->user()?->role === 'admin', 403);

    $validated = $request->validate([
        'function_name' => [
            'required',
            'string',
            'max:255',
            'unique:job_functions,function_name,' .
            $jobFunction->function_id .
            ',function_id'
        ],
    ]);

    $jobFunction->update($validated);

    return response()->json($jobFunction);
}

    /**
     * Remove the specified resource from storage.
     */
public function destroy(jobFunction $jobFunction)
{
    abort_unless(request()->user()?->role === 'admin', 403);

    abort_if(
        $jobFunction->jobs()->exists(),
        409,
        'Cannot delete a job function with jobs.'
    );

    $jobFunction->delete();

    return response()->noContent();
}

}
