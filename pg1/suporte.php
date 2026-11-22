<?php
declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');
session_start();

$aluno = require __DIR__ . '/includes/aluno.php';

function h(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

$assuntos = [
    'Acesso e senha',
    'Notas e boletim',
    'Mensalidade',
    'Aulas e calendário',
    'Outro assunto',
];

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

// Mensagem de sucesso (vem do redirecionamento após o envio)
$protocolo = $_SESSION['protocolo'] ?? null;
unset($_SESSION['protocolo']);

$erros   = [];
$valores = [
    'nome'     => $aluno['nome'],
    'email'    => '',
    'assunto'  => '',
    'mensagem' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($valores as $campo => $padrao) {
        $valores[$campo] = trim((string) ($_POST[$campo] ?? ''));
    }

    $token = (string) ($_POST['csrf'] ?? '');
    if (!hash_equals($_SESSION['csrf'], $token)) {
        $erros[] = 'A sessão expirou. Recarregue a página e envie de novo.';
    }

    $tamNome = mb_strlen($valores['nome']);
    if ($tamNome < 3 || $tamNome > 100) {
        $erros[] = 'Informe seu nome (de 3 a 100 caracteres).';
    }

    if (!filter_var($valores['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($valores['email']) > 120) {
        $erros[] = 'Informe um e-mail válido.';
    }

    if (!in_array($valores['assunto'], $assuntos, true)) {
        $erros[] = 'Escolha um assunto da lista.';
    }

    $tamMsg = mb_strlen($valores['mensagem']);
    if ($tamMsg < 10 || $tamMsg > 2000) {
        $erros[] = 'Escreva uma mensagem de 10 a 2000 caracteres.';
    }

    if (!$erros) {
        $novoProtocolo = 'SUP-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

        $registro = [
            'protocolo' => $novoProtocolo,
            'data'      => date('Y-m-d H:i:s'),
            'nome'      => $valores['nome'],
            'email'     => $valores['email'],
            'assunto'   => $valores['assunto'],
            'mensagem'  => $valores['mensagem'],
        ];

        $pasta = __DIR__ . '/dados';
        if (!is_dir($pasta)) {
            mkdir($pasta, 0755, true);
        }

        $linha  = json_encode($registro, JSON_UNESCAPED_UNICODE) . PHP_EOL;
        $gravou = @file_put_contents($pasta . '/chamados.jsonl', $linha, FILE_APPEND | LOCK_EX);

        if ($gravou === false) {
            $erros[] = 'Não foi possível registrar o chamado agora. Verifique a permissão de escrita da pasta "dados".';
        } else {
            $_SESSION['protocolo'] = $novoProtocolo;
            $_SESSION['csrf']      = bin2hex(random_bytes(32));
            header('Location: suporte.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Portal Escolar - Suporte</title>
  <link rel="stylesheet" href="paginas.css">
  <link rel="stylesheet" href="menu.css">
</head>
<body>

<?php $modoMenu = 'link'; require __DIR__ . '/includes/menu.php'; ?>

  <main class="pagina">
    <h1>Fale com o suporte</h1>

    <?php if ($protocolo !== null): ?>
      <div class="aviso aviso-ok">
        Chamado enviado com sucesso! Guarde o seu protocolo:
        <strong><?= h($protocolo) ?></strong>
      </div>
    <?php endif; ?>

    <?php if ($erros): ?>
      <div class="aviso aviso-erro">
        Corrija o que falta e envie novamente:
        <ul>
          <?php foreach ($erros as $erro): ?>
            <li><?= h($erro) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <section class="cartao">
      <form method="post" action="suporte.php">
        <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">

        <div class="campo">
          <label for="nome">Nome</label>
          <input type="text" id="nome" name="nome" maxlength="100" required value="<?= h($valores['nome']) ?>">
        </div>

        <div class="campo">
          <label for="email">E-mail para resposta</label>
          <input type="email" id="email" name="email" maxlength="120" placeholder="seu@email.com" required value="<?= h($valores['email']) ?>">
        </div>

        <div class="campo">
          <label for="assunto">Assunto</label>
          <select id="assunto" name="assunto" required>
            <option value="">Escolha um assunto</option>
            <?php foreach ($assuntos as $assunto): ?>
              <option value="<?= h($assunto) ?>"<?= $valores['assunto'] === $assunto ? ' selected' : '' ?>><?= h($assunto) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="campo">
          <label for="mensagem">Mensagem</label>
          <textarea id="mensagem" name="mensagem" maxlength="2000" required><?= h($valores['mensagem']) ?></textarea>
        </div>

        <button type="submit" class="btn-enviar">Enviar chamado</button>
      </form>
    </section>
  </main>

</body>
</html>
