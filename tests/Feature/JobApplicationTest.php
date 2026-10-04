<?php

namespace Tests\Feature;

use App\Models\company;
use App\Models\industry;
use App\Models\job;
use App\Models\jobFunction;
use App\Models\JobApplication;
use App\Models\user;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class JobApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_submit_a_resume_and_job_owner_can_view_it_inline(): void
    {
        $owner = user::factory()->create();
        $applicant = user::factory()->create();
        $job = $this->createOpenJob($owner);

        $applicationResponse = $this->actingAs($applicant, 'sanctum')
            ->post('/api/jobs/'.$job->job_id.'/applications', [
                'resume' => UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf'),
                'cover_letter' => 'I would like to apply.',
            ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.applicant.user_id', $applicant->user_id)
            ->assertJsonPath('data.job.job_id', $job->job_id)
            ->assertJsonPath('data.status', 'submitted');

        $this->getJson('/api/my/applications')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.resume_url', $applicationResponse->json('data.resume_url'));

        $applicationId = $applicationResponse->json('data.application_id');
        $application = JobApplication::findOrFail($applicationId);
        $this->assertStringStartsWith('uploads/document_jobApp/', $application->resume_path);
        $this->assertFileExists(public_path($application->resume_path));

        $this->get($applicationResponse->json('data.resume_url'))
            ->assertOk()
            ->assertHeader('content-disposition', 'inline');

        $this->actingAs($owner, 'sanctum')
            ->getJson('/api/jobs/'.$job->job_id.'/applications')
            ->assertOk()
            ->assertJsonPath('data.0.applicant.email', $applicant->email);

        $this->get('/api/jobs/'.$job->job_id.'/applications/'.$applicationId.'/resume')
            ->assertOk()
            ->assertHeader('content-disposition', 'inline');

        $this->getJson('/api/my/jobs')
            ->assertOk()
            ->assertJsonPath('data.0.applications_count', 1);
    }

    public function test_only_the_job_owner_can_view_applicants_or_download_resumes(): void
    {
        $owner = user::factory()->create();
        $applicant = user::factory()->create();
        $otherUser = user::factory()->create();
        $job = $this->createOpenJob($owner);

        $this->actingAs($applicant, 'sanctum')
            ->post('/api/jobs/'.$job->job_id.'/applications', [
                'resume' => UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf'),
            ], ['Accept' => 'application/json'])
            ->assertCreated();

        $applicationId = JobApplication::query()->value('application_id');

        $this->actingAs($otherUser, 'sanctum')
            ->getJson('/api/jobs/'.$job->job_id.'/applications')
            ->assertForbidden();

        $this->get('/api/jobs/'.$job->job_id.'/applications/'.$applicationId.'/resume')
            ->assertForbidden();

        $this->actingAs($applicant, 'sanctum')
            ->get('/api/jobs/'.$job->job_id.'/applications/'.$applicationId.'/resume')
            ->assertOk();
    }

    public function test_user_cannot_apply_twice_or_to_a_closed_job(): void
    {
        $owner = user::factory()->create();
        $applicant = user::factory()->create();
        $job = $this->createOpenJob($owner);
        $payload = [
            'resume' => UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf'),
        ];

        $this->actingAs($applicant, 'sanctum')
            ->post('/api/jobs/'.$job->job_id.'/applications', $payload, ['Accept' => 'application/json'])
            ->assertCreated();

        $this->post('/api/jobs/'.$job->job_id.'/applications', $payload, ['Accept' => 'application/json'])
            ->assertUnprocessable();

        $job->update(['status' => 'closed']);

        $this->actingAs(user::factory()->create(), 'sanctum')
            ->post('/api/jobs/'.$job->job_id.'/applications', [
                'resume' => UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf'),
            ], ['Accept' => 'application/json'])
            ->assertNotFound();
    }

    private function createOpenJob(user $owner): job
    {
        $industry = industry::create(['industry_name' => 'Technology']);
        $company = company::create([
            'company_name' => 'Approved Company',
            'industry_id' => $industry->industry_id,
            'approval_status' => 'approved',
            'user_id' => $owner->user_id,
        ]);
        $function = jobFunction::create(['function_name' => 'Engineering']);

        return job::create([
            'company_id' => $company->company_id,
            'function_id' => $function->function_id,
            'job_title' => 'Software Engineer',
            'job_description' => 'Build software.',
            'status' => 'open',
            'approval_status' => 'approved',
            'user_id' => $owner->user_id,
        ]);
    }

    protected function tearDown(): void
    {
        foreach (JobApplication::query()
            ->where('resume_path', 'like', 'uploads/document_jobApp/%')
            ->pluck('resume_path') as $resumePath) {
            if (basename($resumePath) === substr($resumePath, strlen('uploads/document_jobApp/'))) {
                File::delete(public_path($resumePath));
            }
        }

        parent::tearDown();
    }
}
