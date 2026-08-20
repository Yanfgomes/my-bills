@props(['type' => 'error', 'dataCy' => null])

{{--
    Adaptado de reference/bioform-layout/page-hero-and-cards.md (5.3 AlertBanner.vue). Cores via
    tokens do projeto (--color-danger/--color-warn/--color-success), nunca as classes
    emerald/amber/red hardcoded do fragmento original.
--}}
@php
    $roles = ['warning' => 'alert', 'success' => 'status', 'error' => 'alert'];
    $role = $roles[$type] ?? 'alert';
    $bgStyle = match ($type) {
        'success' => 'background-color:var(--color-success-bg);border-color:var(--color-success-border);color:var(--color-success)',
        'warning' => 'background-color:var(--color-warn-bg);border-color:var(--color-warn-border);color:var(--color-warn)',
        default => 'background-color:var(--color-danger-bg);border-color:var(--color-danger-border);color:var(--color-danger)',
    };
@endphp
<div
    role="{{ $role }}"
    @if ($dataCy) data-cy="{{ $dataCy }}" @endif
    {{ $attributes->class(['mb-4 rounded-lg border px-3 py-2 text-[13px]'])->merge(['style' => $bgStyle]) }}
>
    {{ $slot }}
</div>
