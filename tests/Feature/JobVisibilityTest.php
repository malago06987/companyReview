<?php

namespace Tests\Feature;

use App\Models\company;
use App\Models\industry;
use App\Models\job;
use App\Models\jobFunction;
use App\Models\user;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_job_list_and_details_only_show_open_approved_jobs_of_approved_companies(): void
    {
        $industry = industry::create(['industry_name' => 'Technology']);
        $approvedCompany = company::create([
            'company_name' => 'Approved Company',
            'industry_id' => $industry->industry_id,
            'approval_status' => 'approved',
        ]);
        $pendingCompany = company::create([
            'company_name' => 'Pending Company',
            'industry_id' => $industry->industry_id,
            'approval_status' => 'pending',
        ]);
        $function = jobFunction::create(['function_name' => 'Engineering']);

        $visibleJob = $this->createJob($approvedCompany, $function, 'open', 'approved');
        $closedJob = $this->createJob($approvedCompany, $function, 'closed', 'approved');
        $pendingJob = $this->createJob($approvedCompany, $function, 'open', 'pending');
        $jobOfPendingCompany = $this->createJob($pendingCompany, $function, 'open', 'approved');

        $this->getJson('/api/jobs')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.job_id', $visibleJob->job_id);

        foreach ([$closedJob, $pendingJob, $jobOfPendingCompany] as $hiddenJob) {
            $this->getJson('/api/jobs/'.$hiddenJob->job_id)->assertNotFound();
        }

        $this->getJson('/api/jobs/'.$visibleJob->job_id)->assertOk();
    }

    public function test_admin_can_still_view_closed_jobs_for_management(): void
    {
        $admin = user::factory()->create(['role' => 'admin']);
        $industry = industry::create(['industry_name' => 'Technology']);
        $company = company::create([
            'company_name' => 'Approved Company',
            'industry_id' => $industry->industry_id,
            'approval_status' => 'approved',
        ]);
        $function = jobFunction::create(['function_name' => 'Engineering']);
        $closedJob = $this->createJob($company, $function, 'closed', 'approved');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/jobs/'.$closedJob->job_id)
            ->assertOk()
            ->assertJsonPath('data.job_id', $closedJob->job_id);
    }

    public function test_owner_can_close_and_reopen_approved_job_without_reapproval(): void
    {
        $owner = user::factory()->create();
        $industry = industry::create(['industry_name' => 'Technology']);
        $company = company::create([
            'company_name' => 'Approved Company',
            'industry_id' => $industry->industry_id,
            'approval_status' => 'approved',
        ]);
        $function = jobFunction::create(['function_name' => 'Engineering']);
        $approvedJob = $this->createJob($company, $function, 'open', 'approved');
        $approvedJob->update([
            'user_id' => $owner->user_id,
            'rejection_reason' => 'Preserve existing reason',
        ]);

        $this->actingAs($owner, 'sanctum')
            ->patchJson('/api/jobs/'.$approvedJob->job_id, ['status' => 'closed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'closed')
            ->assertJsonPath('data.approval_status', 'approved');

        $this->assertDatabaseHas('jobs', [
            'job_id' => $approvedJob->job_id,
            'status' => 'closed',
            'approval_status' => 'approved',
            'rejection_reason' => 'Preserve existing reason',
        ]);
        $this->getJson('/api/jobs/'.$approvedJob->job_id)->assertNotFound();
        $this->getJson('/api/jobs')->assertJsonCount(0, 'data');

        $this->patchJson('/api/jobs/'.$approvedJob->job_id, ['status' => 'open'])
            ->assertOk()
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.approval_status', 'approved');

        $this->getJson('/api/jobs/'.$approvedJob->job_id)->assertOk();
    }

    public function test_owner_cannot_change_application_status_before_job_is_approved(): void
    {
        $owner = user::factory()->create();
        $industry = industry::create(['industry_name' => 'Technology']);
        $company = company::create([
            'company_name' => 'Approved Company',
            'industry_id' => $industry->industry_id,
            'approval_status' => 'approved',
        ]);
        $function = jobFunction::create(['function_name' => 'Engineering']);
        $pendingJob = $this->createJob($company, $function, 'open', 'pending');
        $pendingJob->update(['user_id' => $owner->user_id]);

        $this->actingAs($owner, 'sanctum')
            ->patchJson('/api/jobs/'.$pendingJob->job_id, ['status' => 'closed'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'เปลี่ยนสถานะรับสมัครได้หลังจากประกาศผ่านการอนุมัติแล้วเท่านั้น');

        $this->assertDatabaseHas('jobs', [
            'job_id' => $pendingJob->job_id,
            'status' => 'open',
            'approval_status' => 'pending',
        ]);
    }

    public function test_editing_job_details_sends_owner_submission_back_for_approval(): void
    {
        $owner = user::factory()->create();
        $industry = industry::create(['industry_name' => 'Technology']);
        $company = company::create([
            'company_name' => 'Approved Company',
            'industry_id' => $industry->industry_id,
            'approval_status' => 'approved',
        ]);
        $function = jobFunction::create(['function_name' => 'Engineering']);
        $approvedJob = $this->createJob($company, $function, 'open', 'approved');
        $approvedJob->update([
            'user_id' => $owner->user_id,
            'rejection_reason' => 'Old rejection reason',
        ]);

        $this->actingAs($owner, 'sanctum')
            ->patchJson('/api/jobs/'.$approvedJob->job_id, [
                'job_title' => 'Updated title',
            ])
            ->assertOk()
            ->assertJsonPath('data.approval_status', 'pending')
            ->assertJsonPath('data.rejection_reason', null);

        $this->assertDatabaseHas('jobs', [
            'job_id' => $approvedJob->job_id,
            'job_title' => 'Updated title',
            'approval_status' => 'pending',
            'rejection_reason' => null,
        ]);
    }

    public function test_owner_cannot_set_approval_or_rejection_fields_when_changing_status(): void
    {
        $owner = user::factory()->create();
        $industry = industry::create(['industry_name' => 'Technology']);
        $company = company::create([
            'company_name' => 'Approved Company',
            'industry_id' => $industry->industry_id,
            'approval_status' => 'approved',
        ]);
        $function = jobFunction::create(['function_name' => 'Engineering']);
        $approvedJob = $this->createJob($company, $function, 'open', 'approved');
        $approvedJob->update(['user_id' => $owner->user_id]);

        $this->actingAs($owner, 'sanctum')
            ->patchJson('/api/jobs/'.$approvedJob->job_id, [
                'status' => 'closed',
                'approval_status' => 'rejected',
                'rejection_reason' => 'Forged',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['approval_status', 'rejection_reason']);
    }

    private function createJob(company $company, jobFunction $function, string $status, string $approvalStatus): job
    {
        return job::create([
            'company_id' => $company->company_id,
            'function_id' => $function->function_id,
            'job_title' => 'Software Engineer',
            'job_description' => 'Build software.',
            'status' => $status,
            'approval_status' => $approvalStatus,
        ]);
    }
}
