<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeadStage extends Model
{
    protected $fillable = [
        'slug',
        'level_group',
        'name',
        'sort_order',
        'is_default',
        'is_closed',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_default' => 'boolean',
            'is_closed' => 'boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function levelTabs(): array
    {
        return [
            'L0' => 'L0 - Mới',
            'L1' => 'L1 - Cts đã phát sinh cuộc gọi đầu tiên',
            'L2' => 'L2 - Cts có nhu cầu tìm hiểu',
            'L3' => 'L3 - Cts đồng ý học thử',
            'L4' => 'L4 - Cts đã xếp học thử',
            'L5' => 'L5 - Cts đã học thử xong hoặc đã được tư vấn xong khóa học',
            'L6' => 'L6 - Cts đã nộp học phí',
        ];
    }

    /**
     * @return list<array{slug: string, name: string, level_group: string, sort_order: int, is_default: bool, is_closed: bool}>
     */
    public static function catalog(): array
    {
        return [
            ['slug' => 'l0', 'name' => 'L0 - Mới', 'level_group' => 'L0', 'sort_order' => 10, 'is_default' => true, 'is_closed' => false],

            ['slug' => 'l1', 'name' => 'L1 - Cts đã phát sinh cuộc gọi đầu tiên', 'level_group' => 'L1', 'sort_order' => 20, 'is_default' => false, 'is_closed' => false],
            ['slug' => 'l1_1', 'name' => 'L1.1 - knm', 'level_group' => 'L1', 'sort_order' => 21, 'is_default' => false, 'is_closed' => false],
            ['slug' => 'l1_2', 'name' => 'L1.2 - Chưa trao đổi', 'level_group' => 'L1', 'sort_order' => 22, 'is_default' => false, 'is_closed' => false],
            ['slug' => 'l1_3', 'name' => 'L1.3 - Hẹn gọi lại sau', 'level_group' => 'L1', 'sort_order' => 23, 'is_default' => false, 'is_closed' => false],
            ['slug' => 'l1_4', 'name' => 'L1.4 - Ko đúng đối tượng', 'level_group' => 'L1', 'sort_order' => 24, 'is_default' => false, 'is_closed' => true],
            ['slug' => 'l1_5', 'name' => 'L1.5 - Sai số/ Số khóa', 'level_group' => 'L1', 'sort_order' => 25, 'is_default' => false, 'is_closed' => true],
            ['slug' => 'l1_6', 'name' => 'L1.6 - knm nhiều lần', 'level_group' => 'L1', 'sort_order' => 26, 'is_default' => false, 'is_closed' => false],
            ['slug' => 'l1_7', 'name' => 'L1.7 - Ko có nhu cầu', 'level_group' => 'L1', 'sort_order' => 27, 'is_default' => false, 'is_closed' => true],

            ['slug' => 'l2', 'name' => 'L2 - Cts có nhu cầu tìm hiểu', 'level_group' => 'L2', 'sort_order' => 30, 'is_default' => false, 'is_closed' => false],
            ['slug' => 'l2_1', 'name' => 'L2.1 - Chưa đồng ý học thử/ Chưa muốn tìm hiểu', 'level_group' => 'L2', 'sort_order' => 31, 'is_default' => false, 'is_closed' => false],
            ['slug' => 'l2_2', 'name' => 'L2.2 - Gọi lại sau', 'level_group' => 'L2', 'sort_order' => 32, 'is_default' => false, 'is_closed' => false],
            ['slug' => 'l2_3', 'name' => 'L2.3 - Từ chối TV', 'level_group' => 'L2', 'sort_order' => 33, 'is_default' => false, 'is_closed' => true],

            ['slug' => 'l3', 'name' => 'L3 - Cts đồng ý học thử', 'level_group' => 'L3', 'sort_order' => 40, 'is_default' => false, 'is_closed' => false],
            ['slug' => 'l3_1', 'name' => 'L3.1 - Đã có lịch học', 'level_group' => 'L3', 'sort_order' => 41, 'is_default' => false, 'is_closed' => false],
            ['slug' => 'l3_2', 'name' => 'L3.2 - Chưa có lịch học', 'level_group' => 'L3', 'sort_order' => 42, 'is_default' => false, 'is_closed' => false],
            ['slug' => 'l3_3', 'name' => 'L3.3 - Muốn hủy', 'level_group' => 'L3', 'sort_order' => 43, 'is_default' => false, 'is_closed' => false],
            ['slug' => 'l3_4', 'name' => 'L3.4 - knm nhiều lần', 'level_group' => 'L3', 'sort_order' => 44, 'is_default' => false, 'is_closed' => false],

            ['slug' => 'l4', 'name' => 'L4 - Cts đã xếp học thử', 'level_group' => 'L4', 'sort_order' => 50, 'is_default' => false, 'is_closed' => false],
            ['slug' => 'l4_1', 'name' => 'L4.1 - Học thử thành công và đã được tư vấn lộ trình', 'level_group' => 'L4', 'sort_order' => 51, 'is_default' => false, 'is_closed' => false],
            ['slug' => 'l4_2', 'name' => 'L4.2 - Không hoàn tất buổi học thử vì khách hàng gặp trục trặc', 'level_group' => 'L4', 'sort_order' => 52, 'is_default' => false, 'is_closed' => false],
            ['slug' => 'l4_3', 'name' => 'L4.3 - Hủy lịch do phía IECM', 'level_group' => 'L4', 'sort_order' => 53, 'is_default' => false, 'is_closed' => false],
            ['slug' => 'l4_4', 'name' => 'L4.4 - Đổi lịch do khách hàng chủ động báo', 'level_group' => 'L4', 'sort_order' => 54, 'is_default' => false, 'is_closed' => false],
            ['slug' => 'l4_5', 'name' => 'L4.5 - Đến buổi học thì hủy lịch hoặc từ chối nghe tư vấn.', 'level_group' => 'L4', 'sort_order' => 55, 'is_default' => false, 'is_closed' => true],

            ['slug' => 'l5', 'name' => 'L5 - Cts đã học thử xong hoặc đã được tư vấn xong khóa học', 'level_group' => 'L5', 'sort_order' => 60, 'is_default' => false, 'is_closed' => false],
            ['slug' => 'l5_1', 'name' => 'L5.1 - Đã có lịch hẹn chuyển tiền', 'level_group' => 'L5', 'sort_order' => 61, 'is_default' => false, 'is_closed' => false],
            ['slug' => 'l5_2', 'name' => 'L5.2 - Đang cân nhắc suy nghĩ', 'level_group' => 'L5', 'sort_order' => 62, 'is_default' => false, 'is_closed' => false],
            ['slug' => 'l5_3', 'name' => 'L5.3 - Tư vấn xong knm', 'level_group' => 'L5', 'sort_order' => 63, 'is_default' => false, 'is_closed' => false],
            ['slug' => 'l5_4', 'name' => 'L5.4 - Tư vấn xong tư chối', 'level_group' => 'L5', 'sort_order' => 64, 'is_default' => false, 'is_closed' => true],

            ['slug' => 'l6', 'name' => 'L6 - Cts đã nộp học phí', 'level_group' => 'L6', 'sort_order' => 70, 'is_default' => false, 'is_closed' => false],
            ['slug' => 'l6_1', 'name' => 'L6.1 - Chờ vào lớp', 'level_group' => 'L6', 'sort_order' => 71, 'is_default' => false, 'is_closed' => false],
            ['slug' => 'l6_2', 'name' => 'L6.2 - Đã vào lớp', 'level_group' => 'L6', 'sort_order' => 72, 'is_default' => false, 'is_closed' => true],
            ['slug' => 'l6_3', 'name' => 'L6.3 - Bảo lưu', 'level_group' => 'L6', 'sort_order' => 73, 'is_default' => false, 'is_closed' => true],
        ];
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'stage_id');
    }

    public function isMain(): bool
    {
        return (bool) preg_match('/^l[0-6]$/', (string) $this->slug);
    }

    /**
     * @return array<string, list<array{id: int, name: string, slug: string}>>
     */
    public static function detailsByGroup()
    {
        return static::query()
            ->whereNotNull('level_group')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('level_group')
            ->map(fn ($stages) => $stages
                ->reject(fn (self $stage) => $stage->isMain())
                ->values()
                ->map(fn (self $stage) => [
                    'id' => $stage->id,
                    'name' => $stage->name,
                    'slug' => $stage->slug,
                ])
                ->all()
            )
            ->all();
    }
}
