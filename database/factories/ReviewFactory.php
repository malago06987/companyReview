<?php

namespace Database\Factories;

use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        return [
            'company_id' => fake()->numberBetween(1, 20),

            'user_id' => fake()->numberBetween(1, 20),

            'rating_life' => fake()->numberBetween(1, 5),

            'rating_work' => fake()->numberBetween(1, 5),

            'rating_money' => fake()->numberBetween(1, 5),

            'rating_society' => fake()->numberBetween(1, 5),

            'review_text' => fake()->randomElement([
                'บรรยากาศการทำงานดี เพื่อนร่วมงานช่วยเหลือกัน',
                'ได้เรียนรู้เทคโนโลยีใหม่และมีโอกาสพัฒนาทักษะ',
                'งานค่อนข้างท้าทาย แต่มีโอกาสเติบโตในสายงาน',
                'สวัสดิการค่อนข้างดีและมีโบนัสประจำปี',
                'การทำงานเป็นทีมดี แต่บางช่วงงานค่อนข้างหนัก',
                'บริษัทเปิดโอกาสให้พนักงานเสนอความคิดเห็น',
                'โดยรวมพอใจกับการทำงานและสภาพแวดล้อมของบริษัท',
            ]),

            'status' => 'approved',
        ];
    }
}
