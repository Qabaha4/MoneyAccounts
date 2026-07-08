<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'dark') == 'dark'])>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Inline script to apply the correct theme immediately --}}
    <script>
        (function() {
            const appearance = '{{ $appearance ?? 'dark' }}';

            if (appearance === 'system') {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                if (!prefersDark) {
                    document.documentElement.classList.remove('dark');
                }
            } else if (appearance === 'light') {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>

    {{-- Inline style to set the HTML background color based on our theme in app.css --}}
    <style>
        html {
            background-color: oklch(0.97 0 0);
        }

        html.dark {
            background-color: oklch(0.04 0.003 270);
        }
    </style>

    <title inertia>{{ config('app.name', 'Laravel') }}</title>

    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="/favicon-16x16.svg" type="image/svg+xml" sizes="16x16">
    <link rel="apple-touch-icon" href="/apple-touch-icon.svg">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />
    <link href="https://fonts.bunny.net/css?family=amiri:400,700,400i,700i" rel="stylesheet" />

    @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
    @inertiaHead
</head>

<body class="font-sans antialiased">
    @inertia
</body>

</html>
