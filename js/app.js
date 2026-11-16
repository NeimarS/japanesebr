document.addEventListener('DOMContentLoaded', function () {
    const quizEl = document.getElementById('quiz');
    if (!quizEl) return;

    const categoria = quizEl.dataset.categoria;
    let bloqueado = false;

    const perguntaTexto = document.getElementById('pergunta-texto');
    const alternativasEl = document.getElementById('alternativas');
    const feedbackEl = document.getElementById('feedback');
    const placarEl = document.getElementById('placar');
    const btnFalar = document.getElementById('btn-falar');
    const btnProxima = document.getElementById('btn-proxima');

    let perguntaAtual = null;

    function falarJapones(texto) {
        if (!('speechSynthesis' in window)) return;
        window.speechSynthesis.cancel();
        const utter = new SpeechSynthesisUtterance(texto);
        utter.lang = 'ja-JP';
        utter.rate = 0.85;
        window.speechSynthesis.speak(utter);
    }

    async function carregarPergunta() {
        bloqueado = false;
        feedbackEl.textContent = '';
        feedbackEl.className = 'feedback-inline';
        alternativasEl.innerHTML = '<p>Carregando...</p>';

        const resp = await fetch('api/pergunta.php?categoria=' + encodeURIComponent(categoria));
        const dados = await resp.json();

        if (dados.erro) {
            alternativasEl.innerHTML = '<p>' + dados.erro + '</p>';
            return;
        }

        perguntaAtual = dados;
        perguntaTexto.textContent = dados.pergunta;
        perguntaTexto.classList.toggle('frase', dados.pergunta.length > 12);

        alternativasEl.innerHTML = '';
        dados.alternativas.forEach(function (alt) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'alt-btn';
            btn.textContent = alt;
            btn.addEventListener('click', function () { responder(alt, btn); });
            alternativasEl.appendChild(btn);
        });
    }

    async function responder(respostaEscolhida, botaoClicado) {
        if (bloqueado || !perguntaAtual) return;
        bloqueado = true;

        const todosBotoes = alternativasEl.querySelectorAll('.alt-btn');
        todosBotoes.forEach(function (b) { b.disabled = true; });

        const resp = await fetch('api/responder.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                categoria: categoria,
                item_id: perguntaAtual.item_id,
                resposta: respostaEscolhida
            })
        });
        const resultado = await resp.json();

        if (resultado.erro) {
            feedbackEl.textContent = resultado.erro;
            feedbackEl.classList.add('errado-texto');
            bloqueado = false;
            todosBotoes.forEach(function (b) { b.disabled = false; });
            return;
        }

        // Marca visualmente certo/errado (sem popup)
        todosBotoes.forEach(function (b) {
            if (b.textContent === resultado.resposta_correta) {
                b.classList.add('certo');
            } else if (b === botaoClicado && !resultado.acertou) {
                b.classList.add('errado');
            }
        });

        if (resultado.acertou) {
            feedbackEl.textContent = 'Certo! +1 ponto';
            feedbackEl.classList.add('certo-texto');
        } else {
            feedbackEl.textContent = 'Errado! -1 ponto. Resposta certa: ' + resultado.resposta_correta;
            feedbackEl.classList.add('errado-texto');
        }

        placarEl.textContent = 'Pontos: ' + resultado.novo_total;
    }

    if (btnFalar) {
        btnFalar.addEventListener('click', function () {
            if (perguntaAtual) falarJapones(perguntaAtual.pergunta);
        });
    }

    if (btnProxima) {
        btnProxima.addEventListener('click', carregarPergunta);
    }

    carregarPergunta();
});
