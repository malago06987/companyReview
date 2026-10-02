<?php

namespace Database\Factories;

use App\Models\Industry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Industry>
 */
class IndustryFactory extends Factory
{
    protected $model = Industry::class;

    public function definition(): array
    {
        return [
            'industry_name' => fake()->unique()->randomElement([
                'เทคโนโลยีสารสนเทศ',
                'การเงินและการธนาคาร',
                'การแพทย์และสุขภาพ',
                'การผลิต',
                'ค้าปลีกและการขาย',
                'การขนส่งและโลจิสติกส์',
                'การศึกษา',
                'สื่อและบันเทิง',
                'ก่อสร้างและอสังหาริมทรัพย์',
                'อาหารและเครื่องดื่ม',
            ]),
        ];
    }
}
