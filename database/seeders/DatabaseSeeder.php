<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Industry;
use App\Models\Company;
use App\Models\JobFunction;
use App\Models\Job;
use App\Models\Review;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory(20)->create();

        Industry::factory(10)->create();

        Company::factory(20)->create();

        JobFunction::factory(10)->create();

        Job::factory(50)->create();

        Review::factory(100)->create();
    }
}
