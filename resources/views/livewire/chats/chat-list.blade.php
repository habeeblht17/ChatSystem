<div>
    <div class="flex h-[calc(95vh-4rem)] lg:h-[calc(95vh-0rem)]">
        {{-- Desktop aside: visible on large screens --}}
        <aside class="hidden lg:flex lg:w-80 xl:w-96 flex-col text-zinc-800 border-e border-zinc-200 bg-zinc-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
            {{-- Header --}}
            <div class="flex items-center gap-3 px-4 py-3 border-b border-zinc-300 dark:border-zinc-700">
                <div class="text-2xl font-semibold">User Chat Page</div>
                <div class="ml-auto flex items-center gap-2">                
                    <button aria-label="Menu" class="p-2 rounded-full hover:bg-zinc-800">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="5" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="12" cy="19" r="1.5"/></svg>
                    </button>
                </div>
            </div>

            {{-- Search --}}
            <div class="px-4 py-3 border-b border-zinc-300 dark:border-zinc-700">
                <div class="relative">
                    <input type="search" placeholder="Search or start a new chat"
                        class="w-full rounded-full bg-zinc-200 dark:bg-zinc-800 placeholder:text-zinc-400 dark:placeholder:text-zinc-300 text-sm py-2 px-4 pl-10 focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    <div class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 21l-4.35-4.35"/></svg>
                    </div>
                </div>

                {{-- Quick filters --}}
                <div class="mt-3 flex gap-2 overflow-x-auto">
                    <button class="px-3 py-1 rounded-full border border-zinc-300 dark:border-zinc-700 cursor-pointer text-xs">All</button>
                    <button class="px-3 py-1 rounded-full border border-zinc-300 dark:border-zinc-700 cursor-pointer text-xs">Unread</button>
                    <button class="px-3 py-1 rounded-full border border-zinc-300 dark:border-zinc-700 cursor-pointer text-xs">Favorites</button>
                    <button class="px-3 py-1 rounded-full border border-zinc-300 dark:border-zinc-700 cursor-pointer text-xs">Groups</button>
                </div>
            </div>

            {{-- Conversation list (scrollable) --}}
            <div class="flex-1 overflow-auto">
                <nav class="divide-y divide-zinc-200 dark:divide-zinc-800 p-0 space-y-1">
                    {{-- Blade: iterate conversations here. Keep markup static for performance (no DB calls in view). --}}
                    @foreach ($users as $user)
                        @php $active = $selectedUserId && (int)$selectedUserId === (int)$user->id; @endphp
                        
                        <a href="#"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-zinc-200 dark:hover:bg-zinc-800 transition-colors
                            {{ $active  ? 'bg-zinc-200 dark:bg-zinc-800' : ''}}">
                            <div class="relative">
                                <div class="h-10 w-10 rounded-full bg-zinc-400 dark:bg-zinc-700 flex items-center justify-center text-zinc-200 font-semibold
                                {{ $active  ? 'bg-blue-600 ' : ''}}">
                                    {{ $user ? $user->initials() : '' }}
                                </div>                            
                                {{-- <span class="absolute -top-1 -right-1 inline-flex items-center justify-center h-5 w-5 rounded-full bg-blue-600 text-xs text-zinc-100 font-medium">4</span> --}}
                            
                            </div>

                            <div wire:click="selectUser({{ $user->id }})" class="flex-1 min-w-0">
                                <div class="flex items-center">
                                    <p class="truncate font-medium text-zinc-400 hover:text-zinc-500 dark:text-zinc-200 hover:dark:text-zinc-100">{{ $user->name }}</p>
                                    <span class="ml-auto text-xs text-zinc-400">                                        
                                        @php $meta = $this->userMeta[$user->id] ?? null; @endphp
                                        @if (! empty($meta['last_message_at']))
                                            {{ \Carbon\Carbon::parse($meta['last_message_at'])->format('h:i A') }}
                                        @endif
                                    </span>
                                </div>
                                <div class="flex items-center">
                                    <p class="truncate text-xs text-zinc-400 ">
                                        @if (! empty($meta['last_message']))
                                            {{ \Illuminate\Support\Str::limit($meta['last_message'], 40) }}
                                        @endif
                                    </p>

                                    {{-- unread message count --}}
                                    @if (!empty($user->unread_count) && (int)$user->unread_count > 0)
                                        <span class="ml-auto inline-flex items-center justify-center h-5 w-5 rounded-full bg-blue-600 text-xs text-zinc-100 font-medium">
                                            {{ (int) $user->unread_count }}
                                        </span>
                                    @endif
                                </div>                                
                            </div>
                        </a>
                    @endforeach
                </nav>
            </div>
        </aside>

        {{-- Mobile off-canvas panel (small screens). Visibility controlled by parent's Alpine state `showConversations`. --}}
        {{-- Important: this element is LG-hidden and uses Alpine directives; it reuses the same content structure for consistency. --}}
        <div
            x-show="showConversations"
            x-cloak
            x-trap.noscroll="showConversations"
            @keydown.escape.window="showConversations = false"
            class="lg:hidden"
            style="display: none;">

            <!-- overlay -->
            <div
                class="fixed inset-0 z-40 bg-black/40"
                @click="showConversations = false"
                x-show="showConversations"
                x-transition.opacity
                aria-hidden="true"
            ></div>

            <!-- panel -->
            <div
                class="fixed inset-y-0 left-0 z-50 w-80 max-w-full"
                x-show="showConversations"
                x-transition:enter="transform transition ease-in-out duration-200"
                x-transition:enter-start="-translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in-out duration-200"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="-translate-x-full">

                <div class="h-full flex flex-col text-zinc-800 border-e border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 shadow-xl">
                    {{-- Mobile header --}}
                    <div class="flex items-center gap-3 px-4 py-3 border-b border-zinc-300 dark:border-zinc-800">
                        <button class="p-2 rounded-md hover:text-zinc-600 hover:bg-zinc-300" @click="showConversations = false" aria-label="Close">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M6 6L18 18M6 18L18 6"/></svg>
                        </button>

                        <div class="text-lg font-semibold">User Chat Page</div>
                    </div>

                    {{-- Search --}}
                    <div class="px-4 py-3 border-b border-zinc-800">
                        <div class="relative">
                            <input type="search" placeholder="Search or start a new chat"
                                class="w-full rounded-full bg-zinc-200 dark:bg-zinc-800 placeholder:text-zinc-400 dark:placeholder:text-zinc-300 text-sm py-2 px-4 pl-10 focus:outline-none focus:ring-1 focus:ring-blue-500" />
                            <div class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 21l-4.35-4.35"/></svg>
                            </div>
                        </div>

                        {{-- Quick filters --}}
                        <div class="mt-3 flex gap-2 overflow-x-auto">
                            <button class="px-3 py-1 rounded-full border border-zinc-300 dark:border-zinc-700 cursor-pointer text-xs">All</button>
                            <button class="px-3 py-1 rounded-full border border-zinc-300 dark:border-zinc-700 cursor-pointer text-xs">Unread</button>
                            <button class="px-3 py-1 rounded-full border border-zinc-300 dark:border-zinc-700 cursor-pointer text-xs">Favorites</button>
                            <button class="px-3 py-1 rounded-full border border-zinc-300 dark:border-zinc-700 cursor-pointer text-xs">Groups</button>
                        </div>
                    </div>

                    {{-- Conversation list (scrollable) --}}
                    <div class="flex-1 overflow-auto">
                        <nav class="divide-y divide-zinc-200 dark:divide-zinc-800 p-0 space-y-1">
                            @foreach($users as $user)
                                @php $active = $selectedUserId && (int)$selectedUserId === (int)$user->id; @endphp

                                <a href="#"
                                    class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-zinc-200 dark:hover:bg-zinc-800 transition-colors
                                        {{$active ? 'bg-zinc-200 dark:bg-zinc-800' : ''}}">
                                    <div class="relative">
                                        <div class="h-10 w-10 rounded-full bg-zinc-400 dark:bg-zinc-700 flex items-center justify-center text-zinc-200 font-semibold
                                        {{ $active  ? 'bg-blue-600 ' : ''}}">
                                            {{ $user ? $user->initials() : '' }}
                                        </div>                                        
                                        {{-- <span class="absolute -top-1 -right-1 inline-flex items-center justify-center h-5 w-5 rounded-full bg-blue-600 text-xs text-zinc-100 font-medium">4</span>                                         --}}
                                    </div>

                                    <div wire:click="selectUser({{ $user->id }})" @click="showConversations = false"  class="flex-1 min-w-0">
                                        <div class="flex items-center">
                                            <p class="truncate font-medium text-zinc-400 hover:text-zinc-500 dark:text-zinc-200 hover:dark:text-zinc-100">{{ $user->name }}</p>
                                            <span class="ml-auto text-xs text-zinc-400">
                                                @php $meta = $this->userMeta[$user->id] ?? null; @endphp
                                                @if (! empty($meta['last_message_at']))
                                                    {{ \Carbon\Carbon::parse($meta['last_message_at'])->format('h:i A') }}
                                                @endif
                                            </span>
                                        </div>
                                        <div class="flex items-center">
                                            <p class="truncate text-xs text-zinc-400 ">
                                                @if (! empty($meta['last_message']))
                                                    {{ \Illuminate\Support\Str::limit($meta['last_message'], 40) }}
                                                @endif
                                            </p>
                                            
                                            {{-- unread message count --}}
                                            @if (!empty($user->unread_count) && (int)$user->unread_count > 0)
                                                <span class="ml-auto inline-flex items-center justify-center h-5 w-5 rounded-full bg-blue-600 text-xs text-zinc-100 font-medium">
                                                    {{ (int) $user->unread_count }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
