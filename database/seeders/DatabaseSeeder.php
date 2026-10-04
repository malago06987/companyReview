<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Industry;
use App\Models\Company;
use App\Models\JobFunction;
use App\Models\Job;
use App\Models\Review;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Database\Factories\JobFunctionFactory;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory(20)->create();

        Industry::factory(10)->create();

        Company::factory(20)->create();

        JobFunction::query()->insert(array_map(
            fn (string $name) => [
                'function_name' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            JobFunctionFactory::FUNCTION_NAMES,
        ));

        Job::factory(50)->create();

        $reviewPairs = collect(range(1, 20))
            ->crossJoin(range(1, 20))
            ->shuffle()
            ->take(100)
            ->values();

        Review::factory(100)
            ->sequence(fn (Sequence $sequence) => [
                'company_id' => $reviewPairs[$sequence->index][0],
                'user_id' => $reviewPairs[$sequence->index][1],
            ])
            ->create();
    }
}
