<div class="h-full w-full">
    <div
        x-data="stlPreviewer({
            url: @js($fileUrl),
            type: @js($fileType)
        })"
        class="relative h-full w-full flex items-center justify-center overflow-hidden bg-black"
    >
        <div x-show="isLoading" class="absolute inset-0 flex items-center justify-center bg-black/80 z-20 text-white gap-3 backdrop-blur-sm">
            <flux:icon.arrow-path class="animate-spin size-5" />
            <span class="text-sm font-medium">Loading Preview...</span>
        </div>

        <template x-if="type === 'image'">
            <img
                :src="url"
                class="h-full w-auto max-w-full object-contain mx-auto shadow-2xl rounded-xl border border-zinc-800"
                alt="File preview"
            >
        </template>

        <template x-if="type === 'stl'">
            <div x-ref="canvasContainer" class="w-full h-full block"></div>
        </template>

        <template x-if="type === 'unknown'">
            <div class="w-full h-full flex flex-col items-center justify-center text-zinc-500 gap-3">
                <flux:icon.document-magnifying-glass class="size-12 opacity-50" />
                <span class="text-sm">No preview available for this format</span>
            </div>
        </template>
    </div>
</div>

@assets
@vite(['resources/js/three.js'])
@endassets
