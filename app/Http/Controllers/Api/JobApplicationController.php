<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\JobApplicationResource;
use App\Models\JobApplication;
use App\Models\job;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

class JobApplicationController extends Controller
{
    public function store(Request $request, job $job)
    {
        abort_unless(
            $job->approval_status === 'approved'
                && $job->status === 'open'
                && $job->company?->approval_status === 'approved',
            404
        );

        abort_if(
            $job->user_id === $request->user()->user_id,
            422,
            'ไม่สามารถสมัครงานที่ตนเองเป็นผู้ลงประกาศได้'
        );

        $validated = $request->validate([
            'resume' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
            'cover_letter' => ['nullable', 'string', 'max:10000'],
        ]);

        $alreadyApplied = JobApplication::query()
            ->where('job_id', $job->job_id)
            ->where('user_id', $request->user()->user_id)
            ->exists();

        abort_if($alreadyApplied, 422, 'คุณสมัครงานนี้ไปแล้ว');

        $resumePath = $this->storeResume($validated['resume']);

        try {
            $application = JobApplication::create([
                'job_id' => $job->job_id,
                'user_id' => $request->user()->user_id,
                'resume_path' => $resumePath,
                'cover_letter' => $validated['cover_letter'] ?? null,
                'status' => 'submitted',
            ]);
        } catch (\Throwable $exception) {
            if (! File::delete(public_path($resumePath))) {
                throw new \RuntimeException(
                    'Unable to clean up resume after application creation failed.',
                    previous: $exception
                );
            }

            throw $exception;
        }

        return response()->json([
            'message' => 'ส่งใบสมัครเรียบร้อยแล้ว',
            'data' => new JobApplicationResource($application->load(['job', 'applicant'])),
        ], 201);
    }

    public function mine(Request $request)
    {
        $applications = JobApplication::query()
            ->with(['job', 'applicant'])
            ->where('user_id', $request->user()->user_id)
            ->latest('application_id')
            ->paginate(15);

        return JobApplicationResource::collection($applications);
    }

    public function index(Request $request, job $job)
    {
        $this->authorizeJobOwner($request, $job);

        $applications = JobApplication::query()
            ->with(['job', 'applicant'])
            ->where('job_id', $job->job_id)
            ->latest('application_id')
            ->paginate(15);

        return JobApplicationResource::collection($applications);
    }

    public function resume(Request $request, job $job, JobApplication $application)
    {
        abort_unless($application->job_id === $job->job_id, 404);
        abort_unless(
            $request->user()->role === 'admin'
                || $job->user_id === $request->user()->user_id
                || $application->user_id === $request->user()->user_id,
            403
        );

        $resumePath = $application->resume_path;
        abort_unless(
            str_starts_with($resumePath, 'uploads/document_jobApp/')
                && basename($resumePath) === substr($resumePath, strlen('uploads/document_jobApp/'))
                && File::exists(public_path($resumePath)),
            404,
            'ไม่พบไฟล์เรซูเม'
        );

        return response()->file(public_path($resumePath), [
            'Content-Disposition' => 'inline',
        ]);
    }

    private function storeResume(UploadedFile $file): string
    {
        $directory = public_path('uploads/document_jobApp');
        File::ensureDirectoryExists($directory);

        $filename = $file->hashName();
        $file->move($directory, $filename);

        return 'uploads/document_jobApp/'.$filename;
    }

    private function authorizeJobOwner(Request $request, job $job): void
    {
        abort_unless(
            $request->user()->role === 'admin'
                || $job->user_id === $request->user()->user_id,
            403
        );
    }
}
