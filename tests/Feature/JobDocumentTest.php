<?php

namespace Tests\Feature;

use App\Models\company;
use App\Models\industry;
use App\Models\job;
use App\Models\jobFunction;
use App\Models\user;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class JobDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_document_is_stored_under_uploads_documents_job(): void
    {
        $owner = user::factory()->create();
        $admin = user::factory()->create(['role' => 'admin']);
        $industry = industry::create(['industry_name' => 'Technology']);
        $company = company::create([
            'company_name' => 'Approved Company',
            'industry_id' => $industry->industry_id,
        ]);
        $function = jobFunction::create(['function_name' => 'Engineering']);

        $this->actingAs($owner, 'sanctum');

        $response = $this->post('/api/jobs', [
            'company_id' => $company->company_id,
            'function_id' => $function->function_id,
            'job_title' => 'Software Engineer',
            'job_description' => 'Build software.',
            'authorization_document' => UploadedFile::fake()->create(
                'authorization.pdf',
                100,
                'application/pdf'
            ),
        ], ['Accept' => 'application/json'])->assertCreated()
            ->assertJsonMissingPath('data.has_authorization_document');

        $job = job::findOrFail($response->json('data.job_id'));
        $this->assertStringStartsWith('uploads/documents_job/', $job->document);
        $path = public_path($job->document);
        $this->assertFileExists($path);

        $this->actingAs($admin, 'sanctum')
            ->get('/api/admin/jobs/'.$job->job_id.'/authorization-document')
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->actingAs($owner, 'sanctum')
            ->deleteJson('/api/jobs/'.$job->job_id)
            ->assertNoContent();
        $this->assertFileExists($path);
        unlink($path);
    }
}
