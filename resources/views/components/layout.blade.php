<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Pinaton Photographie' }}</title>
    <meta name="description" content="{{ $description ?? 'Pinaton Photographie - Capturez vos moments précieux avec un photographe professionnel.' }}">
    <meta name="keywords" content="{{ $keywords ?? 'photographie, photographe, portrait, mariage, événementiel' }}">
    <meta name="author" content="Pinaton Photographie">
    <meta name="theme-color" content="#ffffff">
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
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <x-plausible::tracking extensions="outbound-links, file-downloads" />
</head>

<body {{ $attributes->class([
    'bg-gray-100' => !str_contains($attributes->get('class') ?? '', 'bg')
]) }}>

    <!-- Header Section -->
    <x-header />

    {{ $slot }}

    <!-- Footer Section -->
    <footer class="footer sm:footer-horizontal footer-center bg-base-300 text-base-content p-4">
        <div class="container mx-auto text-center">
            <p>&copy; 2025 Pinaton Photographie. Tous droits réservés.</p>
        </div>
    </footer>

</body>

</html>
