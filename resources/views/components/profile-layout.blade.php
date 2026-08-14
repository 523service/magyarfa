@props(['title' => 'Profilom'])

<x-layouts.shop>
    <x-slot name="title">{{ $title }} - MagyarSzigetelés.hu</x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto">
            <h1 class="text-2xl font-bold text-[var(--text-main)] mb-8">Fiókom</h1>

            <div class="flex flex-col lg:flex-row gap-6">
                {{-- Sidebar --}}
                <aside class="w-full lg:w-64 flex-shrink-0">
                    <nav class="bg-white rounded-[var(--radius-lg)] shadow-[var(--shadow-sm)] overflow-hidden">
                        <ul class="divide-y divide-[var(--border-subtle)]">
                            <li>
                                <a
                                    href="{{ route('profile.edit') }}"
                                    class="flex items-center gap-3 px-4 py-3 text-sm font-medium transition-colors {{ request()->routeIs('profile.edit') ? 'bg-[var(--accent)]/10 text-[var(--accent)] border-l-4 border-[var(--accent)]' : 'text-[var(--text-main)] hover:bg-gray-50' }}"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                    </svg>
                                    Profil adatok
                                </a>
                            </li>
                            <li>
                                <a
                                    href="{{ route('profile.contact') }}"
                                    class="flex items-center gap-3 px-4 py-3 text-sm font-medium transition-colors {{ request()->routeIs('profile.contact') ? 'bg-[var(--accent)]/10 text-[var(--accent)] border-l-4 border-[var(--accent)]' : 'text-[var(--text-main)] hover:bg-gray-50' }}"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                                    </svg>
                                    Kapcsolat
                                </a>
                            </li>
                            <li>
                                <a
                                    href="{{ route('profile.addresses') }}"
                                    class="flex items-center gap-3 px-4 py-3 text-sm font-medium transition-colors {{ request()->routeIs('profile.addresses') ? 'bg-[var(--accent)]/10 text-[var(--accent)] border-l-4 border-[var(--accent)]' : 'text-[var(--text-main)] hover:bg-gray-50' }}"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                    </svg>
                                    Címeim
                                </a>
                            </li>
                            <li>
                                <a
                                    href="{{ route('profile.password') }}"
                                    class="flex items-center gap-3 px-4 py-3 text-sm font-medium transition-colors {{ request()->routeIs('profile.password') ? 'bg-[var(--accent)]/10 text-[var(--accent)] border-l-4 border-[var(--accent)]' : 'text-[var(--text-main)] hover:bg-gray-50' }}"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                    </svg>
                                    Jelszó
                                </a>
                            </li>
                            <li>
                                <a
                                    href="{{ route('profile.delete') }}"
                                    class="flex items-center gap-3 px-4 py-3 text-sm font-medium transition-colors {{ request()->routeIs('profile.delete') ? 'bg-red-50 text-red-600 border-l-4 border-red-600' : 'text-red-600 hover:bg-red-50' }}"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                    Fiók törlése
                                </a>
                            </li>
                        </ul>
                    </nav>
                </aside>

                {{-- Content --}}
                <div class="flex-1 min-w-0">
                    <div class="bg-white rounded-[var(--radius-lg)] shadow-[var(--shadow-sm)] p-6 sm:p-8">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.shop>
