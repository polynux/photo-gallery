<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Pinaton Photographie' }}</title>
    <meta name="description" content="{{ $description ?? 'Pinaton Photographie - Capturez vos moments précieux avec un photographe professionnel.' }}">
    <meta name="keywords" content="{{ $keywords ?? 'photographie, photographe, portrait, mariage, événementiel' }}">
    <meta name="author" content="Pinaton Photographie">
    <meta name="theme-color" content="#1f2937">
    <meta name="robots" content="index, follow">
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <meta property="og:title" content="{{ $title ?? 'Pinaton Photographie' }}">
    <meta property="og:description" content="{{ $description ?? 'Pinaton Photographie - Capturez vos moments précieux avec un photographe professionnel.' }}">
    <meta property="og:image" content="{{ asset('img/og-image.webp') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title ?? 'Pinaton Photographie' }}">
    <meta name="twitter:description" content="{{ $description ?? 'Pinaton Photographie - Capturez vos moments précieux avec un photographe professionnel.' }}">
    <meta name="twitter:image" content="{{ asset('img/og-image.webp') }}">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <x-plausible::tracking extensions="outbound-links, file-downloads" />
</head>

<body class="bg-white text-gray-900 antialiased">

    <!-- Header Section -->
    <x-header :transparent="request()->routeIs('home')" />

    {{ $slot }}

    <!-- Footer Section -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="container mx-auto px-6">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-4 md:mb-0">
                    <span class="font-display text-2xl font-semibold">Pinaton Photographie</span>
                    <p class="text-gray-400 text-sm mt-2">Capturez vos moments précieux</p>
                </div>
                <div class="flex gap-6">
                    <a href="#" class="text-gray-400 hover:text-white transition-colors">Instagram</a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors">Facebook</a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors">Contact</a>
                </div>
            </div>
            <div class="mt-8 pt-8 border-t border-gray-800 text-center">
                <p class="text-gray-500 text-sm">© 2025 Pinaton Photographie. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

</body>

</html>
