<?php

namespace Tests\Feature;

use App\Models\company;
use App\Models\industry;
use App\Models\job;
use App\Models\jobFunction;
use App\Models\user;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_submit_companies_and_jobs_for_approval(): void
    {
        $owner = user::factory()->create();
        $industry = industry::create(['industry_name' => 'Technology']);
        $function = jobFunction::create(['function_name' => 'Engineering']);
        $approvedCompany = company::create([
            'company_name' => 'Approved Company',
            'industry_id' => $industry->industry_id,
        ]);

        $this->actingAs($owner, 'sanctum');

        $companyResponse = $this->postJson('/api/companies', [
            'company_name' => 'New Company',
            'industry_id' => $industry->industry_id,
        ])->assertCreated()
            ->assertJsonPath('message', 'ส่งข้อมูลบริษัทแล้ว รออนุมัติ')
            ->assertJsonPath('data.approval_status', 'pending');

        $jobResponse = $this->postJson('/api/jobs', [
            'company_id' => $approvedCompany->company_id,
            'function_id' => $function->function_id,
            'job_title' => 'Software Engineer',
            'job_description' => 'Build software.',
        ])->assertCreated()
            ->assertJsonPath('message', 'ส่งประกาศงานแล้ว รออนุมัติ')
            ->assertJsonPath('data.approval_status', 'pending');

        $this->assertDatabaseHas('companies', [
            'company_id' => $companyResponse->json('data.company_id'),
            'user_id' => $owner->user_id,
            'approval_status' => 'pending',
        ]);
        $this->assertDatabaseHas('jobs', [
            'job_id' => $jobResponse->json('data.job_id'),
            'user_id' => $owner->user_id,
            'approval_status' => 'pending',
        ]);
    }

    public function test_guests_cannot_submit_companies_or_jobs(): void
    {
        $industry = industry::create(['industry_name' => 'Technology']);
        $function = jobFunction::create(['function_name' => 'Engineering']);
        $company = company::create([
            'company_name' => 'Approved Company',
            'industry_id' => $industry->industry_id,
        ]);

        $this->postJson('/api/companies', [
            'company_name' => 'New Company',
            'industry_id' => $industry->industry_id,
        ])->assertUnauthorized();

        $this->postJson('/api/jobs', [
            'company_id' => $company->company_id,
            'function_id' => $function->function_id,
            'job_title' => 'Software Engineer',
            'job_description' => 'Build software.',
        ])->assertUnauthorized();
    }

    public function test_users_cannot_set_approval_status_or_submitter_id(): void
    {
        $owner = user::factory()->create();
        $industry = industry::create(['industry_name' => 'Technology']);
        $function = jobFunction::create(['function_name' => 'Engineering']);
        $company = company::create([
            'company_name' => 'Approved Company',
            'industry_id' => $industry->industry_id,
        ]);

        $this->actingAs($owner, 'sanctum');

        $this->postJson('/api/companies', [
            'company_name' => 'New Company',
            'industry_id' => $industry->industry_id,
            'approval_status' => 'approved',
            'user_id' => user::factory()->create()->user_id,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['approval_status', 'user_id']);

        $this->postJson('/api/jobs', [
            'company_id' => $company->company_id,
            'function_id' => $function->function_id,
            'job_title' => 'Software Engineer',
            'job_description' => 'Build software.',
            'approval_status' => 'approved',
            'user_id' => user::factory()->create()->user_id,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['approval_status', 'user_id']);
    }

    public function test_public_lists_and_details_only_expose_approved_companies_and_open_approved_jobs(): void
    {
        $industry = industry::create(['industry_name' => 'Technology']);
        $function = jobFunction::create(['function_name' => 'Engineering']);
        $approvedCompany = company::create([
            'company_name' => 'Approved Company',
            'industry_id' => $industry->industry_id,
        ]);
        $pendingCompany = company::create([
            'company_name' => 'Pending Company',
            'industry_id' => $industry->industry_id,
            'approval_status' => 'pending',
        ]);
        $rejectedCompany = company::create([
            'company_name' => 'Rejected Company',
            'industry_id' => $industry->industry_id,
            'approval_status' => 'rejected',
        ]);

        $approvedOpenJob = job::create([
            'company_id' => $approvedCompany->company_id,
            'function_id' => $function->function_id,
            'job_title' => 'Approved Open',
            'job_description' => 'Visible.',
        ]);
        $pendingJob = job::create([
            'company_id' => $approvedCompany->company_id,
            'function_id' => $function->function_id,
            'job_title' => 'Pending Job',
            'job_description' => 'Hidden.',
            'approval_status' => 'pending',
        ]);
        $rejectedJob = job::create([
            'company_id' => $approvedCompany->company_id,
            'function_id' => $function->function_id,
            'job_title' => 'Rejected Job',
            'job_description' => 'Hidden.',
            'approval_status' => 'rejected',
        ]);
        $closedJob = job::create([
            'company_id' => $approvedCompany->company_id,
            'function_id' => $function->function_id,
            'job_title' => 'Closed Job',
            'job_description' => 'Hidden.',
            'status' => 'closed',
        ]);

        $this->getJson('/api/companies')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.company_id', $approvedCompany->company_id);

        $this->getJson('/api/companies/'.$pendingCompany->company_id)->assertNotFound();
        $this->getJson('/api/companies/'.$rejectedCompany->company_id)->assertNotFound();
        $this->getJson('/api/companies/'.$approvedCompany->company_id)->assertOk();

        $this->getJson('/api/jobs')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.job_id', $approvedOpenJob->job_id);

        $this->getJson('/api/jobs/'.$pendingJob->job_id)->assertNotFound();
        $this->getJson('/api/jobs/'.$rejectedJob->job_id)->assertNotFound();
        $this->getJson('/api/jobs/'.$closedJob->job_id)->assertOk();
    }

    public function test_legacy_records_default_to_approved_and_remain_public(): void
    {
        $industry = industry::create(['industry_name' => 'Technology']);
        $function = jobFunction::create(['function_name' => 'Engineering']);
        $company = company::create([
            'company_name' => 'Legacy Company',
            'industry_id' => $industry->industry_id,
        ]);
        $job = job::create([
            'company_id' => $company->company_id,
            'function_id' => $function->function_id,
            'job_title' => 'Legacy Job',
            'job_description' => 'Existing listing.',
        ]);

        $this->assertSame('approved', $company->fresh()->approval_status);
        $this->assertSame('approved', $job->fresh()->approval_status);
        $this->getJson('/api/companies')->assertJsonPath('data.0.company_id', $company->company_id);
        $this->getJson('/api/jobs')->assertJsonPath('data.0.job_id', $job->job_id);
    }

    public function test_only_admins_can_list_and_change_approval_status(): void
    {
        $industry = industry::create(['industry_name' => 'Technology']);
        $function = jobFunction::create(['function_name' => 'Engineering']);
        $company = company::create([
            'company_name' => 'Pending Company',
            'industry_id' => $industry->industry_id,
            'approval_status' => 'pending',
        ]);
        $job = job::create([
            'company_id' => $company->company_id,
            'function_id' => $function->function_id,
            'job_title' => 'Pending Job',
            'job_description' => 'Review me.',
            'approval_status' => 'pending',
        ]);
        $user = user::factory()->create();

        $this->actingAs($user, 'sanctum');
        $this->getJson('/api/admin/companies')->assertForbidden();
        $this->getJson('/api/admin/jobs')->assertForbidden();
        $this->patchJson('/api/admin/companies/'.$company->company_id.'/approval', [
            'approval_status' => 'approved',
        ])->assertForbidden();
        $this->patchJson('/api/admin/jobs/'.$job->job_id.'/approval', [
            'approval_status' => 'approved',
        ])->assertForbidden();

        $admin = user::factory()->create(['role' => 'admin']);
        $this->actingAs($admin, 'sanctum');

        $this->getJson('/api/admin/companies?approval_status=pending')
            ->assertOk()
            ->assertJsonPath('data.0.approval_status', 'pending');
        $this->getJson('/api/admin/jobs?approval_status=pending')
            ->assertOk()
            ->assertJsonPath('data.0.approval_status', 'pending');

        $this->patchJson('/api/admin/companies/'.$company->company_id.'/approval', [
            'approval_status' => 'rejected',
            'rejection_reason' => 'Please provide more details.',
        ])->assertOk()
            ->assertJsonPath('data.approval_status', 'rejected')
            ->assertJsonPath('data.rejection_reason', 'Please provide more details.');
        $this->patchJson('/api/admin/jobs/'.$job->job_id.'/approval', [
            'approval_status' => 'approved',
        ])->assertOk()
            ->assertJsonPath('data.approval_status', 'approved');

        $this->assertDatabaseHas('companies', [
            'company_id' => $company->company_id,
            'approval_status' => 'rejected',
            'rejection_reason' => 'Please provide more details.',
        ]);
        $this->assertDatabaseHas('jobs', [
            'job_id' => $job->job_id,
            'approval_status' => 'approved',
        ]);
    }

    public function test_users_can_only_view_and_manage_their_own_submissions_and_edits_require_reapproval(): void
    {
        $owner = user::factory()->create();
        $otherUser = user::factory()->create();
        $industry = industry::create(['industry_name' => 'Technology']);
        $function = jobFunction::create(['function_name' => 'Engineering']);
        $company = company::create([
            'company_name' => 'Owned Company',
            'industry_id' => $industry->industry_id,
            'user_id' => $owner->user_id,
            'approval_status' => 'approved',
        ]);
        $otherCompany = company::create([
            'company_name' => 'Other Company',
            'industry_id' => $industry->industry_id,
            'user_id' => $otherUser->user_id,
        ]);
        $job = job::create([
            'company_id' => $company->company_id,
            'function_id' => $function->function_id,
            'job_title' => 'Owned Job',
            'job_description' => 'Old description.',
            'user_id' => $owner->user_id,
            'approval_status' => 'rejected',
            'rejection_reason' => 'Fix this.',
        ]);
        $otherJob = job::create([
            'company_id' => $company->company_id,
            'function_id' => $function->function_id,
            'job_title' => 'Other Job',
            'job_description' => 'Not yours.',
            'user_id' => $otherUser->user_id,
        ]);

        $this->actingAs($owner, 'sanctum');
        $this->getJson('/api/my/companies')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.company_id', $company->company_id);
        $this->getJson('/api/my/jobs')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.job_id', $job->job_id)
            ->assertJsonPath('data.0.rejection_reason', 'Fix this.');

        $this->patchJson('/api/companies/'.$otherCompany->company_id, [
            'company_name' => 'Updated',
        ])->assertForbidden();
        $this->deleteJson('/api/companies/'.$otherCompany->company_id)->assertForbidden();
        $this->patchJson('/api/jobs/'.$otherJob->job_id, [
            'job_title' => 'Updated',
        ])->assertForbidden();
        $this->deleteJson('/api/jobs/'.$otherJob->job_id)->assertForbidden();

        $this->patchJson('/api/companies/'.$company->company_id, [
            'approval_status' => 'approved',
            'user_id' => $otherUser->user_id,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['approval_status', 'user_id']);
        $this->patchJson('/api/jobs/'.$job->job_id, [
            'approval_status' => 'approved',
            'user_id' => $otherUser->user_id,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['approval_status', 'user_id']);

        $this->patchJson('/api/companies/'.$company->company_id, [
            'company_name' => 'Edited Company',
        ])->assertOk()
            ->assertJsonPath('data.approval_status', 'pending');
        $this->patchJson('/api/jobs/'.$job->job_id, [
            'job_description' => 'Updated description.',
        ])->assertOk()
            ->assertJsonPath('data.approval_status', 'pending')
            ->assertJsonPath('data.rejection_reason', null);
    }
}
