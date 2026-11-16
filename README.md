# Nihongo App — Estudo de Japonês (PHP + MySQL)

Site responsivo para estudar hiragana, katakana, kanji N5, vocabulário e frases,
com cadastro/login, sistema de pontos (+1 acerto / -1 erro) e botão de
pronúncia em japonês (Text-to-Speech do navegador).

## 1. Requisitos
- PHP 8.0+ com extensão PDO MySQL habilitada
- MySQL / MariaDB
- Hospedagem com suporte a PHP (ex.: cPanel)

## 2. Banco de dados
1. Crie o banco `neimar60_japones_app` e o usuário `neimar60_neimar` (ou importe
   direto — o script já cria o banco se ele não existir, mas em hospedagens
   compartilhadas o banco/usuário geralmente precisam ser criados antes pelo
   painel do provedor).
2. Importe o arquivo `database/schema.sql` (cria as tabelas `usuarios`, `pontos`
   e as tabelas de conteúdo: `hiragana_dados`, `katakana_dados`, `kanji_n5_dados`,
   `palavras_hiragana_dados`, `palavras_katakana_dados`, `misto_n5_dados`,
   `frases_n5_dados`).
3. Abra `config/database.php` e defina a senha real do usuário do banco em
   `DB_PASS` (e ajuste `DB_HOST` se necessário).

## 3. Popular o conteúdo (hiragana, katakana, kanji, palavras, frases)
Depois de importar o schema, acesse uma vez, pelo navegador:

```
https://seusite.com/seed/seed.php
```

Isso vai inserir automaticamente:
- 104 hiragana (básicos + dakuten/handakuten + yōon)
- 104 katakana (idem)
- 112 kanji do nível N5 (JLPT) com significado e leituras
- 80 palavras em hiragana
- 61 palavras em katakana (empréstimos)
- 72 palavras "mistas" (kanji + kana, forma natural de escrita)
- 102 frases simples do nível N5 com tradução
- 55 itens de gramática/vocabulário inicial (cumprimentos, sala de aula e números)

**Depois de rodar, apague o arquivo `seed/seed.php` do servidor** por segurança
(ele apaga e reinsere o conteúdo toda vez que é executado).

## 4. Estrutura de pastas
```
config/       -> conexão com o banco e configurações gerais
includes/     -> autenticação (auth.php), lógica de perguntas/pontos (functions.php), header/footer
data/         -> arrays PHP com o conteúdo (fonte usada pelo seed.php)
seed/         -> script de importação do conteúdo para o banco
api/          -> endpoints AJAX (pergunta.php, responder.php)
css/, js/     -> estilos e script do quiz (fetch + Web Speech API)
*.php (raiz)  -> páginas: index, login, cadastro, esqueci_senha, redefinir_senha, categoria
```

## 5. Sobre a tabela `pontos`
- `id` é ao mesmo tempo chave primária de `pontos` e chave estrangeira para
  `usuarios.id` (relação 1 para 1 — cada usuário tem uma linha de pontuação).
- Colunas `hiragana`, `katakana`, `kanjiN5`, `whiragana`, `wkatakana`,
  `mistokanjiN5` são `INT(20)`.
- `frasesN5` e `exercicioN5` foram definidas como `VARCHAR(255)`, exatamente
  como pedido na especificação. O sistema usa `frasesN5` para guardar o total
  de pontos da categoria "frases" como texto numérico (ex.: `"7"`). O campo
  `exercicioN5` fica reservado para uma futura categoria de exercícios extra
  e não é usado pelo quiz atual.

## 6. "Esqueci minha senha"
O fluxo gera um token com validade de 1 hora e tenta enviar por e-mail usando
a função nativa `mail()` do PHP. Em muitas hospedagens compartilhadas (cPanel)
isso já funciona sem configuração extra. Se o e-mail não chegar, o mais comum
é configurar um envio via SMTP autenticado (ex.: biblioteca PHPMailer com as
credenciais de e-mail do seu provedor) dentro de `esqueci_senha.php`.

## 7. Como o quiz funciona
- `categoria.php` monta a tela e chama `js/app.js`.
- `js/app.js` busca uma pergunta em `api/pergunta.php` (sorteia 1 item certo +
  3 alternativas erradas da mesma tabela) e envia a resposta escolhida para
  `api/responder.php`.
- `api/responder.php` confere a resposta **no servidor** (busca a resposta
  certa no banco pelo `item_id`, não confia no que vem do navegador), atualiza
  a pontuação (+1 / -1) na tabela `pontos` e devolve o resultado.
- O feedback é mostrado **inline** na própria tela (verde = certo, vermelho =
  errado), sem popups, e o placar no topo é atualizado na hora.
- O botão 🔊 usa a Web Speech API (`speechSynthesis`, idioma `ja-JP`) para
  falar a palavra/frase em japonês — funciona no navegador, sem custo de API.

## 9. Painel do administrador (`admin.php`)
Mostra o total de usuários cadastrados, um gráfico de pizza com a distribuição
geral de pontos por categoria e, para cada usuário, um card com um gráfico de
pizza individual + a tabela com a pontuação exata em cada categoria.

Para dar acesso a um usuário, marque-o como administrador direto no banco
(depois de ele já ter se cadastrado normalmente pelo site):

