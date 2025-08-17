<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="{{ asset('resources/js/assets/images/favicon.ico') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lahomes Vue - Responsive Admin Dashboard Template</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <style>
        #splash-screen {
            position: fixed;
            top: 50%;
            left: 50%;
            background: white;
            display: flex;
            height: 100%;
            width: 100%;
            transform: translate(-50%, -50%);
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 1;
            transition: all 15s linear;
            overflow: hidden;
        }
        
        #splash-screen.remove {
            animation: fadeout 0.7s forwards;
            z-index: 0;
        }
        
        @keyframes fadeout {
            to {
                visibility: hidden;
            }
        }
    </style>
</head>
<body>
    <div id="app"></div>
    <div id="splash-screen">
        <img alt="Logo" class="light" src="{{ Vite::asset('resources/js/assets/images/logo-dark.png') }}" style="height: 10%;" />
    </div>
    
    @vite(['resources/js/main.ts'])
    
    <script>
        const appEl = document.querySelector('#app');
        new MutationObserver(() => {
            document.querySelector('#splash-screen')?.classList.add('remove');
        }).observe(appEl, { childList: true })
    </script>
</body>
</html>