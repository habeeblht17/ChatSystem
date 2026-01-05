<?php 

return [
    
    /**
     * 
     * Disk used to store chat attachments
     */
    'attachment_disk' => env('CHAT_ATTACHMENT_DISK', 'public'),

    /**
     * 
     * Allowed MIME types for attachments
     */
    'allowed_mimes' => [        
        'image/png',
        'image/jpeg',
        'image/webp',
        'image/gif',
        'application/pdf',
    ],

    /**
     * 
     * Maximum upload size (in kilobytes)
     */
    'max_uplaod_kb' => (int) env('CHAT_MAX_UPLOAD_KB', 1024),
];