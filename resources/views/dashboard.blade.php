@extends('layouts.app', ['totalKnowledge' => $totalKnowledge ?? 0])

@section('title', 'Dashboard - Knowledge Base')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link href="https://cdn.jsdelivr.net/npm/lucide-static@0.263.1/font/lucide.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
<!-- Tambahkan di bagian head layout atau langsung di file ini -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container--default .select2-selection--single {
        height: 42px;
        padding: 6px 12px;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        background-color: #ffffff;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    .select2-container--default .select2-selection--single:hover {
        background-color: #f9fafb;
        border-color: #d1d5db;
    }

    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        outline: none;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 28px;
        color: #374151;
        padding-left: 2px;
    }

    .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #000000 !important;
        font-weight: normal;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px;
        right: 8px;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow b {
        border-color: #6b7280 transparent transparent transparent;
        border-width: 5px 4px 0 4px;
        margin-top: -2px;
    }

    .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
        border-color: transparent transparent #6b7280 transparent;
        border-width: 0 4px 5px 4px;
    }

    /* Dropdown container */
    .select2-dropdown {
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        background-color: #ffffff;
        margin-top: 4px;
        overflow: hidden;
    }

    .select2-container--default .select2-results__option {
        padding: 10px 12px;
        color: #374151;
        background-color: transparent;
        border-bottom: 1px solid #f3f4f6;
        cursor: pointer;
        transition: background-color 0.15s ease-in-out;
    }

    .select2-container--default .select2-results__option:last-child {
        border-bottom: none;
    }

    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #f3f4f6;
        color: #374151;
        font-weight: 500;
    }

    .select2-container--default .select2-results__option--selected {
        background-color: #e5e7eb;
        color: #374151;
        font-weight: 500;
    }

    .select2-container--default .select2-results__option:hover {
        background-color: #f9fafb;
    }

    /* Search box in dropdown */
    .select2-container--default .select2-search--dropdown .select2-search__field {
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        padding: 8px 12px;
        margin: 8px;
        width: calc(100% - 16px);
        font-size: 14px;
        color: #374151;
    }

    .select2-container--default .select2-search--dropdown .select2-search__field:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        outline: none;
    }

    /* No results message */
    .select2-container--default .select2-results__message {
        padding: 12px;
        color: #6b7280;
        text-align: center;
        font-style: italic;
    }
</style>


@php
    $supabase = app(\App\Services\SupabaseService::class);
    $users = $supabase->getUsers();
    $staffs = array_filter($users, fn($u) => $u['role'] === 'staff');
    $memories = $supabase->getMemories();

    // Get all knowledges for search/filter functionality

    $allKnowledges = $supabase->getKnowledges(1, $supabase->getKnowledgesCount());

    // Sort by updated_at desc to show recently validated data first
    $allKnowledges = collect($allKnowledges)->sortByDesc('updated_at')->values()->all();
    $totalKnowledge = count($allKnowledges);

    // Pagination for display
    $page = request('page', 1);
    $perPage = 10;
    $offset = ($page - 1) * $perPage;
    $knowledges = array_slice($allKnowledges, $offset, $perPage);
    $totalPages = ceil($totalKnowledge / $perPage);

    $unanswered = $supabase->getUnansweredQuestions();
    $referensi = $supabase->getReferensi();

    // Statistics
    $verifiedData = collect($allKnowledges)->whereNotNull('verified_data_by')->count();
    $verifiedAnswer = collect($allKnowledges)->whereNotNull('verified_answer_by')->count();
    $pending = $totalKnowledge - $verifiedData;

    // Category Statistics
    $categories = collect($allKnowledges)
        ->groupBy(function ($item) {
            $meta = $item['metadata'] ?? null;
            if (is_string($meta)) {
                $decoded = json_decode($meta, true);
                $meta = is_array($decoded) ? $decoded : [];
            }
            if (isset($meta['metadata']) && is_array($meta['metadata'])) {
                $meta = $meta['metadata'];
            }
            return $meta['kategori'] ?? ($item['kategori'] ?? 'Lainnya');
        })
        ->map->count();
@endphp

