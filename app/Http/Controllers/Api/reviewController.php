<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\review;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class reviewController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = review::with(['company', 'user'])
            ->where('status', 'approved');

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->integer('company_id'));
        }

        return response()->json($query->latest('review_id')->paginate(15));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => [
                'required',
                'exists:companies,company_id',
                Rule::unique('reviews', 'company_id')->where('user_id', $request->user()->user_id),
            ],
            'rating_life' => ['required', 'integer', 'between:1,5'],
            'rating_work' => ['required', 'integer', 'between:1,5'],
            'rating_money' => ['required', 'integer', 'between:1,5'],
            'rating_society' => ['required', 'integer', 'between:1,5'],
            'review_text' => ['required', 'string'],
        ]);

        $review = review::create([
            ...$validated,
            'user_id' => $request->user()->user_id,
            'status' => 'pending',
        ]);

        return response()->json($review->load(['company', 'user']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(review $review)
    {
        abort_unless($review->status === 'approved', 404);

        return response()->json($review->load(['company', 'user']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, review $review)
    {
        abort_unless($this->canManage($request, $review), 403);

        $validated = $request->validate([
            'rating_life' => ['sometimes', 'integer', 'between:1,5'],
            'rating_work' => ['sometimes', 'integer', 'between:1,5'],
            'rating_money' => ['sometimes', 'integer', 'between:1,5'],
            'rating_society' => ['sometimes', 'integer', 'between:1,5'],
            'review_text' => ['sometimes', 'string'],
            'status' => ['sometimes', 'in:pending,approved,rejected'],
        ]);

        if ($request->user()->role !== 'admin') {
            unset($validated['status']);
            $validated['status'] = 'pending';
        }

        $review->update($validated);

        return response()->json($review->load(['company', 'user']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, review $review)
    {
        abort_unless($this->canManage($request, $review), 403);
        $review->delete();

        return response()->noContent();
    }

    private function canManage(Request $request, review $review): bool
    {
        return $request->user()->role === 'admin'
            || $request->user()->user_id === $review->user_id;
    }
}
