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
        /* base color (light) */
        background-color: #f5f9fc;

        /* Layer 1: fine dotted texture (repeating radial) - gives a linen-like grain
        Layer 2: soft diagonal sheen (repeating linear) - adds motion and depth */
        background-image:
            repeating-radial-gradient(circle at 0 0, rgba(6,120,160,0.06) 0px, rgba(6,120,160,0.06) 1px, transparent 1px, transparent 28px),
            repeating-linear-gradient(135deg, transparent, transparent 28px, rgba(6,120,160,0.035) 28px, rgba(6,120,160,0.035) 70px);
        
        /* sizing aligns the dot grid and sheen rhythm */
        background-size: 28px 28px, 140px 140px;
        background-position: 0 0, 0 0;
        background-repeat: repeat, repeat;
        /* keep the texture steady while scrolling (optional, remove for mobile) */
        background-attachment: fixed;
    }

    /* Dark theme variant */
    .dark .chat-bg {
        background-color: #071226;

        background-image:
            repeating-radial-gradient(circle at 0 0, rgba(255,255,255,0.03) 0px, rgba(255,255,255,0.03) 1px, transparent 1px, transparent 28px),
            repeating-linear-gradient(135deg, transparent, transparent 28px, rgba(255,255,255,0.02) 28px, rgba(255,255,255,0.02) 70px);

        background-size: 28px 28px, 140px 140px;
        background-position: 0 0, 0 0;
        background-repeat: repeat, repeat;
        background-attachment: fixed;
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
