<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

/**
 * Retorna uma pergunta aleatória de uma categoria, junto com 3 alternativas erradas
 * (para montar múltipla escolha) sorteadas da mesma tabela.
 */
function sortearPergunta(string $categoria): ?array
{
    if (!isset(CATEGORIAS[$categoria])) {
        return null;
    }
    $info = CATEGORIAS[$categoria];
    $pdo = getConexao();

    $tabela = $info['tabela'];
    $campoP = $info['campo_pergunta'];
    $campoR = $info['campo_resposta'];

    // Pergunta correta
    $stmt = $pdo->query("SELECT id, `$campoP` AS pergunta, `$campoR` AS resposta FROM `$tabela` ORDER BY RAND() LIMIT 1");
    $certa = $stmt->fetch();
    if (!$certa) {
        return null;
    }

    // 3 alternativas erradas
    $stmt = $pdo->prepare("SELECT `$campoR` AS resposta FROM `$tabela` WHERE id != :id ORDER BY RAND() LIMIT 3");
    $stmt->execute(['id' => $certa['id']]);
    $erradas = array_column($stmt->fetchAll(), 'resposta');

    $alternativas = array_merge([$certa['resposta']], $erradas);
    shuffle($alternativas);

    return [
        'item_id'       => (int) $certa['id'],
        'pergunta'      => $certa['pergunta'],
        'resposta'      => $certa['resposta'],
        'alternativas'  => $alternativas,
    ];
}

/**
 * Busca no banco a resposta correta de um item específico (evita confiar
 * em valores vindos do cliente para decidir a pontuação).
 */
function buscarRespostaCorreta(string $categoria, int $itemId): ?string
{
    if (!isset(CATEGORIAS[$categoria])) {
        return null;
    }
    $info = CATEGORIAS[$categoria];
    $pdo = getConexao();

    $stmt = $pdo->prepare("SELECT `{$info['campo_resposta']}` AS resposta FROM `{$info['tabela']}` WHERE id = :id");
    $stmt->execute(['id' => $itemId]);
    $linha = $stmt->fetch();

    return $linha ? $linha['resposta'] : null;
}

/**
 * Verifica a resposta e atualiza a pontuação do usuário na tabela `pontos`.
 * +1 ponto em caso de acerto, -1 em caso de erro.
 */
function responderPergunta(int $usuarioId, string $categoria, int $itemId, string $respostaEnviada): ?array
{
    $respostaCorreta = buscarRespostaCorreta($categoria, $itemId);
    if ($respostaCorreta === null) {
        return null;
    }

    $acertou = mb_strtolower(trim($respostaEnviada)) === mb_strtolower(trim($respostaCorreta));
    $variacao = $acertou ? 1 : -1;

    $novoTotal = atualizarPontos($usuarioId, $categoria, $variacao);

    return [
        'acertou'         => $acertou,
        'variacao'        => $variacao,
        'novo_total'      => $novoTotal,
        'resposta_correta'=> $respostaCorreta,
    ];
}

/**
 * Soma (ou subtrai) pontos na coluna da categoria informada.
 * frasesN5 e exercicioN5 são VARCHAR no banco, então são tratados como texto numérico.
 */
function atualizarPontos(int $usuarioId, string $categoria, int $variacao): int
{
    if (!isset(CATEGORIAS[$categoria])) {
        throw new InvalidArgumentException('Categoria inválida.');
    }
    $coluna = CATEGORIAS[$categoria]['coluna'];
    $pdo = getConexao();

    $stmt = $pdo->prepare("SELECT `$coluna` AS valor FROM pontos WHERE id = :id");
    $stmt->execute(['id' => $usuarioId]);
    $linha = $stmt->fetch();

    $atual = $linha ? (int) $linha['valor'] : 0;
    $novo = $atual + $variacao;

    $stmt = $pdo->prepare("UPDATE pontos SET `$coluna` = :novo WHERE id = :id");
    $stmt->execute(['novo' => (string) $novo, 'id' => $usuarioId]);

    return $novo;
}

