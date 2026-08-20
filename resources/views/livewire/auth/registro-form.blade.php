{{--
    Adaptado do template "Formulario" (reference/bioform-layout/form-field.md §6.2) — ver
    comentario equivalente em livewire/auth/login-form.blade.php.
--}}
<div class="max-w-md mx-auto">
    <x-card-base>
        <h1 class="text-[18px] font-extrabold mb-4" style="color:var(--color-text)">
            {{ __('Criar conta') }}
        </h1>

        @if ($erroGeral)
            <x-alert-banner
                type="error"
                data-cy="registro-erro-geral"
                aria-live="assertive"
                tabindex="-1"
                x-data
                x-init="$nextTick(() => $el.focus())"
            >
                {{ $erroGeral }}
            </x-alert-banner>
        @endif

        <form wire:submit="registrar" novalidate>
            <div class="mb-3.5">
                <label for="registro-nome" class="block text-[11.5px] font-semibold mb-1.5" style="color:var(--color-text-muted)">
                    {{ __('Nome') }}
                </label>
                <input
                    id="registro-nome"
                    type="text"
                    wire:model="nome"
                    data-cy="registro-nome"
                    placeholder="{{ __('Seu nome') }}"
                    aria-describedby="registro-nome-error"
                    aria-invalid="{{ $errors->has('nome') ? 'true' : 'false' }}"
                    class="focus-ring w-full px-3 py-2.5 border-[1.5px] rounded-lg text-[13px] outline-none transition-colors"
                    style="border-color:var(--color-border);background-color:var(--color-card);color:var(--color-text)"
                >
                <div id="registro-nome-error" aria-live="polite">
                    @error('nome')
                        <p class="mt-1 text-[12px]" style="color:var(--color-danger)">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mb-3.5">
                <label for="registro-email" class="block text-[11.5px] font-semibold mb-1.5" style="color:var(--color-text-muted)">
                    {{ __('Email') }}
                </label>
                <input
                    id="registro-email"
                    type="email"
                    wire:model="email"
                    data-cy="registro-email"
                    placeholder="{{ __('voce@exemplo.com') }}"
                    aria-describedby="registro-email-error"
                    aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                    class="focus-ring w-full px-3 py-2.5 border-[1.5px] rounded-lg text-[13px] outline-none transition-colors"
                    style="border-color:var(--color-border);background-color:var(--color-card);color:var(--color-text)"
                >
                <div id="registro-email-error" aria-live="polite">
                    @error('email')
                        <p class="mt-1 text-[12px]" style="color:var(--color-danger)">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mb-3.5">
                <label for="registro-senha" class="block text-[11.5px] font-semibold mb-1.5" style="color:var(--color-text-muted)">
                    {{ __('Senha') }}
                </label>
                <input
                    id="registro-senha"
                    type="password"
                    wire:model="senha"
                    data-cy="registro-senha"
                    placeholder="{{ __('minimo 8 caracteres') }}"
                    aria-describedby="registro-senha-error"
                    aria-invalid="{{ $errors->has('senha') ? 'true' : 'false' }}"
                    class="focus-ring w-full px-3 py-2.5 border-[1.5px] rounded-lg text-[13px] outline-none transition-colors"
                    style="border-color:var(--color-border);background-color:var(--color-card);color:var(--color-text)"
                >
                <div id="registro-senha-error" aria-live="polite">
                    @error('senha')
                        <p class="mt-1 text-[12px]" style="color:var(--color-danger)">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mb-3.5">
                <label for="registro-senha-confirma" class="block text-[11.5px] font-semibold mb-1.5" style="color:var(--color-text-muted)">
                    {{ __('Confirmar senha') }}
                </label>
                <input
                    id="registro-senha-confirma"
                    type="password"
                    wire:model="senha_confirmation"
                    data-cy="registro-senha-confirma"
                    placeholder="{{ __('repita a senha') }}"
                    aria-describedby="registro-senha-confirma-error"
                    aria-invalid="{{ $errors->has('senha_confirmation') ? 'true' : 'false' }}"
                    class="focus-ring w-full px-3 py-2.5 border-[1.5px] rounded-lg text-[13px] outline-none transition-colors"
                    style="border-color:var(--color-border);background-color:var(--color-card);color:var(--color-text)"
                >
                <div id="registro-senha-confirma-error" aria-live="polite">
                    @error('senha_confirmation')
                        <p class="mt-1 text-[12px]" style="color:var(--color-danger)">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <button
                type="submit"
                data-cy="registro-submit"
                wire:loading.attr="aria-busy"
                wire:target="registrar"
                class="focus-ring btn-primary-hover w-full mt-2 px-4 py-2.5 rounded-lg text-white text-[13px] font-semibold transition-colors"
                style="background-color:var(--color-primary)"
            >
                {{ __('Criar conta') }}
            </button>
        </form>

        <div class="mt-4">
            <a
                href="{{ route('login') }}"
                data-cy="registro-ir-login"
                class="focus-ring text-[13px] underline"
                style="color:var(--color-primary)"
            >
                {{ __('Ja tem conta? Entrar') }}
            </a>
        </div>
    </x-card-base>
</div>
