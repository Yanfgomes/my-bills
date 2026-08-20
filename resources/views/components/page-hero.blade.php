@props(['title', 'subtitle' => null])

{{--
    Adaptado de reference/bioform-layout/page-hero-and-cards.md (5.1 PageHero.vue).
    Vue <script setup>/props -> Blade anonymous component com @props. Cabecalho obrigatorio de
    toda tela, unico <h1> da pagina. Gradiente via .app-hero (resources/css/app.css), derivado de
    --color-primary/--color-primary-dark (paleta aprovada do projeto, nao o brand.* do fragmento).
--}}
<section class="app-hero relative rounded-xl px-6 py-5 md:px-7 md:py-6 mb-5 overflow-hidden">
    <div aria-hidden="true" class="absolute -right-10 -top-10 w-56 h-56 rounded-full bg-white/[0.06] pointer-events-none"></div>
    <div aria-hidden="true" class="absolute right-16 -bottom-14 w-32 h-32 rounded-full bg-white/[0.05] pointer-events-none"></div>

    <div class="relative z-10 flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-[20px] font-extrabold text-white tracking-tight mb-1">{{ $title }}</h1>
            @if ($subtitle)
                <p class="text-[12px] text-white/70">{{ $subtitle }}</p>
            @endif
        </div>

        @isset($actions)
            <div class="flex items-center gap-2 flex-shrink-0">
                {{ $actions }}
            </div>
        @endisset
    </div>

    @isset($slot)
        @if (trim($slot) !== '')
            <div class="relative z-10 mt-4">
                {{ $slot }}
            </div>
        @endif
    @endisset
</section>
