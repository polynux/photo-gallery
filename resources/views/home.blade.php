<x-layout>
    <!-- Hero Section -->
    <section class="hero bg-cover bg-center h-screen -mt-16"
        style="background-image: url('{{ Vite::asset('resources/images/hero-image.webp') }}');">
        <div class="container mx-auto h-full flex items-center justify-center text-center">
            <div>
                <h1 class="text-5xl font-bold text-gray-900 mb-4">Bienvenue sur PINATON Photographie</h1>
                <p class="text-xl text-gray-800 mb-8">Capturez vos moments précieux avec un photographe professionnel.
                </p>
                <a href="#contact" class="btn">
                    Contactez-moi
                </a>
            </div>
        </div>
    </section>

    <!-- My Universe Section -->
    <section class="py-16 bg-gray-100" id="universe">
        <div class="container mx-auto text-center">
            <h2 class="text-3xl font-bold mb-4 text-base-200">Mon univers visuel</h2>
            <p class="text-gray-700 mb-8">
                J'essaie au travers de chacun de mes clichés de transmettre une émotion singulière,
                un moment sincère ou un instant précieux...
            </p>
            <x-univers />
        </div>
    </section>

    <!-- Gallery Section -->
    <section class="py-8" id="gallery">
        <div class="container mx-auto text-center">
            <h2 class="text-3xl font-bold mb-4 text-base-200">Ma Galerie</h2>
            <p class="text-gray-700 mb-8">
                Accédez à votre galerie photo à l'aide du code d'accès et du mot de passe fournis par votre photographe.
            </p>
            <button class="btn mb-4">
                <a href="{{ route('public.select') }}">Accéder à ma galerie</a>
            </button>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="py-16" id="contact">
        <div class="container mx-auto text-center flex flex-col items-center">
            <h2 class="text-3xl font-bold mb-4 text-base-200">Contactez-moi</h2>
            <p class="text-gray-700 mb-8">
                Pour toute demande de renseignements ou de réservation, n'hésitez pas à me contacter.
            </p>
            <ul class="list text-base-200 text-lg flex flex-col gap-4">
                <li class="flex items-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" fill="none" view-box="0 0 24 24"
                        stroke-width={1.5} stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                    </svg><a href="mailto:contact@pinatonphotos.fr">contact@pinatonphotos.fr</a></li>
                <li class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                    </svg>

                    <a href="tel:+33644751975">+33 6 44 75 19 75</a>
                </li>
                <li class="flex items-center gap-2">
                    <svg width="800px" height="800px" viewBox="0 0 24 24" fill="none"
                        xmlns="http://www.w3.org/2000/svg" class="size-6">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M20 1C21.6569 1 23 2.34315 23 4V20C23 21.6569 21.6569 23 20 23H4C2.34315 23 1 21.6569 1 20V4C1 2.34315 2.34315 1 4 1H20ZM20 3C20.5523 3 21 3.44772 21 4V20C21 20.5523 20.5523 21 20 21H15V13.9999H17.0762C17.5066 13.9999 17.8887 13.7245 18.0249 13.3161L18.4679 11.9871C18.6298 11.5014 18.2683 10.9999 17.7564 10.9999H15V8.99992C15 8.49992 15.5 7.99992 16 7.99992H18C18.5523 7.99992 19 7.5522 19 6.99992V6.31393C19 5.99091 18.7937 5.7013 18.4813 5.61887C17.1705 5.27295 16 5.27295 16 5.27295C13.5 5.27295 12 6.99992 12 8.49992V10.9999H10C9.44772 10.9999 9 11.4476 9 11.9999V12.9999C9 13.5522 9.44771 13.9999 10 13.9999H12V21H4C3.44772 21 3 20.5523 3 20V4C3 3.44772 3.44772 3 4 3H20Z"
                            fill="#0F0F0F" />
                    </svg><a href="https://www.facebook.com/people/Pinaton-Photographie/61575871906908/">Pinaton
                        Photographie</a>
                </li>

            </ul>
        </div>
    </section>

</x-layout>
