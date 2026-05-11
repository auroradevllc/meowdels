<div class="space-y-8" x-data="{
    activeUrl: @entangle('activeUrl').live,
    activeType: @entangle('activeType').live,
}">
    <flux:card class="p-0 overflow-hidden border-none shadow-lg">
        <div class="flex h-125 w-full bg-zinc-900">
            <div class="w-20 md:w-24 shrink-0 border-r border-white/10 bg-zinc-950/50 flex flex-col gap-2 p-2 overflow-y-auto custom-scrollbar">
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
                        class="aspect-square shrink-0 w-full rounded-lg bg-zinc-800 border-2 border-transparent hover:border-zinc-500 focus:border-primary-500 transition-all overflow-hidden flex items-center justify-center p-1">

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

            <div id="three-js-container" class="grow relative flex items-center justify-center bg-[radial-gradient(circle_at_center,_var(--tw-gradient-stops))] from-zinc-900 via-zinc-950 to-black">
                <div class="h-full w-full max-w-full flex items-center justify-center">
                    <livewire:previewer
                        :file-url="$activeUrl"
                        :file-type="$activeType"
                        :key="$activeUrl"
                    />
                </div>

                <div
                    x-show="activeType === 'stl'"
                    class="absolute bottom-8 right-8 bg-black/60 px-4 py-2 rounded-xl text-xs font-bold text-white/90 backdrop-blur-md border border-white/10 shadow-2xl"
                >
                    Interactive Preview
                </div>
            </div>
        </div>
    </flux:card>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
        <div class="lg:col-span-8">
            <div class="rounded-xl border-l-4 border border-zinc-200 dark:border-zinc-800 border-l-primary-500 bg-zinc-50/30 dark:bg-zinc-900/30 p-6">
                <header class="border-b border-zinc-200 dark:border-zinc-800 pb-4">
                    <flux:heading size="xl" level="1">{{ $model->name }}</flux:heading>
                    @if (!$model->authors->isEmpty())
                    <flux:text size="sm" class="mt-1">
                        by <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $model->present()->authorName() }}</span>
                    </flux:text>
                    @endif
                </header>

                @if($model->tags)
                    <div class="mb-8 border-t border-zinc-200 dark:border-zinc-800 flex flex-wrap gap-2">
                        @foreach($model->tags as $tag)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-zinc-200/50 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 border border-zinc-300/50 dark:border-zinc-700">
                                {{ $tag->name }}
                            </span>
                        @endforeach
                    </div>
                @endif

                <div class="mb-8 border-t">
                    <livewire:collection-add :model="$model" />
                </div>

                <div class="prose dark:prose-invert max-w-none">
                    @markdown($model->description)
                </div>
            </div>
        </div>

        <div class="lg:col-span-4 space-y-6">
            <div class="space-y-4">
                <div class="bg-zinc-50 dark:bg-zinc-800/50 p-4 rounded-xl space-y-3 border border-zinc-200/50 dark:border-zinc-700/50 shadow-sm">
                    <div class="flex justify-between text-sm">
                        <span class="text-zinc-500">License</span>
                        <span class="font-medium text-zinc-800 dark:text-zinc-200 text-right ml-4">{{ $model->license }}</span>
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
            </div>

            <div class="lg:col-span-5 space-y-6">
                <div class="space-y-3">
                    <flux:heading size="sm" class="px-1 text-zinc-500 uppercase tracking-wider">
                        Files
                    </flux:heading>

                    <flux:button variant="primary" icon="arrow-down-tray" class="w-full shadow-sm" href="{{ route('models.download', $model->slug) }}">
                        Download All Files
                    </flux:button>

                    <flux:card class="p-2 shadow-sm">
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column class="px-4">File</flux:table.column>
                                <flux:table.column align="end" class="pr-4">Actions</flux:table.column>
                            </flux:table.columns>

                            <flux:table.rows>
                                @foreach($model->previewableMedia() as $file)
                                    <flux:table.row>
                                        <flux:table.cell>
                                            <div class="flex items-center gap-3 py-2 pl-4">
                                                <div class="size-10 flex-shrink-0 rounded-lg bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 overflow-hidden flex items-center justify-center shadow-sm">
                                                    @if($file->hasGeneratedConversion('thumb'))
                                                        <img src="{{ $file->getUrl('thumb') }}" alt="" class="object-cover size-full">
                                                    @elseif ($previewUrl = $model->getRenderUrl($file))
                                                        <img src="{{ $previewUrl }}" alt="" class="object-cover size-full">
                                                    @else
                                                        <flux:icon.cube class="size-5 text-zinc-400" />
                                                    @endif
                                                </div>

                                                <div class="flex flex-col min-w-0">
                                                    <span class="font-semibold text-sm text-zinc-800 dark:text-zinc-200 leading-tight break-words">
                                                        {{ $file->file_name }}
                                                    </span>
                                                    <span class="text-[10px] text-zinc-500 uppercase font-bold tracking-tight">
                                                        {{ Number::fileSize($file->size) }}
                                                    </span>
                                                </div>
                                            </div>
                                        </flux:table.cell>

                                        <flux:table.cell align="end" class="pr-4">
                                            <div class="flex items-center justify-end gap-1">
                                                <flux:button
                                                    variant="ghost"
                                                    size="sm"
                                                    icon="arrow-down-tray"
                                                    class="text-zinc-400 hover:text-primary-600 dark:hover:text-primary-400"
                                                    href="{{ $file->getUrl() }}"
                                                />
                                                <flux:button
                                                    variant="ghost"
                                                    size="sm"
                                                    icon="printer"
                                                    class="text-zinc-400 hover:text-primary-600 dark:hover:text-primary-400"
                                                    title="Send to Slicer"
                                                    href="orcaslicer://open?url={{ urlencode($file->getUrl()) }}"
                                                />
                                            </div>
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    </flux:card>
                </div>
            </div>
        </div>
    </div>
</div>
