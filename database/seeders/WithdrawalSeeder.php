<?php

namespace Database\Seeders;

use App\Models\Contribution;
use App\Models\User;
use App\Models\Wish;
use App\Models\Withdrawal;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class WithdrawalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::find(1);

        Contribution::factory()->settledAt(now()->subWeek())->create([
            'wish_id' => Wish::factory()->create(['user_id' => $admin->id])->id,
            'amount' => 900_000,
        ]);

        Withdrawal::create([
            'user_id' => $admin->id,
            'amount' => 700_000,
            'fee' => config('withdrawals.fee'),
            'sheba' => 'IR062960000000100324200001',
            'requested_at' => Carbon::now(),
        ]);
    }
}
