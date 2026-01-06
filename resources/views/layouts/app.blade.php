<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard')</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .sidebar-transition {
            transition: width 0.3s ease-in-out;
        }

        .content-transition {
            transition: margin-left 0.3s ease-in-out;
        }
    </style>

    @php
        $user = session('supabase_user');
    @endphp
</head>

<body class="bg-gray-100">
    <div class="flex h-screen overflow-hidden">

        <!-- SIDEBAR -->
        <aside id="sidebar"
            class="sidebar-transition w-0 bg-gradient-to-b from-gray-800 to-gray-900 text-white flex flex-col relative">

            <!-- TOGGLE BUTTON -->
            <button id="toggleSidebar"
                class="absolute -right-3 top-1/2 -translate-y-1/2 bg-gray-800 hover:bg-gray-700 text-white rounded-full p-2 shadow-lg z-10">
                <svg id="iconOpen" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 19l-7-7 7-7" />
                </svg>
                <svg id="iconClose" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5l7 7-7 7" />
                </svg>
            </button>

            <div id="sidebarContent" class="opacity-0 overflow-hidden flex flex-col h-full">

                <!-- USER PROFILE -->
                <div class="p-6 border-b border-gray-700">
                    <div class="flex items-center gap-3">

                        <!-- AVATAR INITIAL (SOLID NAVY – NO GRADIENT) -->
                        <div
                            class="w-10 h-10 rounded-full bg-indigo-900 flex items-center justify-center text-white font-semibold text-lg shadow-sm">
                            {{ strtoupper(substr(session('supabase_user.username'), 0, 1)) }}
                        </div>

                        <div>
                            <h2 class="text-lg font-semibold leading-tight">
                                {{ session('supabase_user.username') }}
                            </h2>

                            <div class="flex items-center text-orange-400 text-sm mt-0.5">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 11c1.657 0 3-1.343 3-3S13.657 5 12 5s-3 1.343-3 3 1.343 3 3 3z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 21v-2a4 4 0 00-4-4H9a4 4 0 00-4 4v2" />
                                </svg>
                                {{ ucfirst($user['role'] ?? 'guest') }}
                            </div>
                        </div>

                    </div>
                </div>

                <!-- INFO SESI -->
                <div class="px-6 py-4">
                    <div class="flex items-center mb-3">
                        <svg class="w-6 h-6 mr-2 text-blue-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M12 20a8 8 0 100-16 8 8 0 000 16z" />
                        </svg>
                        <h3 class="font-semibold text-lg">Info Sesi</h3>
                    </div>

                    <div class="bg-blue-900/40 rounded-lg p-4 space-y-2">
                        <div>
                            <span class="text-blue-300 font-medium">Nama</span>
                            <span class="text-white ml-2">
                                {{ $user['full_name'] ?? '-' }}
                            </span>
                        </div>

                        <div>
                            <span class="text-blue-300 font-medium">Login</span>
                            <span class="text-white ml-2">
                                {{ isset($user['login_at']) ? \Carbon\Carbon::parse($user['login_at'])->format('d/m/Y H:i') : '-' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- TOTAL KNOWLEDGE -->
                <div class="px-6 py-4 border-t border-gray-700">
                    <div class="flex items-center mb-2">
                        <svg class="w-6 h-6 mr-2 text-green-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6l-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V8a2 2 0 00-2-2h-4l-2 2z" />
                        </svg>
                        <h3 class="font-semibold">Total Knowledge</h3>
                    </div>
                    <div class="text-5xl font-bold">
                        {{ $totalKnowledge ?? 0 }}
                    </div>
                </div>

                <!-- LOGOUT -->
                <div class="p-6 border-t border-gray-700 mt-auto">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="w-full flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 rounded-lg font-semibold transition shadow-lg hover:scale-105">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1" />
                            </svg>
                            Logout
                        </button>
                    </form>
                </div>

            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main id="mainContent" class="content-transition flex-1 overflow-y-auto bg-gray-100">
            @yield('content')
        </main>
    </div>

    <script>
        const sidebar = document.getElementById('sidebar');
        const sidebarContent = document.getElementById('sidebarContent');
        const toggleBtn = document.getElementById('toggleSidebar');
        const iconOpen = document.getElementById('iconOpen');
        const iconClose = document.getElementById('iconClose');

        let isOpen = false;

        toggleBtn.addEventListener('click', () => {
            isOpen = !isOpen;

            if (isOpen) {
                sidebar.classList.replace('w-0', 'w-80');
                sidebarContent.classList.remove('opacity-0');
                iconOpen.classList.remove('hidden');
                iconClose.classList.add('hidden');
            } else {
                sidebar.classList.replace('w-80', 'w-0');
                sidebarContent.classList.add('opacity-0');
                iconOpen.classList.add('hidden');
                iconClose.classList.remove('hidden');
            }
        });
    </script>

</body>
</html>
