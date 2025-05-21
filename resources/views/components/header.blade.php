<header class="navbar justify-center sticky top-0 z-50 transition-colors duration-300">
    <a href="{{ route('home') }}" class="btn btn-ghost normal-case text-xl text-base-200 hover:text-gray-100">Pinaton Photographie</a>
</header>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const navbar = document.querySelector('header.navbar');

        window.addEventListener('scroll', function() {
            if (window.scrollY > 10) {
                navbar.classList.add('bg-base-100', 'shadow-md');
                navbar.children[0].classList.add('text-gray-100');
            } else {
                navbar.classList.remove('bg-base-100', 'shadow-md');
                navbar.children[0].classList.remove('text-gray-100');
            }
        });
    });
</script>
