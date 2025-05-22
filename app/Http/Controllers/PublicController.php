<?php

namespace App\Http\Controllers;

use App\Models\PhotoGallery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipStream\ZipStream;
use Illuminate\Support\Str;

class PublicController extends Controller
{
    public function show($access_code)
    {
        $photoGallery = PhotoGallery::where('access_code', $access_code)->firstOrFail();
        if (!$photoGallery) {
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
        if (!$photoGallery) {
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
        $photoGallery = PhotoGallery::where('access_code', $access_code)->firstOrFail();

        if (!session('authenticated_gallery_' . $photoGallery->id)) {
            return redirect()->route('public.show', $access_code);
        }

        $photos = $photoGallery->photos()->orderBy('order')->get();
        return view('public.gallery', compact('photoGallery', 'photos'));
    }

    public function download($access_code)
    {
        $photoGallery = PhotoGallery::where('access_code', $access_code)->firstOrFail();

        if (!session('authenticated_gallery_' . $photoGallery->id)) {
            return redirect()->route('public.show', $access_code);
        }

        // Create the temp directory if it doesn't exist
        if (!Storage::exists('temp')) {
            Storage::makeDirectory('temp');
        }

        $zipName = Str::slug($photoGallery->name) . '.zip';

        $zip = new ZipStream(
            outputName: $zipName,
            sendHttpHeaders: true,
        );

        foreach ($photoGallery->photos as $photo) {
            $filePath = storage_path('app/public/' . $photo->path);

            // Check if the file exists before adding it
            if (file_exists($filePath)) {
                // Use a clean filename based on the alt text or a default name
                $filename = $photo->alt
                    ? Str::slug($photo->alt) . '.jpg'
                    : 'photo_' . $photo->id . '.jpg';

                $zip->addFileFromPath($filename, $filePath);
            } else {
                Log::warning("File not found: $filePath");
            }
        }

        $zip->finish();
        exit;
    }
}
