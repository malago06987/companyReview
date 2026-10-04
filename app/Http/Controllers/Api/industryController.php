<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\industryResource;
use App\Models\industry;
use Illuminate\Http\Request;

class industryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $industries = industry::withCount('companies')
            ->latest('industry_id')
            ->get();

        return industryResource::collection($industries);
    }

    /**
     * Store a newly created resource in storage.
     */
public function store(Request $request)
{
    abort_unless($request->user()?->role === 'admin', 403);

    $validated = $request->validate([
        'industry_name' => [
            'required',
            'string',
            'max:255',
            'unique:industries,industry_name'
        ],
    ]);

    $industry = industry::create($validated);

    return new industryResource($industry);
}

    /**
     * Display the specified resource.
     */
    public function show(industry $industry)
    {
        return new industryResource($industry);
    }

    /**
     * Update the specified resource in storage.
     */
public function update(Request $request, industry $industry)
{
    abort_unless($request->user()?->role === 'admin', 403);

    $validated = $request->validate([
        'industry_name' => [
            'required',
            'string',
            'max:255',
            'unique:industries,industry_name,' .
            $industry->industry_id .
            ',industry_id'
        ],
    ]);

    $industry->update($validated);

    return new industryResource($industry);
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(industry $industry)
{
    abort_unless(request()->user()?->role === 'admin', 403);

    abort_if(
        $industry->companies()->exists(),
        409,
        'ไม่สามารถลบประเภทธุรกิจนี้ได้ เนื่องจากยังมีบริษัทใช้งานอยู่'
    );

    $industry->delete();

    return response()->noContent();
}
}
