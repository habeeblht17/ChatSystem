<div x-data="{ 
    showChat: false,
    scrollToBottom() {
        this.$nextTick(() => {
            const container = this.$refs.messagesContainer;
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        });
    }
}" 
x-init="
    $watch('showChat', value => { if(value) scrollToBottom() });
"
@resize.window="if (window.innerWidth >= 768) showChat = false"
class="flex h-[calc(95vh-4rem)] lg:h-[calc(95vh-0rem)] bg-white dark:bg-zinc-800">
    
    <!-- Chat List Sidebar -->
    <div x-show="!showChat || window.innerWidth >= 768" 
        x-cloak
        class="w-full md:w-[35%] lg:w-[30%] border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 flex flex-col">
        
        <!-- Header -->
        <div class="px-4 py-3 flex items-center justify-between border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <h1 class="text-zinc-500 dark:text-white/80 text-xl font-medium">Messages</h1>
            <div class="flex items-center gap-4">        
                <button type="button" class="ttext-zinc-500 dark:text-white/80 transition">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Search -->
        <div class="px-3 py-3 bg-zinc-50 dark:bg-zinc-900">
            <div class="bg-white dark:bg-zinc-800 rounded-full border border-zinc-200 dark:border-zinc-700 px-4 py-2 flex items-center gap-3">
                <svg class="w-5 h-5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input type="text" placeholder="Search conversations" class="bg-transparent text-zinc-500 dark:text-white/80 text-sm w-full outline-none placeholder-zinc-500">
            </div>
        </div>

        <!-- Tabs -->
        <div class="flex overflow-x-auto text-zinc-500 dark:text-white/80 hover:text-zinc-800 dark:hover:text-white text-xs scrollbar-hidden gap-2 px-4 py-3 border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <button type="button" class="font-medium px-2 py-1 cursor-pointer rounded-full border border-blue-600 bg-blue-600/10">All</button>
            <button type="button" class="px-2 py-1 cursor-pointer rounded-full border border-zinc-200 dark:border-zinc-700 hover:border-zinc-800 transition">Unread</button>
            <button type="button" class="px-2 py-1 cursor-pointer rounded-full border border-zinc-200 dark:border-zinc-700 hover:border-zinc-800 transition">Favourites</button>
            <button type="button" class="px-2 py-1 cursor-pointer rounded-full border border-zinc-200 dark:border-zinc-700 hover:border-zinc-800 transition">Groups</button>
        </div>

        <!-- Chat List -->
        <div class="flex-1 overflow-y-auto scrollbar-hidden">
            @forelse ($users as $user)
                @php
                    $userStatus = $this->getUserStatus($user->id);
                @endphp

                <div wire:click="selectUser({{ $user->id }})" 
                    @click="showChat = true; scrollToBottom()"
                    class="px-4 py-3 cursor-pointer border-b border-zinc-200  dark:border-zinc-700 
                    transition hover:bg-zinc-800/10 dark:hover:bg-white/10  
                    text-zinc-500 dark:text-white/80 hover:text-zinc-800 dark:hover:text-white 
                    {{ $selectedUser?->id === $user->id ? 'bg-zinc-200 dark:bg-zinc-800' : 'bg-zinc-50 dark:bg-zinc-900' }}">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-zinc-300 dark:bg-zinc-700 flex items-center justify-center hover:text-zinc-800 
                        dark:hover:text-white text-zinc-500 dark:text-white/80 text-sm font-medium shrink-0 overflow-hidden
                        {{ $selectedUser?->id === $user->id ? 'ring-2 ring-blue-600' : ' ' }}">
                            @if($user->image)
                                <img src="{{ asset('storage/'. $user->image) }}" alt="{{ $user->name }}" class="w-full h-full object-cover">
                            @else
                                {{ $user->initials() }}
                            @endif

                            {{-- <!-- Online Status Indicator -->
                            @if($userStatus['is_online'])
                                <span class="relative z-40 -top-2 w-3 h-2 bg-blue-600 border-2 border-zinc-900 rounded-full"></span>
                            @endif --}}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-1">
                                <h3 class="text-base font-medium truncate">{{ $user->name }}</h3>
                                <span class="text-xs">{{ now()->format('g:i a') }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                @php
                                    $lastMsg = $this->getLastMessage($user->id);
                                    $unreadCount = $user->unread_count ?? 0;
                                    
                                    if ($lastMsg) {
                                        if ($lastMsg->attachment_path && empty($lastMsg->message)) {
                                            $preview = $lastMsg->sender_id === auth()->id() ? 'You sent an attachment' : 'Sent an attachment';
                                        } elseif ($lastMsg->attachment_path && !empty($lastMsg->message)) {
                                            $preview = $lastMsg->sender_id === auth()->id() ? 'You: ' . $lastMsg->message : $lastMsg->message;
                                        } else {
                                            $preview = $lastMsg->sender_id === auth()->id() ? 'You: ' . $lastMsg->message : $lastMsg->message;
                                        }
                                    } else {
                                        $preview = 'No messages yet';
                                    }
                                @endphp
                                <p class="text-zinc-400 text-sm truncate pr-2">{{ $preview }}</p>
                                @if($unreadCount > 0)
                                    <span class="bg-blue-600 text-white text-xs rounded-full min-w-5 h-5 flex items-center justify-center font-medium shrink-0 px-1.5">{{ $unreadCount }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-4 py-8 text-center">
                    <p class="text-sm">No users available</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Chat Box -->
    <div x-show="showChat || window.innerWidth >= 768"
        x-cloak
        class="w-full md:w-[65%] lg:w-[70%] flex flex-col bg-zinc-100 dark:bg-zinc-900">
        
        @if($selectedUser)
            <!-- Chat Header -->
            <div class="px-4 py-3 flex items-center justify-between border-b border-zinc-200 bg-zinc-50 
                dark:border-zinc-700 dark:bg-zinc-900 text-zinc-500 dark:text-white/80">
                <div class="flex items-center gap-3">
                    <!-- Toggle Chatbox -->
                    <button @click="showChat = false" 
                        type="button"
                        class="md:hidden text-white mr-2 hover:bg-zinc-700 p-1 rounded transition">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                        </svg>

                    </button>

                    <div class="w-10 h-10 rounded-full flex items-center justify-center bg-zinc-300 dark:bg-zinc-700 text-zinc-500 
                    dark:text-white/80 font-medium shrink-0 overflow-hidden {{ $selectedUser ? 'ring-2 ring-blue-600' : ' ' }}">
                        @if($selectedUser->image)
                            <img src="{{ asset('storage/'. $selectedUser->image) }}" alt="{{ $selectedUser->name }}" class="w-full h-full object-cover">
                        @else
                            {{ $selectedUser->initials() }}
                        @endif
                    </div>
                    <div>
                        <h2 class="text-base font-medium">{{ $selectedUser->name }}</h2>
                        <p class="text-xs {{ $this->selectedUserStatus['class'] }}">
                            {{ $this->selectedUserStatus['label'] }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    
                    <button type="button" class="text-zinc-400 hover:text-white transition">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Messages Area -->
            <div id="chat-box" x-ref="messagesContainer" class="flex-1 overflow-y-auto scrollbar-hidden px-2 py-4 space-y-3 chat-bg relative">
                @php $lastDay = null; @endphp
                
                @forelse ($messages as $message)
                    @php
                        $attachmentUrl = null;
                        $isImage = false;
                        $day = $message->created_at ? \Carbon\Carbon::parse($message->created_at)->format('Y-m-d') : null;
                        $dayDate = $message->created_at ? \Carbon\Carbon::parse($message->created_at) : null;
                        
                        if ($message->attachment_path) {
                            try {
                                $attachmentUrl = Storage::disk(config('chat.attachment_disk'))->url($message->attachment_path);
                                $isImage = in_array($message->attachment_mime_type, ['image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/gif']);
                            } catch (\Exception $e) {
                                $attachmentUrl = null;
                            }
                        }
                    @endphp

                    @if($day && $lastDay !== $day)
                        @php
                            if ($dayDate && $dayDate->isToday()) {
                                $sep = 'Today';
                            } elseif ($dayDate && $dayDate->isYesterday()) {
                                $sep = 'Yesterday';
                            } else {
                                $sep = $dayDate ? $dayDate->format('F j, Y') : '';
                            }
                            $lastDay = $day;
                        @endphp

                        <!-- Date separator -->
                        <div class="flex justify-center my-2"><span class="text-xs text-zinc-600 bg-zinc-300 dark:text-zinc-400 dark:bg-zinc-800/90 px-3 py-1 rounded-full">{{ $sep }}</span></div>
                    @endif

                    @if ($message->sender_id === auth()->id())
                        <!-- Sent Message -->
                        <div class="flex justify-end" wire:key="message-{{ $message->id }}">
                            <div class="max-w-[65%]">
                                @if($message->attachment_path && $attachmentUrl)
                                    @if($isImage)
                                        <!-- Image Message -->
                                        <div class="relative group bg-blue-600 text-white px-2 py-2 rounded-lg shadow">
                                            <img src="{{ $attachmentUrl }}" 
                                                 alt="{{ $message->attachment_name }}"
                                                 class="rounded-t-lg cursor-pointer max-w-xs max-h-60 w-full object-cover"
                                                 wire:click="openFilePreview({{ $message->id }})">
                                            
                                            @if(!empty($message->message))
                                                <div class="px-2 py-1 text-sm text-white rounded-lg shadow break-words">
                                                    {{ $message->message }}
                                                </div>
                                            @endif
                                            
                                            <div class="flex items-center justify-end gap-1 px-2 pb-2 text-xs text-zinc-300">
                                                <span>{{ $message->created_at->format('g:i a') }}</span>
                                                @if($message->status->value === 'read')
                                                    <svg class="w-4 h-4 text-blue-900" viewBox="0 0 16 15" fill="currentColor">
                                                        <path d="M15.01 3.316l-.478-.372a.365.365 0 0 0-.51.063L8.666 9.88a.32.32 0 0 1-.484.032l-.358-.325a.32.32 0 0 0-.484.032l-.378.48a.418.418 0 0 0 .036.54l1.32 1.267a.32.32 0 0 0 .484-.034l6.272-8.048a.366.366 0 0 0-.064-.512zm-4.1 0l-.478-.372a.365.365 0 0 0-.51.063L4.566 9.88a.32.32 0 0 1-.484.032L1.892 7.77a.366.366 0 0 0-.516.005l-.423.433a.364.364 0 0 0 .006.514l3.255 3.185a.32.32 0 0 0 .484-.033l6.272-8.048a.365.365 0 0 0-.063-.51z"></path>
                                                    </svg>
                                                @elseif($message->status->value === 'delivered')
                                                    <svg class="w-4 h-4 text-zinc-500 dark:text-zinc-400" viewBox="0 0 16 15" fill="currentColor">
                                                        <path d="M15.01 3.316l-.478-.372a.365.365 0 0 0-.51.063L8.666 9.88a.32.32 0 0 1-.484.032l-.358-.325a.32.32 0 0 0-.484.032l-.378.48a.418.418 0 0 0 .036.54l1.32 1.267a.32.32 0 0 0 .484-.034l6.272-8.048a.366.366 0 0 0-.064-.512zm-4.1 0l-.478-.372a.365.365 0 0 0-.51.063L4.566 9.88a.32.32 0 0 1-.484.032L1.892 7.77a.366.366 0 0 0-.516.005l-.423.433a.364.364 0 0 0 .006.514l3.255 3.185a.32.32 0 0 0 .484-.033l6.272-8.048a.365.365 0 0 0-.063-.51z"></path>
                                                    </svg>
                                                @else
                                                    <svg class="w-4 h-4 text-zinc-500 dark:text-zinc-400" viewBox="0 0 12 11" fill="currentColor">
                                                        <path d="M11.796 5.516a.37.37 0 0 0-.037-.273.35.35 0 0 0-.23-.164.353.353 0 0 0-.522.177L6.47 10.72a.32.32 0 0 1-.484.033l-3.255-3.185a.364.364 0 0 0-.516-.005l-.423.433a.364.364 0 0 0 .006.514l4.573 4.474a.32.32 0 0 0 .484-.033l5.44-6.935z"></path>
                                                    </svg>
                                                @endif
                                            </div>
                                            
                                            <!-- Download Button on Hover -->
                                            <a href="{{ $attachmentUrl }}" 
                                               download="{{ $message->attachment_name }}" 
                                               class="absolute top-2 right-2 bg-black/60 hover:bg-black/80 p-2 rounded-full opacity-0 group-hover:opacity-100 transition-opacity">
                                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                </svg>
                                            </a>
                                        </div>
                                    @else
                                        <!-- File/PDF Message -->
                                        <div class="bg-blue-600 text-white rounded-lg px-2 py-2 shadow">
                                            <div class="flex items-center gap-3 cursor-pointer" wire:click="openFilePreview({{ $message->id }})">
                                                <div class="flex-shrink-0">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-red-600" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-pdf" viewBox="0 0 16 16">
                                                        <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2M9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5z"/>
                                                        <path d="M4.603 14.087a.8.8 0 0 1-.438-.42c-.195-.388-.13-.776.08-1.102.198-.307.526-.568.897-.787a7.7 7.7 0 0 1 1.482-.645 20 20 0 0 0 1.062-2.227 7.3 7.3 0 0 1-.43-1.295c-.086-.4-.119-.796-.046-1.136.075-.354.274-.672.65-.823.192-.077.4-.12.602-.077a.7.7 0 0 1 .477.365c.088.164.12.356.127.538.007.188-.012.396-.047.614-.084.51-.27 1.134-.52 1.794a11 11 0 0 0 .98 1.686 5.8 5.8 0 0 1 1.334.05c.364.066.734.195.96.465.12.144.193.32.2.518.007.192-.047.382-.138.563a1.04 1.04 0 0 1-.354.416.86.86 0 0 1-.51.138c-.331-.014-.654-.196-.933-.417a5.7 5.7 0 0 1-.911-.95 11.7 11.7 0 0 0-1.997.406 11.3 11.3 0 0 1-1.02 1.51c-.292.35-.609.656-.927.787a.8.8 0 0 1-.58.029m1.379-1.901q-.25.115-.459.238c-.328.194-.541.383-.647.547-.094.145-.096.25-.04.361q.016.032.026.044l.035-.012c.137-.056.355-.235.635-.572a8 8 0 0 0 .45-.606m1.64-1.33a13 13 0 0 1 1.01-.193 12 12 0 0 1-.51-.858 21 21 0 0 1-.5 1.05zm2.446.45q.226.245.435.41c.24.19.407.253.498.256a.1.1 0 0 0 .07-.015.3.3 0 0 0 .094-.125.44.44 0 0 0 .059-.2.1.1 0 0 0-.026-.063c-.052-.062-.2-.152-.518-.209a4 4 0 0 0-.612-.053zM8.078 7.8a7 7 0 0 0 .2-.828q.046-.282.038-.465a.6.6 0 0 0-.032-.198.5.5 0 0 0-.145.04c-.087.035-.158.106-.196.283-.04.192-.03.469.046.822q.036.167.09.346z"/>
                                                    </svg>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="text-sm text-zinc-100 font-medium truncate">{{ $message->attachment_name }}</div>
                                                    <div class="text-xs text-zinc-300">{{ number_format($message->attachment_size / 1024, 1) }} KB</div>
                                                </div>
                                                <a href="{{ $attachmentUrl }}" 
                                                   download="{{ $message->attachment_name }}" 
                                                   class="flex-shrink-0"
                                                   onclick="event.stopPropagation()">
                                                    <svg class="w-4 h-4  text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                    </svg>
                                                </a>
                                            </div>
                                            
                                            @if(!empty($message->message))
                                                <div class="mt-2 text-sm bg-blue-600 text-white rounded-lg  break-words">
                                                    {{ $message->message }}
                                                </div>
                                            @endif
                                            
                                            <div class="flex items-center justify-end gap-1 mt-1 text-xs text-zinc-300">
                                                <span>{{ $message->created_at->format('g:i a') }}</span>
                                                @if($message->status->value === 'read')
                                                    <svg class="w-4 h-4 text-blue-500" viewBox="0 0 16 15" fill="currentColor">
                                                        <path d="M15.01 3.316l-.478-.372a.365.365 0 0 0-.51.063L8.666 9.88a.32.32 0 0 1-.484.032l-.358-.325a.32.32 0 0 0-.484.032l-.378.48a.418.418 0 0 0 .036.54l1.32 1.267a.32.32 0 0 0 .484-.034l6.272-8.048a.366.366 0 0 0-.064-.512zm-4.1 0l-.478-.372a.365.365 0 0 0-.51.063L4.566 9.88a.32.32 0 0 1-.484.032L1.892 7.77a.366.366 0 0 0-.516.005l-.423.433a.364.364 0 0 0 .006.514l3.255 3.185a.32.32 0 0 0 .484-.033l6.272-8.048a.365.365 0 0 0-.063-.51z"></path>
                                                    </svg>
                                                @elseif($message->status->value === 'delivered')
                                                    <svg class="w-4 h-4 text-zinc-500 dark:text-zinc-400" viewBox="0 0 16 15" fill="currentColor">
                                                        <path d="M15.01 3.316l-.478-.372a.365.365 0 0 0-.51.063L8.666 9.88a.32.32 0 0 1-.484.032l-.358-.325a.32.32 0 0 0-.484.032l-.378.48a.418.418 0 0 0 .036.54l1.32 1.267a.32.32 0 0 0 .484-.034l6.272-8.048a.366.366 0 0 0-.064-.512zm-4.1 0l-.478-.372a.365.365 0 0 0-.51.063L4.566 9.88a.32.32 0 0 1-.484.032L1.892 7.77a.366.366 0 0 0-.516.005l-.423.433a.364.364 0 0 0 .006.514l3.255 3.185a.32.32 0 0 0 .484-.033l6.272-8.048a.365.365 0 0 0-.063-.51z"></path>
                                                    </svg>
                                                @else
                                                    <svg class="w-4 h-4 text-zinc-500 dark:text-zinc-400" viewBox="0 0 12 11" fill="currentColor">
                                                        <path d="M11.796 5.516a.37.37 0 0 0-.037-.273.35.35 0 0 0-.23-.164.353.353 0 0 0-.522.177L6.47 10.72a.32.32 0 0 1-.484.033l-3.255-3.185a.364.364 0 0 0-.516-.005l-.423.433a.364.364 0 0 0 .006.514l4.573 4.474a.32.32 0 0 0 .484-.033l5.44-6.935z"></path>
                                                    </svg>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                @else
                                    <!-- Text Only Message -->
                                    <div class="bg-blue-600 text-white rounded-lg px-2 py-2 shadow inline-block">
                                        <div class="text-sm text-zinc-100 break-words whitespace-pre-wrap">{{ $message->message }}</div>
                                        <div class="flex items-center justify-end gap-1 text-xs text-zinc-300 mt-1">
                                            <span>{{ $message->created_at->format('g:i a') }}</span>
                                            @if($message->status->value === 'read')
                                                <svg class="w-4 h-4 text-blue-900" viewBox="0 0 16 15" fill="currentColor">
                                                    <path d="M15.01 3.316l-.478-.372a.365.365 0 0 0-.51.063L8.666 9.88a.32.32 0 0 1-.484.032l-.358-.325a.32.32 0 0 0-.484.032l-.378.48a.418.418 0 0 0 .036.54l1.32 1.267a.32.32 0 0 0 .484-.034l6.272-8.048a.366.366 0 0 0-.064-.512zm-4.1 0l-.478-.372a.365.365 0 0 0-.51.063L4.566 9.88a.32.32 0 0 1-.484.032L1.892 7.77a.366.366 0 0 0-.516.005l-.423.433a.364.364 0 0 0 .006.514l3.255 3.185a.32.32 0 0 0 .484-.033l6.272-8.048a.365.365 0 0 0-.063-.51z"></path>
                                                </svg>
                                            @elseif($message->status->value === 'delivered')
                                                <svg class="w-4 h-4 text-zinc-500 dark:text-zinc-400" fill="currentColor" viewBox="0 0 16 15">
                                                    <path d="M15.01 3.316l-.478-.372a.365.365 0 0 0-.51.063L8.666 9.88a.32.32 0 0 1-.484.032l-.358-.325a.32.32 0 0 0-.484.032l-.378.48a.418.418 0 0 0 .036.54l1.32 1.267a.32.32 0 0 0 .484-.034l6.272-8.048a.366.366 0 0 0-.064-.512zm-4.1 0l-.478-.372a.365.365 0 0 0-.51.063L4.566 9.88a.32.32 0 0 1-.484.032L1.892 7.77a.366.366 0 0 0-.516.005l-.423.433a.364.364 0 0 0 .006.514l3.255 3.185a.32.32 0 0 0 .484-.033l6.272-8.048a.365.365 0 0 0-.063-.51z"></path>
                                                </svg>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-zinc-500 dark:text-zinc-400" width="16" height="16" fill="currentColor" class="bi bi-check2" viewBox="0 0 16 16">
                                                    <path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0"/>
                                                </svg>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @else
                        <!-- Received Message -->
                        <div class="flex justify-start" wire:key="message-{{ $message->id }}">
                            <div class="max-w-[65%]">
                                @if($message->attachment_path && $attachmentUrl)
                                    @if($isImage)
                                        <!-- Image Message -->
                                        <div class="relative group bg-zinc-600 text-white rounded-lg px-2 py-2 overflow-hidden shadow">
                                            <img src="{{ $attachmentUrl }}" 
                                                 alt="{{ $message->attachment_name }}"
                                                 class="rounded-t-lg cursor-pointer max-w-xs max-h-60 w-full object-cover"
                                                 wire:click="openFilePreview({{ $message->id }})">
                                            
                                            @if(!empty($message->message))
                                                <div class="px-2 py-1 text-white break-words">
                                                    {{ $message->message }}
                                                </div>
                                            @endif
                                            
                                            <div class="px-2 pb-2 text-xs text-zinc-300 text-right">
                                                {{ $message->created_at->format('g:i a') }}
                                            </div>
                                            
                                            <!-- Download Button on Hover -->
                                            <a href="{{ $attachmentUrl }}" 
                                               download="{{ $message->attachment_name }}"
                                               class="absolute top-2 right-2 bg-black/60 hover:bg-black/80 p-2 rounded-full opacity-0 group-hover:opacity-100 transition-opacity">
                                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                </svg>
                                            </a>
                                        </div>
                                    @else
                                        <!-- File/PDF Message -->
                                        <div class="bg-zinc-600 text-white rounded-lg px-2 py-2 shadow">
                                            <div class="flex items-center gap-3 cursor-pointer" wire:click="openFilePreview({{ $message->id }})">
                                                <div class="flex-shrink-0">                                                    
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-red-600" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-pdf" viewBox="0 0 16 16">
                                                        <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2M9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5z"/>
                                                        <path d="M4.603 14.087a.8.8 0 0 1-.438-.42c-.195-.388-.13-.776.08-1.102.198-.307.526-.568.897-.787a7.7 7.7 0 0 1 1.482-.645 20 20 0 0 0 1.062-2.227 7.3 7.3 0 0 1-.43-1.295c-.086-.4-.119-.796-.046-1.136.075-.354.274-.672.65-.823.192-.077.4-.12.602-.077a.7.7 0 0 1 .477.365c.088.164.12.356.127.538.007.188-.012.396-.047.614-.084.51-.27 1.134-.52 1.794a11 11 0 0 0 .98 1.686 5.8 5.8 0 0 1 1.334.05c.364.066.734.195.96.465.12.144.193.32.2.518.007.192-.047.382-.138.563a1.04 1.04 0 0 1-.354.416.86.86 0 0 1-.51.138c-.331-.014-.654-.196-.933-.417a5.7 5.7 0 0 1-.911-.95 11.7 11.7 0 0 0-1.997.406 11.3 11.3 0 0 1-1.02 1.51c-.292.35-.609.656-.927.787a.8.8 0 0 1-.58.029m1.379-1.901q-.25.115-.459.238c-.328.194-.541.383-.647.547-.094.145-.096.25-.04.361q.016.032.026.044l.035-.012c.137-.056.355-.235.635-.572a8 8 0 0 0 .45-.606m1.64-1.33a13 13 0 0 1 1.01-.193 12 12 0 0 1-.51-.858 21 21 0 0 1-.5 1.05zm2.446.45q.226.245.435.41c.24.19.407.253.498.256a.1.1 0 0 0 .07-.015.3.3 0 0 0 .094-.125.44.44 0 0 0 .059-.2.1.1 0 0 0-.026-.063c-.052-.062-.2-.152-.518-.209a4 4 0 0 0-.612-.053zM8.078 7.8a7 7 0 0 0 .2-.828q.046-.282.038-.465a.6.6 0 0 0-.032-.198.5.5 0 0 0-.145.04c-.087.035-.158.106-.196.283-.04.192-.03.469.046.822q.036.167.09.346z"/>
                                                    </svg>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="text-sm text-zinc-100 font-medium truncate">{{ $message->attachment_name }}</div>
                                                    <div class="text-xs text-zinc-300">{{ number_format($message->attachment_size / 1024, 1) }} KB</div>
                                                </div>
                                                <a href="{{ $attachmentUrl }}" 
                                                   download="{{ $message->attachment_name }}" 
                                                   class="flex-shrink-0"
                                                   onclick="event.stopPropagation()">
                                                    <svg class="w-4 h-4 text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                    </svg>
                                                </a>
                                            </div>
                                            
                                            @if(!empty($message->message))
                                                <div class="mt-2 text-sm text-white break-words">
                                                    {{ $message->message }}
                                                </div>
                                            @endif
                                            
                                            <div class="text-xs text-zinc-300 text-right mt-1">
                                                {{ $message->created_at->format('g:i a') }}
                                            </div>
                                        </div>
                                    @endif
                                @else
                                    <!-- Text Only Message -->
                                    <div class="bg-zinc-600 text-white rounded-lg px-2 py-2 shadow inline-block">
                                        <div class="text-sm text-zinc-100 break-words whitespace-pre-wrap">{{ $message->message }}</div>
                                        <div class="text-xs text-zinc-300 text-right mt-1">
                                            {{ $message->created_at->format('g:i a') }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                @empty                        
                    <div class="flex items-center justify-center h-full">
                        <p class="text-zinc-500 dark:text-white/80 text-sm">No messages yet. Start a conversation!</p>
                    </div>
                @endforelse

                <!-- Scroll to Bottom Button -->
                {{-- <div class="fixed bottom-2 right-8 md:absolute md:bottom-4 md:right-4 z-10">
                    <button @click="scrollToBottom()" 
                            type="button"
                            class="bg-zinc-800 hover:bg-zinc-700 text-white p-3 rounded-full shadow-lg transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                </div> --}}
            </div>

            <!-- Typing Indicator -->
            <div id="typing-indicator" class="px-4 pb-1 text-xs text-zinc-500 dark:text-white/80 italic min-h-5"></div>

            <!-- Message Input -->
            <div class="px-4 py-3 border-t border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
                <!-- File Preview in Input Area -->
                @if($attachment)
                    <div class="mb-3 bg-zinc-700 rounded-lg p-3">
                        @if(in_array($attachment->getMimeType(), ['image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/gif']))
                            <!-- Image Preview -->
                            <div class="flex items-start gap-3">
                                <img src="{{ $attachment->temporaryUrl() }}"  alt="Preview" class="w-24 h-24 object-cover rounded-lg flex shrink-0">
                                <div class="flex-1 min-w-0">
                                    <p class="text-white text-sm font-medium truncate">{{ $attachment->getClientOriginalName() }}</p>
                                    <p class="text-zinc-400 text-xs">{{ number_format($attachment->getSize() / 1024, 1) }} KB</p>
                                </div>
                                <button type="button" 
                                        wire:click="removeAttachment"
                                        class="text-zinc-400 hover:text-red-400 transition flex shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        @else
                            <!-- File Preview -->
                            <div class="flex items-center gap-3">
                                <svg class="w-8 h-8 text-blue-400 flex shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                                <div class="flex-1 min-w-0">
                                    <p class="text-white text-sm font-medium truncate">{{ $attachment->getClientOriginalName() }}</p>
                                    <p class="text-zinc-400 text-xs">{{ number_format($attachment->getSize() / 1024, 1) }} KB</p>
                                </div>
                                <button type="button" 
                                    wire:click="removeAttachment"
                                    class="text-zinc-400 hover:text-red-400 transition flex shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- form -->
                <form wire:submit="submitMessage" class="flex items-center gap-3">
                    <!-- Attachment icon & file Input -->
                    <label class="text-zinc-500 dark:text-white/80 transition cursor-pointer">
                        <input type="file" wire:model="attachment" class="hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="m16 6-8.414 8.586a2 2 0 0 0 2.829 2.829l8.414-8.586a4 4 0 1 0-5.657-5.657l-8.379 8.551a6 6 0 1 0 8.485 8.485l8.379-8.551"/>
                        </svg>
                    </label>
                    
                    <!-- Text Input -->
                    <div class="flex-1 bg-white dark:bg-zinc-800 rounded-full px-4 py-2.5 flex items-center">
                        <input wire:model.live="newMessage" 
                        type="text" 
                        placeholder="Type a message" 
                        class="bg-transparent text-zinc-500 dark:text-white/80 text-sm w-full outline-none placeholder-zinc-400"
                        @keydown.enter.prevent="$wire.submitMessage().then(() => scrollToBottom())">
                    </div>
                    
                    <!-- Send Message Icon -->
                    <button type="submit" 
                        @click="scrollToBottom()"
                        wire:loading.attr="disabled"
                        class="text-blue-600 hover:text-blue-700 transition disabled:opacity-50">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                        </svg>
                    </button>
                </form>

                <div wire:loading wire:target="attachment" class="mt-2 text-zinc-500 dark:text-white/80 text-xs">
                    Uploading file...
                </div>

                @error('attachment')
                    <div class="mt-2 text-red-400 text-xs">{{ $message }}</div>
                @enderror
                @error('newMessage')
                    <div class="mt-2 text-red-400 text-xs">{{ $message }}</div>
                @enderror
            </div>
        @else
            <div class="flex items-center justify-center h-full">
                <p class="text-zinc-500 dark:text-white/80">Select a user to start chatting</p>
            </div>
        @endif
    </div>

    <!-- File Preview Modal -->
    @if($showFilePreview && $previewFile)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-100 dark:bg-zinc-800 bg-opacity-75 p-4"
            wire:click="closeFilePreview">
            <div class="bg-zinc-50  dark:bg-zinc-900 rounded-lg max-w-4xl max-h-[90vh] w-full overflow-hidden"
                @click.stop>
                <!-- Modal Header -->
                <div class="flex items-center justify-between p-4 border-b border-zinc-200 dark:border-zinc-700">
                    <h3 class="text-zinc-500 dark:text-white/80 font-medium">{{ $previewFile->attachment_name }}</h3>
                    <div class="flex items-center gap-3">
                        <a href="{{ Storage::disk(config('chat.attachment_disk'))->url($previewFile->attachment_path) }}" 
                           download="{{ $previewFile->attachment_name }}"
                           class="text-blue-400 hover:text-blue-300 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </a>
                        <button wire:click="closeFilePreview" 
                            class="text-zinc-500 dark:text-white/80 transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Content -->
                <div class="p-4 overflow-auto max-h-[calc(90vh-80px)]">
                    @if(str_starts_with($previewFile->attachment_mime_type, 'image/'))
                        <img src="{{ Storage::disk(config('chat.attachment_disk'))->url($previewFile->attachment_path) }}" 
                             alt="{{ $previewFile->attachment_name }}"
                             class="max-w-full h-auto mx-auto rounded-lg">
                    @elseif($previewFile->attachment_mime_type === 'application/pdf')
                        <iframe src="{{ Storage::disk(config('chat.attachment_disk'))->url($previewFile->attachment_path) }}" 
                                class="w-full h-[70vh] rounded-lg">
                        </iframe>
                    @else
                        <div class="text-center py-12">
                            <svg class="w-16 h-16 text-zinc-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                            <p class="text-zinc-400 mb-4">Preview not available for this file type</p>
                            <a href="{{ Storage::disk(config('chat.attachment_disk'))->url($previewFile->attachment_path) }}" 
                               download="{{ $previewFile->attachment_name }}"
                               class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Download File
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

<script>
    // Global scroll function accessible from PHP side
    function scrollToLatestMessage() {
        const chatBox = document.getElementById("chat-box");
        if (!chatBox) return;
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    document.addEventListener('livewire:initialized', () => {
        // Listen for userTyping event from Livewire
        Livewire.on('userTyping', (event) => {
            console.log('User typing event:', event);
            
            window.Echo.private(`chat.${event.selectedUserID}`).whisper('typing', {
                    userID: event.userID,
                    userName: event.userName,
            });
        });

        // Listen for typing whisper on current user's private channel
        window.Echo.private(`chat.{{ $loginID }}`)
            .listenForWhisper('typing', (event) => {
                console.log('Received typing event:', event);
                
                const typingIndicator = document.getElementById("typing-indicator");
                
                if (typingIndicator) {
                    typingIndicator.innerText = `${event.userName} is typing...`;
                    typingIndicator.classList.add('text-blue-600');

                    // Clear the typing indicator after 2 seconds
                    setTimeout(() => {
                        typingIndicator.innerText = '';
                        typingIndicator.classList.remove('text-blue-600');
                    }, 2000);
                }
            });
    });

    // Listen for new message notification (triggered by Livewire component)
    document.addEventListener('livewire:initialized', () => {
        const notificationSound = new Audio('/sounds/message.mp3');
        notificationSound.preload = 'auto';

        // Listen for new messages via Livewire event
        Livewire.on('newMessageReceived', () => {
            console.log('New message received - playing sound');
            
            // Play notification sound
            notificationSound.currentTime = 0;
            notificationSound.play().catch(error => {
                console.log('Audio playback failed:', error);
            });
        });
    });

    // Listen for user logout events
    document.addEventListener('livewire:initialized', () => {
        window.Echo.channel('user-status')
            .listen('.UserLoggedOut', (event) => {
                console.log('User logged out:', event);
                // Livewire will handle the update via the listener
            });
    });
</script>