<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accéder à ma galerie - Pinaton Photographie</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite('resources/css/app.css')
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .font-display {
            font-family: 'Playfair Display', serif;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="w-full max-w-md mx-auto px-6">
        <!-- Card -->
        <div class="bg-white rounded-2xl shadow-xl p-8 md:p-10">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                    <svg class="w-8 h-8 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"></path>
                    </svg>
                </div>
                
                <h1 class="font-display text-2xl font-bold text-gray-900 mb-2">Accéder à ma galerie</h1>
                
                <p class="text-gray-600">Entrez vos identifiants pour accéder à votre galerie privée</p>
            </div>

            <!-- Form -->
            <form action="{{ route('public.authenticate-select') }}" method="POST" class="space-y-6">
                @csrf
                
                <div>
                    <label for="access-code" class="block text-sm font-medium text-gray-700 mb-2">Code d'accès</label>
                    <input
                        type="text"
                        id="access-code"
                        name="access_code"
                        class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-gray-900 focus:outline-none transition-colors uppercase tracking-wider"
                        placeholder="ABC123"
                        required
                    >
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Mot de passe</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-gray-900 focus:outline-none transition-colors"
                        placeholder="Votre mot de passe"
                        required
                    >
                    @error('password')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <button 
                    type="submit" 
                    class="w-full py-4 bg-gray-900 text-white rounded-lg font-medium transition-all hover:bg-gray-800 hover:shadow-lg"
                >
                    Accéder à la galerie
                </button>
            </form>

            <!-- Footer -->
            <div class="mt-6 pt-6 border-t border-gray-100 text-center">
                <a href="{{ route('home') }}" class="text-sm text-gray-500 hover:text-gray-900 transition-colors">
                    ← Retour à l'accueil
                </a>
            </div>
        </div>
        
        <!-- Brand -->
        <div class="text-center mt-8">
            <span class="font-display text-lg text-gray-400">Pinaton Photographie</span>
        </div>
    </div>
</body>
</html>
