<?php

use App\Domain\Enums\WorkflowType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->unique()->constrained('forms')->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', WorkflowType::values());
            $table->unsignedInteger('required_approvals')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflows');
    }
};
