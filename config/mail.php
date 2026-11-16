<?php
/**
 * Credenciais de e-mail (SMTP) usadas para enviar o link de "esqueci minha senha".
 *
 * Como obter esses dados no cPanel:
 * 1. Crie uma conta de e-mail em cPanel > Contas de E-mail (ex.: no-reply@seusite.com).
 * 2. Clique em "Conectar dispositivos" (ou "Configurar cliente de e-mail") nessa conta.
 * 3. O cPanel mostra o host SMTP e as portas recomendadas (SSL ou TLS). Copie para cá.
 */

define('SMTP_HOST', 'mail.seusite.com');   // host de SMTP do seu cPanel
define('SMTP_PORT', 465);                  // 465 = SSL, 587 = TLS
define('SMTP_SECURE', 'ssl');              // 'ssl' para porta 465, 'tls' para porta 587
define('SMTP_USUARIO', 'no-reply@seusite.com'); // e-mail completo criado no cPanel
define('SMTP_SENHA', 'SENHA_DESSA_CONTA_DE_EMAIL');  // senha dessa conta de e-mail
define('SMTP_FROM_NOME', 'Nihongo App');

// E-mail do administrador que recebe as mensagens do formulário "Dúvidas/Sugestões"
define('ADMIN_EMAIL', 'neimaro@gmail.com');
