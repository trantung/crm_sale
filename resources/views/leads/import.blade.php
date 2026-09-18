<x-app-layout title="Import Lead">
    <div class="ts-crumb">
        <div class="ts-crumb-path">
            📁 Quản lý Leads <span>›</span>
            <a href="{{ route('leads.index') }}" class="hover:underline">Danh sách Lead</a>
            <span>›</span>
            <strong>Import</strong>
        </div>
    </div>

    <div class="ts-panel p-6 max-w-2xl">
        <div class="ts-card-title">📥 Import CSV</div>
        <p class="text-sm text-slate-500 mb-4">File CSV cần dòng tiêu đề. Các cột nhận: <code>name, phone, email, source_code, note</code> (hoặc <code>ten, sdt, nguon, ghi_chu</code>).</p>
        <form method="POST" action="{{ route('leads.import.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="ts-label">File CSV</label>
                <input type="file" name="file" accept=".csv,text/csv" class="ts-input" required>
                <x-input-error :messages="$errors->get('file')" class="mt-2" />
            </div>
            <div class="flex justify-end gap-2">
                <a href="{{ route('leads.index') }}" class="ts-btn ts-btn-ghost">Hủy</a>
                <button class="ts-btn ts-btn-primary" type="submit">Import</button>
            </div>
        </form>
    </div>
</x-app-layout>
