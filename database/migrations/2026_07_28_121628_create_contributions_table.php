<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Payment;
use App\Models\User;
use App\Models\Wish;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Wish::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'contributor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignIdFor(Payment::class)->constrained()->restrictOnDelete();
            $table->integer('amount');
            $table->text('message')->nullable();
            $table->string('visibility')->default(ContributionVisibility::Public->value);
            $table->string('status')->default(ContributionStatus::Pending->value);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contributions');
    }
};
