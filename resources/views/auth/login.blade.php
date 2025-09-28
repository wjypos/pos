<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="{{ asset('images/logo.png') }}" type="image/png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="theme-color" content="#01d679">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="POS P-Plus">
    <meta name="description" content="POS P-Plus System">
    <meta name="application-name" content="POS P-Plus">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, post-check=0, pre-check=0">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="-1">
    
    <title>POS </title>
    <link rel="manifest" href="{{ asset('manifest.json') }}" crossorigin="use-credentials">
    <link rel="apple-touch-icon" href="{{ asset('pwa/icons/ios/192.png') }}">
    <link rel="icon" type="image/png" sizes="196x196" href="{{ asset('pwa/icons/ios/196.png') }}">
    @include('layouts.pwa-icons')
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>
<body>
    <div class="login-container">
        <form method="POST" action="{{ route('login') }}" class="login-form" autocomplete="off">
            @csrf
            <input type="hidden" name="_token" value="{{ csrf_token() }}" />
            <input type="hidden" name="_session_id" value="{{ session()->getId() }}" />
            <div class="logo">
                <img src="{{ asset('pwa/icons/ios/192.png') }}" alt="POS P-Plus Logo" width="80">
            </div>
            <h1>P-Plus</h1>
            
            @if ($errors->any())
            <div class="alert alert-error">
                {{ $errors->first() }}
            </div>
            @endif

            <div class="form-group">
                <input type="email" name="email" placeholder="Email" required autofocus>
            </div>

            <div class="form-group">
                <input type="password" name="password" placeholder="Password" required>
            </div>

            <button type="submit" class="login-button">Login</button>
        </form>
    </div>

    <div id="pwa-install-prompt" style="display: none;" class="install-prompt">
        <button id="install-button">Install POS P-Plus App</button>
    </div>

    <script>
        // Add CSRF token to all AJAX requests
        document.addEventListener('DOMContentLoaded', function() {
            let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function() {
                    if (!this.querySelector('input[name="_token"]')) {
                        let csrfInput = document.createElement('input');
                        csrfInput.type = 'hidden';
                        csrfInput.name = '_token';
                        csrfInput.value = token;
                        this.appendChild(csrfInput);
                    }
                });
            });

            // Refresh CSRF token periodically
            setInterval(function() {
                fetch('/csrf-token')
                    .then(response => response.json())
                    .then(data => {
                        document.querySelector('meta[name="csrf-token"]').setAttribute('content', data.token);
                        document.querySelector('input[name="_token"]').value = data.token;
                    });
            }, 3600000); // Refresh every hour
        });

        let deferredPrompt;
        const installButton = document.getElementById('install-button');
        const promptContainer = document.getElementById('pwa-install-prompt');

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            promptContainer.style.display = 'block';
        });

        installButton.addEventListener('click', async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                console.log(`User response to install prompt: ${outcome}`);
                deferredPrompt = null;
                promptContainer.style.display = 'none';
            }
        });

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => {
                        console.log('ServiceWorker registration successful:', registration.scope);
                    })
                    .catch(error => {
                        console.error('ServiceWorker registration failed:', error);
                    });
            });
        }
    </script>
</body>
</html>
</html>
