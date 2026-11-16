<?php
declare(strict_types=1);

/**
 * Cliente SMTP mínimo (sem dependências externas) para enviar e-mails
 * autenticados usando uma conta de e-mail real do seu cPanel.
 *
 * Uso típico com o cPanel:
 *   Host: mail.seusite.com  (ou o host de SMTP que o cPanel mostrar em
 *         "Configurar cliente de e-mail" dentro de Contas de E-mail)
 *   Porta 465 com secure = 'ssl'   OU   porta 587 com secure = 'tls'
 *   Usuário: o e-mail completo, ex. no-reply@seusite.com
 *   Senha:  a senha dessa conta de e-mail (criada em cPanel > Contas de E-mail)
 */
class SmtpMailer
{
    public function __construct(
        private string $host,
        private int $port,
        private string $usuario,
        private string $senha,
        private string $secure, // 'ssl' ou 'tls'
        private string $fromEmail,
        private string $fromNome = ''
    ) {}

    /**
     * Envia um e-mail. Retorna ['sucesso' => bool, 'erro' => string].
     * $replyTo (opcional): se informado, adiciona um cabeçalho Reply-To,
     * assim quem receber o e-mail pode simplesmente clicar em "Responder"
     * e a resposta vai direto para esse endereço.
     */
    public function enviar(string $destinatario, string $assunto, string $corpo, string $replyTo = ''): array
    {
        $prefixo = $this->secure === 'ssl' ? 'ssl://' : '';
        $socket = @fsockopen($prefixo . $this->host, $this->port, $codigo, $mensagemErro, 15);

        if (!$socket) {
            return ['sucesso' => false, 'erro' => "Falha ao conectar em {$this->host}:{$this->port} — $mensagemErro"];
        }

        try {
            $this->lerResposta($socket, 220);

            $this->comando($socket, "EHLO " . $this->hostLocal(), 250);

            if ($this->secure === 'tls') {
                $this->comando($socket, "STARTTLS", 220);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    return ['sucesso' => false, 'erro' => 'Falha ao iniciar TLS (STARTTLS).'];
                }
                $this->comando($socket, "EHLO " . $this->hostLocal(), 250);
            }

            $this->comando($socket, "AUTH LOGIN", 334);
            $this->comando($socket, base64_encode($this->usuario), 334);
            $this->comando($socket, base64_encode($this->senha), 235);

            $this->comando($socket, "MAIL FROM:<{$this->fromEmail}>", 250);
            $this->comando($socket, "RCPT TO:<{$destinatario}>", 250);
            $this->comando($socket, "DATA", 354);

            $nomeDe = $this->fromNome !== '' ? "{$this->fromNome} <{$this->fromEmail}>" : $this->fromEmail;
            $cabecalhoReplyTo = $replyTo !== '' ? "Reply-To: {$replyTo}\r\n" : '';
            $dataMsg = "From: {$nomeDe}\r\n"
                . "To: <{$destinatario}>\r\n"
                . $cabecalhoReplyTo
                . "Subject: =?UTF-8?B?" . base64_encode($assunto) . "?=\r\n"
                . "MIME-Version: 1.0\r\n"
                . "Content-Type: text/plain; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: base64\r\n\r\n"
                . chunk_split(base64_encode($corpo))
                . "\r\n.";

            $this->comando($socket, $dataMsg, 250);
            $this->comando($socket, "QUIT", 221);
        } catch (RuntimeException $ex) {
            fclose($socket);
            return ['sucesso' => false, 'erro' => $ex->getMessage()];
        }

        fclose($socket);
        return ['sucesso' => true, 'erro' => ''];
    }

    private function hostLocal(): string
    {
        return $_SERVER['HTTP_HOST'] ?? 'localhost';
    }

    private function comando($socket, string $comando, int $codigoEsperado): string
    {
        fwrite($socket, $comando . "\r\n");
        return $this->lerResposta($socket, $codigoEsperado);
    }

    private function lerResposta($socket, int $codigoEsperado): string
    {
        $resposta = '';
        while ($linha = fgets($socket, 515)) {
            $resposta .= $linha;
            // linha final de uma resposta multi-linha tem um espaço após o código (não hífen)
            if (isset($linha[3]) && $linha[3] === ' ') {
                break;
            }
        }

        $codigo = (int) substr($resposta, 0, 3);
        if ($codigo !== $codigoEsperado) {
            throw new RuntimeException("Servidor SMTP respondeu: " . trim($resposta));
        }

        return $resposta;
    }
}
