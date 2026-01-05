<?php

namespace App\Livewire\Chats;

use Carbon\Carbon;
use App\Models\User;
use Livewire\Component;
use App\Enums\MessageType;
use App\Events\MessageSent;
use App\Models\ChatMessage;
use App\Services\FileService;
use Livewire\WithFileUploads;
use App\Enums\ChatMessageStatus;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Events\MessageReadStatusUpdated;

class Chat extends Component
{
    use WithFileUploads;

    public $users;
    public $selectedUser;
    public $newMessage;
    public $messages;
    public $loginID;
    public $attachment;
    public $showFilePreview = false;
    public $previewFile = null;

    protected $listeners = [
        'echo-private:chat.{loginID},MessageSent' => 'newChatMessageNotification',
        'echo-private:chat.{loginID},MessageReadStatusUpdated' => 'handleMessageReadStatus',
        'echo:user-status,UserLoggedOut' => 'handleUserLogout',
    ];

    protected function rules()
    {
        $fileService = app(FileService::class);
        
        return [
            'newMessage' => 'nullable|string|max:5000',
            'attachment' => [
                'nullable',
                'file',
                'max:' . $fileService->getMaxSizeKb(),
                'mimes:' . implode(',', array_map(
                    fn($mime) => str_replace(['image/', 'application/'], '', $mime),
                    $fileService->getAllowedMimes()
                )),
            ],
        ];
    }

    protected function messages()
    {
        return [
            'newMessage.max' => 'Message cannot exceed 5000 characters.',
            'attachment.max' => 'File size cannot exceed ' . app(FileService::class)->getMaxSizeKb() . ' KB.',
            'attachment.mimes' => 'Invalid file type. Allowed types: PNG, JPEG, WEBP, GIF, PDF.',
        ];
    }

    public function mount()
    {
        $this->users = $this->getUsersWithUnreadCounts();
        $this->selectedUser = null; // Don't auto-select
        $this->loginID = Auth::id();
    }

    public function selectUser($id)
    {
        $this->selectedUser = User::find($id);

        if ($this->selectedUser) {
            $this->loadMessages();
            $this->markMessagesAsDelivered();
            $this->markMessagesAsRead();
            
            // Scroll after DOM updates
            $this->js('setTimeout(() => scrollToLatestMessage(), 100)');
        }
    }

    public function updatedNewMessage($value)
    {
        if ($this->selectedUser) {
            $this->dispatch("userTyping", userID: $this->loginID, userName: Auth::user()->name, selectedUserID: $this->selectedUser->id);
        }
    }

    public function updatedAttachment()
    {
        $this->validateOnly('attachment');
    }

    public function removeAttachment()
    {
        $this->reset('attachment');
    }

    public function openFilePreview($messageId)
    {
        $message = ChatMessage::find($messageId);
        
        if ($message && $message->attachment_path) {
            $this->previewFile = $message;
            $this->showFilePreview = true;
            
            // Mark as read when opening file
            if ($message->receiver_id === Auth::id() && $message->status !== ChatMessageStatus::READ->value) {
                $this->markMessageAsRead($messageId);
            }
        }
    }

    public function closeFilePreview()
    {
        $this->showFilePreview = false;
        $this->previewFile = null;
    }

    public function markMessageAsRead($messageId)
    {
        $message = ChatMessage::find($messageId);
        
        if ($message && $message->receiver_id === Auth::id() && $message->status !== ChatMessageStatus::READ->value) {
            $message->update([
                'status' => ChatMessageStatus::READ->value,
                'read_at' => now(),
            ]);

            $this->loadMessages();

            // Refresh chat list to show latest message
            $this->users = $this->getUsersWithUnreadCounts();
            $this->dispatch('messageStatusUpdated', messageId: $messageId, status: 'read');
        }
    }

    /**
     * mark messages as delivered
    */
    protected function markMessagesAsDelivered()
    {
        if (!$this->selectedUser) return;

        $updated =ChatMessage::where('receiver_id', Auth::id())
            ->where('sender_id', $this->selectedUser->id)
            ->where('status', ChatMessageStatus::SENT->value)
            ->update([
                'status' => ChatMessageStatus::DELIVERED->value,
                'delivered_at' => now(),
            ]);

        $this->loadMessages();

        if ($updated > 0) {
            $this->loadMessages();

            // Refresh chat list to show latest message
            $this->users = $this->getUsersWithUnreadCounts();
        }
    }

    /**
     * mark all messages as read
    */
    protected function markMessagesAsRead()
    {
        if (!$this->selectedUser) return;

        // Get IDs of messages that will be marked as read
        $messageIds = ChatMessage::where('receiver_id', Auth::id())
        ->where('sender_id', $this->selectedUser->id)
        ->whereIn('status', [ChatMessageStatus::SENT->value, ChatMessageStatus::DELIVERED->value])
        ->pluck('id')
        ->toArray();

        if (count($messageIds) > 0) {
            // Update messages to read status
            ChatMessage::whereIn('id', $messageIds)->update([
                'status' => ChatMessageStatus::READ->value,
                'read_at' => now(),
            ]);

            $this->loadMessages();
        
            // Refresh chat list to show latest message
            $this->users = $this->getUsersWithUnreadCounts();

            // Broadcast to sender that messages have been read
            broadcast(new MessageReadStatusUpdated(
                $this->selectedUser->id,
                Auth::id(),
                $messageIds
            ))->toOthers();
        }
    }

