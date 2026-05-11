<div class="max-w-2xl">
    <form wire:submit.prevent="import" class="space-y-6">
        <div class="space-y-2">
            <flux:input
                wire:model="url"
                label="{{ __('Model URL') }}"
                placeholder="https://example.com/model.stl"
                :invalid="$errors->has('url')"
            />
            @error('url') <flux:error>{{ $message }}</flux:error> @enderror
        </div>

        <div class="flex items-center justify-between pt-2">
            <div class="flex items-center h-10">
                <div wire:loading.flex wire:target="import" class="items-center text-sm text-zinc-500 dark:text-zinc-400">
                    <flux:icon.arrow-path class="mr-2 size-4 animate-spin shrink-0" />
                    <span class="font-medium leading-none whitespace-nowrap" wire:stream="import-status">
                        {{ $status }}
                    </span>
                </div>
            </div>

            <flux:button
                type="submit"
                variant="primary"
                wire:loading.attr="disabled"
            >
                {{ __('Import Model') }}
            </flux:button>
        </div>
    </form>
</div>

@script
<script>
    $wire.on('import-render', async (event) => {
        const { id, files } = event[0];

        try {
            const renderPromises = await Promise.all(files.map(async (file) => {
                const response = await fetch(file.url);
                const fileData = await response.blob();

                const modelFile = new File([fileData], file.name);

                const blob = await window.renderPreview(modelFile, 1024);

                // Return an object that links the original name to the new File
                return new File(
                    [blob],
                    file.name.replace(/\.(stl|3mf)/, '.png'),
                    { type: 'image/png' }
                );
            }));

            $wire.uploadMultiple('renderImages', renderPromises,
                (uploadedUrls) => {
                    // Success callback: Trigger a save or finalize method
                    $wire.handleRenderUploads(id);
                },
                (error) => {
                    // Error callback
                    console.error('Upload failed', error);
                },
                (event) => {
                    // Progress callback (event.detail.progress)
                }
            );
        } catch (error) {
            console.error('Client-side rendering failed:', error);
        }
    });
</script>
@endscript
