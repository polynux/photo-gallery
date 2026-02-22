<header id="main-header" class="fixed top-0 left-0 right-0 z-50 transition-all duration-500 {{ $transparent ?? false ? 'header-transparent' : 'bg-white/95 backdrop-blur-md shadow-sm' }}">
    <div class="container mx-auto px-6 py-4">
        <div class="flex justify-between items-center">
            <!-- Logo -->
            <a href="{{ route('home') }}" class="font-display text-xl font-semibold transition-colors duration-300 header-logo {{ $transparent ?? false ? 'text-white' : 'text-gray-900' }}">
                Pinaton Photographie
            </a>
            
            <!-- Navigation -->
            <nav class="hidden md:flex items-center gap-8">
                <a href="{{ route('home') }}" class="text-sm font-medium transition-colors duration-300 header-link {{ $transparent ?? false ? 'text-white/80 hover:text-white' : 'text-gray-600 hover:text-gray-900' }}">Accueil</a>
                <a href="{{ route('home') }}#universe" class="text-sm font-medium transition-colors duration-300 header-link {{ $transparent ?? false ? 'text-white/80 hover:text-white' : 'text-gray-600 hover:text-gray-900' }}">Univers</a>
                <a href="{{ route('home') }}#gallery" class="text-sm font-medium transition-colors duration-300 header-link {{ $transparent ?? false ? 'text-white/80 hover:text-white' : 'text-gray-600 hover:text-gray-900' }}">Galerie</a>
                <a href="{{ route('home') }}#contact" class="text-sm font-medium transition-colors duration-300 header-link {{ $transparent ?? false ? 'text-white/80 hover:text-white' : 'text-gray-600 hover:text-gray-900' }}">Contact</a>
            </nav>
            
            <!-- Mobile Menu Button -->
            <button id="mobile-menu-btn" class="md:hidden p-2" aria-label="Menu">
                <svg class="w-6 h-6 header-icon {{ $transparent ?? false ? 'text-white' : 'text-gray-900' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>
    </div>
    
    <!-- Mobile Menu -->
    <div id="mobile-menu" class="hidden md:hidden bg-white border-t border-gray-100">
        <div class="container mx-auto px-6 py-4 space-y-4">
            <a href="{{ route('home') }}" class="block text-gray-900 font-medium">Accueil</a>
            <a href="{{ route('home') }}#universe" class="block text-gray-900 font-medium">Univers</a>
            <a href="{{ route('home') }}#gallery" class="block text-gray-900 font-medium">Galerie</a>
            <a href="{{ route('home') }}#contact" class="block text-gray-900 font-medium">Contact</a>
        </div>
    </div>
</header>

@if($transparent ?? false)
<script>
    // Header scroll effect - only for transparent header on homepage
    document.addEventListener('DOMContentLoaded', function() {
        const header = document.getElementById('main-header');
        const logo = document.querySelector('.header-logo');
        const links = document.querySelectorAll('.header-link');
        const icon = document.querySelector('.header-icon');
        
        function updateHeader() {
            if (window.scrollY > 50) {
                // Scrolled state
                header.classList.add('bg-white/95', 'backdrop-blur-md', 'shadow-sm');
                header.classList.remove('header-transparent');
                
                logo.classList.remove('text-white');
                logo.classList.add('text-gray-900');
                
                links.forEach(link => {
                    link.classList.remove('text-white/80');
                    link.classList.add('text-gray-600');
                });
                
                if (icon) {
                    icon.classList.remove('text-white');
                    icon.classList.add('text-gray-900');
                }
            } else {
                // Top state (transparent)
                header.classList.remove('bg-white/95', 'backdrop-blur-md', 'shadow-sm');
                header.classList.add('header-transparent');
                
                logo.classList.add('text-white');
                logo.classList.remove('text-gray-900');
                
                links.forEach(link => {
                    link.classList.add('text-white/80');
                    link.classList.remove('text-gray-600');
                });
                
                if (icon) {
                    icon.classList.add('text-white');
                    icon.classList.remove('text-gray-900');
                }
            }
        }
        
        // Initial check
        updateHeader();
        
        // Scroll listener
        window.addEventListener('scroll', updateHeader, { passive: true });
    });
</script>
@endif

<script>
    // Mobile menu toggle
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        
        if (mobileMenuBtn && mobileMenu) {
            mobileMenuBtn.addEventListener('click', function() {
                mobileMenu.classList.toggle('hidden');
            });
        }
    });
</script>
