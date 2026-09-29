<?php

namespace Database\Seeders;

use App\Models\LeadStage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeadStageSeeder extends Seeder
{
    public function run(): void
    {
        $legacy = [
            'new' => 'l0',
            'contacted' => 'l1',
            'qualified' => 'l2',
            'consulting' => 'l3',
            'ordered' => 'l6',
            'lost' => 'l1_7',
        ];

        foreach ($legacy as $oldSlug => $newSlug) {
            $row = LeadStage::query()->where('slug', $oldSlug)->first();
            if ($row && ! LeadStage::query()->where('slug', $newSlug)->exists()) {
                $row->slug = $newSlug;
                $row->save();
            }
        }

        foreach (LeadStage::catalog() as $stage) {
            LeadStage::query()->updateOrCreate(
                ['slug' => $stage['slug']],
                $stage
            );
        }

        $keep = collect(LeadStage::catalog())->pluck('slug')->all();
        LeadStage::query()->whereNotIn('slug', $keep)->update(['level_group' => null]);

        DB::table('lead_stages')->whereNull('level_group')->update(['is_default' => false]);
    }
}
