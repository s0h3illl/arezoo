<?php

use App\Enums\ContributionStatus;
use App\Enums\ContributionVisibility;
use App\Models\Payment;
use App\Models\User;
use App\Models\Wish;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
            $table->foreignIdFor(User::class, 'contributor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignIdFor(Payment::class)->unique()->constrained()->restrictOnDelete();
            $table->unsignedInteger('amount');
            $table->text('message')->nullable();
            $table->string('visibility')->default(ContributionVisibility::Public->value);
            $table->string('status')->default(ContributionStatus::Pending->value);
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();

            $table->index(['wish_id', 'status']);
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
