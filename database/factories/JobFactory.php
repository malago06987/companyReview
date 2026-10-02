<?php

namespace Database\Factories;

use App\Models\Job;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Job>
 */
class JobFactory extends Factory
{
    protected $model = Job::class;

    public function definition(): array
    {
        return [
            'company_id' => fake()->numberBetween(1, 20),

            'function_id' => fake()->numberBetween(1, 10),

            'job_title' => fake()->randomElement([
                'Frontend Developer',
                'Backend Developer',
                'Full Stack Developer',
                'Mobile Developer',
                'UI/UX Designer',
                'Data Analyst',
                'Cybersecurity Analyst',
                'Software Engineer',
                'System Administrator',
                'Marketing Specialist',
            ]),

            'job_description' => fake()->randomElement([
                'พัฒนาและดูแลระบบของบริษัท รวมถึงแก้ไขปัญหาและปรับปรุงประสิทธิภาพของระบบ',
                'ออกแบบและพัฒนาเว็บไซต์ให้ตรงตามความต้องการของผู้ใช้งาน',
                'พัฒนา API และระบบหลังบ้านสำหรับรองรับการทำงานของระบบ',
                'วิเคราะห์ข้อมูลและจัดทำรายงานเพื่อสนับสนุนการตัดสินใจขององค์กร',
                'ดูแลความปลอดภัยของระบบและตรวจสอบความผิดปกติของเครือข่าย',
            ]),

            'salary' => fake()->randomElement([
                '20,000 - 30,000 บาท',
                '25,000 - 35,000 บาท',
                '30,000 - 40,000 บาท',
                '35,000 - 50,000 บาท',
                '40,000 - 60,000 บาท',
                '50,000 - 70,000 บาท',
            ]),

            'work_location' => fake()->randomElement([
                'กรุงเทพมหานคร',
                'เชียงใหม่',
                'เชียงราย',
                'ชลบุรี',
                'นนทบุรี',
                'ปทุมธานี',
                'Hybrid',
                'Remote',
            ]),

            'employment_type' => fake()->randomElement([
                'Full-time',
                'Part-time',
                'Internship',
            ]),

            'status' => 'open',
        ];
    }
}
