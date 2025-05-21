<x-layout class="min-h-screen bg-base-100">
    <div class="container card mx-auto px-4 py-8 max-w-md">
        <form action="{{ route('public.authenticate-select') }}" method="POST" class="card-body bg-base-100 rounded-lg shadow-lg">
            @csrf
            <div class="mb-4">
                <label for="access-code" class="block text-gray-300 font-medium mb-2">Code d'accès</label>
                <input type="text" id="access-code" name="access_code" class="input input-bordered w-full" required>
                <label for="password" class="block text-gray-300 font-medium mb-2">Mot de passe</label>
                <input type="password" id="password" name="password" class="input input-bordered w-full" required>
                @error('password')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn btn-soft w-full">Accéder à la galerie</button>
        </form>
    </div>
</x-layout>