/**
 * Retorna dados para o painel administrativo: total de usuários,
 * pontuação de cada usuário em cada categoria e o total agregado por categoria.
 */
function obterEstatisticasAdmin(): array
{
    $pdo = getConexao();

    $stmt = $pdo->query(
        'SELECT u.id, u.email, u.criado_em, u.is_admin,
                p.hiragana, p.katakana, p.kanjiN5, p.whiragana, p.wkatakana, p.mistokanjiN5,
                p.frasesN5, p.exercicioN5
         FROM usuarios u
         LEFT JOIN pontos p ON p.id = u.id
         ORDER BY u.email'
    );
    $linhas = $stmt->fetchAll();

    $colunas = ['hiragana','katakana','kanjiN5','whiragana','wkatakana','mistokanjiN5','frasesN5','exercicioN5'];
    $agregado = array_fill_keys($colunas, 0);
    $usuarios = [];

    foreach ($linhas as $linha) {
        $pontosUsuario = [];
        foreach ($colunas as $col) {
            $valor = (int) ($linha[$col] ?? 0);
            $pontosUsuario[$col] = $valor;
            $agregado[$col] += $valor;
        }
        $usuarios[] = [
            'id'        => (int) $linha['id'],
            'email'     => $linha['email'],
            'criado_em' => $linha['criado_em'],
            'is_admin'  => (bool) $linha['is_admin'],
            'pontos'    => $pontosUsuario,
            'total'     => array_sum($pontosUsuario),
        ];
    }

    return [
        'total_usuarios' => count($usuarios),
        'usuarios'       => $usuarios,
        'agregado'       => $agregado,
    ];
}

/**
 * Retorna todos os pontos do usuário, já convertidos para inteiro.
 */
function obterPontos(int $usuarioId): array
{
    $pdo = getConexao();
    $stmt = $pdo->prepare('SELECT hiragana, katakana, kanjiN5, whiragana, wkatakana, mistokanjiN5, frasesN5, exercicioN5 FROM pontos WHERE id = :id');
    $stmt->execute(['id' => $usuarioId]);
    $linha = $stmt->fetch();

    if (!$linha) {
        return array_fill_keys(['hiragana','katakana','kanjiN5','whiragana','wkatakana','mistokanjiN5','frasesN5','exercicioN5'], 0);
    }

    return array_map('intval', $linha);
}

/**
 * Exclui um usuário (somente chamado a partir do painel admin).
 * Retorna string vazia em caso de sucesso, ou uma mensagem de erro.
 *
 * - Não permite excluir a própria conta do administrador logado.
 * - Não permite excluir outras contas de administrador por aqui (evita
 *   perder o acesso ao painel por engano; isso só deve ser feito direto no banco).
 * - A linha correspondente em `pontos` é apagada automaticamente (FK ON DELETE CASCADE)
 *   e as mensagens de `duvidas_sugestoes` ficam preservadas (usuario_id vira NULL).
 */
function excluirUsuario(int $usuarioId, int $adminLogadoId): string
{
    if ($usuarioId <= 0) {
        return 'Usuário inválido.';
    }
    if ($usuarioId === $adminLogadoId) {
        return 'Você não pode excluir a sua própria conta.';
    }

    $pdo = getConexao();

    $stmt = $pdo->prepare('SELECT id, email, is_admin FROM usuarios WHERE id = :id');
    $stmt->execute(['id' => $usuarioId]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        return 'Usuário não encontrado (talvez já tenha sido excluído).';
    }
    if ((int) $usuario['is_admin'] === 1) {
        return 'Contas de administrador não podem ser excluídas por aqui.';
    }

    $stmt = $pdo->prepare('DELETE FROM usuarios WHERE id = :id');
    $stmt->execute(['id' => $usuarioId]);

    return '';
}
