<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

trait FileHelper
{

    
    function upload(UploadedFile $file, string $path, string $filename = null)
    {
        if ($file == null) {
            Log::error('The file object cannot be null');
            return null;
        }
        $extension = $file->getClientOriginalExtension();
        if ($filename == null) {
            $filename = uniqid() . "." . $extension;
        } else {
            $filenameParts = explode('/', $filename);
            $filename = $filenameParts[sizeof($filenameParts) - 1];
        }
        $file->move($path, $filename);
        return $filename;
    }

   
    function download(string $path, string $filename)
    {
        if ($path == null) {
            Log::error('The path cannot be null to download file from server');
            return response()->json(['status' => 'error', 'message' => 'The path cannot be null to download file from server']);
        }
        return response()->download($path . '/' . $filename, $filename, ['Content-Type : ' . $this->getMimeType($path . '/' . $filename)]);
    }

    
    function getMimeType(string $fullPath)
    {
        return File::mimeType($fullPath);
    }

}