    public function newChatMessageNotification($data)
    {
        $messageObj = ChatMessage::find($data['id']);
    
        if (!$messageObj) {
            $this->users = $this->getUsersWithUnreadCounts();
            return;
        }


        // Check if this message is from the currently selected user
        if ($data['sender_id'] == $this->selectedUser->id) {
               
            // Mark as READ immediately since user has this chat open                            
            $messageObj->update([
                'status' => ChatMessageStatus::READ->value,
                'delivered_at' => now(),
                'read_at' => now(),
            ]);

            $this->messages->push($messageObj);

            $this->dispatch('newMessageReceived');
            
            // Scroll after DOM updates
            $this->js('setTimeout(() => scrollToLatestMessage(), 100)');


            // Broadcast to sender that message was read immediately
            broadcast(new MessageReadStatusUpdated(
                $this->selectedUser->id,
                Auth::id(),
                [$messageObj->id]
            ))->toOthers();
            
        } else {
            // User has different chat open or none - mark as DELIVERED only
            if ($messageObj->status === ChatMessageStatus::SENT->value) {
                $messageObj->update([
                    'status' => ChatMessageStatus::DELIVERED->value,
                    'delivered_at' => now(),
                ]);
            }
        }

        // Refresh chat list to show latest message
        $this->users = $this->getUsersWithUnreadCounts();
    }

    public function handleMessageReadStatus($data)
    {
        if ($data['receiver_id'] == Auth::id() && $data['sender_id'] == $this->selectedUser->id) {
            // Reload messages to reflect updated read status
            $this->loadMessages();
        }
    }

    public function submitMessage()
    {
        // Validate that at least one field is present
        if (empty($this->newMessage) && !$this->attachment) {
            session()->flash('error', 'Please enter a message or attach a file.');
            $this->dispatch('showError', message: 'Please enter a message or attach a file.');
            return;
        }

        $this->validate();

        try {
            DB::beginTransaction();

            $messageData = [
                'sender_id' => Auth::id(),
                'receiver_id' => $this->selectedUser->id,
                'message' => $this->newMessage,
                'message_type' => MessageType::TEXT->value,
                'status' => ChatMessageStatus::SENT->value,
            ];

            // Handle file attachment
            if ($this->attachment) {
                $fileService = app(FileService::class);
                $uploadedFile = $fileService->uploadChatAttachment($this->attachment);

                $messageData['attachment_path'] = $uploadedFile['path'];
                $messageData['attachment_name'] = $uploadedFile['original_name'];
                $messageData['attachment_mime_type'] = $uploadedFile['mime'];
                $messageData['attachment_size'] = $uploadedFile['size'];
                $messageData['message_type'] = MessageType::ATTACHMENT->value;
                
                // If only file without text, use filename as message
                if (empty($this->newMessage)) {
                    $messageData['message'] = 'Sent a file: ' . $uploadedFile['original_name'];
                }
            }

            $message = ChatMessage::create($messageData);
            $this->messages->push($message);

            // Reset form
            $this->reset(['newMessage', 'attachment']);

            // Broadcast event
            broadcast(new MessageSent($message))->toOthers();

            DB::commit();

            // Refresh chat list to show latest message
            $this->users = $this->getUsersWithUnreadCounts();

            $this->dispatch('messageSent');
            // Scroll after DOM updates
            $this->js('setTimeout(() => scrollToLatestMessage(), 100)');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Chat message send failed', ['error' => $e->getMessage()]);
            
            session()->flash('error', 'Failed to send message. Please try again.');
            $this->dispatch('showError', message: 'Failed to send message. Please try again.');
        }
    }

