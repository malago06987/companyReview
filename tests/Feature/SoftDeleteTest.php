<?php

namespace Tests\Feature;

use App\Models\company;
use App\Models\industry;
use App\Models\job;
use App\Models\jobFunction;
use App\Models\review;
use App\Models\user;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_delete_endpoints_soft_delete_company_job_review_and_user(): void
    {
        $owner = user::factory()->create();
        $admin = user::factory()->create(['role' => 'admin']);
        $deletedUser = user::factory()->create();
        $industry = industry::create(['industry_name' => 'Technology']);
        $function = jobFunction::create(['function_name' => 'Engineering']);

        $company = company::create([
            'company_name' => 'Company to delete',
            'industry_id' => $industry->industry_id,
            'user_id' => $owner->user_id,
            'approval_status' => 'approved',
        ]);
        $job = job::create([
            'company_id' => $company->company_id,
            'function_id' => $function->function_id,
            'job_title' => 'Job to delete',
            'job_description' => 'Description',
            'user_id' => $owner->user_id,
            'approval_status' => 'approved',
        ]);
        $review = review::create([
            'company_id' => $company->company_id,
            'user_id' => $owner->user_id,
            'rating_life' => 4,
            'rating_work' => 4,
            'rating_money' => 4,
            'rating_society' => 4,
            'review_text' => 'Review to delete',
        ]);

        $this->actingAs($owner, 'sanctum')
            ->deleteJson('/api/companies/'.$company->company_id)
            ->assertOk();
        $this->assertDatabaseHas('jobs', [
            'job_id' => $job->job_id,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('reviews', [
            'review_id' => $review->review_id,
            'deleted_at' => null,
        ]);
        $this->deleteJson('/api/jobs/'.$job->job_id)->assertNoContent();
        $this->deleteJson('/api/reviews/'.$review->review_id)->assertNoContent();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/users/'.$deletedUser->user_id)
            ->assertOk();

        foreach ([
            [company::class, $company->company_id, 'companies'],
            [job::class, $job->job_id, 'jobs'],
            [review::class, $review->review_id, 'reviews'],
            [user::class, $deletedUser->user_id, 'users'],
        ] as [$model, $id, $table]) {
            $this->assertFalse($model::query()->whereKey($id)->exists());
            $this->assertTrue($model::withTrashed()->whereKey($id)->exists());
            $this->assertDatabaseHas($table, [
                match ($table) {
                    'companies' => 'company_id',
                    'jobs' => 'job_id',
                    'reviews' => 'review_id',
                    'users' => 'user_id',
                } => $id,
            ]);
            $this->assertNotNull($model::withTrashed()->findOrFail($id)->deleted_at);
        }

        $this->assertFalse($company->jobs()->whereKey($job->job_id)->exists());
        $this->assertFalse($company->reviews()->whereKey($review->review_id)->exists());
        $this->getJson('/api/companies')
            ->assertOk()
            ->assertJsonMissing(['company_id' => $company->company_id]);
        $this->getJson('/api/jobs')
            ->assertOk()
            ->assertJsonMissing(['job_id' => $job->job_id]);
        $this->getJson('/api/reviews')
            ->assertOk()
            ->assertJsonMissing(['review_id' => $review->review_id]);
        $this->getJson('/api/companies/'.$company->company_id)->assertNotFound();
        $this->getJson('/api/jobs/'.$job->job_id)->assertNotFound();
        $this->getJson('/api/reviews/'.$review->review_id)->assertNotFound();
        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/users')
            ->assertOk()
            ->assertJsonMissing(['user_id' => $deletedUser->user_id]);
        $this->getJson('/api/users/'.$deletedUser->user_id)
            ->assertNotFound();
    }
}
