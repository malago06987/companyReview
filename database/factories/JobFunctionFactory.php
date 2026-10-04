<?php

namespace Database\Factories;

use App\Models\JobFunction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobFunction>
 */
class JobFunctionFactory extends Factory
{
    public const FUNCTION_NAMES = [
        'Software Development',
        'Web Development',
        'Mobile Development',
        'Data & Analytics',
        'Artificial Intelligence & Machine Learning',
        'Cybersecurity',
        'IT & Network',
        'Cloud & DevOps',
        'UI/UX & Graphic Design',
        'Product Management',
        'Project Management',
        'Business & Consulting',
        'Sales',
        'Marketing & Digital Marketing',
        'Human Resources',
        'Finance & Accounting',
        'Banking & Investment',
        'Legal & Compliance',
        'Engineering',
        'Architecture & Construction',
        'Manufacturing',
        'Healthcare & Medical',
        'Science & Research',
        'Education & Training',
        'Government & Public Administration',
        'Agriculture & Food',
        'Energy & Environment',
        'Transportation & Logistics',
        'Retail & E-commerce',
        'Hospitality & Tourism',
        'Media & Entertainment',
        'Customer Service',
        'Administration',
        'Real Estate',
        'Sports & Fitness',
        'Other',
    ];

    protected $model = JobFunction::class;

    public function definition(): array
    {
        return [
            'function_name' => fake()->unique()->randomElement(self::FUNCTION_NAMES),
        ];
    }
}
