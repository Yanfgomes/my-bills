<div class="max-w-md mx-auto">
    <div
        class="rounded-[10px] border p-5"
        style="background-color:var(--color-card);border-color:var(--color-border)"
    >
        <h1 class="text-[18px] font-semibold mb-4" style="color:var(--color-text)">
            {{ __('Criar conta') }}
        </h1>

        @if ($erroGeral)
            <div
                role="alert"
                aria-live="assertive"
                tabindex="-1"
                x-data
                x-init="$nextTick(() => $el.focus())"
                class="mb-4 rounded-lg border px-3 py-2 text-[13px]"
                style="background-color:var(--color-danger-bg);border-color:var(--color-danger-border);color:var(--color-danger)"
            >
                {{ $erroGeral }}
            </div>
        @endif

        <form wire:submit="registrar" novalidate>
            <div class="mb-3.5">
                <label for="registro-nome" class="block text-[12px] font-semibold mb-1.5" style="color:var(--color-text)">
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
                    class="focus-ring w-full px-3 py-2.5 border rounded-lg text-[13px] outline-none"
                    style="border-color:var(--color-border);background-color:var(--color-card);color:var(--color-text)"
                >
                <div id="registro-nome-error" aria-live="polite">
                    @error('nome')
                        <p class="mt-1 text-[12px]" style="color:var(--color-danger)">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mb-3.5">
                <label for="registro-email" class="block text-[12px] font-semibold mb-1.5" style="color:var(--color-text)">
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
                    class="focus-ring w-full px-3 py-2.5 border rounded-lg text-[13px] outline-none"
                    style="border-color:var(--color-border);background-color:var(--color-card);color:var(--color-text)"
                >
                <div id="registro-email-error" aria-live="polite">
                    @error('email')
                        <p class="mt-1 text-[12px]" style="color:var(--color-danger)">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mb-3.5">
                <label for="registro-senha" class="block text-[12px] font-semibold mb-1.5" style="color:var(--color-text)">
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
                    class="focus-ring w-full px-3 py-2.5 border rounded-lg text-[13px] outline-none"
                    style="border-color:var(--color-border);background-color:var(--color-card);color:var(--color-text)"
                >
                <div id="registro-senha-error" aria-live="polite">
                    @error('senha')
                        <p class="mt-1 text-[12px]" style="color:var(--color-danger)">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mb-3.5">
                <label for="registro-senha-confirma" class="block text-[12px] font-semibold mb-1.5" style="color:var(--color-text)">
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
                    class="focus-ring w-full px-3 py-2.5 border rounded-lg text-[13px] outline-none"
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
                class="focus-ring w-full mt-2 px-4 py-2.5 rounded-lg text-white text-[13px] font-semibold transition-colors"
                style="background-color:var(--color-primary)"
                onmouseover="this.style.backgroundColor='var(--color-primary-dark)'"
                onmouseout="this.style.backgroundColor='var(--color-primary)'"
            >
                {{ __('Criar conta') }}
            </button>
        </form>

        <div class="mt-4">
            {{-- rota nomeada 'login' e criada pelo RF-002 (proximo RF da ordem de execucao);
                 ate la, link aponta para o path fixo para nao quebrar esta tela. --}}
            <a
                href="/login"
                data-cy="registro-ir-login"
                class="focus-ring text-[13px] underline"
                style="color:var(--color-primary)"
            >
                {{ __('Ja tem conta? Entrar') }}
            </a>
        </div>
    </div>
</div>