    public function loadMessages()
    {
        if (!$this->selectedUser) {
            $this->messages = collect();
            return;
        }

        $this->messages = ChatMessage::query()
            ->where(function($q) {
                $q->where('sender_id', Auth::id())
                  ->where('receiver_id', $this->selectedUser->id);
            })
            ->orWhere(function($q) {
                $q->where('sender_id', $this->selectedUser->id)
                  ->where('receiver_id', Auth::id());
            })
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function getLastMessage($userId)
    {
        return ChatMessage::query()
            ->where(function($q) use ($userId) {
                $q->where('sender_id', Auth::id())
                ->where('receiver_id', $userId);
            })
            ->orWhere(function($q) use ($userId) {
                $q->where('sender_id', $userId)
                ->where('receiver_id', Auth::id());
            })
            ->latest('created_at')
            ->first();
    }

    public function getUnreadCount($userId)
    {
        return ChatMessage::where('sender_id', $userId)
        ->where('receiver_id', Auth::id())
        ->whereIn('status', [ChatMessageStatus::SENT->value, ChatMessageStatus::DELIVERED->value])
        ->count();
    }

    public function getLastMessageTime($userId)
    {
        $lastMessage = ChatMessage::query()
        ->where(function($q) use ($userId) {
            $q->where('sender_id', Auth::id())
            ->where('receiver_id', $userId);
        })
        ->orWhere(function($q) use ($userId) {
            $q->where('sender_id', $userId)
            ->where('receiver_id', Auth::id());
        })
        ->latest('created_at')
        ->first();
        
        return $lastMessage ? $lastMessage->created_at : null;
    }

    /**
     * Computed Property to Get Users with Unread Message counts
    */
    public function getUsersWithUnreadCounts()
    {
        // Get all users except current user
        $users = User::whereNot('id', Auth::id())->get();

        // Map unread counts and last message time to each user
        $usersWithData = $users->map(function ($user) {
            // Get unread count
            $user->unread_count = ChatMessage::where('sender_id', $user->id)
            ->where('receiver_id', Auth::id())
            ->whereIn('status', [ChatMessageStatus::SENT->value, ChatMessageStatus::DELIVERED->value])
            ->count();
            
            // Get last message timestamp for sorting
            $lastMessage = ChatMessage::query()
            ->where(function($q) use ($user) {
                $q->where('sender_id', Auth::id())
                ->where('receiver_id', $user->id);
            })
            ->orWhere(function($q) use ($user) {
                $q->where('sender_id', $user->id)
                ->where('receiver_id', Auth::id());
            })
            ->latest('created_at')
            ->first();
            
            $user->last_message_time = $lastMessage ? $lastMessage->created_at : null;
            
            return $user;
        });


        // Sort by last message time (most recent first), users with no messages go to bottom
        return $usersWithData->sortByDesc(function ($user) {
            return $user->last_message_time ?? now()->subYears(100); 
        })->values();

    }

    
    public function handleUserLogout($data)
    {
        // Refresh users list to update status
        $this->users = $this->getUsersWithUnreadCounts();
        
        // If the logged out user is currently selected, refresh their status
        if ($this->selectedUser && $this->selectedUser->id == $data['userId']) {
            $this->selectedUser->last_seen_at = $data['lastSeenAt'];
        }
    }

    /*
     * Get selected user's online status
     */
    #[Computed]
    public function selectedUserStatus()
    {
        if (!$this->selectedUser) {
            return ['label' => '', 'class' => ''];
        }

        $isOnline = Cache::has("user-is-online-{$this->selectedUser->id}");
        
        if ($isOnline) {
            return [
                'label' => 'Online',
                'class' => 'text-blue-600 dark:text-blue-400'
            ];
        }

        $lastSeen = $this->selectedUser->last_seen_at;
        
        if (!$lastSeen) {
            return [
                'label' => 'Offline',
                'class' => 'text-zinc-500 dark:text-zinc-400'
            ];
        }

        $diff = Carbon::parse($lastSeen)->diffInHours(now());
        
        if ($diff < 24) {
            return [
                'label' => 'Last seen today at ' . Carbon::parse($lastSeen)->format('g:i a'),
                'class' => 'text-zinc-500 dark:text-zinc-400'
            ];
        }
        
        if ($diff < 48) {
            return [
                'label' => 'Last seen yesterday at ' . Carbon::parse($lastSeen)->format('g:i a'),
                'class' => 'text-zinc-500 dark:text-zinc-400'
            ];
        }

        return [
            'label' => 'Last seen on ' . Carbon::parse($lastSeen)->format('M d, Y'),
            'class' => 'text-zinc-500 dark:text-zinc-400'
        ];
    }

    /**
     * Get user's online status for chat list
     */
    public function getUserStatus($userId)
    {
        $isOnline = Cache::has("user-is-online-{$userId}");
        
        if ($isOnline) {
            return [
                'is_online' => true,
                'label' => 'Online',
                'class' => 'text-blue-600 dark:text-blue-400'
            ];
        }

        $user = User::find($userId);
        $lastSeen = $user->last_seen_at;
        
        if (!$lastSeen) {
            return [
                'is_online' => false,
                'label' => 'Offline',
                'class' => 'text-zinc-500 dark:text-zinc-400'
            ];
        }

        $diff = Carbon::parse($lastSeen)->diffInHours(now());
        
        if ($diff < 24) {
            return [
                'is_online' => false,
                'label' => 'Last seen today',
                'class' => 'text-zinc-500 dark:text-zinc-400'
            ];
        }
        
        if ($diff < 48) {
            return [
                'is_online' => false,
                'label' => 'Last seen yesterday',
                'class' => 'text-zinc-500 dark:text-zinc-400'
            ];
        }

        return [
            'is_online' => false,
            'label' => 'Last seen ' . Carbon::parse($lastSeen)->format('M d'),
            'class' => 'text-zinc-500 dark:text-zinc-400'
        ];
    }

    public function render()
    {
        return view('livewire.chats.chat');
    }
}