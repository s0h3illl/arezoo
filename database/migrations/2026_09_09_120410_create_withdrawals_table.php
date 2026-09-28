<?php

use App\Enums\WithdrawalStatus;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->unsignedInteger('fee');
            $table->string('sheba', 26);
            $table->string('status')->default(WithdrawalStatus::Requested->value);
            // No column default, unlike its neighbours. MySQL rejects a default on a
            // TEXT column outright (error 1101) where SQLite accepts one, so the
            // default lives where it belongs anyway: Withdrawal's $attributes.
            $table->text('note');
            $table->timestamp('requested_at');
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
