<?php

namespace App\Models;

use App\Enums\MessageType;
use App\Enums\ChatMessageStatus;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;


class ChatMessage extends Model
{
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'parent_id',
        'message',
        'attachment_path',
        'attachment_name',
        'attachment_mime_type',
        'attachment_size',
        'message_type',
        'status',
        'delivered_at',
        'read_at',
        'is_system',
    ];


    protected $casts = [
        'message_type' => MessageType::class,
        'status' => ChatMessageStatus::class,
        'attachment_size' => 'integer',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'is_system' => 'boolean',
    ];


    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        if (empty($this->attachment_path)) {
            return null;
        }

        $disk = config('chat.attachment_disk', 'public');

        // Normalize stored path (remove any leading slash)
        $path = ltrim($this->attachment_path, '/');

        try {
            $url = Storage::disk($disk)->url($path);

            if ($url) {
                // Remove spaces by encoding (quick sanitization). If complex issues exist, controller approach is safer.
                if (strpos($url, ' ') !== false) {
                    $url = str_replace(' ', '%20', $url);
                }
                return $url;
            }
        } catch (\Throwable $e) {
            Log::debug('ChatMessage::getAttachmentUrlAttribute - Storage::url() failed', [
                'disk' => $disk,
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            if ($disk === 'public' && Storage::disk('public')->exists($path)) {
                $segments = explode('/', $path);
                $encoded = implode('/', array_map('rawurlencode', $segments));
                return asset('storage/' . $encoded);
            }
        } catch (\Throwable $e) {
            Log::debug('ChatMessage::getAttachmentUrlAttribute - asset fallback failed', [
                'disk' => $disk,
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * True if the attachment mime type looks like an image.
     */
    public function getAttachmentIsImageAttribute(): bool
    {
        $mime = $this->attachment_mime_type ?? '';
        return is_string($mime) && str_starts_with($mime, 'image/');
    }

    public function markDelivered(): bool
    {
        $this->delivered_at = now();
        $this->status = 'delivered';
        return $this->save();
    }

    public function markRead(): bool
    {
        $this->read_at = now();
        $this->status = 'read';
        return $this->save();
    }
    
}