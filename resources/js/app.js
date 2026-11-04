/*
 * JavaScript do site público. Pequeno de propósito:
 *  - escolher o tema (automático / claro / escuro);
 *  - mostrar o nome ao tocar num número da fotografia;
 *  - esconder e mostrar os números.
 */

function guardar(chave, valor) {
    try { localStorage.setItem(chave, valor); } catch (e) { /* sem armazenamento: ignora */ }
}

// ------------------------------------------------------------ tema

const NOMES_TEMA = { auto: 'Tema: automático', claro: 'Tema: claro', escuro: 'Tema: escuro' };

function aplicarTema(tema) {
    if (tema === 'auto') {
        document.documentElement.removeAttribute('data-tema');
    } else {
        document.documentElement.setAttribute('data-tema', tema);
    }
    document.querySelectorAll('[data-botao-tema]').forEach((botao) => {
        botao.textContent = NOMES_TEMA[tema];
        botao.dataset.estado = tema;
    });
}

document.querySelectorAll('[data-botao-tema]').forEach((botao) => {
    aplicarTema(document.documentElement.getAttribute('data-tema') || 'auto');
    botao.addEventListener('click', () => {
        const seguinte = { auto: 'claro', claro: 'escuro', escuro: 'auto' }[botao.dataset.estado || 'auto'];
        guardar('crevelhos-tema', seguinte);
        aplicarTema(seguinte);
    });
});

// ------------------------------------------------------------ números na fotografia

document.querySelectorAll('[data-imagem-marcada]').forEach((caixa) => {
    const numeros = caixa.querySelectorAll('.marca-numero');

    const fecharTodos = (exceto) => numeros.forEach((n) => n !== exceto && n.setAttribute('aria-expanded', 'false'));

    numeros.forEach((numero) => {
        numero.addEventListener('click', (evento) => {
            evento.stopPropagation();
            const aberto = numero.getAttribute('aria-expanded') === 'true';
            fecharTodos(numero);
            numero.setAttribute('aria-expanded', aberto ? 'false' : 'true');

            // Destaca a linha correspondente na lista "Quem está na fotografia".
            document.querySelectorAll('[data-linha-numero]').forEach((linha) => {
                linha.classList.toggle('destacada', !aberto && linha.dataset.linhaNumero === numero.dataset.numero);
            });
        });
    });

    document.addEventListener('click', () => fecharTodos(null));
    document.addEventListener('keydown', (e) => e.key === 'Escape' && fecharTodos(null));
});

document.querySelectorAll('[data-alternar-numeros]').forEach((botao) => {
    const alvo = document.getElementById(botao.getAttribute('aria-controls'));
    botao.addEventListener('click', () => {
        const escondidos = alvo.classList.toggle('sem-numeros');
        botao.setAttribute('aria-pressed', escondidos ? 'true' : 'false');
        botao.textContent = escondidos ? 'Mostrar os números' : 'Esconder os números';
    });
});
