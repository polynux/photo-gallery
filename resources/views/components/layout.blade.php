<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Benjamin Photos' }}</title>
    @if (isset($description))
        <meta name="description" content="{{ $description }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body {{ $attributes->class([
    'bg-gray-100' => !str_contains($attributes->get('class') ?? '', 'bg')
]) }}>

    {{ $slot }}

    <!-- Footer Section -->
    <footer class="footer sm:footer-horizontal footer-center bg-base-300 text-base-content p-4">
        <div class="container mx-auto text-center">
            <p>&copy; 2025 Benjamin Photos. All rights reserved.</p>
        </div>
    </footer>

</body>

</html>