@section('content')

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-30 z-50 flex items-center justify-center hidden">
        <div class="bg-white p-6 rounded-lg shadow-lg flex items-center gap-3">
            <svg class="animate-spin h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                </circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span class="font-semibold text-gray-700">Loading...</span>
        </div>
    </div>

    <div class="min-h-screen bg-gray-50">
        <!-- Header Section -->
        <div class="bg-gradient-to-r from-gray-900 to-gray-800 text-white py-8 px-8">
            <div class="flex items-center gap-4 mb-4">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <h1 class="text-4xl font-bold">Pusat Kontrol Basis Pengetahuan</h1>
            </div>
            <p class="text-gray-300 text-lg ml-16">Sistem Manajemen Pengetahuan Chatbot Wali Kota</p>
        </div>

        <!-- Navigation Tabs -->
        <div class="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-20" id="navigationTabs">
            <div class="px-8">
                <nav class="flex gap-1 -mb-px overflow-x-auto">
                    <button onclick="showTab('kontrol-pengetahuan')"
                        class="tab-button px-6 py-4 text-sm font-medium border-b-2 transition-all duration-200 hover:text-blue-600 hover:border-blue-300 border-blue-600 text-blue-600 whitespace-nowrap flex items-center gap-2"
                        data-tab="kontrol-pengetahuan">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        Kontrol Pengetahuan
                    </button>
                    <button onclick="showTab('tambah-data')"
                        class="tab-button px-6 py-4 text-sm font-medium border-b-2 transition-all duration-200 hover:text-blue-600 hover:border-blue-300 border-transparent text-gray-600 whitespace-nowrap flex items-center gap-2"
                        data-tab="tambah-data">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah Data
                    </button>
                     <button onclick="showTab('referensi-sumber')"
                        class="tab-button px-6 py-4 text-sm font-medium border-b-2 transition-all duration-200 hover:text-blue-600 hover:border-blue-300 border-transparent text-gray-600 whitespace-nowrap flex items-center gap-2"
                        data-tab="referensi-sumber">
                         <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6M7 4h8l4 4v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z" />
                        </svg>
                        Referensi Sumber Data
                    </button>
                    <button onclick="showTab('pertanyaan')"
                        class="tab-button px-6 py-4 text-sm font-medium border-b-2 transition-all duration-200 hover:text-blue-600 hover:border-blue-300 border-transparent text-gray-600 whitespace-nowrap flex items-center gap-2"
                        data-tab="pertanyaan">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Pertanyaan Belum Terjawab
                    </button>
                    <button onclick="showTab('manajemen-user')"
                        class="tab-button px-6 py-4 text-sm font-medium border-b-2 transition-all duration-200 hover:text-blue-600 hover:border-blue-300 border-transparent text-gray-600 whitespace-nowrap flex items-center gap-2"
                        data-tab="manajemen-user">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        Manajemen User
                    </button>
                </nav>
            </div>
        </div>

        <!-- Content Sections -->
        <div class="p-8">
            <!-- TAB 1: Kontrol Pengetahuan -->
            <div id="kontrol-pengetahuan" class="tab-content">
                <div class="bg-white rounded-xl p-6 shadow space-y-6">
                    <div class="flex items-center gap-2">
                        <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <h2 class="text-2xl font-semibold">Daftar Pengetahuan Chatbot</h2>
                    </div>

                    <!-- Statistics Dashboard with Charts -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Overview Stats Card -->
                        <div class="bg-gradient-to-br from-blue-500 to-blue-700 text-white rounded-xl p-6 shadow-lg">
                            <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                Statistik Overview
                            </h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <div class="text-sm opacity-90">Total Pengetahuan</div>
                                    <div class="text-4xl font-bold mt-1">{{ $totalKnowledge }}</div>
                                </div>
                                <div>
                                    <div class="text-sm opacity-90">Menunggu Review</div>
                                    <div class="text-4xl font-bold mt-1">{{ $pending }}</div>
                                </div>
                                <div>
                                    <div class="text-sm opacity-90">Data Tervalidasi</div>
                                    <div class="text-3xl font-bold mt-1">{{ $verifiedData }}</div>
                                    <div class="text-xs opacity-75">
                                        {{ $totalKnowledge > 0 ? round(($verifiedData / $totalKnowledge) * 100) : 0 }}%</div>
                                </div>
                                <div>
                                    <div class="text-sm opacity-90">Jawaban Diuji</div>
                                    <div class="text-3xl font-bold mt-1">{{ $verifiedAnswer }}</div>
                                    <div class="text-xs opacity-75">
                                        {{ $totalKnowledge > 0 ? round(($verifiedAnswer / $totalKnowledge) * 100) : 0 }}%</div>
                                </div>
                            </div>
                        </div>

                        <!-- Verification Progress Chart -->
                        <div class="bg-white border border-gray-200 rounded-xl p-6 shadow">
                            <h3 class="text-lg font-semibold mb-4 text-gray-800 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                                Progress Verifikasi
                            </h3>
                            <div style="height: 250px;">
                                <canvas id="verificationChart"></canvas>
                            </div>
                        </div>

                        <!-- Category Distribution Chart -->
                        <div class="bg-white border border-gray-200 rounded-xl p-6 shadow">
                            <h3 class="text-lg font-semibold mb-4 text-gray-800 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                Distribusi Kategori
                            </h3>
                            <div style="height: 250px;">
                                <canvas id="categoryChart"></canvas>
                            </div>
                        </div>

                        <!-- Status Pie Chart -->
                        <div class="bg-white border border-gray-200 rounded-xl p-6 shadow">
                            <h3 class="text-lg font-semibold mb-4 text-gray-800 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                                </svg>
                                Status Validasi
                            </h3>
                            <div style="height: 250px;">
                                <canvas id="statusChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <hr class="my-6">

                    <!-- Search and Filter -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2 relative">
                            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input type="text" id="searchKnowledge"
                                placeholder="Cari berdasarkan judul, konten, atau pembuat..."
                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <select id="filterStatus"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="all">Semua Status</option>
                                <option value="unverified">Belum Validasi</option>
                                <option value="verified">Sudah Validasi</option>
                                <option value="untested">Belum Tes Jawaban</option>
                            </select>
                        </div>
                    </div>

                    <p class="text-sm text-gray-500">
                        Menampilkan <span id="showingCount">{{ count($knowledges) }}</span> dari <span
                            id="totalCount">{{ $totalKnowledge }}</span> data
                    </p>

                    <!-- Knowledge List -->
                    <div class="space-y-4" id="knowledgeList">
                        @forelse($allKnowledges as $item)
                            @php
                                $meta = $item['metadata'] ?? [];
                                if (is_string($meta)) {
                                    $meta = json_decode($meta, true) ?? [];
                                }
                                if (isset($meta['metadata']) && is_array($meta['metadata'])) {
                                    $meta = $meta['metadata'];
                                }
                                $judul = $item['judul'] ?? ($meta['judul'] ?? 'Tanpa Judul');
                            @endphp

                            <div class="knowledge-item border border-gray-200 rounded-lg hover:shadow-md transition-shadow"
                                data-content="{{ strtolower($item['content'] ?? '') }}"
                                data-creator="{{ strtolower($item['created_by'] ?? '') }}"
                                data-verified="{{ $item['verified_data_by'] ? 'yes' : 'no' }}"
                                data-tested="{{ $item['verified_answer_by'] ? 'yes' : 'no' }}"
                                data-updated-at="{{ $item['updated_at'] ?? '' }}">

                                <div class="p-3 bg-gray-50 cursor-pointer"
     onclick="toggleExpand({{ $item['id'] }})">

    <div class="flex items-center justify-between gap-3">
        <!-- Kiri -->
        <div class="flex items-center gap-3">
            <!-- ID kecil (UI only) -->
            <span class="px-2 py-0.5 text-[10px] font-semibold
                         bg-gray-200 text-gray-700 rounded">
                 {{ $loop->iteration }}
            </span>

            <h3 class="text-sm font-medium text-gray-800 leading-tight">
                {{ $judul }}
            </h3>
        </div>

        <!-- Kanan -->
        <div class="flex items-center gap-2">
            @if (!empty($item['verified_data_by']))
                <span
                    class="px-2 py-0.5 bg-green-100 text-green-700 text-[10px]
                           font-semibold rounded-full">
                    VALID
                </span>
            @else
                <span
                    class="px-2 py-0.5 bg-yellow-100 text-yellow-700 text-[10px]
                           font-semibold rounded-full">
                    PENDING
                </span>
            @endif

            <!-- ICON TOGGLE (ID DB, JANGAN DIGANTI) -->
            <svg class="w-4 h-4 text-gray-400 transform transition-transform
                        expand-icon-{{ $item['id'] }}"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </div>
    </div>
