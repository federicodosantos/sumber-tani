<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SumberTani - @yield('title', 'Dashboard')</title>
    
    {{-- Memuat Vite untuk Tailwind --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100/50 antialiased">
    <div class="flex h-screen">
        
        <aside class="w-64 bg-white shadow-sm flex flex-col" style="border-right: 1px solid #e5e7eb;">
            <div class="p-6">
                <h1 class="text-2xl font-bold text-blue-600">SumberTani</h1>
            </div>

            <nav class="flex-1 px-4 space-y-2">
                <a href="#" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-gray-700 hover:bg-gray-100">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
                    <span>Dashboard</span>
                </a>
                
                {{-- Link Aktif --}}
                <a href="#" class="flex items-center gap-3 px-4 py-2.5 rounded-lg bg-blue-600 text-white font-medium">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25A2.25 2.25 0 0 1 13.5 8.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" /></svg>
                    <span>Product</span>
                </a>

                <a href="#" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-gray-700 hover:bg-gray-100">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" /></svg>
                    <span>Product Stock</span>
                </a>
                
                <a href="#" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-gray-700 hover:bg-gray-100">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.35 3.836c-.065.22-.1.447-.1.684v16.16c0 .663.26 1.295.73 1.765C12.42 22.86 13.18 23 14 23c.82 0 1.58-.14 2.27-.406.73-.28 1.39-.74 1.87-1.37.48-.63.73-1.4.73-2.225V4.52c0-.237-.035-.464-.1-.684M11.35 3.836c.065-.22.1-.447.1-.684V3c0-.82.14-1.58.406-2.27.28-.73.74-1.39 1.37-1.87.63-.48 1.4-.73 2.225-.73.825 0 1.595.25 2.225.73.48.48.94 1.14 1.37 1.87.266.69.406 1.45.406 2.27v.836M11.35 3.836c-.73.28-1.39.74-1.87 1.37C8.85 5.836 8.6 6.6 8.6 7.425v10.15c0 .825.25 1.595.73 2.225.48.63 1.14.94 1.87 1.37.69.266 1.45.406 2.27.406.82 0 1.58-.14 2.27-.406.73-.28 1.39-.74 1.87-1.37.48-.63.73-1.4.73-2.225V7.425c0-.825-.25-1.595-.73-2.225-.48-.63-1.14-.94-1.87-1.37C12.93 3.556 12.17 3.42 11.45 3.42c-.237 0-.464.035-.684.1Z" /></svg>
                    <span>Item Categories</span>
                </a>

                <a href="#" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-gray-700 hover:bg-gray-100">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h12A2.25 2.25 0 0 0 20.25 14.25V3m-16.5 0h16.5m-16.5 0H3.75m0 0h.008v.008H3.75V3Zm0 0h.008v.008H3.75V3Zm0 0h.008v.008H3.75V3Zm0 0h.008v.008H3.75V3Zm0 0V3m0 18v-2.25m0 0h16.5m-16.5 0h.008v.008H3.75V18.75Zm0 0h.008v.008H3.75V18.75Zm0 0h.008v.008H3.75V18.75Zm0 0h.008v.008H3.75V18.75Zm0 0h16.5m-16.5 0V18.75m0 2.25h16.5m0 0h.008v.008H20.25V21Zm0 0h.008v.008H20.25V21Zm0 0h.008v.008H20.25V21Zm0 0h.008v.008H20.25V21Zm0 0V21m-2.25-18h-12A2.25 2.25 0 0 0 3.75 5.25v11.25c0 1.242 1.008 2.25 2.25 2.25h12c1.242 0 2.25-1.008 2.25-2.25V5.25A2.25 2.25 0 0 0 18 3Z" /></svg>
                    <span>Laporan Keuangan</span>
                </a>
                
                <a href="#" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-gray-700 hover:bg-gray-100">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" /></svg>
                    <span>Riwayat Aktivitas</span>
                </a>
            </nav>

            <div class="px-4 py-6 mt-auto space-y-2">
                <a href="#" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-gray-700 hover:bg-gray-100">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.242 1.418l-1.06 1.06c-.318.319-.385.78-.182 1.15l.202.368c.075.136.14.279.196.426.063.16.094.33.094.5s-.03.34-.094.5a2.5 2.5 0 0 1-.196.426l-.202.368c-.203.37-.005.83.318 1.15l1.06 1.06c.516.516.558 1.353.242 1.418l-1.296 2.247a1.125 1.125 0 0 1-1.37.49l-1.217-.456c-.354-.133-.75-.072-1.075.124a3.1 3.1 0 0 1-.22.127c-.332.183-.582.495-.645.87l-.213 1.281c-.09.542-.56.94-1.11.94h-2.593c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.063-.374-.313-.686-.645-.87a3.1 3.1 0 0 1-.22-.127c-.325-.196-.72-.257-1.075-.124l-1.217.456a1.125 1.125 0 0 1-1.37-.49l-1.296-2.247a1.125 1.125 0 0 1 .242-1.418l1.06-1.06c.318-.319.385-.78.182-1.15l-.202-.368a2.5 2.5 0 0 1-.196-.426c-.063-.16-.094-.33-.094-.5s.03-.34.094-.5c.056-.147.12-.29.196-.426l.202-.368c.203-.37.005-.83-.318-1.15l-1.06-1.06a1.125 1.125 0 0 1-.242-1.418l1.296-2.247a1.125 1.125 0 0 1 1.37-.49l1.217.456c.354.133.75.072 1.075-.124.073-.044.146-.087.22-.127.332-.183.582-.495.645-.87l.213-1.281Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                    <span>Settings</span>
                </a>
                <a href="#" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-gray-700 hover:bg-gray-100">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" /></svg>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <main class="flex-1 overflow-y-auto">
            <div class="p-6 lg:p-10">
                @yield('content')
            </div>
        </main>

    </div>
</body>
</html>