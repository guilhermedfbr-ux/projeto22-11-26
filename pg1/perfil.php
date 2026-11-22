<?php
declare(strict_types=1);

session_start();

$aluno = require __DIR__ . '/includes/aluno.php';

function h(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['csrf_perfil'])) {
    $_SESSION['csrf_perfil'] = bin2hex(random_bytes(32));
}

// Mensagem de sucesso (vem do redirecionamento após salvar)
$mensagem = $_SESSION['config_ok'] ?? null;
unset($_SESSION['config_ok']);

$pasta   = __DIR__ . '/dados';
$erros   = [];
$valores = [
    'nome'  => $aluno['nome'],
    'email' => $aluno['email'],
];

$tamanhoMaximo = 2 * 1024 * 1024; // 2 MB
$extensoes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = (string) ($_POST['acao'] ?? 'salvar');

    if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        // O servidor recusou o envio inteiro por ser grande demais
        $erros[] = 'A imagem é grande demais. Envie uma foto de até 2 MB.';
    } elseif (!hash_equals($_SESSION['csrf_perfil'], (string) ($_POST['csrf'] ?? ''))) {
        $erros[] = 'A sessão expirou. Recarregue a página e tente de novo.';
    } else {
        $novoNome  = $aluno['nome'];
        $novoEmail = $aluno['email'];
        $novaFoto  = $aluno['foto_arquivo'];
        $novoHash  = $aluno['senha_hash'];
        $fotoEnviada = null; // nome do arquivo novo, se houver upload válido
        $textoOk = 'Alterações salvas com sucesso!';

        if ($acao === 'remover_foto') {
            $novaFoto = '';
            $textoOk  = 'Foto removida.';
        } elseif ($acao === 'senha') {
            $atual    = (string) ($_POST['senha_atual'] ?? '');
            $nova     = (string) ($_POST['senha_nova'] ?? '');
            $confirma = (string) ($_POST['senha_confirma'] ?? '');

            // Enquanto a senha nunca foi trocada, vale a senha inicial
            $atualCorreta = $aluno['senha_hash'] !== ''
                ? password_verify($atual, $aluno['senha_hash'])
                : hash_equals($aluno['senha_inicial'], $atual);

            if (!$atualCorreta) {
                usleep(500000); // pequena pausa contra tentativas em sequência
                $erros[] = 'A senha atual está incorreta.';
            }

            if (mb_strlen($nova) < 8 || strlen($nova) > 72) {
                $erros[] = 'A nova senha deve ter de 8 a 72 caracteres.';
            } elseif ($nova === $atual) {
                $erros[] = 'A nova senha deve ser diferente da atual.';
            }

            if ($nova !== $confirma) {
                $erros[] = 'A confirmação não é igual à nova senha.';
            }

            if (!$erros) {
                $novoHash = password_hash($nova, PASSWORD_DEFAULT);
                $textoOk  = 'Senha alterada com sucesso!';
            }
        } else {
            $valores['nome']  = trim((string) ($_POST['nome'] ?? ''));
            $valores['email'] = trim((string) ($_POST['email'] ?? ''));

            $tamNome = mb_strlen($valores['nome']);
            if ($tamNome < 2 || $tamNome > 100 || preg_match('/[\x00-\x1F\x7F]/', $valores['nome']) === 1) {
                $erros[] = 'Informe um nome de 2 a 100 caracteres.';
            }

            if (!filter_var($valores['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($valores['email']) > 120) {
                $erros[] = 'Informe um e-mail válido.';
            }

            $arquivo = $_FILES['foto'] ?? null;
            if (is_array($arquivo) && ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                if ($arquivo['error'] === UPLOAD_ERR_INI_SIZE || $arquivo['error'] === UPLOAD_ERR_FORM_SIZE) {
                    $erros[] = 'A imagem é grande demais. Envie uma foto de até 2 MB.';
                } elseif ($arquivo['error'] !== UPLOAD_ERR_OK) {
                    $erros[] = 'Não foi possível enviar a imagem. Tente novamente.';
                } elseif ($arquivo['size'] > $tamanhoMaximo) {
                    $erros[] = 'A imagem é grande demais. Envie uma foto de até 2 MB.';
                } else {
                    $info = @getimagesize($arquivo['tmp_name']);
                    if ($info === false || !isset($extensoes[$info['mime']])) {
                        $erros[] = 'Use uma imagem JPG, PNG ou WEBP.';
                    } elseif ($info[0] > 5000 || $info[1] > 5000) {
                        $erros[] = 'A imagem tem dimensões grandes demais (máximo 5000 x 5000 pixels).';
                    } elseif (!$erros) {
                        $fotoEnviada = bin2hex(random_bytes(8)) . '.' . $extensoes[$info['mime']];
                        if (!is_dir($pasta)) {
                            mkdir($pasta, 0755, true);
                        }
                        if (!move_uploaded_file($arquivo['tmp_name'], $pasta . '/' . $fotoEnviada)) {
                            $fotoEnviada = null;
                            $erros[] = 'Não foi possível salvar a imagem. Verifique a permissão de escrita da pasta "dados".';
                        } else {
                            $novaFoto = $fotoEnviada;
                        }
                    }
                }
            }

            if (!$erros) {
                $novoNome  = $valores['nome'];
                $novoEmail = $valores['email'];
            }
        }

        if (!$erros) {
            $json = json_encode(
                [
                    'nome'       => $novoNome,
                    'email'      => $novoEmail,
                    'foto'       => $novaFoto,
                    'senha_hash' => $novoHash,
                ],
                JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            );
            $gravou = @file_put_contents($pasta . '/perfil.json', $json, LOCK_EX);

            if ($gravou === false) {
                if ($fotoEnviada !== null) {
                    @unlink($pasta . '/' . $fotoEnviada);
                }
                $erros[] = 'Não foi possível salvar agora. Verifique a permissão de escrita da pasta "dados".';
            } else {
                // Apaga a foto antiga quando ela foi trocada ou removida
                if ($aluno['foto_arquivo'] !== '' && $aluno['foto_arquivo'] !== $novaFoto) {
                    @unlink($pasta . '/' . $aluno['foto_arquivo']);
                }
                $_SESSION['config_ok']   = $textoOk;
                $_SESSION['csrf_perfil'] = bin2hex(random_bytes(32));
                header('Location: perfil.php#configuracoes');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Portal Escolar - Meu perfil</title>
  <link rel="stylesheet" href="paginas.css">
  <link rel="stylesheet" href="menu.css">
</head>
<body>

<?php $modoMenu = 'link'; require __DIR__ . '/includes/menu.php'; ?>

  <main class="pagina">
    <h1>Meu perfil</h1>

    <section class="cartao">
      <div class="perfil-cabeca">
        <div class="avatar-grande">
<?php if ($aluno['foto_url'] !== ''): ?>
          <img src="<?= h($aluno['foto_url']) ?>" alt="Foto de <?= h($aluno['nome']) ?>">
<?php else: ?>
          <?= h($aluno['avatar']) ?>
<?php endif; ?>
        </div>
        <div>
          <strong><?= h($aluno['nome']) ?></strong>
          <small><?= h($aluno['tipo']) ?></small>
        </div>
      </div>
    </section>

    <section class="cartao">
      <h2>Dados do aluno</h2>
      <dl class="dados">
        <div>
          <dt>Nome</dt>
          <dd><?= h($aluno['nome']) ?></dd>
        </div>
        <div>
          <dt>E-mail</dt>
          <dd><?= h($aluno['email']) ?></dd>
        </div>
        <div>
          <dt>Matrícula</dt>
          <dd><?= h($aluno['matricula']) ?></dd>
        </div>
        <div>
          <dt>Ano letivo</dt>
          <dd><?= h($aluno['ano_letivo']) ?></dd>
        </div>
        <div>
          <dt>Turma</dt>
          <dd><?= h($aluno['turma']) ?></dd>
        </div>
        <div>
          <dt>Turno</dt>
          <dd><?= h($aluno['turno']) ?></dd>
        </div>
        <div>
          <dt>Coordenador</dt>
          <dd><?= h($aluno['coordenador']) ?></dd>
        </div>
      </dl>
      <p class="nota-exemplo">Nome, e-mail e foto podem ser alterados em Configurações, logo abaixo. Os demais dados são exemplos.</p>
    </section>

    <section class="cartao" id="configuracoes">
      <h2>Configurações</h2>

<?php if ($mensagem !== null): ?>
      <div class="aviso aviso-ok"><?= h($mensagem) ?></div>
<?php endif; ?>

<?php if ($erros): ?>
      <div class="aviso aviso-erro">
        Corrija o que falta e tente novamente:
        <ul>
<?php foreach ($erros as $erro): ?>
          <li><?= h($erro) ?></li>
<?php endforeach; ?>
        </ul>
      </div>
<?php endif; ?>

      <form method="post" action="perfil.php#configuracoes" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf_perfil']) ?>">

        <div class="campo">
          <label for="nome">Nome</label>
          <input type="text" id="nome" name="nome" maxlength="100" required value="<?= h($valores['nome']) ?>">
        </div>

        <div class="campo">
          <label for="email">E-mail</label>
          <input type="email" id="email" name="email" maxlength="120" required value="<?= h($valores['email']) ?>">
        </div>

        <div class="campo">
          <label for="foto">Foto de perfil</label>
          <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp">
          <p class="dica">JPG, PNG ou WEBP, até 2 MB. Deixe em branco para manter a foto atual.</p>
        </div>

        <div class="acoes-form">
          <button type="submit" name="acao" value="salvar" class="btn-enviar">Salvar alterações</button>
<?php if ($aluno['foto_arquivo'] !== ''): ?>
          <button type="submit" name="acao" value="remover_foto" class="btn-secundario" formnovalidate>Remover foto</button>
<?php endif; ?>
        </div>
      </form>

      <h3>Alterar senha</h3>
      <p class="dica">Opcional. Use só se quiser trocar a senha. A nova senha precisa ter pelo menos 8 caracteres.</p>

      <form method="post" action="perfil.php#configuracoes">
        <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf_perfil']) ?>">

        <div class="campo">
          <label for="senha_atual">Senha atual</label>
          <input type="password" id="senha_atual" name="senha_atual" autocomplete="current-password" required>
        </div>

        <div class="campo">
          <label for="senha_nova">Nova senha</label>
          <input type="password" id="senha_nova" name="senha_nova" minlength="8" maxlength="72" autocomplete="new-password" required>
        </div>

        <div class="campo">
          <label for="senha_confirma">Confirmar nova senha</label>
          <input type="password" id="senha_confirma" name="senha_confirma" minlength="8" maxlength="72" autocomplete="new-password" required>
        </div>

        <div class="acoes-form">
          <button type="submit" name="acao" value="senha" class="btn-enviar">Alterar senha</button>
        </div>
      </form>
    </section>

  </main>

</body>
</html>
