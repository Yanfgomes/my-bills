@props(['title' => null])

{{--
    Adaptado de reference/bioform-layout/page-hero-and-cards.md (5.2 CardBase.vue).
    Bloco de conteudo padrao de toda tela — nunca <div> estilizada a mao fora deste componente.
--}}
<div {{ $attributes->class(['rounded-[10px] border overflow-hidden'])->merge(['style' => 'background-color:var(--color-card);border-color:var(--color-border)']) }}>
    @if ($title || isset($header))
        <div class="flex items-center justify-between px-5 py-3.5 border-b" style="border-color:var(--color-border)">
            @isset($header)
                {{ $header }}
            @else
                <h2 class="text-[13px] font-bold" style="color:var(--color-text)">{{ $title }}</h2>
            @endisset
        </div>
    @endif
    <div class="p-5">
        {{ $slot }}
    </div>
</div>
