<x-layout>
    <!-- Hero Section -->
    <section class="hero bg-cover bg-center h-screen -mt-16"
        style="background-image: url('{{ Vite::asset('resources/images/hero-image.jpg') }}');">
        <div class="container mx-auto h-full flex items-center justify-center text-center">
            <div>
                <h1 class="text-5xl font-bold text-gray-900 mb-4">Bienvenue sur Benjamin Photos</h1>
                <p class="text-xl text-gray-800 mb-8">Capturez vos moments précieux avec un photographe professionnel.</p>
                <a href="#" class="btn">
                    Réservez une séance
                </a>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="py-16">
        <div class="container mx-auto text-center">
            <h2 class="text-3xl font-bold mb-4 text-base-200">Contactez-moi</h2>
            <p class="text-gray-700 mb-8">
                Pour toute demande de renseignements ou de réservation, n'hésitez pas à me contacter.
            </p>
            <a href="#" class="btn">
                Contactez-moi
            </a>
        </div>
    </section>

</x-layout>
