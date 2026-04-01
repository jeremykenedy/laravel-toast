@php
    $globalPosition = config('toast.position', 'top-right');
    $stack = config('toast.stack', true);
    $positionMap = [
        'top-left'=>'top:0.5rem;left:0.5rem;','top-center'=>'top:0.5rem;left:50%;transform:translateX(-50%);',
        'top-right'=>'top:0.5rem;right:0.5rem;','bottom-right'=>'bottom:0.5rem;right:0.5rem;',
        'bottom-left'=>'bottom:0.5rem;left:0.5rem;','bottom-center'=>'bottom:0.5rem;left:50%;transform:translateX(-50%);',
    ];
    $grouped = [];
    $displayToasts = $stack ? $toasts : (count($toasts) ? [end($toasts)] : []);
    foreach ($displayToasts as $t) {
        $pos = $t['position'] ?? $globalPosition;
        if (!isset($positionMap[$pos])) $pos = 'top-right';
        $grouped[$pos][] = $t;
    }
@endphp
<div>
@if(count($displayToasts) > 0)
{!! '<style>' !!}
{!! "@keyframes toast-slide-left{0%{transform:translateX(0);opacity:1}100%{transform:translateX(-120%);opacity:0}} @keyframes toast-slide-right{0%{transform:translateX(0);opacity:1}100%{transform:translateX(120%);opacity:0}} @keyframes toast-slide-top{0%{transform:translateY(0);opacity:1}100%{transform:translateY(-120%);opacity:0}} @keyframes toast-slide-bottom{0%{transform:translateY(0);opacity:1}100%{transform:translateY(120%);opacity:0}} @keyframes toast-bounce-left{0%{transform:translateX(0);opacity:1}30%{transform:translateX(8%)}100%{transform:translateX(-120%);opacity:0}} @keyframes toast-bounce-right{0%{transform:translateX(0);opacity:1}30%{transform:translateX(-8%)}100%{transform:translateX(120%);opacity:0}} @keyframes toast-bounce-top{0%{transform:translateY(0);opacity:1}30%{transform:translateY(15%)}100%{transform:translateY(-120%);opacity:0}} @keyframes toast-bounce-bottom{0%{transform:translateY(0);opacity:1}30%{transform:translateY(-15%)}100%{transform:translateY(120%);opacity:0}} @keyframes toast-fade{0%{opacity:1}100%{opacity:0}} @keyframes toast-shrink-left{0%{transform:scaleX(1);transform-origin:right;opacity:1}100%{transform:scaleX(0);opacity:0}} @keyframes toast-shrink-right{0%{transform:scaleX(1);transform-origin:left;opacity:1}100%{transform:scaleX(0);opacity:0}} @keyframes toast-shrink-top{0%{transform:scaleY(1);transform-origin:bottom;opacity:1}100%{transform:scaleY(0);opacity:0}} @keyframes toast-shrink-bottom{0%{transform:scaleY(1);transform-origin:top;opacity:1}100%{transform:scaleY(0);opacity:0}} @keyframes toast-enter-slide-left{0%{transform:translateX(-120%);opacity:0}100%{transform:translateX(0);opacity:1}} @keyframes toast-enter-slide-right{0%{transform:translateX(120%);opacity:0}100%{transform:translateX(0);opacity:1}} @keyframes toast-enter-slide-top{0%{transform:translateY(-120%);opacity:0}100%{transform:translateY(0);opacity:1}} @keyframes toast-enter-slide-bottom{0%{transform:translateY(120%);opacity:0}100%{transform:translateY(0);opacity:1}} @keyframes toast-enter-bounce-left{0%{transform:translateX(-120%);opacity:0}70%{transform:translateX(5%);opacity:1}100%{transform:translateX(0)}} @keyframes toast-enter-bounce-right{0%{transform:translateX(120%);opacity:0}70%{transform:translateX(-5%);opacity:1}100%{transform:translateX(0)}} @keyframes toast-enter-bounce-top{0%{transform:translateY(-120%);opacity:0}70%{transform:translateY(10%);opacity:1}100%{transform:translateY(0)}} @keyframes toast-enter-bounce-bottom{0%{transform:translateY(120%);opacity:0}70%{transform:translateY(-10%);opacity:1}100%{transform:translateY(0)}} @keyframes toast-enter-fade{0%{opacity:0}100%{opacity:1}} @keyframes toast-enter-shrink-left{0%{transform:scaleX(0);transform-origin:right;opacity:0}100%{transform:scaleX(1);opacity:1}} @keyframes toast-enter-shrink-right{0%{transform:scaleX(0);transform-origin:left;opacity:0}100%{transform:scaleX(1);opacity:1}} @keyframes toast-enter-shrink-top{0%{transform:scaleY(0);transform-origin:bottom;opacity:0}100%{transform:scaleY(1);opacity:1}} @keyframes toast-enter-shrink-bottom{0%{transform:scaleY(0);transform-origin:top;opacity:0}100%{transform:scaleY(1);opacity:1}}" !!}
{!! '</style>' !!}
@foreach($grouped as $pos => $posToasts)
<div style="position:fixed;{{ $positionMap[$pos] }} z-index:9999; width:24rem; max-width:calc(100vw - 1rem); display:flex; flex-direction:column; gap:0.75rem;">
    @foreach($posToasts as $toast)
    @php
        $enterStyle = (($toast['enter_animation'] ?? 'none') !== 'none') ? 'animation:toast-enter-' . $toast['enter_animation'] . ' ' . ($toast['enter_duration'] ?? 0.5) . 's ease forwards;' : '';
        $opacityStyle = (($toast['opacity'] ?? 1) < 1) ? 'opacity:' . $toast['opacity'] . ';' : '';
    @endphp
    <div wire:key="{{ $toast['id'] }}"
         id="lw-toast-{{ $toast['id'] }}"
         dir="{{ $toast['dir'] ?? 'ltr' }}"
         style="cursor:default;{{ $opacityStyle }}{{ $enterStyle }}"
         data-auto-dismiss="{{ ($toast['auto_dismiss'] ?? true) ? 'true' : 'false' }}"
         data-duration="{{ $toast['duration'] }}"
         data-pause-on-hover="{{ ($toast['pause_on_hover'] ?? true) ? 'true' : 'false' }}"
         data-exit-animation="{{ $toast['exit_animation'] ?? 'none' }}"
         data-exit-duration="{{ $toast['exit_duration'] ?? 0.5 }}"
         class="rounded-lg shadow-lg overflow-hidden
            {{ ($toast['show_border'] ?? true) !== false ? 'border' : '' }}
            @switch($toast['type'])
                @case('success') bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200 {{ ($toast['show_border'] ?? true) !== false ? 'border-green-200 dark:border-green-800' : '' }} @break
                @case('error') bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200 {{ ($toast['show_border'] ?? true) !== false ? 'border-red-200 dark:border-red-800' : '' }} @break
                @case('warning') bg-amber-50 text-amber-800 dark:bg-amber-950 dark:text-amber-200 {{ ($toast['show_border'] ?? true) !== false ? 'border-amber-200 dark:border-amber-800' : '' }} @break
                @default bg-blue-50 text-blue-800 dark:bg-blue-950 dark:text-blue-200 {{ ($toast['show_border'] ?? true) !== false ? 'border-blue-200 dark:border-blue-800' : '' }}
            @endswitch"
         role="alert">
        @if(($toast['auto_dismiss'] ?? true) && ($toast['show_progress'] ?? true) !== false && ($toast['duration'] ?? 0) > 0 && ($toast['progress_position'] ?? 'top') === 'top')
        <div class="h-1 w-full @switch($toast['type']) @case('success') bg-green-200 dark:bg-green-900 @break @case('error') bg-red-200 dark:bg-red-900 @break @case('warning') bg-amber-200 dark:bg-amber-900 @break @default bg-blue-200 dark:bg-blue-900 @endswitch">
            <div class="toast-progress-bar h-full @switch($toast['type']) @case('success') bg-green-500 dark:bg-green-400 @break @case('error') bg-red-500 dark:bg-red-400 @break @case('warning') bg-amber-500 dark:bg-amber-400 @break @default bg-blue-500 dark:bg-blue-400 @endswitch" style="width:100%;transition:none;{{ ($toast['progress_direction'] ?? 'rtl') === 'rtl' ? 'margin-left:auto;' : '' }}" data-duration="{{ $toast['duration'] }}"></div>
        </div>
        @endif
        <div class="p-4 flex items-start gap-3">
            @if(($toast['show_icon'] ?? true) !== false)
            <div class="flex-shrink-0 mt-0.5">
                @if($toast['custom_icon'] ?? null) {!! $toast['custom_icon'] !!}
                @else
                    @switch($toast['type'])
                        @case('success') <svg class="h-5 w-5 text-green-500 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> @break
                        @case('error') <svg class="h-5 w-5 text-red-500 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> @break
                        @case('warning') <svg class="h-5 w-5 text-amber-500 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg> @break
                        @default <svg class="h-5 w-5 text-blue-500 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @endswitch
                @endif
            </div>
            @endif
            <div class="flex-1 min-w-0" style="cursor:default;">
                @if($toast['title'] ?? null) <p class="text-sm font-semibold">{{ $toast['title'] }}</p> @endif
                <p class="text-sm">{{ $toast['message'] }}</p>
            </div>
            @if(($toast['show_close'] ?? true) !== false)
            <button wire:click="dismiss('{{ $toast['id'] }}')" class="flex-shrink-0 rounded-md p-1 opacity-60 hover:opacity-100 transition-opacity cursor-pointer" aria-label="{{ __('toast::toast.dismiss') }}">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            </button>
            @endif
        </div>
        @if(($toast['auto_dismiss'] ?? true) && ($toast['show_progress'] ?? true) !== false && ($toast['duration'] ?? 0) > 0 && ($toast['progress_position'] ?? 'top') !== 'top')
        <div class="h-1 w-full @switch($toast['type']) @case('success') bg-green-200 dark:bg-green-900 @break @case('error') bg-red-200 dark:bg-red-900 @break @case('warning') bg-amber-200 dark:bg-amber-900 @break @default bg-blue-200 dark:bg-blue-900 @endswitch">
            <div class="toast-progress-bar h-full @switch($toast['type']) @case('success') bg-green-500 dark:bg-green-400 @break @case('error') bg-red-500 dark:bg-red-400 @break @case('warning') bg-amber-500 dark:bg-amber-400 @break @default bg-blue-500 dark:bg-blue-400 @endswitch" style="width:100%;transition:none;{{ ($toast['progress_direction'] ?? 'rtl') === 'rtl' ? 'margin-left:auto;' : '' }}" data-duration="{{ $toast['duration'] }}"></div>
        </div>
        @endif
    </div>
    @endforeach
