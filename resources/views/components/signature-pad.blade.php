@props([
    'name' => 'signature_data',
    'id' => 'signature-pad-' . uniqid(),
    'height' => 190,
    'existingSignature' => null,
    'prompt' => 'Draw your signature inside the box',
    'required' => false,
])

<div
    id="{{ $id }}"
    x-data="{
        mode: 'draw',
        hasStrokes: false,
        strokes: [],
        currentStroke: [],
        isDrawing: false,
        inkColor: '#0f172a',
        lineWidth: 2.4,
        dataUrl: '',
        canvas: null,
        ctx: null,
        dpr: window.devicePixelRatio || 1,
        existingSig: @js($existingSignature),
        lastPoint: null,
        lastMid: null,
        resizeObserver: null,

        init() {
            this.$nextTick(() => {
                this.setupCanvas();
            });
        },

        setupCanvas() {
            this.canvas = this.$refs.sigCanvas;
            if (!this.canvas) return;

            this.ctx = this.canvas.getContext('2d', { willReadFrequently: true });
            this.resizeCanvas();

            // Observe resize / visibility changes (e.g. when unhidden by Alpine x-show)
            if (window.ResizeObserver) {
                this.resizeObserver = new ResizeObserver(() => {
                    this.ensureCanvasSize();
                });
                this.resizeObserver.observe(this.canvas);
            }

            window.addEventListener('resize', () => this.ensureCanvasSize());

            // Global pointerup to ensure stroke ends even if mouse released outside canvas
            window.addEventListener('pointerup', (e) => {
                if (this.isDrawing) {
                    this.handlePointerUp(e);
                }
            });
        },

        ensureCanvasSize() {
            if (!this.canvas) return;
            const rect = this.canvas.getBoundingClientRect();
            if (rect.width === 0 || rect.height === 0) return;

            const targetWidth = Math.round(rect.width * (window.devicePixelRatio || 1));
            const targetHeight = Math.round(rect.height * (window.devicePixelRatio || 1));

            if (this.canvas.width !== targetWidth || this.canvas.height !== targetHeight) {
                this.resizeCanvas();
            }
        },

        resizeCanvas() {
            if (!this.canvas || !this.ctx) return;

            const rect = this.canvas.getBoundingClientRect();
            if (rect.width === 0 || rect.height === 0) return;

            const prevStrokes = JSON.parse(JSON.stringify(this.strokes));

            this.dpr = window.devicePixelRatio || 1;
            this.canvas.width = Math.round(rect.width * this.dpr);
            this.canvas.height = Math.round(rect.height * this.dpr);

            // Re-apply scale
            this.ctx.setTransform(this.dpr, 0, 0, this.dpr, 0, 0);

            if (prevStrokes.length > 0) {
                this.strokes = prevStrokes;
                this.redrawAll();
            }
        },

        getCoords(e) {
            const rect = this.canvas.getBoundingClientRect();
            let clientX, clientY;

            if (e.clientX !== undefined) {
                clientX = e.clientX;
                clientY = e.clientY;
            } else if (e.touches && e.touches.length > 0) {
                clientX = e.touches[0].clientX;
                clientY = e.touches[0].clientY;
            } else if (e.changedTouches && e.changedTouches.length > 0) {
                clientX = e.changedTouches[0].clientX;
                clientY = e.changedTouches[0].clientY;
            } else {
                clientX = rect.left;
                clientY = rect.top;
            }

            // High-precision scale calculation
            const scaleX = rect.width > 0 ? (this.canvas.width / this.dpr) / rect.width : 1;
            const scaleY = rect.height > 0 ? (this.canvas.height / this.dpr) / rect.height : 1;

            return {
                x: (clientX - rect.left) * scaleX,
                y: (clientY - rect.top) * scaleY
            };
        },

        handlePointerDown(e) {
            if (e.button !== undefined && e.button !== 0) return; // Only primary mouse button

            this.ensureCanvasSize();

            try {
                if (e.target && typeof e.target.setPointerCapture === 'function') {
                    e.target.setPointerCapture(e.pointerId);
                }
            } catch (_) {}

            this.isDrawing = true;
            const pt = this.getCoords(e);
            pt.color = this.inkColor;
            pt.width = this.lineWidth;

            this.lastPoint = { x: pt.x, y: pt.y };
            this.lastMid = { x: pt.x, y: pt.y };
            this.currentStroke = [pt];

            // Draw initial dot immediately
            this.ctx.beginPath();
            this.ctx.arc(pt.x, pt.y, this.lineWidth / 2, 0, Math.PI * 2);
            this.ctx.fillStyle = this.inkColor;
            this.ctx.fill();
        },

        handlePointerMove(e) {
            if (!this.isDrawing) return;

            // Extract coalesced sub-frame micro-events if available for high-poll-rate mice
            const events = (typeof e.getCoalescedEvents === 'function' && e.getCoalescedEvents().length > 0)
                ? e.getCoalescedEvents()
                : [e];

            for (let i = 0; i < events.length; i++) {
                this.addPoint(this.getCoords(events[i]));
            }
        },

        addPoint(pt) {
            if (!this.isDrawing || !this.lastPoint) return;

            // Skip duplicate zero-distance movements to prevent ink puddles
            const dx = pt.x - this.lastPoint.x;
            const dy = pt.y - this.lastPoint.y;
            if (Math.abs(dx) < 0.2 && Math.abs(dy) < 0.2) return;

            pt.color = this.inkColor;
            pt.width = this.lineWidth;
            this.currentStroke.push(pt);

            // Compute midpoint between last point and current point
            const mid = {
                x: (this.lastPoint.x + pt.x) / 2,
                y: (this.lastPoint.y + pt.y) / 2
            };

            this.ctx.beginPath();
            this.ctx.moveTo(this.lastMid.x, this.lastMid.y);
            this.ctx.quadraticCurveTo(this.lastPoint.x, this.lastPoint.y, mid.x, mid.y);
            this.ctx.strokeStyle = this.inkColor;
            this.ctx.lineWidth = this.lineWidth;
            this.ctx.lineCap = 'round';
            this.ctx.lineJoin = 'round';
            this.ctx.stroke();

            this.lastPoint = { x: pt.x, y: pt.y };
            this.lastMid = mid;
        },

        handlePointerUp(e) {
            if (!this.isDrawing) return;

            try {
                if (e && e.target && typeof e.target.releasePointerCapture === 'function' && e.pointerId) {
                    if (e.target.hasPointerCapture(e.pointerId)) {
                        e.target.releasePointerCapture(e.pointerId);
                    }
                }
            } catch (_) {}

            if (this.lastPoint && this.lastMid) {
                // Draw final stroke directly to the end point
                this.ctx.beginPath();
                this.ctx.moveTo(this.lastMid.x, this.lastMid.y);
                this.ctx.lineTo(this.lastPoint.x, this.lastPoint.y);
                this.ctx.strokeStyle = this.inkColor;
                this.ctx.lineWidth = this.lineWidth;
                this.ctx.lineCap = 'round';
                this.ctx.lineJoin = 'round';
                this.ctx.stroke();
            }

            this.isDrawing = false;
            this.lastPoint = null;
            this.lastMid = null;

            if (this.currentStroke.length > 0) {
                this.strokes.push([...this.currentStroke]);
                this.currentStroke = [];
                this.hasStrokes = true;
                this.syncDataUrl();
            }
        },

        redrawAll() {
            const rect = this.canvas.getBoundingClientRect();
            this.ctx.clearRect(0, 0, rect.width, rect.height);

            this.strokes.forEach(stroke => {
                if (!stroke || stroke.length === 0) return;

                const color = stroke[0].color || this.inkColor;
                const width = stroke[0].width || this.lineWidth;

                if (stroke.length === 1) {
                    this.ctx.beginPath();
                    this.ctx.arc(stroke[0].x, stroke[0].y, width / 2, 0, Math.PI * 2);
                    this.ctx.fillStyle = color;
                    this.ctx.fill();
                    return;
                }

                let lastP = { x: stroke[0].x, y: stroke[0].y };
                let lastM = { x: stroke[0].x, y: stroke[0].y };

                for (let i = 1; i < stroke.length; i++) {
                    const pt = stroke[i];
                    const mid = {
                        x: (lastP.x + pt.x) / 2,
                        y: (lastP.y + pt.y) / 2
                    };

                    this.ctx.beginPath();
                    this.ctx.moveTo(lastM.x, lastM.y);
                    this.ctx.quadraticCurveTo(lastP.x, lastP.y, mid.x, mid.y);
                    this.ctx.strokeStyle = color;
                    this.ctx.lineWidth = width;
                    this.ctx.lineCap = 'round';
                    this.ctx.lineJoin = 'round';
                    this.ctx.stroke();

                    lastP = { x: pt.x, y: pt.y };
                    lastM = mid;
                }

                // Final segment
                this.ctx.beginPath();
                this.ctx.moveTo(lastM.x, lastM.y);
                this.ctx.lineTo(lastP.x, lastP.y);
                this.ctx.strokeStyle = color;
                this.ctx.lineWidth = width;
                this.ctx.lineCap = 'round';
                this.ctx.lineJoin = 'round';
                this.ctx.stroke();
            });
        },

        undo() {
            if (this.strokes.length === 0) return;
            this.strokes.pop();
            this.hasStrokes = this.strokes.length > 0;
            this.redrawAll();
            this.syncDataUrl();
        },

        clear() {
            const rect = this.canvas.getBoundingClientRect();
            this.ctx.clearRect(0, 0, rect.width, rect.height);
            this.strokes = [];
            this.currentStroke = [];
            this.lastPoint = null;
            this.lastMid = null;
            this.hasStrokes = false;
            this.dataUrl = '';
            if (this.$refs.fileInput) {
                this.$refs.fileInput.value = '';
            }
        },

        syncDataUrl() {
            if (!this.hasStrokes) {
                this.dataUrl = '';
                return;
            }
            this.dataUrl = this.canvas.toDataURL('image/png');
        },

        setInk(color) {
            this.inkColor = color;
        },

        setWidth(w) {
            this.lineWidth = w;
        },

        handleFileUpload(e) {
            const file = e.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = (event) => {
                const img = new Image();
                img.onload = () => {
                    this.ensureCanvasSize();
                    const rect = this.canvas.getBoundingClientRect();
                    this.ctx.clearRect(0, 0, rect.width, rect.height);

                    const scale = Math.min((rect.width - 40) / img.width, (rect.height - 40) / img.height, 1);
                    const w = img.width * scale;
                    const h = img.height * scale;
                    const x = (rect.width - w) / 2;
                    const y = (rect.height - h) / 2;

                    this.ctx.drawImage(img, x, y, w, h);
                    this.hasStrokes = true;
                    this.strokes = [[{ x: x, y: y, color: this.inkColor, width: this.lineWidth }]];
                    this.dataUrl = this.canvas.toDataURL('image/png');
                };
                img.src = event.target.result;
            };
            reader.readAsDataURL(file);
        }
    }"
    class="signature-pad-wrapper w-full rounded-2xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-emerald-300"
