<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $photoGallery->name }} - Authentification</title>
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                    </svg>
                </div>
                
                <h1 class="font-display text-2xl font-bold text-gray-900 mb-2">{{ $photoGallery->name }}</h1>
                
                @if($photoGallery->description)
                    <p class="text-gray-600">{{ $photoGallery->description }}</p>
                @endif
            </div>

            <!-- Form -->
            <form action="{{ route('public.authenticate', $photoGallery->access_code) }}" method="POST" class="space-y-6">
                @csrf
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Mot de passe</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-gray-900 focus:outline-none transition-colors"
                        placeholder="Entrez votre mot de passe"
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
