<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title><?= isset($tituloPagina) ? e($tituloPagina) . ' — ' : '' ?>Nihongo App</title>
<link rel="stylesheet" href="css/style.css?v=<?= @filemtime(__DIR__ . '/../css/style.css') ?: time() ?>">
</head>
<body>
<header class="topbar">
    <a class="logo" href="index.php">日本<span>語</span><span class="logo-nome">Japanese BR</span></a>
    <?php if (estaLogado()): ?>
    <nav>
        <a href="index.php">Início</a>
        <a href="referencia.php">Tabelas</a>
        <a href="gramatica.php">Gramática</a>
        <a href="progresso.php">Meu Progresso</a>
        <a href="duvidas.php">Dúvidas/Sugestões</a>
        <?php if (ehAdmin()): ?><a href="admin.php">Admin</a><?php endif; ?>
        <a href="logout.php">Sair</a>
    </nav>
    <?php endif; ?>
</header>
