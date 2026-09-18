<?php

use App\Models\Lead;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('code', 32)->nullable()->unique()->after('id');
            $table->string('call_result', 32)->nullable()->after('note');
            $table->timestamp('last_called_at')->nullable()->after('call_result');
            $table->timestamp('callback_at')->nullable()->after('last_called_at');
        });

        Lead::query()->orderBy('id')->each(function (Lead $lead) {
            if (! $lead->code) {
                $lead->code = 'LD-'.$lead->created_at?->format('Y').$lead->id;
            }
            if (! $lead->call_result) {
                $lead->call_result = 'not_called';
            }
            $lead->saveQuietly();
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['code', 'call_result', 'last_called_at', 'callback_at']);
        });
    }
};
