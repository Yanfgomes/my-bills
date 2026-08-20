{{--
    Adaptado do template "Formulario" (reference/bioform-layout/form-field.md §6.2), sem
    AppLayout/PageHero (guest layout ja traz o cabecalho de marca — ver layouts/guest.blade.php).
    CardBase + AlertBanner via components/card-base.blade.php e components/alert-banner.blade.php;
    campos seguem a mesma composicao visual de FormField.vue (label + input, erro sempre abaixo).
--}}
<div class="max-w-md mx-auto">
    <x-card-base>
        <h1 class="text-[18px] font-extrabold mb-4" style="color:var(--color-text)">
            {{ __('Entrar') }}
        </h1>

        @if ($erroGeral)
            <x-alert-banner
                type="error"
                data-cy="login-erro-geral"
                aria-live="assertive"
                tabindex="-1"
                x-data
                x-init="$nextTick(() => $el.focus())"
            >
                {{ $erroGeral }}
            </x-alert-banner>
        @endif

        <form wire:submit="autenticar" novalidate>
            <div class="mb-3.5">
                <label for="login-email" class="block text-[11.5px] font-semibold mb-1.5" style="color:var(--color-text-muted)">
                    {{ __('Email') }}
                </label>
                <input
                    id="login-email"
                    type="email"
                    wire:model="email"
                    data-cy="login-email"
                    placeholder="{{ __('voce@exemplo.com') }}"
                    aria-describedby="login-email-error"
                    aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                    class="focus-ring w-full px-3 py-2.5 border-[1.5px] rounded-lg text-[13px] outline-none transition-colors"
                    style="border-color:var(--color-border);background-color:var(--color-card);color:var(--color-text)"
                >
                <div id="login-email-error" aria-live="polite">
                    @error('email')
                        <p class="mt-1 text-[11px]" style="color:var(--color-danger)">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mb-3.5">
                <label for="login-senha" class="block text-[11.5px] font-semibold mb-1.5" style="color:var(--color-text-muted)">
                    {{ __('Senha') }}
                </label>
                <input
                    id="login-senha"
                    type="password"
                    wire:model="senha"
                    data-cy="login-senha"
                    placeholder="********"
                    aria-describedby="login-senha-error"
                    aria-invalid="{{ $errors->has('senha') ? 'true' : 'false' }}"
                    class="focus-ring w-full px-3 py-2.5 border-[1.5px] rounded-lg text-[13px] outline-none transition-colors"
                    style="border-color:var(--color-border);background-color:var(--color-card);color:var(--color-text)"
                >
                <div id="login-senha-error" aria-live="polite">
                    @error('senha')
                        <p class="mt-1 text-[11px]" style="color:var(--color-danger)">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <button
                type="submit"
                data-cy="login-submit"
                wire:loading.attr="aria-busy"
                wire:target="autenticar"
                class="focus-ring btn-primary-hover w-full mt-2 px-4 py-2.5 rounded-lg text-white text-[13px] font-semibold transition-colors"
                style="background-color:var(--color-primary)"
            >
                {{ __('Entrar') }}
            </button>
        </form>

        <div class="mt-4">
            <a
                href="{{ route('registro') }}"
                data-cy="login-ir-registro"
                class="focus-ring text-[13px] underline"
                style="color:var(--color-primary)"
            >
                {{ __('Nao tem conta? Cadastre-se') }}
            </a>
        </div>
    </x-card-base>
</div>
