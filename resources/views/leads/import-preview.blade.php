<x-app-layout title="Preview import lead">
    <div class="ts-crumb">
        <div class="ts-crumb-path">
            📁 Quản lý Leads <span>›</span>
            <a href="{{ route('leads.import') }}" class="hover:underline">Import</a>
            <span>›</span>
            <strong>Preview {{ number_format($total) }} dòng</strong>
        </div>
    </div>

    <form method="POST" action="{{ route('leads.import.preview.save') }}" class="space-y-3">
        @csrf
        <input type="hidden" name="page" value="{{ $paginator->currentPage() }}">

        <div class="ts-panel overflow-x-auto">
            <table class="ts-table ts-table-wide">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>UTM</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $index => $row)
                        @php $hasUtm = trim((string) ($row['utm'] ?? '')) !== ''; @endphp
                        <tr x-data="{
                            utm: @js($row['utm'] ?? ''),
                            mode: @js($hasUtm ? 'custom' : 'preset')
                        }">
                            <td class="text-slate-500">{{ $row['stt'] ?? ($index + 1) }}</td>
                            <td>
                                <input class="ts-input" name="rows[{{ $index }}][name]" value="{{ $row['name'] ?? '' }}" required>
                            </td>
                            <td>
                                <input class="ts-input" name="rows[{{ $index }}][phone]" value="{{ $row['phone'] ?? '' }}">
                            </td>
                            <td>
                                <input class="ts-input" type="email" name="rows[{{ $index }}][email]" value="{{ $row['email'] ?? '' }}">
                            </td>
                            <td style="min-width:260px;">
                                <input type="hidden" :name="'rows[{{ $index }}][utm]'" x-model="utm">
                                <select class="ts-select mb-2" x-show="mode === 'preset'" x-cloak
                                    @change="if ($el.value === '__custom') { mode = 'custom'; utm = ''; } else { utm = $el.value; }">
                                    <option value="">— Chọn UTM có sẵn —</option>
                                    @foreach ($sources as $source)
                                        <option value="{{ $source->code }}" @selected(($row['utm'] ?? '') === $source->code)>{{ $source->name }}</option>
                                    @endforeach
                                    <option value="__custom">Khác (điền tay)</option>
                                </select>
                                <div x-show="mode === 'custom'" x-cloak class="space-y-2">
                                    <input class="ts-input" type="text" x-model="utm" placeholder="Nhập UTM">
                                    <button type="button" class="ts-btn ts-btn-ghost" style="padding:6px 10px;"
                                        @click="mode = 'preset'; utm = ''">Chọn UTM có sẵn</button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="ts-pager">
                <div>Trang <strong>{{ $paginator->currentPage() }}</strong> / {{ $paginator->lastPage() }} · {{ number_format($total) }} dòng</div>
                <div class="flex flex-wrap gap-1">
                    @for ($page = 1; $page <= $paginator->lastPage(); $page++)
                        <button type="submit" name="next_page" value="{{ $page }}"
                            class="ts-page-link {{ $page === $paginator->currentPage() ? 'active' : '' }}">{{ $page }}</button>
                    @endfor
                </div>
            </div>
        </div>

        <div class="flex flex-wrap justify-end gap-2">
            <button type="submit" class="ts-btn ts-btn-ghost" name="next_page" value="{{ $paginator->currentPage() }}">Lưu trang này</button>
            <button type="submit" class="ts-btn ts-btn-danger" formaction="{{ route('leads.import.cancel') }}" formnovalidate>Hủy import</button>
            <button type="submit" class="ts-btn ts-btn-primary" formaction="{{ route('leads.import.commit') }}">Xác nhận import tất cả</button>
        </div>
    </form>
</x-app-layout>
