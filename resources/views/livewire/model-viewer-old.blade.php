<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 p-6 bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800">
    <div class="lg:col-span-2 space-y-4" x-data="{
    activeUrl: @entangle('activeUrl').live,
    activeType: @entangle('activeType').live,
}">
        <div>
            <livewire:previewer
                :file-url="$activeUrl"
                :file-type="$activeType"
                :key="$activeUrl"
            />
        </div>

        <div class="flex gap-3 overflow-x-auto pb-2 scrollbar-hide">
            @foreach($model->previewableMedia() as $file)
                @php
                    $url = $file->getUrl();
                    $extension = pathinfo($file->file_name, PATHINFO_EXTENSION);
                    $type = in_array($extension, config('meowdels.file_types')) ? 'stl' : 'image';
                @endphp

                <button
                    type="button"
                    @click="activeUrl = '{{ $url }}'; activeType = '{{ $type }}'"
                    :class="activeUrl === '{{ $url }}' ? 'border-accent-500 scale-105' : 'border-transparent opacity-70 hover:opacity-100'"
                    class="relative size-20 shrink-0 rounded-lg overflow-hidden border-2 transition-all"
                >
                    @if($type === 'image')
                        <img src="{{ $url }}" class="object-cover w-full h-full">
                    @elseif($renderUrl = $model->getRenderUrl($file))
                        <img src="{{ $renderUrl }}" class="object-cover w-full h-full">
                    @else
                        <div class="flex flex-col items-center justify-center h-full text-xs text-white">
                            <flux:icon.cube class="size-6 mb-1" />
                            <span>{{ strtoupper($extension) }}</span>
                        </div>
                    @endif
                </button>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col space-y-6">
        <div>
            <flux:heading size="xl">{{ $model->name }}</flux:heading>
            <flux:text size="sm" class="flex items-center gap-2 mt-1">
                <flux:icon.user variant="micro" /> {{ $model->authors->pluck('name')->join(', ') }}
            </flux:text>
        </div>

        <flux:separator variant="subtle" />

        <div class="flex-1">
            <flux:heading size="sm" class="mb-2">Description</flux:heading>
            <flux:text class="leading-relaxed">
                @markdown($model->description ?: 'No description provided for this model.')
            </flux:text>
        </div>

        <div class="bg-zinc-50 dark:bg-zinc-800/50 p-4 rounded-xl space-y-3">
            <div class="flex justify-between text-sm">
                <span class="text-zinc-500">License</span>
                <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $model->license }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-zinc-500">File Format</span>
                <span class="font-medium uppercase">{{ $model->present()->availableFileTypes() }}</span>
            </div>
            @if ($model->url)
                <div class="flex justify-between text-sm">
                    <span class="text-zinc-500">Source</span>
                    <span class="font-medium text-zinc-800 dark:text-zinc-200"><a href="{{ $model->url }}">{{ $model->present()->sourceName() }}</a></span>
                </div>
            @endif
        </div>

        <flux:button variant="primary" icon-leading="cloud-arrow-down" class="w-full"
        href="{{ route('models.download', $model->slug) }}">
            Download Files
        </flux:button>
    </div>
</div>