</div>
@endforeach
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[id^="lw-toast-"][data-auto-dismiss="true"]').forEach(function(el) {
        var duration = parseInt(el.dataset.duration) || 5000, pauseOnHover = el.dataset.pauseOnHover === 'true';
        var exitAnim = el.dataset.exitAnimation || 'none', exitDur = parseFloat(el.dataset.exitDuration) || 0.5;
        var bar = el.querySelector('.toast-progress-bar'), start = Date.now(), paused = false, pausedAt = 0, elapsed = 0;
        function dismiss() { if (exitAnim !== 'none') { el.style.animation = 'toast-' + exitAnim + ' ' + exitDur + 's ease forwards'; setTimeout(function(){el.remove();}, exitDur*1000); } else { el.style.opacity='0'; setTimeout(function(){el.remove();}, 200); } }
        function tick() { if (paused) return; var e = Date.now()-start-elapsed; if (bar) bar.style.width = Math.max(0,100-(e/duration*100))+'%'; if (e >= duration) dismiss(); else requestAnimationFrame(tick); }
        if (pauseOnHover) { el.addEventListener('mouseenter', function(){paused=true;pausedAt=Date.now();}); el.addEventListener('mouseleave', function(){elapsed+=Date.now()-pausedAt;paused=false;requestAnimationFrame(tick);}); }
        requestAnimationFrame(tick);
    });
});
</script>
@endif
</div>