>
    <!-- Hidden input containing the base64 data URI -->
    <input
        type="hidden"
        name="{{ $name }}"
        :value="dataUrl"
        @if($required) required @endif
    >

    <!-- Top Bar Controls -->
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 pb-3">
        <div class="flex items-center gap-2">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-800 ring-1 ring-emerald-700/15">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                </svg>
            </span>
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-gray-900">{{ __('Digital Signature Pad') }}</h4>
                <p class="text-[11px] text-gray-500">{{ __($prompt) }}</p>
            </div>
        </div>

        <!-- Controls: Thickness, Colors, Actions -->
        <div class="flex flex-wrap items-center gap-2">
            <!-- Pen Thickness (Fine, Medium, Bold) -->
            <div class="flex items-center gap-1 rounded-lg border border-gray-200 bg-gray-50/80 p-1" title="{{ __('Pen Stroke Thickness') }}">
                <button
                    type="button"
                    @click="setWidth(1.5)"
                    class="rounded px-2 py-0.5 text-[10px] font-bold transition focus:outline-none"
                    :class="lineWidth === 1.5 ? 'bg-white text-emerald-900 shadow-xs ring-1 ring-gray-200' : 'text-gray-500 hover:text-gray-900'"
                >
                    {{ __('Fine') }}
                </button>
                <button
                    type="button"
                    @click="setWidth(2.4)"
                    class="rounded px-2 py-0.5 text-[10px] font-bold transition focus:outline-none"
                    :class="lineWidth === 2.4 ? 'bg-white text-emerald-900 shadow-xs ring-1 ring-gray-200' : 'text-gray-500 hover:text-gray-900'"
                >
                    {{ __('Medium') }}
                </button>
                <button
                    type="button"
                    @click="setWidth(3.6)"
                    class="rounded px-2 py-0.5 text-[10px] font-bold transition focus:outline-none"
                    :class="lineWidth === 3.6 ? 'bg-white text-emerald-900 shadow-xs ring-1 ring-gray-200' : 'text-gray-500 hover:text-gray-900'"
                >
                    {{ __('Bold') }}
                </button>
            </div>

            <!-- Ink Colors -->
            <div class="flex items-center gap-1 rounded-lg border border-gray-200 bg-gray-50/80 p-1" title="{{ __('Ink Color') }}">
                <button
                    type="button"
                    @click="setInk('#0f172a')"
                    title="{{ __('Classic Black') }}"
                    class="h-5 w-5 rounded-full transition focus:outline-none"
                    :class="inkColor === '#0f172a' ? 'ring-2 ring-emerald-600 ring-offset-1 scale-110' : 'opacity-80 hover:opacity-100'"
                    style="background-color: #0f172a;"
                ></button>
                <button
                    type="button"
                    @click="setInk('#1e3a8a')"
                    title="{{ __('Deep Navy') }}"
                    class="h-5 w-5 rounded-full transition focus:outline-none"
                    :class="inkColor === '#1e3a8a' ? 'ring-2 ring-emerald-600 ring-offset-1 scale-110' : 'opacity-80 hover:opacity-100'"
                    style="background-color: #1e3a8a;"
                ></button>
                <button
                    type="button"
                    @click="setInk('#064e3b')"
                    title="{{ __('Emerald Ink') }}"
                    class="h-5 w-5 rounded-full transition focus:outline-none"
                    :class="inkColor === '#064e3b' ? 'ring-2 ring-emerald-600 ring-offset-1 scale-110' : 'opacity-80 hover:opacity-100'"
                    style="background-color: #064e3b;"
                ></button>
            </div>

            <!-- Undo Button -->
            <button
                type="button"
                @click="undo()"
                :disabled="!hasStrokes"
                title="{{ __('Undo last stroke') }}"
                class="inline-flex h-8 items-center gap-1 rounded-lg border border-gray-200 bg-white px-2.5 text-xs font-semibold text-gray-700 shadow-xs transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
            >
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                </svg>
                <span class="hidden sm:inline">{{ __('Undo') }}</span>
            </button>

            <!-- Clear Button -->
            <button
                type="button"
                @click="clear()"
                :disabled="!hasStrokes"
                title="{{ __('Clear signature') }}"
                class="inline-flex h-8 items-center gap-1 rounded-lg border border-rose-200 bg-rose-50/50 px-2.5 text-xs font-semibold text-rose-700 shadow-xs transition hover:bg-rose-100/70 disabled:cursor-not-allowed disabled:opacity-40"
            >
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                </svg>
                <span class="hidden sm:inline">{{ __('Clear') }}</span>
            </button>
        </div>
    </div>

    <!-- Canvas Drawing Container -->
    <div class="relative w-full overflow-hidden rounded-xl border-2 border-dashed border-gray-300 bg-white transition hover:border-emerald-400 focus-within:border-emerald-600 focus-within:ring-2 focus-within:ring-emerald-600/20">
        <!-- Baseline watermark guide -->
        <div class="pointer-events-none absolute inset-x-0 bottom-8 flex items-center justify-between px-6 opacity-40 select-none">
            <div class="flex items-center gap-2 text-[11px] font-semibold tracking-wider text-gray-500 uppercase">
                <span class="text-sm font-bold text-gray-400">✗</span>
                <span>{{ __('Sign Above Line') }}</span>
            </div>
            <div class="h-px flex-1 ml-3 border-b border-dashed border-gray-400"></div>
        </div>

        <!-- Ultra-sensitive High-Precision Canvas -->
        <canvas
            x-ref="sigCanvas"
            @pointerdown="handlePointerDown($event)"
            @pointermove="handlePointerMove($event)"
            @pointerup="handlePointerUp($event)"
            @pointercancel="handlePointerUp($event)"
            style="height: {{ $height }}px; width: 100%; touch-action: none; cursor: crosshair;"
            class="relative z-10 block select-none"
        ></canvas>

        <!-- Status watermark / Indicator -->
        <div class="pointer-events-none absolute top-3 right-3 z-20 flex items-center gap-1.5 rounded-full bg-white/95 px-2.5 py-1 text-[10px] font-semibold shadow-xs ring-1 ring-gray-200 select-none">
            <span
                class="h-2 w-2 rounded-full transition"
                :class="hasStrokes ? 'bg-emerald-500 animate-pulse' : 'bg-gray-300'"
            ></span>
            <span x-text="hasStrokes ? 'Signature Captured' : 'Pad Ready (Sensitive)'"></span>
        </div>
    </div>

    <!-- Optional File Upload Alternative & Tip Footer -->
    <div class="mt-3 flex flex-col gap-2 text-xs text-gray-500 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-2">
            <label class="cursor-pointer font-medium text-emerald-800 hover:text-emerald-700 hover:underline">
                <span>{{ __('Or upload a transparent signature image (PNG/JPG)') }}</span>
                <input
                    x-ref="fileInput"
                    type="file"
                    accept="image/png,image/jpeg"
                    class="hidden"
                    @change="handleFileUpload($event)"
                >
            </label>
        </div>
        <p class="text-[11px] text-gray-400">
            {{ __('Real-time micro-sampling enabled for natural mouse & pen response') }}
        </p>
    </div>
</div>
