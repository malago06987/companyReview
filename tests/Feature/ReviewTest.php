<?php

namespace Tests\Feature;

use App\Models\company;
use App\Models\industry;
use App\Models\user;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_the_expected_records(): void
    {
        $this->seed();

        $this->assertDatabaseCount('users', 20);
        $this->assertDatabaseCount('industries', 10);
        $this->assertDatabaseCount('companies', 20);
        $this->assertDatabaseCount('job_functions', 10);
        $this->assertDatabaseCount('jobs', 50);
        $this->assertDatabaseCount('reviews', 100);
    }

    public function test_a_user_can_review_a_company_only_once(): void
    {
        $user = user::create([
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        $industry = industry::create(['industry_name' => 'Technology']);
        $company = company::create([
            'company_name' => 'Example Company',
            'industry_id' => $industry->industry_id,
        ]);

        $this->actingAs($user, 'sanctum');

        $review = [
            'company_id' => $company->company_id,
            'rating_life' => 4,
            'rating_work' => 4,
            'rating_money' => 4,
            'rating_society' => 4,
            'review_text' => 'A good place to work.',
        ];

        $this->postJson('/api/reviews', $review)->assertCreated();
        $this->postJson('/api/reviews', $review)->assertUnprocessable()->assertJsonValidationErrors('company_id');

        $this->assertDatabaseCount('reviews', 1);
    }
}
