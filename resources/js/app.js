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

// ------------------------------------------------------------ menu do telemóvel

document.querySelectorAll('[data-botao-menu]').forEach((botao) => {
    const cabecalho = botao.closest('.cabecalho');
    const texto = botao.querySelector('[data-texto-menu]');

    const definir = (aberto) => {
        cabecalho.classList.toggle('menu-aberto', aberto);
        botao.setAttribute('aria-expanded', aberto ? 'true' : 'false');
        texto.textContent = aberto ? 'Fechar' : 'Menu';
    };

    botao.addEventListener('click', () => definir(botao.getAttribute('aria-expanded') !== 'true'));
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && cabecalho.classList.contains('menu-aberto')) {
            definir(false);
            botao.focus();
        }
    });
});

// ------------------------------------------------------------ filtros do arquivo

// No telemóvel, os campos tipo/década/tema começam fechados (se não houver filtros
// ativos), para os resultados aparecerem logo. No computador estão sempre abertos.
document.querySelectorAll('[data-mais-filtros]').forEach((bloco) => {
    const computador = window.matchMedia('(min-width: 960px)');
    const ajustar = () => {
        bloco.open = computador.matches || bloco.hasAttribute('data-ativos');
    };
    ajustar();
    computador.addEventListener('change', ajustar);
});

// ------------------------------------------------------------ números na fotografia

// Desloca o balão com o nome para não sair pelas margens do ecrã.
function encaixarBalao(numero) {
    const balao = numero.querySelector('.balao');
    if (!balao) return;
    balao.style.setProperty('--desvio', '0px');
    const r = balao.getBoundingClientRect();
    const margem = 8;
    const largura = document.documentElement.clientWidth;
    let desvio = 0;
    if (r.left < margem) desvio = margem - r.left;
    else if (r.right > largura - margem) desvio = largura - margem - r.right;
    balao.style.setProperty('--desvio', `${Math.round(desvio)}px`);
}

document.querySelectorAll('[data-imagem-marcada]').forEach((caixa) => {
    const numeros = caixa.querySelectorAll('.marca-numero');

    const fecharTodos = (exceto) => numeros.forEach((n) => n !== exceto && n.setAttribute('aria-expanded', 'false'));

    numeros.forEach((numero) => {
        numero.addEventListener('mouseenter', () => encaixarBalao(numero));
        numero.addEventListener('focus', () => encaixarBalao(numero));
        numero.addEventListener('click', (evento) => {
            encaixarBalao(numero);
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
