<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use App\Models\PhotoGallery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipStream\ZipStream;

class PublicController extends Controller
{
    public function show(string $accessCode): RedirectResponse|View
    {
        $photoGallery = PhotoGallery::query()->where('access_code', $accessCode)->firstOrFail();

        if (session($this->gallerySessionKey($photoGallery))) {
            return redirect()->route('public.gallery', $accessCode);
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
        ]);

        $photoGallery = PhotoGallery::query()->where('access_code', $accessCode)->firstOrFail();

        return $this->attemptGalleryAuthentication($request, $photoGallery, $validated['password'], $accessCode);
    }

    public function authenticateSelect(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'access_code' => ['required', 'string', 'exists:photo_galleries,access_code'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $photoGallery = PhotoGallery::query()->where('access_code', $validated['access_code'])->firstOrFail();

        return $this->attemptGalleryAuthentication($request, $photoGallery, $validated['password'], $validated['access_code']);
    }

    public function gallery(string $accessCode): RedirectResponse|View
    {
        $photoGallery = PhotoGallery::query()->where('access_code', $accessCode)
            ->with(['sections' => function ($query) {
                $query->orderBy('position')->with(['photos' => function ($q) {
                    $q->orderBy('position');
                }]);
            }])
            ->firstOrFail();

        if (! session($this->gallerySessionKey($photoGallery))) {
            return redirect()->route('public.show', $accessCode);
        }

        $slideshowData = $photoGallery->sections->map(function ($section) {
            return [
                'id' => $section->id,
                'name' => $section->name,
                'photos' => $section->photos->map(function ($photo) {
                    return [
                        'src' => Storage::disk('photo')->url($photo->path),
                        'alt' => $photo->alt ?? 'Photo #' . $photo->id,
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
        $photoGallery = PhotoGallery::query()->where('access_code', $accessCode)
            ->with(['sections' => function ($query) {
                $query->orderBy('position')->with(['photos' => function ($q) {
                    $q->orderBy('position');
                }]);
            }])
            ->firstOrFail();

        if (! session($this->gallerySessionKey($photoGallery))) {
            return redirect()->route('public.show', $accessCode);
        }

        $zipName = Str::slug($photoGallery->name) . '.zip';
        $galleryFolder = $photoGallery->name;
        $sections = $photoGallery->sections;
        $hasMultipleSections = $sections->count() > 1 || $sections->first()?->is_default === false;

        return response()->streamDownload(function () use ($galleryFolder, $hasMultipleSections, $sections, $zipName): void {
            set_time_limit(0);

            $zip = new ZipStream(
                outputName: $zipName,
                sendHttpHeaders: false,
            );

            foreach ($sections as $section) {
                $sectionFolder = $hasMultipleSections
                    ? $galleryFolder . '/' . $section->name
                    : $galleryFolder;

                $maxPosition = $section->photos->count();
                $paddingLength = max(2, strlen((string) $maxPosition));

                foreach ($section->photos as $photo) {
                    $filePath = storage_path('app/private/photos/' . $photo->path);

                    if (! file_exists($filePath)) {
                        Log::warning("File not found: {$filePath}");

                        continue;
                    }

                    $position = str_pad((string) $photo->position, $paddingLength, '0', STR_PAD_LEFT);

                    $filename = $hasMultipleSections
                        ? "{$position} - {$section->name}.jpg"
                        : "{$position}.jpg";

                    $zip->addFileFromPath("{$sectionFolder}/{$filename}", $filePath);
                }
            }

            $zip->finish();
            set_time_limit(30);
        }, $zipName);
    }

    public function showPhoto(string $gallery, string $photo)
    {
        if (! session('authenticated_gallery_' . $gallery) && ! auth()->check()) {
            Log::info('User not authenticated for gallery: ' . $gallery);

            return redirect()->route('public.select');
        }
        $photo = Photo::where('path', $gallery . '/' . $photo)
            ->where('photo_gallery_id', $gallery)
            ->firstOrFail();

        return Storage::disk('photo')->response($photo->path);
    }

    public function showThumbnail(string $gallery, string $photo)
    {
        if (! session('authenticated_gallery_' . $gallery) && ! auth()->check()) {
            Log::info('User not authenticated for gallery: ' . $gallery);

            return redirect()->route('public.select');
        }
        $photo = Photo::where('path', $gallery . '/' . $photo)
            ->where('photo_gallery_id', $gallery)
            ->firstOrFail();
        if (Storage::disk('thumbnails')->exists($photo->path)) {
            return Storage::disk('thumbnails')->response($photo->path);
        }

        return abort(404, 'Thumbnail not found');
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

        return redirect()->route('public.gallery', $accessCode);
    }

    private function gallerySessionKey(PhotoGallery $photoGallery): string
    {
        return 'authenticated_gallery_' . $photoGallery->id;
    }
}
