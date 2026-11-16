<?php
/**
 * Configuração de conexão com o banco de dados MySQL.
 * Ajuste a senha (DB_PASS) e o host (DB_HOST) conforme os dados
 * fornecidos pelo seu provedor de hospedagem.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'neimar60_japones_app');
define('DB_USER', 'neimar60_neimar');
define('DB_PASS', 'COLOQUE_A_SENHA_DO_BANCO_AQUI'); // <-- defina a senha do usuário do MySQL

function getConexao(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $opcoes = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opcoes);
        } catch (PDOException $e) {
            http_response_code(500);
            die('Erro ao conectar ao banco de dados. Verifique config/database.php.');
        }
    }

    return $pdo;
}
