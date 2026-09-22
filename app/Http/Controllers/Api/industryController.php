<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\industry;
use Illuminate\Http\Request;

class industryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(industry::withCount('companies')->latest('industry_id')->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'industry_name' => ['required', 'string', 'max:255', 'unique:industries,industry_name'],
        ]);

        return response()->json(industry::create($validated), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(industry $industry)
    {
        return response()->json($industry->load('companies'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, industry $industry)
    {
        $validated = $request->validate([
            'industry_name' => ['required', 'string', 'max:255', 'unique:industries,industry_name,'.$industry->industry_id.',industry_id'],
        ]);

        $industry->update($validated);

        return response()->json($industry);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(industry $industry)
    {
        abort_if($industry->companies()->exists(), 409, 'Cannot delete an industry with companies.');
        $industry->delete();

        return response()->noContent();
    }
}