```sql
UPDATE usuarios SET is_admin = 1 WHERE email = 'seuemail@exemplo.com';
```

Só usuários com `is_admin = 1` conseguem abrir `admin.php` (qualquer outro é
redirecionado para `index.php`). O link "Admin" aparece no menu do topo
automaticamente para quem tem esse acesso.

Os gráficos usam a biblioteca Chart.js carregada via CDN (cdnjs), então o
servidor onde o site estiver hospedado precisa ter acesso normal à internet
(o que já é o padrão em qualquer hospedagem).

## 11. Página de referência (`referencia.php`)
Mostra as três tabelas completas — hiragana, katakana e kanji N5 — como grades
de caracteres clicáveis, organizadas em abas. Clicar em qualquer caractere
toca a pronúncia com a Web Speech API (mesmo mecanismo do botão 🔊 do quiz).
O conteúdo vem direto das tabelas `hiragana_dados`, `katakana_dados` e
`kanji_n5_dados` (ou seja, é o mesmo conjunto de dados usado nos quizzes,
só que exibido por completo para consulta/estudo).

O link "Tabelas" aparece no menu do topo para qualquer usuário logado.

## 12. Meu Progresso (`progresso.php`)
Versão individual do gráfico de pizza do painel admin, só que qualquer usuário
logado vê **apenas os próprios pontos** (não tem acesso aos dados de outras
pessoas). Mostra o total de pontos, a categoria com melhor desempenho, um
gráfico de pizza da distribuição entre categorias e a tabela com os valores
exatos. Link "Meu Progresso" no menu do topo e um atalho na página inicial.

## 14. Página "Gramática" (`gramatica.php`) — só consulta, sem pontos
Página separada (não é uma categoria de quiz, não gera pontuação) com o
conteúdo inicial extraído de `gramatica_japonês.docx`: cumprimentos (あいさつ),
expressões usadas em sala de aula e números de 1 a 900. Os itens ficam
organizados em cards por grupo, e cada um toca a pronúncia ao ser clicado
(mesmo mecanismo de Text-to-Speech usado no resto do site).

Os dados ficam na tabela `gramatica_dados` (colunas `item`, `romaji`,
`traducao`, `grupo`), populada pelo `seed/seed.php`. Para adicionar mais
conteúdo depois, edite `data/gramatica.php` (formato `[item, romaji, tradução,
grupo]`) e rode o seed novamente — isso substitui todo o conteúdo da tabela.

Link "Gramática" no menu do topo e um atalho na página inicial.

> **Nota de transição:** se você chegou a rodar a versão anterior deste projeto
> (onde "Gramática" era uma categoria de quiz), pode limpar os resquícios com:
> ```sql
> ALTER TABLE pontos DROP COLUMN gramatica;      -- opcional, só se essa coluna existir
> ALTER TABLE gramatica_dados ADD COLUMN grupo VARCHAR(100) NOT NULL DEFAULT 'Geral';
> ```
> Depois rode `seed/seed.php` de novo para preencher a coluna `grupo` corretamente.

## 15. Dúvidas e Sugestões (`duvidas.php`)
Formulário simples (assunto + mensagem) disponível para qualquer usuário
logado. Ao enviar:
1. A mensagem é sempre **salva na tabela `duvidas_sugestoes`** (com o e-mail
   de quem enviou, mesmo que o envio de e-mail falhe — nada se perde).
2. O sistema tenta **notificar por e-mail** o administrador
   (`neimaro@gmail.com`, definido em `config/mail.php` na constante
   `ADMIN_EMAIL`), usando o mesmo cliente SMTP (`includes/SmtpMailer.php`) já
   configurado para o "esqueci minha senha" — ou seja, usa as mesmas
   credenciais `SMTP_*` de `config/mail.php`.

Assim como em `esqueci_senha.php`, `duvidas.php` tem uma constante
`DEBUG_EMAIL_DUVIDAS = true` no topo do arquivo: se o e-mail falhar, o erro
técnico aparece na tela para você depurar. Mude para `false` depois que
confirmar que está funcionando.

Link "Dúvidas/Sugestões" no menu do topo e um atalho na página inicial.

## 16. Exclusão de usuários (painel admin)
Cada card de usuário em `admin.php` tem um botão **"Excluir usuário"**, com
confirmação antes de executar. Ao excluir, os pontos do usuário também são
apagados (FK `ON DELETE CASCADE` em `pontos`), e as mensagens que ele enviou em
Dúvidas/Sugestões continuam salvas (o `usuario_id` fica `NULL`).

Proteções:
- Só administradores acessam (`exigirAdmin()`), e a ação exige token CSRF.
- Não é possível excluir a própria conta nem outras contas de administrador
  por essa tela (isso evita perder o acesso ao painel por engano; se precisar,
  faça direto no banco).

## 17. Segurança implementada
- Senhas com `password_hash()` (bcrypt) / `password_verify()`.
- Consultas sempre via PDO com *prepared statements* (evita SQL Injection).
- Sessões PHP para login; todas as páginas internas exigem login
  (`exigirLogin()`).
- Saída sempre escapada com `htmlspecialchars()` (função `e()`).
