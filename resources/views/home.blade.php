<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nadiahungphotography inspired</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="bg-gray-100">

<!-- Hero Section -->
<section class="hero bg-cover bg-center h-screen" style="background-image: url('{{ asset('images/hero-image.jpg') }}');">
    <div class="container mx-auto h-full flex items-center justify-center text-center">
        <div>
            <h1 class="text-5xl font-bold text-white mb-4">Your Unique Photography</h1>
            <p class="text-xl text-gray-300 mb-8">Capture your story with us.</p>
            <a href="#" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Book a Session
            </a>
        </div>
    </div>
</section>

<!-- About Section -->
<section class="py-16">
    <div class="container mx-auto text-center">
        <h2 class="text-3xl font-bold mb-4">About Nadiahungphotography</h2>
        <img src="{{ asset('images/photographer.jpg') }}" alt="Photographer" class="rounded-full w-48 h-48 mx-auto mb-4">
        <p class="text-gray-700">
            Nadiahungphotography is dedicated to capturing timeless moments with a unique and artistic vision. With years of experience and a passion for storytelling, we strive to deliver exceptional photography that exceeds your expectations.
        </p>
    </div>
</section>

<!-- Featured Galleries Section -->
<section class="py-16 bg-gray-200">
    <div class="container mx-auto">
        <h2 class="text-3xl font-bold text-center mb-8">Featured Galleries</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Gallery 1 -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <img src="{{ asset('images/gallery1.jpg') }}" alt="Gallery 1" class="w-full h-64 object-cover">
                <div class="p-4">
                    <h3 class="text-xl font-semibold mb-2">Elizabeth & Ryan</h3>
                    <p class="text-gray-700">A beautiful wedding captured with love and artistry.</p>
                    <a href="#" class="text-blue-500 hover:underline">View Gallery</a>
                </div>
            </div>

            <!-- Gallery 2 -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <img src="{{ asset('images/gallery2.jpg') }}" alt="Gallery 2" class="w-full h-64 object-cover">
                <div class="p-4">
                    <h3 class="text-xl font-semibold mb-2">Family Portraits</h3>
                    <p class="text-gray-700">Cherishing precious family moments through professional portraits.</p>
                    <a href="#" class="text-blue-500 hover:underline">View Gallery</a>
                </div>
            </div>

            <!-- Gallery 3 -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <img src="{{ asset('images/gallery3.jpg') }}" alt="Gallery 3" class="w-full h-64 object-cover">
                <div class="p-4">
                    <h3 class="text-xl font-semibold mb-2">Event Photography</h3>
                    <p class="text-gray-700">Capturing the energy and excitement of your special events.</p>
                    <a href="#" class="text-blue-500 hover:underline">View Gallery</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section class="py-16">
    <div class="container mx-auto text-center">
        <h2 class="text-3xl font-bold mb-4">Contact Us</h2>
        <p class="text-gray-700 mb-8">
            Ready to book a session or have questions? Get in touch with us today!
        </p>
        <a href="#" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
            Book Now
        </a>
    </div>
</section>

<!-- Footer Section -->
<footer class="bg-gray-800 text-white py-8">
    <div class="container mx-auto text-center">
        <p>&copy; 2025 Nadiahungphotography. All rights reserved.</p>
    </div>
</footer>

</body>
</html>
