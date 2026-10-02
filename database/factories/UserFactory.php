<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        $names = [
            'สมชาย ใจดี',
            'สมหญิง รักงาน',
            'กิตติพงษ์ มีสุข',
            'นภัสสร แสงทอง',
            'ธนกร พัฒนกิจ',
            'พิมพ์ชนก สุขใจ',
            'ณัฐวุฒิ ศรีสุข',
            'อรทัย ตั้งใจ',
            'วรพล เทพรักษา',
            'ชลธิชา เจริญดี',
        ];

        return [
            'full_name' => fake()->randomElement($names),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'profile_image' => null,
            'role' => 'user',
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
