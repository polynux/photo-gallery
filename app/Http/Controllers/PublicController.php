<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use App\Models\PhotoGallery;
use App\Services\GalleryZipStream;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicController extends Controller
{
    public function show(string $accessCode): RedirectResponse|View
    {
        $photoGallery = PhotoGallery::query()->where('access_code', Str::upper($accessCode))->firstOrFail();

        if ($this->canViewGallery($photoGallery)) {
            return redirect()->route('public.gallery', $photoGallery->access_code);
        }

        return view('public.login', ['photoGallery' => $photoGallery]);
    }

    public function showForm(): View
    {
        return view('public.gallery-select');
    }

    public function authenticate(Request $request, string $accessCode): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'max:255'],
        ], [
            'password.required' => 'Le mot de passe est obligatoire',
        ]);

        $photoGallery = PhotoGallery::query()->where('access_code', Str::upper($accessCode))->firstOrFail();

        return $this->attemptGalleryAuthentication($request, $photoGallery, $validated['password'], $accessCode);
    }

    public function authenticateSelect(Request $request): RedirectResponse
    {
        $request->merge([
            'access_code' => Str::upper(trim((string) $request->input('access_code'))),
        ]);

        $validated = $request->validate([
            'access_code' => ['required', 'string', 'exists:photo_galleries,access_code'],
            'password' => ['required', 'string', 'max:255'],
        ], [
            'access_code.required' => 'Le code d\'accès est obligatoire',
            'access_code.exists' => 'Code d\'accès inconnu',
            'password.required' => 'Le mot de passe est obligatoire',
        ]);

        $photoGallery = PhotoGallery::query()->where('access_code', $validated['access_code'])->firstOrFail();

        return $this->attemptGalleryAuthentication($request, $photoGallery, $validated['password'], $validated['access_code']);
    }

    public function gallery(string $accessCode): RedirectResponse|View
    {
        $photoGallery = PhotoGallery::query()->where('access_code', Str::upper($accessCode))
            ->with(['sections' => function ($query) {
                $query->orderBy('position')->with(['photos' => function ($q) {
                    $q->orderBy('position');
                }]);
            }])
            ->firstOrFail();

        if (! $this->canViewGallery($photoGallery)) {
            return redirect()->route('public.show', $photoGallery->access_code);
        }

        $slideshowData = $photoGallery->sections->map(function ($section) {
            return [
                'id' => $section->id,
                'name' => $section->name,
                'photos' => $section->photos->map(function ($photo) {
                    return [
                        'src' => Storage::disk('photo')->url($photo->path),
                        'alt' => $photo->alt ?? 'Photo #'.$photo->id,
                    ];
                })->values()->toArray(),
            ];
        })->values()->toArray();

        return view('public.gallery', [
            'photoGallery' => $photoGallery,
            'slideshowData' => $slideshowData,
        ]);
    }

    public function download(string $accessCode): RedirectResponse|StreamedResponse
    {
        $photoGallery = PhotoGallery::query()->where('access_code', Str::upper($accessCode))->firstOrFail();

        if (! $this->canViewGallery($photoGallery)) {
            return redirect()->route('public.show', $photoGallery->access_code);
        }

        $zipStream = app(GalleryZipStream::class);
        $zipName = $zipStream->slugArchiveName($photoGallery);

        return response()->streamDownload(function () use ($zipStream, $photoGallery, $zipName): void {
            set_time_limit(0);
            $zipStream->stream($photoGallery, $zipName);
            set_time_limit(30);
        }, $zipName);
    }

    public function showPhoto(string $gallery, string $photo)
    {
        $photo = Photo::where('path', $gallery.'/'.$photo)
            ->where('photo_gallery_id', $gallery)
            ->firstOrFail();

        if (! $this->canViewGallery($photo->photoGallery)) {
            Log::info('User not authenticated for gallery: '.$gallery);

            return redirect()->route('public.select');
        }

        return Storage::disk('photo')->response($photo->path, headers: [
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    public function showThumbnail(string $gallery, string $photo)
    {
        $photo = Photo::where('path', $gallery.'/'.$photo)
            ->where('photo_gallery_id', $gallery)
            ->firstOrFail();

        if (! $this->canViewGallery($photo->photoGallery)) {
            Log::info('User not authenticated for gallery: '.$gallery);

            return redirect()->route('public.select');
        }

        if (Storage::disk('thumbnails')->exists($photo->path)) {
            return Storage::disk('thumbnails')->response($photo->path, headers: [
                'Cache-Control' => 'private, max-age=604800',
            ]);
        }

        return abort(404, 'Thumbnail not found');
    }

    public function lock(Request $request, string $accessCode): RedirectResponse
    {
        $photoGallery = PhotoGallery::query()->where('access_code', Str::upper($accessCode))->firstOrFail();

        $request->session()->forget($this->gallerySessionKey($photoGallery));

        return redirect()->route('public.show', $photoGallery->access_code);
    }

    private function attemptGalleryAuthentication(
        Request $request,
        PhotoGallery $photoGallery,
        string $password,
        string $accessCode,
    ): RedirectResponse {
        if (! Hash::check($password, $photoGallery->password)) {
            return back()
                ->withErrors(['password' => 'Mot de passe incorrect'])
                ->onlyInput('access_code');
        }

        $request->session()->regenerate();
        $request->session()->put($this->gallerySessionKey($photoGallery), true);

        return redirect()->route('public.gallery', $photoGallery->access_code);
    }

    /**
     * Whether the current visitor may view the gallery: either a
     * password-authenticated customer session, or any authenticated
     * admin (intentional bypass so the photographer can preview any
     * gallery from the admin panel without knowing client passwords).
     */
    private function canViewGallery(PhotoGallery $photoGallery): bool
    {
        return session($this->gallerySessionKey($photoGallery)) || auth()->check();
    }

    private function gallerySessionKey(PhotoGallery $photoGallery): string
    {
        return 'authenticated_gallery_'.$photoGallery->id;
    }
}
