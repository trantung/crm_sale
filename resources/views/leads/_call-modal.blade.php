<div class="ts-modal-bg" x-show="callOpen" x-cloak @click.self="callOpen = false">
    <div class="ts-modal" @click.stop>
        <div class="text-lg font-extrabold text-[#0f2744]">Ghi kết quả cuộc gọi</div>
        <p class="text-sm text-slate-500 mt-1" x-text="callName + ' · ' + callPhone"></p>
        <form method="POST" class="mt-4 space-y-3" :action="callAction">
            @csrf
            <div>
                <label class="ts-label">Kết quả</label>
                <select name="call_result" class="ts-select" x-model="callResult">
                    @foreach ($callResults as $value => $label)
                        @if ($value !== 'not_called')
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div x-show="callResult === 'callback'">
                <label class="ts-label">Hẹn gọi lại lúc</label>
                <input type="datetime-local" name="callback_at" class="ts-input">
            </div>
            <div>
                <label class="ts-label">Ghi chú</label>
                <textarea name="content" rows="3" class="ts-textarea" placeholder="Nội dung trao đổi..."></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="ts-btn ts-btn-ghost" @click="callOpen = false">Đóng</button>
                <button type="submit" class="ts-btn ts-btn-primary">Lưu kết quả</button>
            </div>
        </form>
    </div>
</div>
