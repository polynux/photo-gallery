<x-layout>
    <!-- Hero Section - Full Screen with Background Image -->
    <section id="hero" class="relative h-screen flex items-center justify-center overflow-hidden">
        <!-- Background Image -->
        <div class="absolute inset-0 bg-cover bg-center bg-no-repeat"
            style="background-image: url('{{ Vite::asset('resources/images/hero-image.webp') }}');">
        </div>
        
        <!-- Overlay -->
        <div class="absolute inset-0 bg-gradient-to-b from-black/40 via-black/30 to-black/60"></div>
        
        <!-- Content -->
        <div class="relative z-10 container mx-auto px-6 text-center">
            <p class="text-sm font-medium tracking-[0.2em] uppercase mb-6 text-white/80 animate-fade-in-up">
                Photographie Professionnelle
            </p>
            
            <h1 class="text-5xl md:text-7xl lg:text-8xl font-display font-bold mb-8 text-white animate-fade-in-up delay-100">
                Capturez l'Instant
            </h1>
            
            <p class="text-xl md:text-2xl mb-12 text-white/90 max-w-2xl mx-auto animate-fade-in-up delay-200">
                Vos moments précieux méritent un regard professionnel
            </p>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center animate-fade-in-up delay-300">
                <a href="#contact" 
                   class="px-8 py-4 bg-white text-gray-900 rounded-full font-medium transition-all hover:shadow-lg hover:scale-105">
                    Me contacter
                </a>
                <a href="#universe" 
                   class="px-8 py-4 border-2 border-white text-white rounded-full font-medium transition-all hover:bg-white/10">
                    Découvrir
                </a>
            </div>
        </div>
        
        <!-- Scroll Indicator -->
        <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce">
            <svg class="w-6 h-6 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
            </svg>
        </div>
    </section>

    <!-- Universe Section -->
    <section id="universe" class="py-24 bg-gray-50">
        <div class="container mx-auto px-6">
            <div class="text-center mb-16">
                <p class="text-sm font-medium tracking-[0.2em] uppercase mb-4 text-gray-500">Portfolio</p>
                <h2 class="text-4xl md:text-5xl font-display font-bold text-gray-900 mb-6">Mon Univers Visuel</h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    J'essaie au travers de chacun de mes clichés de transmettre une émotion singulière,
                    un moment sincère ou un instant précieux...
                </p>
            </div>
            
            <!-- Universe Grid Component -->
            <x-univers />
        </div>
    </section>

    <!-- Gallery CTA Section -->
    <section id="gallery" class="py-24 bg-white">
        <div class="container mx-auto px-6">
            <div class="max-w-4xl mx-auto text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-100 mb-8">
                    <svg class="w-10 h-10 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"></path>
                    </svg>
                </div>
                
                <p class="text-sm font-medium tracking-[0.2em] uppercase mb-4 text-gray-500">Espace Client</p>
                
                <h2 class="text-4xl md:text-5xl font-display font-bold text-gray-900 mb-6">Ma Galerie</h2>
                
                <p class="text-lg text-gray-600 mb-8 max-w-2xl mx-auto">
                    Accédez à votre galerie photo à l'aide du code d'accès et du mot de passe 
                    fournis par votre photographe. Téléchargez vos photos en haute qualité.
                </p>
                
                <a href="{{ route('public.select') }}" 
                   class="inline-flex items-center px-8 py-4 bg-gray-900 text-white rounded-full font-medium transition-all hover:shadow-lg hover:scale-105">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                    </svg>
                    Accéder à ma galerie
                </a>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-24 bg-gray-50">
        <div class="container mx-auto px-6">
            <div class="max-w-6xl mx-auto">
                <div class="text-center mb-16">
                    <p class="text-sm font-medium tracking-[0.2em] uppercase mb-4 text-gray-500">Contact</p>
                    
                    <h2 class="text-4xl md:text-5xl font-display font-bold text-gray-900 mb-6">Contactez-moi</h2>
                    
                    <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                        Pour toute demande de renseignements ou de réservation, n'hésitez pas à me contacter.
                    </p>
                </div>
                
                <div class="grid md:grid-cols-2 gap-12">
                    <!-- Contact Info -->
                    <div class="space-y-8">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-full bg-gray-900 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 mb-1">Email</h3>
                                <a href="mailto:contact@pinatonphotos.fr" class="text-gray-600 hover:text-gray-900 transition-colors">contact@pinatonphotos.fr</a>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-full bg-gray-900 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 mb-1">Téléphone</h3>
                                <a href="tel:+33644751975" class="text-gray-600 hover:text-gray-900 transition-colors">+33 6 44 75 19 75</a>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-full bg-gray-900 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 mb-1">Facebook</h3>
                                <a href="https://www.facebook.com/people/Pinaton-Photographie/61575871906908/" target="_blank" class="text-gray-600 hover:text-gray-900 transition-colors">Pinaton Photographie</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contact Form -->
                    <div class="bg-white p-8 rounded-2xl shadow-lg border border-gray-100">
                        <h3 class="font-display text-2xl font-semibold text-gray-900 mb-6">Envoyez un message</h3>
                        
                        <form action="#" method="POST" class="space-y-5">
                            @csrf
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Nom</label>
                                <input type="text" id="name" name="name" required
                                    class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-gray-900 focus:outline-none transition-colors"
                                    placeholder="Votre nom">
                            </div>
                            
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                <input type="email" id="email" name="email" required
                                    class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-gray-900 focus:outline-none transition-colors"
                                    placeholder="votre@email.com">
                            </div>
                            
                            <div>
                                <label for="message" class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                                <textarea id="message" name="message" rows="4" required
                                    class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-gray-900 focus:outline-none transition-colors resize-none"
                                    placeholder="Décrivez votre projet..."></textarea>
                            </div>
                            
                            <button type="submit" 
                                class="w-full py-4 bg-gray-900 text-white rounded-lg font-medium transition-all hover:bg-gray-800 hover:shadow-lg">
                                Envoyer le message
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

</x-layout>
