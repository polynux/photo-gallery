<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use App\Models\PhotoGallery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipStream\ZipStream;

class PublicController extends Controller
{
    public function show($access_code)
    {
        $photoGallery = PhotoGallery::where('access_code', $access_code)->firstOrFail();
        if (! $photoGallery) {
            return back()->withErrors(['access_code' => 'Cette galerie n\'existe pas']);
        }
        if (session('authenticated_gallery_' . $photoGallery->id)) {
            return redirect()->route('public.gallery', $access_code);
        }

        return view('public.login', compact('photoGallery'));
    }

    public function showForm()
    {
        return view('public.gallery-select');
    }

    public function authenticate(Request $request, $access_code)
    {
        $photoGallery = PhotoGallery::where('access_code', $access_code)->firstOrFail();

        if ($request->password === $photoGallery->password) {
            session(['authenticated_gallery_' . $photoGallery->id => true]);

            return redirect()->route('public.gallery', $access_code);
        }

        return back()->withErrors(['password' => 'Mot de passe incorrect']);
    }

    public function authenticateSelect(Request $request)
    {
        $request->validate([
            'access_code' => 'required|exists:photo_galleries,access_code',
        ]);

        $photoGallery = PhotoGallery::where('access_code', $request->access_code)->firstOrFail();
        if (! $photoGallery) {
            return back()->withErrors(['access_code' => 'Cette galerie n\'existe pas']);
        }

        if ($request->password === $photoGallery->password) {
            session(['authenticated_gallery_' . $photoGallery->id => true]);

            return redirect()->route('public.gallery', $request->access_code);
        }

        return back()->withErrors(['password' => 'Mot de passe incorrect']);
    }

    public function gallery($access_code)
    {
        $photoGallery = PhotoGallery::where('access_code', $access_code)
            ->with(['sections' => function ($query) {
                $query->orderBy('position')->with(['photos' => function ($q) {
                    $q->orderBy('position');
                }]);
            }])
            ->firstOrFail();

        if (! session('authenticated_gallery_' . $photoGallery->id)) {
            return redirect()->route('public.show', $access_code);
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

        return view('public.gallery', compact('photoGallery', 'slideshowData'));
    }

    public function download($access_code)
    {
        $photoGallery = PhotoGallery::where('access_code', $access_code)
            ->with(['sections' => function ($query) {
                $query->orderBy('position')->with(['photos' => function ($q) {
                    $q->orderBy('position');
                }]);
            }])
            ->firstOrFail();

        if (! session('authenticated_gallery_' . $photoGallery->id)) {
            return redirect()->route('public.show', $access_code);
        }

        $zipName = Str::slug($photoGallery->name) . '.zip';

        set_time_limit(0);

        $zip = new ZipStream(
            outputName: $zipName,
            sendHttpHeaders: true,
        );

        $galleryFolder = $photoGallery->name;
        $sections = $photoGallery->sections;
        $hasMultipleSections = $sections->count() > 1 || $sections->first()?->is_default === false;
        $totalPhotos = $sections->sum(fn ($section) => $section->photos->count());
        $paddingLength = max(3, strlen((string) $totalPhotos));

        $globalPosition = 1;
        foreach ($sections as $section) {
            $sectionFolder = $hasMultipleSections
                ? $galleryFolder . '/' . $section->name
                : $galleryFolder;

            foreach ($section->photos as $photo) {
                $filePath = storage_path('app/private/photos/' . $photo->path);

                if (! file_exists($filePath)) {
                    Log::warning("File not found: {$filePath}");

                    continue;
                }

                $position = str_pad($globalPosition, $paddingLength, '0', STR_PAD_LEFT);

                $filename = $hasMultipleSections
                    ? "{$position} - {$section->name}.jpg"
                    : "{$position}.jpg";

                $zip->addFileFromPath("{$sectionFolder}/{$filename}", $filePath);

                $globalPosition++;
            }
        }

        $zip->finish();

        set_time_limit(30);
        exit;
    }

    public function showPhoto($gallery, $photo)
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

    public function showThumbnail($gallery, $photo)
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
}
