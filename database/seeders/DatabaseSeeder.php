<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\LeadStage;
use App\Models\User;
use App\Services\LeadService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Admin CRM',
                'email' => 'admin@crm.ieltscheckmate.com',
                'password' => config('crm.default_password'),
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        User::query()->updateOrCreate(
            ['username' => 'sale'],
            [
                'name' => 'Nguyễn Văn A',
                'email' => 'sale@crm.ieltscheckmate.com',
                'password' => config('crm.default_password'),
                'role' => User::ROLE_SALE,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $this->seedSources();
        $this->seedStages();
        $this->seedDemoLeads();
    }

    private function seedSources(): void
    {
        $sources = [
            ['code' => 'website', 'name' => 'Form Website', 'sort_order' => 1],
            ['code' => 'landing', 'name' => 'Landing Page', 'sort_order' => 2],
            ['code' => 'facebook_ads', 'name' => 'Facebook Ads', 'sort_order' => 3],
            ['code' => 'google_ads', 'name' => 'Google Ads', 'sort_order' => 4],
            ['code' => 'tiktok_ads', 'name' => 'TikTok Ads', 'sort_order' => 5],
            ['code' => 'zalo', 'name' => 'Zalo', 'sort_order' => 6],
            ['code' => 'hotline', 'name' => 'Hotline', 'sort_order' => 7],
            ['code' => 'walk_in', 'name' => 'Walk-in', 'sort_order' => 8],
            ['code' => 'referral', 'name' => 'Referral / Giới thiệu', 'sort_order' => 9],
            ['code' => 'other', 'name' => 'Khác', 'sort_order' => 99],
        ];

        foreach ($sources as $source) {
            LeadSource::query()->updateOrCreate(
                ['code' => $source['code']],
                ['name' => $source['name'], 'sort_order' => $source['sort_order'], 'is_active' => true]
            );
        }
    }

    private function seedStages(): void
    {
        $stages = [
            ['slug' => 'new', 'name' => 'Mới', 'sort_order' => 1, 'is_default' => true, 'is_closed' => false],
            ['slug' => 'contacted', 'name' => 'Đã liên hệ', 'sort_order' => 2, 'is_default' => false, 'is_closed' => false],
            ['slug' => 'qualified', 'name' => 'Đủ điều kiện', 'sort_order' => 3, 'is_default' => false, 'is_closed' => false],
            ['slug' => 'consulting', 'name' => 'Đang tư vấn', 'sort_order' => 4, 'is_default' => false, 'is_closed' => false],
            ['slug' => 'ordered', 'name' => 'Đã tạo đơn', 'sort_order' => 5, 'is_default' => false, 'is_closed' => false],
            ['slug' => 'lost', 'name' => 'Không chốt', 'sort_order' => 6, 'is_default' => false, 'is_closed' => true],
        ];

        foreach ($stages as $stage) {
            LeadStage::query()->updateOrCreate(
                ['slug' => $stage['slug']],
                $stage
            );
        }
    }

    private function seedDemoLeads(): void
    {
        if (Lead::query()->count() >= 5) {
            return;
        }

        $service = app(LeadService::class);
        $sale = User::query()->where('username', 'sale')->first();
        $admin = User::query()->where('username', 'admin')->first();

        $samples = [
            ['name' => 'Nguyễn Văn An', 'phone' => '0983000789', 'source_code' => 'facebook_ads', 'call_result' => 'not_called'],
            ['name' => 'Trần Thị Bích', 'phone' => '0904000123', 'source_code' => 'landing', 'call_result' => 'callback', 'callback_at' => now()->setTime(15, 0), 'last_called_at' => now()->setTime(10, 0)],
            ['name' => 'Lê Hoàng Cường', 'phone' => '0912000456', 'source_code' => 'website', 'call_result' => 'interested', 'last_called_at' => now()->subDay()->setTime(16, 30)],
            ['name' => 'Phạm Minh Dũng', 'phone' => '0978000999', 'source_code' => 'hotline', 'call_result' => 'no_answer', 'last_called_at' => now()->setTime(9, 15)],
            ['name' => 'Hoàng Thị Mai', 'phone' => '0356000111', 'source_code' => 'tiktok_ads', 'call_result' => 'wrong_number', 'last_called_at' => now()->subDay()->setTime(14, 0)],
        ];

        foreach ($samples as $sample) {
            $result = $service->ingest([
                'name' => $sample['name'],
                'phone' => $sample['phone'],
                'source_code' => $sample['source_code'],
                'owner_id' => $sale?->id,
            ], 'crm', $admin);

            $lead = $result['lead'];
            $lead->call_result = $sample['call_result'];
            $lead->last_called_at = $sample['last_called_at'] ?? null;
            $lead->callback_at = $sample['callback_at'] ?? null;
            $lead->save();
        }
    }
}
