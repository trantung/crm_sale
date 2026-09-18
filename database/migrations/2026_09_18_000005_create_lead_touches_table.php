<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_touches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->string('kind', 16)->default('visit'); // first handled via lead.first_touch_id
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('landing_url', 2048)->nullable();
            $table->string('referrer', 2048)->nullable();
            $table->string('gclid')->nullable();
            $table->string('fbclid')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('channel', 16)->default('api'); // api | crm
            $table->timestamps();
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->foreign('first_touch_id')->references('id')->on('lead_touches')->nullOnDelete();
            $table->foreign('last_touch_id')->references('id')->on('lead_touches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['first_touch_id']);
            $table->dropForeign(['last_touch_id']);
        });
        Schema::dropIfExists('lead_touches');
    }
};
