<?php

namespace App\Modules\Communication\Application\Services;

use App\Models\Communication\CommAttachment;
use App\Models\Communication\CommConversation;
use App\Models\Communication\CommMessage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class AttachmentService
{
    public function storeForMessage(CommConversation $conversation, CommMessage $message, UploadedFile $file): CommAttachment
    {
        $type = $this->detectType($file);
        $name = Str::uuid().'_'.preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
        $path = storage_path('app/comm/uploads/'.$name);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $file->move(dirname($path), basename($path));

        return CommAttachment::create([
            'message_id' => $message->id,
            'conversation_id' => $conversation->id,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'message_type' => $type,
        ]);
    }

    private function detectType(UploadedFile $file): string
    {
        $mime = $file->getMimeType() ?? '';

        if (str_starts_with($mime, 'image/')) {
            return 'image';
        }
        if (str_starts_with($mime, 'video/')) {
            return 'video';
        }
        if (str_starts_with($mime, 'audio/')) {
            return 'voice';
        }

        return 'file';
    }
}
