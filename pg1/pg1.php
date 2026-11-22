<?php
declare(strict_types=1);

$aluno = require __DIR__ . '/includes/aluno.php';

function h(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

// Seção que abre marcada (ex.: pg1.php?aba=notas). Só aceita nomes da lista.
$abas = ['aulas', 'notas', 'turmas', 'reunioes', 'calendario', 'mensalidade'];
$abaAtual = $_GET['aba'] ?? 'aulas';
if (!is_string($abaAtual) || !in_array($abaAtual, $abas, true)) {
    $abaAtual = 'aulas';
}

$modoMenu = 'painel';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Portal Escolar - Painel do Aluno</title>
  <link rel="stylesheet" href="pg.css">
  <link rel="stylesheet" href="menu.css">
</head>
<body>

  <div class="layout-escola">

    <!-- Controles invisíveis das abas (devem ficar antes do menu e do conteúdo) -->
<?php foreach ($abas as $aba): ?>
    <input type="radio" name="abas" id="tab-<?= $aba ?>" class="radio-aba"<?= $aba === $abaAtual ? ' checked' : '' ?>>
<?php endforeach; ?>

<?php require __DIR__ . '/includes/menu.php'; ?>

    <!-- Área Principal de Conteúdo -->
    <main class="conteudo-principal">

      <header class="cabecalho-topo">
        <div>
          <h1>Bem-vindo ao Painel do Aluno</h1>
          <span class="ano-letivo">Ano Letivo: <?= h($aluno['ano_letivo']) ?></span>
        </div>

        <div class="topo-acoes">
          <!-- Fora da barra deslizante: abre em nova aba -->
          <a href="suporte.php" class="btn-suporte" target="_blank" rel="noopener">Suporte</a>

          <div class="perfil-usuario">
            <a href="perfil.php" class="perfil-link" target="_blank" rel="noopener" title="Abrir meu perfil em uma nova aba">
              <div class="avatar">
<?php if ($aluno['foto_url'] !== ''): ?>
                <img src="<?= h($aluno['foto_url']) ?>" alt="Foto de <?= h($aluno['nome']) ?>">
<?php else: ?>
                <?= h($aluno['avatar']) ?>
<?php endif; ?>
              </div>
              <div class="perfil-texto">
                <strong><?= h($aluno['nome']) ?></strong>
                <small><?= h($aluno['tipo']) ?></small>
              </div>
            </a>
            <div class="perfil-menu">
              <a href="perfil.php" target="_blank" rel="noopener">Meu perfil</a>
              <a href="perfil.php#configuracoes" target="_blank" rel="noopener">Configurações</a>
              <a href="suporte.php" target="_blank" rel="noopener">Suporte</a>
              <a href="../projeto.html" target="_blank" rel="noopener">Sair do sistema</a>
            </div>
          </div>
        </div>
      </header>

      <!-- Aba 1: Aulas -->
      <section class="conteudo-aba sec-aulas">
        <h2>Minhas Aulas</h2>
        <div class="grade-cards">
          <div class="card">
            <h3>Matemática</h3>
            <p>Segunda e Quarta - 08:00 às 09:40</p>
            <span class="status">Sala 102</span>
          </div>
          <div class="card">
            <h3>História</h3>
            <p>Terça e Quinta - 10:00 às 11:40</p>
            <span class="status">Sala 205</span>
          </div>
        </div>
      </section>

      <!-- Aba 2: Notas -->
      <section class="conteudo-aba sec-notas">
        <h2>Boletim de Notas</h2>
        <table class="tabela-notas">
          <thead>
            <tr>
              <th>Disciplina</th>
              <th>1º Bimestre</th>
              <th>2º Bimestre</th>
              <th>Situação</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Matemática</td>
              <td>8.5</td>
              <td>9.0</td>
              <td>Aprovado</td>
            </tr>
            <tr>
              <td>História</td>
              <td>7.5</td>
              <td>8.0</td>
              <td>Aprovado</td>
            </tr>
          </tbody>
        </table>
      </section>

      <!-- Aba 3: Turmas -->
      <section class="conteudo-aba sec-turmas">
        <h2>Minhas Turmas</h2>
        <div class="card">
          <h3><?= h($aluno['turma']) ?></h3>
          <p>Turno: <?= h($aluno['turno']) ?> | Coordenador: <?= h($aluno['coordenador']) ?></p>
        </div>
      </section>

      <!-- Aba 4: Reuniões -->
      <section class="conteudo-aba sec-reunioes">
        <h2>Reuniões Agendadas</h2>
        <div class="card">
          <h3>Reunião de Pais e Mestres</h3>
          <p>Data: 25/11/2026 às 19:00 | Local: Auditório Principal</p>
        </div>
      </section>

      <!-- Aba 5: Calendário Escolar -->
      <section class="conteudo-aba sec-calendario">
        <h2>Calendário Escolar</h2>
        <div class="card">
          <h3>Próximos Eventos</h3>
          <p><strong>20/11:</strong> Feriado Nacional</p>
          <p><strong>05/12:</strong> Início das Provas Finais</p>
        </div>
      </section>

      <!-- Aba 6: Mensalidade -->
      <section class="conteudo-aba sec-mensalidade">
        <h2>Mensalidades</h2>
        <div class="grade-cards">
          <div class="card">
            <h3>Situação financeira</h3>
            <p><strong>Mensalidade:</strong> R$ 650,00</p>
            <p><strong>Vencimento:</strong> 10/12/2026</p>
            <span class="status-pendente">Pagamento pendente (exemplo)</span>
          </div>
          <div class="card">
            <h3>Histórico de pagamentos</h3>
            <p>Novembro/2026 — R$ 650,00 — Pago (exemplo)</p>
            <p>Outubro/2026 — R$ 650,00 — Pago (exemplo)</p>
            <p class="aviso-exemplo">Os valores e situações são ilustrativos.</p>
          </div>
        </div>
      </section>

    </main>
  </div>

</body>
</html>
