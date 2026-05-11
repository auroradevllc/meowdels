<div class="max-w-2xl">
    <form wire:submit.prevent="save" class="space-y-6">

        <div class="grid grid-cols-1 gap-6">
            <flux:input
                wire:model="name"
                label="{{ __('Model Name') }}"
                placeholder="e.g. Articulated Dragon"
                required
            />

            <div class="grid grid-cols-2 gap-4">
                <flux:input
                    wire:model="author"
                    label="{{ __('Author') }}"
                    placeholder="Username"
                />

                <flux:select wire:model="license" label="{{ __('License') }}">
                    <option value="">Select a license</option>
                    <option value="cc-by">Creative Commons - Attribution</option>
                    <option value="cc-by-sa">Creative Commons - Share Alike</option>
                    <option value="cc-by-nd">Creative Commons - No Derivatives</option>
                    <option value="cc-by-nc">Creative Commons - Non Commercial</option>
                    <option value="cc-by-nc-sa">Creative Commons - Non Commercial - Share Alike</option>
                    <option value="cc-by-nc-nd">Creative Commons - Non Commercial - No Derivatives</option>
                    <option value="cc-0">Creative Commons - Public Domain Dedication</option>
                    <option value="gpl">GNU - GPL</option>
                    <option value="lgpl">GNU - LGPL</option>
                    <option value="bsd">BSD License</option>
                </flux:select>
            </div>

            <div class="block min-h-[100px] w-full">
                <flux:textarea wire:model="description" label="Description" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:checkbox.group variant="cards" class="max-sm:flex-col">
                    <flux:checkbox checked
                                   wire:model="is_publici"
                                   value="public"
                                   label="Public"
                                   description="Allow all logged in users to view this model"
                    />
                </flux:checkbox.group>
            </div>
        </div>

        <flux:field>
            <flux:label>{{ __('Model Files (3D or ZIP)') }}</flux:label>

            <div
                x-data="{
                    isUploading: false,
                    progress: 0,
                    isDropping: false,
                    async onFileChange(event) {
                        const files = Array.from(event.target.files);
                        if (!files.length) return;

                        this.isProcessing = true;
                        try {
                            await handleHybridUpload(files, @this, 'files');
                        } catch (err) {
                            console.error('Upload error:', err);
                            alert('Something went wrong during the upload.');
                        } finally {
                            this.isProcessing = false;
                            event.target.value = ''; // Reset input
                        }
                    }
                }"
                x-on:livewire-upload-start="isUploading = true"
                x-on:livewire-upload-finish="isUploading = false; isDropping = false"
                x-on:livewire-upload-error="isUploading = false; isDropping = false"
                x-on:livewire-upload-progress="progress = $event.detail.progress"
                class="relative"
            >
                <label
                    for="file-upload"
                    :class="isDropping ? 'border-accent-500 bg-accent-50' : 'border-zinc-200 dark:border-zinc-700'"
                    class="flex flex-col items-center justify-center w-full h-40 border-2 border-dashed rounded-xl cursor-pointer hover:bg-zinc-50 dark:hover:bg-white/5 transition-colors"
                    @dragover.prevent="isDropping = true"
                    @dragleave.prevent="isDropping = false"
                    @drop="isDropping = false"
                >
                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                        <flux:icon.cloud-arrow-up class="mb-3 size-8 text-zinc-400" />
                        <p class="mb-2 text-sm text-zinc-500 dark:text-zinc-400">
                            <span class="font-semibold">Click to upload</span> or drag and drop
                        </p>
                        <p class="text-xs text-zinc-400">STL, 3MF, Images, or ZIP (Max 100MB)</p>
                    </div>

                    <input id="file-upload"
                           type="file"
                           class="hidden"
                           multiple
                           accept=".stl,.3mf,.zip,.jpg,.jpeg,.png,.zip"
                           @change="onFileChange" />
                </label>

                <div x-show="isUploading" class="mt-4">
                    <flux:text size="xs" class="mb-1">Uploading... <span x-text="progress + '%'"></span></flux:text>
                    <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-1.5 overflow-hidden">
                        <div
                            class="bg-accent-500 h-1.5 transition-all duration-200"
                            :style="`width: ${progress}%`"
                        ></div>
                    </div>
                </div>

                @error('files') <flux:error>{{ $message }}</flux:error> @enderror
                @error('files.*') <flux:error>{{ $message }}</flux:error> @enderror
            </div>

            @if ($files)
                <div class="mt-4 space-y-2">
                    @foreach ($files as $index => $file)
                        @continue(str_ends_with($file->getClientOriginalName(), '-render.png'))

                        <div class="flex items-center justify-between p-3 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="size-16 shrink-0">
                                    @if (in_array($file->getClientOriginalExtension(), ['jpg', 'jpeg', 'png', 'gif']))
                                        <img src="{{ $file->temporaryUrl() }}" class="object-cover size-16 rounded-lg">
                                    @elseif (in_array($file->getClientOriginalExtension(), config('meowdels.file_types')))
                                        @if ($renderUrl = $this->getRenderUrl($file))
                                            <img src="{{ $renderUrl }}" class="object-cover size-16 rounded-lg">
                                        @else
                                            <div class="size-16 bg-zinc-200 dark:bg-zinc-800 rounded-lg flex items-center justify-center">
                                                <flux:icon.cube-transparent class="size-8 text-zinc-500" />
                                            </div>
                                        @endif
                                    @endif
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                        {{ $file->getClientOriginalName() }}
                                    </span>
                                    <span class="text-xs text-zinc-500">
                                        {{ round($file->getSize() / 1024 / 1024, 2) }} MB
                                    </span>
                                </div>
                            </div>

                            <flux:button
                                variant="ghost"
                                size="sm"
                                icon="x-mark"
                                wire:click="removeFile({{ $index }})"
                            />
                        </div>
                    @endforeach
                </div>
            @endif
        </flux:field>

        <div class="flex justify-end border-t border-zinc-200 dark:border-zinc-800 pt-6">
            <flux:button variant="primary" type="submit" icon="check">
                {{ __('Publish Model') }}
            </flux:button>
        </div>
    </form>

    <script>
        let fileTypes = @js(config('meowdels.file_types'));
        let licenseMap = @js(config('meowdels.license_map'));
        async function handleHybridUpload(inputFiles, lwComponent, propertyName) {
            const zip = new JSZip();
            let finalQueue = [];

            for (const file of inputFiles) {
                const isZip = file.name.toLowerCase().endsWith('.zip') || file.type === 'application/zip';

                if (isZip) {
                    try {
                        const zipContent = await zip.loadAsync(file);
                        const extracted = await Promise.all(
                            Object.entries(zipContent.files)
                                .filter(([name, entry]) => !entry.dir && !name.startsWith('__MACOSX/'))
                                .map(async ([name, entry]) => {
                                    const fileName = name.split('/').pop();

                                    // Check specifically for README.txt
                                    if (fileName.toLowerCase() === 'readme.txt') {
                                        const text = await entry.async('string');

                                        const currentDesc = await lwComponent.get('description');
                                        if (!currentDesc || currentDesc.trim() === '') {
                                            lwComponent.set('description', text.trim());
                                        }

                                        let m = text.match(/by (.*?) on (thingiverse)/i);

                                        if (m) {
                                            const currentAuthor = await lwComponent.get('author');
                                            if (!currentAuthor || currentAuthor.trim() === '') {
                                                lwComponent.set('author', m[1]);
                                            }
                                        }
                                    } else if (fileName.toLowerCase() === 'license.txt') {
                                        const rawText = await entry.async('string');

                                        let license = await detectLicense(rawText);

                                        if (license) {
                                            const currentLicense = await lwComponent.get('license');
                                            if (!currentLicense) {
                                                lwComponent.set('license', licenseMap[license]);
                                            }
                                        }
                                    }

                                    if (fileName.indexOf(".txt") !== -1) {
                                        return null;
                                    }

                                    const blob = await entry.async('blob');
                                    return new File([blob], name.split('/').pop());
                                })
                        );

                        finalQueue.push(...extracted.filter(Boolean));

                        const renderableFiles = files.filter(file =>
                            file.name.toLowerCase().endsWith('.stl') ||
                            file.name.toLowerCase().endsWith('.3mf')
                        );

                        const renderPromises = await Promise.all(renderableFiles.map(async (file) => {
                            const blob = await window.renderPreview(file, 1024);

                            // Return an object that links the original name to the new File
                            return new File(
                                [blob],
                                file.name.replace(/\.(stl|3mf)/, '-render.png'),
                                { type: 'image/png' }
                            );
                        }));

                        finalQueue.push(...renderPromises);
                    } catch (e) {
                        console.error("ZIP Error:", e);
                    }
                } else {
                    finalQueue.push(file);

                    const converted = await window.renderPreview(file);
                    finalQueue.push(new File([converted], file.name.replace(/\.stl$/, '-render.png'), { type: 'image/png' }));
                }
            }

            if (finalQueue.length === 0) return;

            const modelFile = finalQueue.find(f => {
                let ext = f.name.toLowerCase();
                ext = ext.substring(ext.lastIndexOf('.')+1);
                return fileTypes.includes(ext);
            });

            if (modelFile) {
                const currentName = await lwComponent.get('name');

                if (!currentName || currentName.trim() === '') {
                    let cleanName = modelFile.name
                        .replace(/\.(stl|step|3mf|zip)$/i, "") // Remove extensions
                        .replace(/[_-]/g, " ")             // Spaces instead of underscores/dashes
                        .replace(/\b\w/g, l => l.toUpperCase()) // Capitalize
                        .trim();

                    lwComponent.set('name', cleanName);
                }
            }

            return new Promise((resolve, reject) => {
                lwComponent.uploadMultiple(propertyName, finalQueue, resolve, reject);
            });
        }

        /**
         * Maps extracted text to Thingiverse-supported license slugs.
         */
        async function detectLicense(text) {
            const lowerText = text.toLowerCase();

            // Normalize text for matching (remove spaces to handle variations)
            const normalizedContent = lowerText.replace(/[\s]/g, '-');

            return Object.keys(licenseMap).find(key => normalizedContent.includes(key));
        }
    </script>
</div>

