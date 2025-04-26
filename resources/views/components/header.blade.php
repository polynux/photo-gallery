<header class="navbar justify-center sticky top-0 z-50 transition-colors duration-300">
    <a href="{{ route('home') }}" class="btn btn-ghost normal-case text-xl">Benjamin Photos</a>
</header>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const navbar = document.querySelector('header.navbar');

        window.addEventListener('scroll', function() {
            if (window.scrollY > 10) {
                navbar.classList.add('bg-base-100', 'shadow-md');
            } else {
                navbar.classList.remove('bg-base-100', 'shadow-md');
            }
        });
    });
</script>
