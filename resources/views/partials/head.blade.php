<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $title ?? config('app.name') }}</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
<style>
    [x-cloak] { display: none !important; }
    .chat-bg {
        background-image: 
            repeating-linear-gradient(45deg, transparent, transparent 35px, rgba(0,0,0,.02) 35px, rgba(0,0,0,.02) 70px),
            repeating-linear-gradient(-45deg, transparent, transparent 35px, rgba(0,0,0,.02) 35px, rgba(0,0,0,.02) 70px);
    }
    
    .dark .chat-bg {
        /* background-color: #0b141a; */
        background-image: 
            repeating-linear-gradient(45deg, transparent, transparent 35px, rgba(255,255,255,.02) 35px, rgba(255,255,255,.02) 70px),
            repeating-linear-gradient(-45deg, transparent, transparent 35px, rgba(255,255,255,.02) 35px, rgba(255,255,255,.02) 70px);
    }

    .scrollbar-hidden::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }

    .scrollbar-hidden::-webkit-scrollbar-track {
        background: transparent;
    }

    .scrollbar-hidden::-webkit-scrollbar-thumb {
        background: #374045;
        border-radius: 10px;
    }

    .message-bubble {
        max-width: 65%;
        word-wrap: break-word;
    }

    @media (max-width: 768px) {
        .chat-list.hidden-mobile {
            display: none;
        }
        .chat-box.hidden-mobile {
            display: none;
        }
        .chat-box.show-mobile {
            display: flex !important;
        }
    }
</style>

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
