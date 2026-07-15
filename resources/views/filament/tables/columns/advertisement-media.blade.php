@php
    /** @var \App\Models\Advertisement $record */
    $record = $getRecord();
    $url = \Illuminate\Support\Facades\Storage::disk('public')->url($record->media_path);
@endphp
<div class="fi-ad-media-preview flex aspect-video w-full items-center justify-center overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-800">
    @if ($record->type === \App\Enums\AdvertisementType::Image)
        <img
            src="{{ $url }}"
            alt=""
            class="max-h-full max-w-full object-contain p-2"
            loading="lazy"
        />
    @else
        <video
            src="{{ $url }}"
            class="max-h-full max-w-full bg-black object-contain"
            controls
            muted
            playsinline
        ></video>
    @endif
</div>
