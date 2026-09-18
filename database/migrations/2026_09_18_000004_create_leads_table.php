<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 32)->nullable()->index();
            $table->string('phone_normalized', 32)->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->string('sso_id', 64)->nullable()->index();
            $table->foreignId('source_id')->nullable()->constrained('lead_sources')->nullOnDelete();
            $table->foreignId('stage_id')->constrained('lead_stages');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('interested_product', 64)->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('first_touch_id')->nullable();
            $table->unsignedBigInteger('last_touch_id')->nullable();
            $table->string('origin', 16)->default('crm'); // crm | api
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
