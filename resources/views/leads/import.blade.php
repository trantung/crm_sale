<x-app-layout title="Import Lead">
    <div class="ts-crumb">
        <div class="ts-crumb-path">
            📁 Quản lý Leads <span>›</span>
            <a href="{{ route('leads.index') }}" class="hover:underline">Danh sách Lead</a>
            <span>›</span>
            <strong>Import Excel</strong>
        </div>
    </div>

    <div class="ts-panel ts-import p-6">
        <div class="ts-import-head">
            <h2>Import lead từ Excel</h2>
            <p>Chọn file rồi bấm Import để xem preview, sửa từng ô, phân trang, sau đó mới ghi vào danh sách.</p>
        </div>

        <form method="POST" action="{{ route('leads.import.store') }}" enctype="multipart/form-data"
              x-data="{ name: '' }">
            @csrf
            <div class="ts-file-box">
                <div class="ts-file-icon" aria-hidden="true">📄</div>
                <div class="ts-file-meta">
                    <strong x-text="name || 'Chưa chọn file'"></strong>
                    <span>Hỗ trợ .xlsx, .xls, .csv · tối đa 5MB</span>
                </div>
                <div class="ts-file-actions">
                    <label class="ts-btn ts-btn-ghost" style="cursor:pointer;margin:0;">
                        Chọn file
                        <input type="file" name="file" accept=".xlsx,.xls,.csv,text/csv" required class="sr-only"
                               @change="name = $event.target.files[0] ? $event.target.files[0].name : ''">
                    </label>
                    <a href="{{ route('leads.import.sample') }}" class="ts-btn ts-btn-ghost">Tải mẫu lead.xlsx</a>
                    <button class="ts-btn ts-btn-primary" type="submit">Import</button>
                </div>
            </div>
            <x-input-error :messages="$errors->get('file')" class="mt-2" />

            <div class="ts-import-cols">
                <em>STT</em>
                <em>Name *</em>
                <em>Phone</em>
                <em>Email</em>
                <em>UTM</em>
            </div>
        </form>
    </div>
</x-app-layout>
