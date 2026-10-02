<?php

namespace Database\Factories;

use App\Models\JobFunction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobFunction>
 */
class JobFunctionFactory extends Factory
{
    protected $model = JobFunction::class;

    public function definition(): array
    {
        return [
            'function_name' => fake()->unique()->randomElement([
                'Software Development',
                'Web Development',
                'Mobile Development',
                'UI/UX Design',
                'Data Science',
                'Cybersecurity',
                'Marketing',
                'Human Resources',
                'Accounting',
                'Sales',
            ]),
        ];
    }
}
