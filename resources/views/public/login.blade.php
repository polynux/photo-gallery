<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $photoGallery->name }} - Login</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-base-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-base rounded-lg shadow-lg p-8">
        <h1 class="text-2xl font-bold text-center mb-6">{{ $photoGallery->name }}</h1>

        @if($photoGallery->description)
            <p class="text-gray-600 mb-6 text-center">{{ $photoGallery->description }}</p>
        @endif

        <form action="{{ route('public.authenticate', $photoGallery->access_code) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label for="password" class="block text-gray-300 font-medium mb-2">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="input input-bordered w-full"
                    required
                >
                @error('password')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn btn-soft w-full">View Photos</button>
        </form>
    </div>
</body>
</html>
