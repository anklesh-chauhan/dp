<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\DMS\Services\ControlledDocumentAccessService;
use App\Models\ControlledDocument;
use App\Models\DocumentOriginalArtifact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

final class ControlledDocumentOriginalArtifactController extends Controller
{
    public function __construct(private readonly ControlledDocumentAccessService $accessService) {}

    public function __invoke(Request $request, ControlledDocument $controlledDocument, DocumentOriginalArtifact $artifact): Response
    {
        abort_unless($artifact->controlled_document_id === $controlledDocument->getKey(), 404);
        abort_unless(Storage::disk($artifact->disk)->exists($artifact->path), 404);

        $mode = (string) $request->route('artifact_access_mode', 'view');
        $allowed = match ($mode) {
            'print' => $this->accessService->canPrint($request->user(), $controlledDocument),
            'download' => $this->accessService->canDownload($request->user(), $controlledDocument),
            default => $this->accessService->canView($request->user(), $controlledDocument),
        };
        abort_unless($allowed, 403, 'You do not have permission to access this original file.');

        $path = $mode === 'download' || $artifact->preview_path === null ? $artifact->path : $artifact->preview_path;
        $filename = $mode === 'download' ? $artifact->original_name : pathinfo($artifact->original_name, PATHINFO_FILENAME).'.pdf';

        if ($mode === 'download') {
            return Storage::disk($artifact->disk)->download($path, $filename, [
                'Content-Type' => $artifact->mime_type ?? 'application/octet-stream',
            ]);
        }

        return response()->stream(function () use ($artifact, $path): void {
            $stream = Storage::disk($artifact->disk)->readStream($path);
            if ($stream === false) {
                return;
            }
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $artifact->preview_path !== null ? 'application/pdf' : ($artifact->mime_type ?? 'application/octet-stream'),
            'Content-Disposition' => 'inline; filename="'.addslashes($filename).'"',
        ]);
    }
}
