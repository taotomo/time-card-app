<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $clockIn = fake()->dateTimeBetween('-1 month', 'now');
        $clockOut = fake()->optional(0.8)->dateTimeBetween($clockIn, 'now');
        
        return [
            'user_id' => User::factory(),
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'break_times' => [],  // デフォルトは空配列
            'remarks' => null,
            'approval_status' => 0,
        ];
    }
    
    /**
     * 出勤のみ（退勤なし）の状態
     */
    public function working()
    {
        return $this->state(fn (array $attributes) => [
            'clock_out' => null,
            'break_times' => [],
        ]);
    }
    
    /**
     * 休憩中の状態
     */
    public function onBreak()
    {
        return $this->state(fn (array $attributes) => [
            'clock_out' => null,
            'break_times' => [
                ['start' => now()->subHours(2)->format('H:i'), 'end' => null],
            ],
        ]);
    }
    
    /**
     * 勤務終了の状態
     */
    public function finished()
    {
        return $this->state(fn (array $attributes) => [
            'clock_in' => now()->subHours(8)->format('Y-m-d H:i:s'),
            'clock_out' => now()->subHours(1)->format('Y-m-d H:i:s'),
            'break_times' => [
                ['start' => now()->subHours(5)->format('H:i'), 'end' => now()->subHours(4)->format('H:i')],
            ],
        ]);
    }
}
