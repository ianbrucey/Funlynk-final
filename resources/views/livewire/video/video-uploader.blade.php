<div 
    x-data="videoUploader(@js([
        'maxFileSize' => $maxFileSize,
        'maxDuration' => $maxDuration,
        'allowedMimes' => $allowedMimes,
    ]))"
    class="w-full"
>
    {{-- Upload Zone --}}
    <div 
        x-show="!isUploading && !isProcessing && !uploadedVideo"
        x-on:drop.prevent="handleDrop($event)"
        x-on:dragover.prevent="isDragging = true"
        x-on:dragleave.prevent="isDragging = false"
        :class="{ 'border-cyan-500 bg-cyan-500/10': isDragging }"
        class="relative p-8 glass-card border-2 border-dashed border-white/20 rounded-2xl text-center cursor-pointer hover:border-cyan-500/50 transition-all duration-300"
    >
        <div class="top-accent-center"></div>
        
        <input 
            type="file" 
            x-ref="fileInput"
            x-on:change="handleFileSelect($event)"
            accept="video/mp4,video/webm,video/quicktime,video/x-msvideo"
            class="hidden"
        />

        <div class="py-8" x-on:click="$refs.fileInput.click()">
            {{-- Icon --}}
            <div class="mb-4 inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-r from-pink-500/20 to-purple-500/20 border border-white/10">
                <svg class="w-8 h-8 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
            </div>

            <h3 class="text-lg font-semibold text-white mb-2">
                Upload Video
            </h3>
            <p class="text-gray-400 text-sm mb-4">
                Drag and drop or click to select
            </p>
            <p class="text-gray-500 text-xs">
                MP4, WebM, MOV, AVI • Max {{ number_format($maxFileSize / 1048576) }}MB • Max {{ $maxDuration }}s
            </p>
        </div>
    </div>

    {{-- Uploading State --}}
    <div 
        x-show="isUploading"
        x-cloak
        class="relative p-6 glass-card rounded-2xl"
    >
        <div class="top-accent-center"></div>
        
        <div class="flex items-center gap-4">
            {{-- Video Preview --}}
            <div class="w-24 h-24 rounded-xl bg-slate-800/50 overflow-hidden flex-shrink-0">
                <video 
                    x-ref="videoPreview"
                    class="w-full h-full object-cover"
                    muted
                ></video>
            </div>

            <div class="flex-1 min-w-0">
                <h4 class="text-white font-medium truncate" x-text="fileName"></h4>
                <p class="text-gray-400 text-sm" x-text="formatBytes(fileSize)"></p>

                {{-- Progress Bar --}}
                <div class="mt-3 h-2 bg-slate-700/50 rounded-full overflow-hidden">
                    <div 
                        class="h-full bg-gradient-to-r from-pink-500 to-purple-500 transition-all duration-300"
                        :style="`width: ${uploadProgress}%`"
                    ></div>
                </div>
                <p class="mt-1 text-xs text-gray-500">
                    Uploading... <span x-text="uploadProgress"></span>%
                </p>
            </div>

            {{-- Cancel Button --}}
            <button 
                x-on:click="cancelUpload()"
                class="p-2 text-gray-400 hover:text-red-400 transition"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Processing State --}}
    <div 
        x-show="isProcessing"
        x-cloak
        class="relative p-6 glass-card rounded-2xl"
    >
        <div class="top-accent-center"></div>
        
        <div class="flex items-center gap-4">
            {{-- Spinning Loader --}}
            <div class="w-16 h-16 rounded-full bg-gradient-to-r from-pink-500/20 to-purple-500/20 flex items-center justify-center">
                <svg class="w-8 h-8 text-cyan-400 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>

            <div class="flex-1">
                <h4 class="text-white font-medium">Processing Video</h4>
                <p class="text-gray-400 text-sm" x-text="processingStage || 'Transcoding...'"></p>

                {{-- Progress Bar --}}
                <div class="mt-3 h-2 bg-slate-700/50 rounded-full overflow-hidden">
                    <div 
                        class="h-full bg-gradient-to-r from-cyan-500 to-blue-500 transition-all duration-300"
                        :style="`width: ${processingProgress}%`"
                    ></div>
                </div>
                <p class="mt-1 text-xs text-gray-500">
                    <span x-text="processingProgress"></span>% complete
                </p>
            </div>
        </div>
    </div>

    {{-- Success State --}}
    <div 
        x-show="uploadedVideo"
        x-cloak
        class="relative p-6 glass-card rounded-2xl"
    >
        <div class="top-accent-center"></div>
        
        <div class="flex items-center gap-4">
            {{-- Success Icon --}}
            <div class="w-16 h-16 rounded-full bg-green-500/20 flex items-center justify-center">
                <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>

            <div class="flex-1">
                <h4 class="text-white font-medium">Video Ready!</h4>
                <p class="text-gray-400 text-sm">Your video is ready to be shared</p>
            </div>

            {{-- Replace Button --}}
            <button 
                x-on:click="reset()"
                class="px-4 py-2 bg-slate-800/50 border border-white/10 rounded-xl text-sm text-gray-300 hover:border-cyan-500/50 transition"
            >
                Replace
            </button>
        </div>
    </div>

    {{-- Error State --}}
    <div 
        x-show="uploadError"
        x-cloak
        class="mt-4 p-4 bg-red-500/10 border border-red-500/20 rounded-xl"
    >
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-red-400 text-sm" x-text="uploadError"></p>
            <button 
                x-on:click="uploadError = null; reset()"
                class="ml-auto text-red-400 hover:text-red-300"
            >
                Try Again
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('videoUploader', (config) => ({
        // Config
        maxFileSize: config.maxFileSize,
        maxDuration: config.maxDuration,
        allowedMimes: config.allowedMimes,

        // State
        isDragging: false,
        isUploading: false,
        isProcessing: false,
        uploadProgress: 0,
        processingProgress: 0,
        processingStage: null,
        uploadError: null,
        uploadedVideo: null,

        // File info
        fileName: null,
        fileSize: 0,
        file: null,

        // Upload tracking
        currentVideoId: null,
        uploadToken: null,
        xhr: null,

        handleDrop(event) {
            this.isDragging = false;
            const files = event.dataTransfer?.files;
            if (files?.length) {
                this.processFile(files[0]);
            }
        },

        handleFileSelect(event) {
            const files = event.target.files;
            if (files?.length) {
                this.processFile(files[0]);
            }
        },

        async processFile(file) {
            // Validate file type
            if (!this.allowedMimes.includes(file.type)) {
                this.uploadError = 'Invalid file type. Allowed: MP4, WebM, MOV, AVI';
                return;
            }

            // Validate file size
            if (file.size > this.maxFileSize) {
                this.uploadError = `File too large. Maximum: ${this.formatBytes(this.maxFileSize)}`;
                return;
            }

            this.file = file;
            this.fileName = file.name;
            this.fileSize = file.size;
            this.uploadError = null;

            // Show video preview
            if (this.$refs.videoPreview) {
                this.$refs.videoPreview.src = URL.createObjectURL(file);
            }

            // Get video duration
            const duration = await this.getVideoDuration(file);
            if (duration > this.maxDuration) {
                this.uploadError = `Video too long. Maximum: ${this.maxDuration} seconds`;
                return;
            }

            // Request pre-signed URL via Livewire
            const response = await @this.requestUploadUrl(
                file.name,
                file.size,
                file.type,
                Math.round(duration)
            );

            if (response.error) {
                this.uploadError = response.error;
                return;
            }

            this.currentVideoId = response.videoId;
            this.uploadToken = response.uploadToken;
            this.isUploading = true;

            // Upload directly to S3
            await this.uploadToS3(response.uploadUrl, file);
        },

        getVideoDuration(file) {
            return new Promise((resolve) => {
                const video = document.createElement('video');
                video.preload = 'metadata';
                video.onloadedmetadata = () => {
                    URL.revokeObjectURL(video.src);
                    resolve(video.duration);
                };
                video.onerror = () => resolve(0);
                video.src = URL.createObjectURL(file);
            });
        },

        async uploadToS3(uploadUrl, file) {
            this.xhr = new XMLHttpRequest();
            
            this.xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    this.uploadProgress = Math.round((e.loaded / e.total) * 100);
                    @this.updateUploadProgress(this.uploadProgress);
                }
            });

            this.xhr.addEventListener('load', async () => {
                if (this.xhr.status >= 200 && this.xhr.status < 300) {
                    // Confirm upload with server
                    const result = await @this.confirmUpload(
                        this.currentVideoId,
                        this.uploadToken
                    );

                    if (result.success) {
                        this.isUploading = false;
                        this.isProcessing = true;
                        this.processingProgress = 0;
                        this.processingStage = 'Queued for processing';
                    } else {
                        this.uploadError = result.error || 'Upload confirmation failed';
                        this.isUploading = false;
                    }
                } else {
                    this.uploadError = `Upload failed: ${this.xhr.statusText}`;
                    this.isUploading = false;
                    @this.uploadFailed(this.uploadError);
                }
            });

            this.xhr.addEventListener('error', () => {
                this.uploadError = 'Upload failed. Please try again.';
                this.isUploading = false;
                @this.uploadFailed(this.uploadError);
            });

            this.xhr.open('PUT', uploadUrl);
            this.xhr.setRequestHeader('Content-Type', file.type);
            this.xhr.send(file);
        },

        cancelUpload() {
            if (this.xhr) {
                this.xhr.abort();
            }
            this.reset();
        },

        reset() {
            this.isUploading = false;
            this.isProcessing = false;
            this.uploadProgress = 0;
            this.processingProgress = 0;
            this.processingStage = null;
            this.uploadError = null;
            this.uploadedVideo = null;
            this.fileName = null;
            this.fileSize = 0;
            this.file = null;
            this.currentVideoId = null;
            this.uploadToken = null;
            this.xhr = null;
            
            if (this.$refs.fileInput) {
                this.$refs.fileInput.value = '';
            }
            if (this.$refs.videoPreview) {
                this.$refs.videoPreview.src = '';
            }
            
            @this.resetState();
        },

        formatBytes(bytes) {
            const units = ['B', 'KB', 'MB', 'GB'];
            let i = 0;
            while (bytes >= 1024 && i < units.length - 1) {
                bytes /= 1024;
                i++;
            }
            return bytes.toFixed(2) + ' ' + units[i];
        },

        // Listen for processing updates from Livewire
        init() {
            this.$watch('$wire.processingProgress', (value) => {
                this.processingProgress = value;
            });
            this.$watch('$wire.processingStage', (value) => {
                this.processingStage = value;
            });
            this.$watch('$wire.uploadedVideo', (value) => {
                if (value) {
                    this.uploadedVideo = value;
                    this.isProcessing = false;
                }
            });
            this.$watch('$wire.uploadError', (value) => {
                if (value) {
                    this.uploadError = value;
                    this.isProcessing = false;
                }
            });
        }
    }));
});
</script>
@endpush