</div>

                                <div id="expand-{{ $item['id'] }}" class="hidden p-6 border-t border-gray-200">
                                    <!-- Konten -->
                                    <div class="mb-6">
                                        <h4 class="text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            Konten
                                        </h4>
                                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                            <p class="text-gray-700 whitespace-pre-wrap">{{ $item['content'] ?? '-' }}</p>
                                        </div>
                                    </div>

                                    <div class="bg-blue-50 p-4 rounded-lg space-y-2 text-sm">

                                        {{-- METADATA LAIN (JIKA ADA) --}}
                                        @foreach ($meta as $key => $value)
                                            @if (!in_array($key, ['tahun', 'tags', 'sumber', 'judul']))
                                                <div>
                                                    <strong class="capitalize">
                                                        {{ str_replace('_', ' ', $key) }}:
                                                    </strong>
                                                    <span>
                                                        {{ is_array($value) ? implode(', ', $value) : $value }}
                                                    </span>
                                                </div>
                                            @endif
                                        @endforeach

                                        {{-- TAHUN --}}
                                        @if (!empty($meta['tahun']))
                                            <div>
                                                <strong>Tahun:</strong>
                                                <span>
                                                    {{ is_array($meta['tahun']) ? implode(', ', $meta['tahun']) : $meta['tahun'] }}
                                                </span>
                                            </div>
                                        @endif


                                        @if (!empty($meta['tags']))
                                            <div>
                                                <strong>Tags:</strong>
                                                <span>
                                                    {{ is_array($meta['tags']) ? implode(', ', $meta['tags']) : $meta['tags'] }}
                                                </span>
                                            </div>
                                        @endif


                                        {{-- SUMBER --}}
                                        @if (!empty($meta['sumber']))
                                            <div>
                                                <strong>Sumber:</strong>
                                                <span>
                                                    {{ is_array($meta['sumber']) ? implode(', ', $meta['sumber']) : ucfirst($meta['sumber']) }}
                                                </span>
                                            </div>
                                        @endif




                                        @if (empty($meta))
                                            <p class="text-gray-500 italic">Tidak ada metadata</p>
                                        @endif
                                    </div>


                                    <hr class="my-4">

                                    <!-- Actions -->
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <!-- Verifikasi Data -->
                                        <div>
                                            <h5 class="text-sm font-semibold mb-2 flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                </svg>
                                                Verifikasi Data
                                            </h5>
                                            @if ($item['verified_data_by'])
                                                <span
                                                    class="px-3 py-2 bg-green-100 text-green-700 text-xs font-bold rounded inline-flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    TERVALIDASI
                                                </span>
                                                <p class="text-xs text-gray-600 mt-2 flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                    </svg>
                                                    Oleh: <strong>{{ $item['verified_data_by'] }}</strong>
                                                </p>
                                            @else
                                                <span
                                                    class="px-3 py-2 bg-yellow-100 text-yellow-700 text-xs font-bold rounded inline-flex items-center gap-1 mb-2">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                    </svg>
                                                    Menunggu
                                                </span>
                                                <form action="{{ route('knowledge.verify.data', $item['id']) }}"
                                                    method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit"
                                                        class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded transition flex items-center justify-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                        Validasi Data
                                                    </button>
                                                </form>
                                            @endif
                                        </div>

                                        <!-- Verifikasi Jawaban -->
                                        <div>
                                            <h5 class="text-sm font-semibold mb-2 flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                </svg>
                                                Verifikasi Jawaban
                                            </h5>
                                            @if ($item['verified_answer_by'])
                                                <span
                                                    class="px-3 py-2 bg-green-100 text-green-700 text-xs font-bold rounded inline-flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    TERUJI
                                                </span>
                                                <p class="text-xs text-gray-600 mt-2 flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                    </svg>
                                                    Oleh: <strong>{{ $item['verified_answer_by'] }}</strong>
                                                </p>
                                            @else
                                                <span
                                                    class="px-3 py-2 bg-yellow-100 text-yellow-700 text-xs font-bold rounded inline-flex items-center gap-1 mb-2">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                    </svg>
                                                    Belum Tes
                                                </span>
                                                <form action="{{ route('knowledge.verify.answer', $item['id']) }}"
                                                    method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit"
                                                        class="w-full px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold rounded transition flex items-center justify-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                        Validasi Jawaban
                                                    </button>
                                                </form>
                                            @endif
                                        </div>

                                        <!-- Hapus -->
                                        <div>
                                            <h5 class="text-sm font-semibold mb-2 flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                Aksi
                                            </h5>
                                            <form action="{{ route('knowledge.destroy', $item['id']) }}" method="POST"
                                                id="delete-knowledge-form-{{ $item['id'] }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button"
                                                    onclick="showConfirmationModal('Hapus Data Pengetahuan', 'Apakah Anda yakin ingin menghapus data pengetahuan ini secara permanen?', document.getElementById('delete-knowledge-form-{{ $item['id'] }}'))"
                                                    class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded transition flex items-center justify-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-12 text-gray-400">
                                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                <p class="text-lg">Belum ada data knowledge base</p>
                            </div>
                        @endforelse
                    </div>

                    <!-- Pagination -->
                    <div id="paginationContainer" class="mt-6 flex justify-center" style="display: none;">
                        <nav class="inline-flex items-center rounded-lg  bg-white shadow-sm">

                            <!-- Prev -->
                            <button id="prevBtn"
                                class="px-3 py-2 text-gray-400 hover:text-gray-600 disabled:opacity-40 disabled:cursor-not-allowed">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>

                            <!-- Page Numbers -->
                            <div id="pageNumbers" class="flex items-center space-x-1 px-2"></div>

                            <!-- Next -->
                            <button id="nextBtn" class="px-3 py-2 text-gray-600 hover:text-gray-800">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </button>

                        </nav>
                    </div>


                    <div id="pageIndicator" class="mt-2 text-center text-sm text-gray-500" style="display: none;"></div>
                </div>
            </div>

            <!-- TAB 2: Tambah Data -->
            <div id="tambah-data" class="tab-content hidden">
                <div class="bg-white rounded-xl p-6 shadow space-y-6">

                    <!-- HEADER -->
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <h2 class="text-2xl font-semibold">Input Pengetahuan Baru</h2>
                    </div>

                    <!-- SUCCESS -->
                    @if (session('success'))
                       <div class="success-message bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded flex gap-3">

                            <svg class="w-5 h-5 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="font-semibold">{{ session('success') }}</p>
                        </div>
                    @endif

                    <!-- ERROR -->
                    @if ($errors->any())
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded">
                            <div class="flex gap-3">
                                <svg class="w-5 h-5 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <ul class="list-disc list-inside">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <!-- FORM -->
                    <form method="POST" action="{{ route('knowledge.store') }}" class="space-y-6">
                        @csrf

                        <!-- GRID ATAS -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                            <!-- KATEGORI -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Kategori</label>
                                <select name="kategori" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    @foreach (['Ekonomi', 'Infrastruktur', 'Kemiskinan', 'Kependudukan', 'Kesehatan', 'Lingkungan Hidup', 'Pariwisata dan Kebudayaan', 'Pemerintahan', 'Pendidikan', 'Sosial'] as $kategori)
                                        <option value="{{ $kategori }}"
                                            {{ old('kategori') == $kategori ? 'selected' : '' }}>
                                            {{ $kategori }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- TAHUN -->
                           <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Tahun Data</label>
    <select name="tahun" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
        <option disabled selected>-- Pilih Tahun --</option>
        @for ($year = date('Y') + 5; $year >= date('Y') - 5; $year--)
            <option value="{{ $year }}" {{ old('tahun') == $year ? 'selected' : '' }}>
                {{ $year }}
            </option>
        @endfor
    </select>
</div>

                            <!-- SUMBER -->
                       <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Sumber Data</label>
    <select name="sumber" class="sumber-select w-full px-4 py-2 border border-gray-300 rounded-lg">
    <option value="">-- Pilih Sumber --</option>
    @foreach ($referensi ?? [] as $ref)
        <option value="{{ $ref['sumber_data'] }}"
            {{ old('sumber') == $ref['sumber_data'] ? 'selected' : '' }}>
            {{ $ref['sumber_data'] }}
        </option>
    @endforeach
</select>
</div>

                            <!-- JUDUL -->
                            <div class="md:col-span-3">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Judul / Topik</label>
                                <input type="text" name="judul" required value="{{ old('judul') }}"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                    placeholder="Misal: Jadwal Pelayanan Publik">
                            </div>
                        </div>

                        <!-- KONTEN -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Detail Pengetahuan</label>
                            <textarea name="content" rows="8" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                placeholder="Masukkan informasi lengkap...">{{ old('content') }}</textarea>
                        </div>

                        <!-- METADATA -->
                        <div class="border border-gray-300 rounded-lg p-4 bg-gray-50 space-y-4">
                            <div class="font-semibold text-gray-700">Metadata Tambahan (Opsional)</div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tags</label>
                                <input type="text" name="tags" value="{{ old('tags') }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                    placeholder="pelayanan, jadwal, kantor">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan Tambahan</label>
                                <textarea name="keterangan" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                    placeholder="Catatan atau konteks tambahan">{{ old('keterangan') }}</textarea>
                            </div>
                        </div>

                        <!-- ACTION -->
                        <div class="flex gap-3">
                            <button type="submit"
                                class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow flex items-center gap-2">
                                Simpan & Proses ke AI (n8n)
                            </button>

                            <button type="reset"
                                class="px-6 py-3 bg-gray-300 hover:bg-gray-400 text-gray-700 font-semibold rounded-lg">
                                Reset Form
                            </button>
                        </div>

                    </form>
                </div>
            </div>


            <!-- TAB 3: Referensi Sumber -->
            <div id="referensi-sumber" class="tab-content hidden">
                <div class="bg-white rounded-xl p-6 shadow space-y-6">
                    <div class="flex items-center gap-2">
                        <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6M7 4h8l4 4v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z" />
                        </svg>
                        <h2 class="text-2xl font-semibold">Referensi Sumber Data</h2>
                    </div>

                    <!-- Success/Error Messages -->
                    @if (session('success'))
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded flex gap-3">
                            <svg class="w-5 h-5 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="font-semibold">{{ session('success') }}</p>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded flex gap-3">
                            <svg class="w-5 h-5 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="font-semibold">{{ session('error') }}</p>
                        </div>
                    @endif

                    <!-- Statistics -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-lg p-6 shadow-lg">
                            <div class="text-sm opacity-90 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6M7 4h8l4 4v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z" />
                                </svg>
                                Total Referensi
                            </div>
                            <div class="text-4xl font-bold mt-2">{{ count($referensi ?? []) }}</div>
                        </div>

                        <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-lg p-6 shadow-lg">
                            <div class="text-sm opacity-90 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                Status
                            </div>
                            <div class="text-2xl font-bold mt-2">Aktif</div>
                        </div>
                    </div>

                    <hr class="my-6">

                    <!-- Form Tambah Referensi -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                        <h3 class="text-xl font-semibold mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            Tambah Referensi Sumber Data Baru
                        </h3>
                        <form method="POST" action="{{ route('referensi.store') }}" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Sumber Data</label>
                                <input type="text" name="sumber_data" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                    placeholder="Contoh: Disdukcapil, BPS, Dinas Kesehatan, dll">
                            </div>

                            <button type="submit"
                                class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                Tambah Referensi
                            </button>
                        </form>
                    </div>

                    <hr class="my-6">

                    <!-- List Referensi -->
                    <div>
                        <h3 class="text-xl font-semibold mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            Daftar Referensi
                        </h3>

                        @if (count($referensi ?? []) === 0)
                            <div class="text-center py-12">
                                <svg class="w-20 h-20 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6M7 4h8l4 4v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z" />
                                </svg>
                                <p class="text-2xl text-gray-400 font-semibold">Belum ada referensi sumber data</p>
                                <p class="text-gray-600 mt-2">Tambahkan referensi sumber data pertama Anda di atas.</p>
                            </div>
                        @else
                            <div class="space-y-4">
                                @foreach ($referensi ?? [] as $index => $ref)
                                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <span class="px-3 py-1 bg-blue-100 text-blue-700 text-sm font-semibold rounded-full">
                                                    #{{ $index + 1 }}
                                                </span>
                                                <h4 class="text-lg font-medium text-gray-800">{{ $ref['sumber_data'] }}</h4>
                                                <span class="px-2 py-1 bg-green-100 text-green-700 text-xs font-semibold rounded">
                                                    Aktif
                                                </span>
                                            </div>

                                            <div class="flex items-center gap-2">
                                                <span class="text-sm text-gray-500">
                                                    Dibuat: {{ \Carbon\Carbon::parse($ref['created_at'])->format('d M Y, H:i') }}
                                                </span>
                                                <form action="{{ route('referensi.destroy', $ref['id']) }}" method="POST"
                                                    class="inline" id="delete-referensi-form-{{ $ref['id'] }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button"
                                                        onclick="showConfirmationModal('Hapus Referensi', 'Apakah Anda yakin ingin menghapus referensi sumber data ini?', document.getElementById('delete-referensi-form-{{ $ref['id'] }}'))"
                                                        class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded transition flex items-center gap-1">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                        Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- TAB 4: Pertanyaan Belum Terjawab -->
            <div id="pertanyaan" class="tab-content hidden space-y-6">
                <div class="bg-white rounded-xl p-6 shadow">
                    <div class="flex items-center gap-2 mb-6">
                        <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h2 class="text-2xl font-semibold">Pertanyaan yang Belum Dapat Dijawab</h2>
                    </div>
                    <p class="text-gray-600 mb-6">Tracking pertanyaan dari Wali Kota/Pimpinan yang tidak memiliki jawaban
                        di database</p>

                    <!-- Statistics -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-gradient-to-br from-red-500 to-red-600 text-white rounded-lg p-6 shadow-lg">
                            <div class="text-sm opacity-90 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Total Belum Terjawab
                            </div>
                            <div class="text-4xl font-bold mt-2">{{ count($unanswered) }}</div>
                        </div>

                        <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 text-white rounded-lg p-6 shadow-lg">
                            <div class="text-sm opacity-90 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                Sesi Percakapan
                            </div>
                            <div class="text-4xl font-bold mt-2">
                                {{ collect($unanswered)->pluck('session_id')->unique()->count() }}</div>
                        </div>

                        <div class="bg-gradient-to-br from-teal-500 to-teal-600 text-white rounded-lg p-6 shadow-lg">
                            <div class="text-sm opacity-90 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                Status
                            </div>
                            <div class="text-2xl font-bold mt-2">
                                {{ count($unanswered) > 0 ? 'Perlu Tindak Lanjut' : 'Semua Terjawab' }}</div>
                        </div>
                    </div>

                    <hr class="my-6">

                    @if (count($unanswered) === 0)
                        <div class="text-center py-12">
                            <svg class="w-20 h-20 mx-auto mb-4 text-green-500" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-2xl text-green-600 font-semibold">Tidak ada pertanyaan yang belum terjawab!</p>
                            <p class="text-gray-600 mt-2">Knowledge base sudah lengkap.</p>
                        </div>
                    @else
                        <!-- List Pertanyaan -->
                        <div class="space-y-4">
                            @foreach ($unanswered as $idx => $q)
                                <details class="border border-gray-200 rounded-lg hover:shadow-md transition">
                                    <summary class="cursor-pointer p-4 bg-gray-50 font-semibold flex items-center gap-2">
                                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        #{{ $idx + 1 }} {{ Str::limit($q['question'], 80) }}
                                    </summary>

                                    <div class="p-6 space-y-4">
                                        <div>
                                            <h4 class="font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                                </svg>
                                                Pertanyaan dari User
                                            </h4>
                                            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                                <p class="text-gray-800">{{ $q['user_message'] }}</p>
                                            </div>
                                        </div>

                                        <div>
                                            <h4 class="font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                </svg>
                                                Response AI (Tidak Memadai)
                                            </h4>
                                            <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                                                <p class="text-red-700">{{ $q['ai_response'] }}</p>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-2 gap-3 text-xs text-gray-600">
                                            <p><strong>Session ID:</strong> {{ Str::limit($q['session_id'], 20) }}</p>
                                            <p><strong>Record ID:</strong> {{ $q['id'] }}</p>
                                        </div>

                                        <hr>

                                        <!-- Quick Add Form -->
                                        <form method="POST" action="{{ route('knowledge.quick-add') }}" class="space-y-6">
    @csrf

    <!-- Hidden fields -->
    <input type="hidden" name="original_question" value="{{ $q['question'] }}">
    <input type="hidden" name="session_id" value="{{ $q['session_id'] }}">

    <!-- GRID ATAS -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        <!-- KATEGORI -->
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Kategori</label>
            <select name="kategori" required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                @foreach (['Ekonomi', 'Infrastruktur', 'Kemiskinan', 'Kependudukan', 'Kesehatan', 'Lingkungan Hidup', 'Pariwisata dan Kebudayaan', 'Pemerintahan', 'Pendidikan', 'Sosial'] as $kategori)
                    <option value="{{ $kategori }}"
                        {{ old('kategori') == $kategori ? 'selected' : '' }}>
                        {{ $kategori }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- TAHUN -->
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Tahun Data</label>
            <select name="tahun" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                <option disabled selected>-- Pilih Tahun --</option>
                @for ($year = date('Y') + 5; $year >= date('Y') - 5; $year--)
                    <option value="{{ $year }}"
                        {{ old('tahun') == $year ? 'selected' : '' }}>
                        {{ $year }}
                    </option>
                @endfor
            </select>
        </div>

        <!-- SUMBER -->
         <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Sumber Data</label>
    <select name="sumber" class="sumber-select w-full px-4 py-2 border border-gray-300 rounded-lg">
    <option value="">-- Pilih Sumber --</option>
    @foreach ($referensi ?? [] as $ref)
        <option value="{{ $ref['sumber_data'] }}"
            {{ old('sumber') == $ref['sumber_data'] ? 'selected' : '' }}>
            {{ $ref['sumber_data'] }}
        </option>
    @endforeach
</select>
</div>

        <!-- JUDUL -->
        <div class="md:col-span-3">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Judul / Topik</label>
            <input type="text" name="judul" required value="{{ old('judul') }}"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                placeholder="Misal: Jadwal Pelayanan Publik">
        </div>
    </div>

    <!-- KONTEN -->
    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-2">Detail Pengetahuan</label>
        <textarea name="content" rows="8" required
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            placeholder="Masukkan informasi lengkap...">{{ old('content') }}</textarea>
    </div>

    <!-- METADATA -->
    <div class="border border-gray-300 rounded-lg p-4 bg-gray-50 space-y-4">
        <div class="font-semibold text-gray-700">Metadata Tambahan (Opsional)</div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tags</label>
            <input type="text" name="tags" value="{{ old('tags') }}"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                placeholder="pelayanan, jadwal, kantor">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan Tambahan</label>
            <textarea name="keterangan" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                placeholder="Catatan atau konteks tambahan">{{ old('keterangan') }}</textarea>
        </div>
    </div>

    <!-- ACTION -->
    <div class="flex gap-3">
        <button type="submit"
            class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow flex items-center gap-2">
            Simpan & Proses ke AI (n8n)
        </button>

        <button type="reset"
            class="px-6 py-3 bg-gray-300 hover:bg-gray-400 text-gray-700 font-semibold rounded-lg">
            Reset Form
        </button>
    </div>

</form>
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- TAB 4: Manajemen User -->
            <div id="manajemen-user" class="tab-content hidden">
                <div class="bg-white rounded-xl p-6 shadow space-y-8">
                    <div class="flex items-center gap-2">
                        <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <h2 class="text-2xl font-semibold">Manajemen User</h2>
                    </div>

                    @if (session('success'))
                        <div
                            class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded flex items-start gap-3">
                            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <ul class="list-disc list-inside">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <!-- Form Tambah User -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                        <h3 class="text-xl font-semibold mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                            Tambah User Baru
                        </h3>
                        <form method="POST" action="{{ route('users.store') }}" class="space-y-4">
                            @csrf
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        Username
                                    </label>
                                    <input type="text" name="username" required value="{{ old('username') }}"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="Masukkan username">
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Full Name
                                    </label>
                                    <input type="text" name="full_name" required value="{{ old('full_name') }}"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="Masukkan nama lengkap">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                        Password
                                    </label>
                                    <input type="password" name="password" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="Minimal 6 karakter">
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                        Role
                                    </label>
                                    <select name="role" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        <option value="staff" {{ old('role') == 'staff' ? 'selected' : '' }}>Staff
                                        </option>
                                        <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <button type="submit"
                                class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                Tambah User
                            </button>
                        </form>
                    </div>

                    <hr class="my-8">

                    <!-- Form Ganti Password User -->
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                        <h3 class="text-xl font-semibold mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                            </svg>
                            Ganti Password User
                        </h3>
                        <form method="POST" action="{{ route('users.updatePassword') }}" class="space-y-4">
                            @csrf
                            @method('PUT')

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    Pilih User
                                </label>
                                <select name="user_id" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
                                    <option value="">-- Pilih User --</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user['id'] }}">{{ $user['username'] }} -
                                            {{ $user['full_name'] ?? '-' }} ({{ ucfirst($user['role']) }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                        Password Baru
                                    </label>
                                    <input type="password" name="password" required minlength="6"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent"
                                        placeholder="Minimal 6 karakter">
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                        Konfirmasi Password
                                    </label>
                                    <input type="password" name="password_confirmation" required minlength="6"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent"
                                        placeholder="Ulangi password baru">
                                </div>
                            </div>

                            <button type="submit"
                                class="px-6 py-3 bg-yellow-600 hover:bg-yellow-700 text-white font-semibold rounded-lg transition flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Ganti Password
                            </button>
                        </form>
                    </div>

                    <hr class="my-8">

                    <!-- Daftar User -->
                    <div>
                        <h3 class="text-xl font-semibold mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            Daftar User
                        </h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white border border-gray-200 rounded-lg">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                            ID</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                            Username</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                            Full Name</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                            Role</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                            Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @forelse($users as $user)
                                        <tr class="hover:bg-gray-50 transition">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                                {{ $user['id'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div
                                                        class="flex-shrink-0 h-10 w-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                                                        {{ strtoupper(substr($user['username'], 0, 1)) }}
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900">
                                                            {{ $user['username'] }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                                {{ $user['full_name'] ?? '-' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if ($user['role'] === 'admin')
                                                    <span
                                                        class="px-3 py-1 bg-purple-100 text-purple-700 text-xs font-semibold rounded-full flex items-center gap-1 w-fit">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                                        </svg>
                                                        Admin
                                                    </span>
                                                @else
                                                    <span
                                                        class="px-3 py-1 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full flex items-center gap-1 w-fit">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                        </svg>
                                                        Staff
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <form action="{{ route('users.destroy', $user['id']) }}" method="POST"
                                                    class="inline" id="delete-user-form-{{ $user['id'] }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button"
                                                        onclick="showConfirmationModal('Hapus User', 'Apakah Anda yakin ingin menghapus user {{ $user['username'] }}?', document.getElementById('delete-user-form-{{ $user['id'] }}'))"
                                                        class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded transition flex items-center gap-1">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                        Hapus
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-6 py-8 text-center text-gray-400">
                                                <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                                Belum ada user
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Loading Overlay
        window.addEventListener('load', () => {
            const overlay = document.getElementById('loadingOverlay');
            overlay.classList.remove('hidden');
            setTimeout(() => overlay.classList.add('hidden'), 500);
        });

        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', () => {
                document.getElementById('loadingOverlay').classList.remove('hidden');
            });
        });

        // Initialize Charts
        document.addEventListener('DOMContentLoaded', function() {
            const verificationCtx = document.getElementById('verificationChart').getContext('2d');
            new Chart(verificationCtx, {
                type: 'bar',
                data: {
                    labels: ['Data Tervalidasi', 'Jawaban Diuji', 'Menunggu Review'],
                    datasets: [{
                        label: 'Jumlah',
                        data: [{{ $verifiedData }}, {{ $verifiedAnswer }}, {{ $pending }}],
                        backgroundColor: [
                            'rgba(34, 197, 94, 0.8)',
                            'rgba(168, 85, 247, 0.8)',
                            'rgba(249, 115, 22, 0.8)'
                        ],
                        borderColor: [
                            'rgb(34, 197, 94)',
                            'rgb(168, 85, 247)',
                            'rgb(249, 115, 22)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });

            // Category Distribution Chart
            const categoryCtx = document.getElementById('categoryChart').getContext('2d');
            new Chart(categoryCtx, {
                type: 'doughnut',
                data: {
                    labels: {!! json_encode($categories->keys()) !!},
                    datasets: [{
                        data: {!! json_encode($categories->values()) !!},
                        backgroundColor: [
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(16, 185, 129, 0.8)',
                            'rgba(245, 158, 11, 0.8)',
                            'rgba(239, 68, 68, 0.8)',
                            'rgba(139, 92, 246, 0.8)',
                            'rgba(236, 72, 153, 0.8)',
                            'rgba(14, 165, 233, 0.8)',
                            'rgba(132, 204, 22, 0.8)',
                            'rgba(249, 115, 22, 0.8)',
                            'rgba(107, 114, 128, 0.8)'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                boxWidth: 12,
                                padding: 10,
                                font: {
                                    size: 11
                                }
                            }
                        }
                    }
                }
            });

            // Status Pie Chart
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            new Chart(statusCtx, {
                type: 'pie',
                data: {
                    labels: ['Tervalidasi', 'Belum Validasi'],
                    datasets: [{
                        data: [{{ $verifiedData }}, {{ $totalKnowledge - $verifiedData }}],
                        backgroundColor: [
                            'rgba(34, 197, 94, 0.8)',
                            'rgba(239, 68, 68, 0.8)'
                        ],
                        borderColor: '#fff',
                        borderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                font: {
                                    size: 12
                                }
                            }
                        }
                    }
                }
            });
        });

        // Tab Navigation
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.add('hidden');
            });

            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('border-blue-600', 'text-blue-600');
                btn.classList.add('border-transparent', 'text-gray-600');
            });

            document.getElementById(tabName).classList.remove('hidden');

            const activeBtn = document.querySelector(`[data-tab="${tabName}"]`);
            activeBtn.classList.remove('border-transparent', 'text-gray-600');
            activeBtn.classList.add('border-blue-600', 'text-blue-600');
        }

        // Toggle expand knowledge item
        function toggleExpand(id) {
            const content = document.getElementById(`expand-${id}`);
            const icon = document.querySelector(`.expand-icon-${id}`);

            content.classList.toggle('hidden');
            icon.classList.toggle('rotate-180');
        }

        // Search and Filter Knowledge
        const searchInput = document.getElementById('searchKnowledge');
        const filterStatus = document.getElementById('filterStatus');
        const knowledgeItems = document.querySelectorAll('.knowledge-item');
        const showingCount = document.getElementById('showingCount');
        const knowledgeList = document.getElementById('knowledgeList');
        const paginationContainer = document.querySelector('.mt-6.flex.justify-center');
        let currentPage = 1;
        let itemsPerPage = 10;
        let filteredItems = Array.from(knowledgeItems);
        let isFiltered = false;

        function filterKnowledge() {
            const searchTerm = searchInput.value.toLowerCase();
            const statusFilter = filterStatus.value;

            // Check if any filtering is applied
            isFiltered = searchTerm.length > 0 || statusFilter !== 'all';

            if (isFiltered) {
                filteredItems = Array.from(knowledgeItems).filter(item => {
                    const content = item.dataset.content;
                    const creator = item.dataset.creator;
                    const verified = item.dataset.verified;
                    const tested = item.dataset.tested;

                    const matchesSearch = content.includes(searchTerm) || creator.includes(searchTerm);

                    let matchesStatus = true;
                    if (statusFilter === 'unverified') {
                        matchesStatus = verified === 'no';
                    } else if (statusFilter === 'verified') {
                        matchesStatus = verified === 'yes';
                    } else if (statusFilter === 'untested') {
                        matchesStatus = tested === 'no';
                    }

                    return matchesSearch && matchesStatus;
                });
            } else {
                // If no filtering, show all items
                filteredItems = Array.from(knowledgeItems);
            }

            // Reset to page 1 when filtering
            currentPage = 1;
            updateDisplay();
        }

        function updateDisplay() {
    // Hide all items first (pakai class, bukan style)
    knowledgeItems.forEach(item => {
        item.classList.add('hidden');
    });

    if (isFiltered) {
        // Show all filtered items
        filteredItems.forEach(item => {
            item.classList.remove('hidden');
        });

        // Hide pagination
        if (paginationContainer) {
            paginationContainer.classList.add('hidden');
        }
    } else {
        // Pagination mode
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;

        filteredItems
            .slice(startIndex, endIndex)
            .forEach(item => {
                item.classList.remove('hidden');
            });

        // Show pagination
        if (paginationContainer) {
            paginationContainer.classList.remove('hidden');
        }

        updatePagination();
    }

    // Update counter
    showingCount.textContent = filteredItems.length;
}


        function updatePagination() {
            const totalPages = Math.ceil(filteredItems.length / itemsPerPage);
            const paginationContainer = document.getElementById('paginationContainer');
            const pageIndicator = document.getElementById('pageIndicator');
            const pageNumbers = document.getElementById('pageNumbers');
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');

            if (totalPages <= 1) {
                if (paginationContainer) paginationContainer.style.display = 'none';
                if (pageIndicator) pageIndicator.style.display = 'none';
                return;
            }

            if (paginationContainer) {
                paginationContainer.style.display = 'flex';
            }
            if (pageIndicator) {
                pageIndicator.style.display = 'block';
                pageIndicator.textContent = `Halaman ${currentPage} dari ${totalPages}`;
            }

            // Update Previous button
            if (currentPage > 1) {
                prevBtn.className =
                    'px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-l-md hover:bg-gray-50';
                prevBtn.disabled = false;
            } else {
                prevBtn.className =
                    'px-3 py-2 text-sm font-medium text-gray-300 bg-gray-100 border border-gray-300 rounded-l-md cursor-not-allowed';
                prevBtn.disabled = true;
            }

            // Update Next button
            if (currentPage < totalPages) {
                nextBtn.className =
                    'px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-r-md hover:bg-gray-50';
                nextBtn.disabled = false;
            } else {
                nextBtn.className =
                    'px-3 py-2 text-sm font-medium text-gray-300 bg-gray-100 border border-gray-300 rounded-r-md cursor-not-allowed';
                nextBtn.disabled = true;
            }

            // Generate page number buttons
            pageNumbers.innerHTML = '';
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(totalPages, currentPage + 2);

            for (let i = startPage; i <= endPage; i++) {
                const pageBtn = document.createElement('button');
                pageBtn.textContent = i;
                pageBtn.className = 'px-3 py-2 text-sm font-medium border border-gray-300 hover:bg-gray-50';

                if (i === currentPage) {
                    pageBtn.className = 'px-3 py-2 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-500';
                } else {
                    pageBtn.className =
                        'px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 hover:bg-gray-50';
                }

                pageBtn.addEventListener('click', () => goToPage(i));
                pageNumbers.appendChild(pageBtn);
            }
        }

        // Pagination controls
        function goToPage(page) {
            const totalPages = Math.ceil(filteredItems.length / itemsPerPage);
            if (page < 1 || page > totalPages) return;
            currentPage = page;
            updateDisplay();
        }

        // Event listeners
        searchInput?.addEventListener('input', filterKnowledge);
        filterStatus?.addEventListener('change', filterKnowledge);

        // Pagination button event listeners
        document.getElementById('prevBtn')?.addEventListener('click', () => {
            if (currentPage > 1) {
                goToPage(currentPage - 1);
            }
        });

        document.getElementById('nextBtn')?.addEventListener('click', () => {
            const totalPages = Math.ceil(filteredItems.length / itemsPerPage);
            if (currentPage < totalPages) {
                goToPage(currentPage + 1);
            }
        });

        // Initial display
        updateDisplay();

        // Success message auto-hide
        setTimeout(() => {
            const successMsg = document.querySelector('.success-message');
            if (successMsg) {
                successMsg.style.transition = 'opacity 0.5s';
                successMsg.style.opacity = '0';
                setTimeout(() => successMsg.remove(), 500);
            }
        }, 5000);
        
    </script>

    <!-- Custom Confirmation Modal -->
    <div id="confirmationModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <svg class="w-6 h-6 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <h3 id="modalTitle" class="text-lg font-semibold text-gray-900">Konfirmasi Hapus</h3>
                </div>
                <p id="modalMessage" class="text-gray-600 mb-6">Apakah Anda yakin ingin melanjutkan?</p>
                <div class="flex justify-end gap-3">
                    <button id="modalCancel" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-lg transition">
                        Batal
                    </button>
                    <button id="modalConfirm" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                        Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Modal functionality
        let currentForm = null;

        function showConfirmationModal(title, message, formElement) {
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalMessage').textContent = message;
            currentForm = formElement;
            document.getElementById('confirmationModal').classList.remove('hidden');
        }

        function hideConfirmationModal() {
            document.getElementById('confirmationModal').classList.add('hidden');
            currentForm = null;
        }

        function confirmAction() {
            if (currentForm) {
                currentForm.submit();
            }
            hideConfirmationModal();
        }

        // Event listeners
        document.getElementById('modalCancel').addEventListener('click', hideConfirmationModal);
        document.getElementById('modalConfirm').addEventListener('click', confirmAction);

        // Close modal when clicking outside
        document.getElementById('confirmationModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideConfirmationModal();
            }
        });
    </script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        // Initialize select2 for all elements with class sumber-select
        $('.sumber-select').select2({
            placeholder: "-- Pilih Sumber --",
            allowClear: true,
            width: '100%'
        });
        
        // Re-initialize when tab becomes visible
        $(document).on('click', '[data-tab]', function() {
            setTimeout(function() {
                $('.sumber-select').select2('destroy').select2({
                    placeholder: "-- Pilih Sumber --",
                    allowClear: true,
                    width: '100%'
                });
            }, 100);
        });
    });
</script>
@endsection
