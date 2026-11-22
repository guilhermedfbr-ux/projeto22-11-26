<?php
// Barra deslizante usada em TODAS as páginas (painel, perfil e suporte).
// $modoMenu = 'painel' -> troca de seção na mesma página (usa os rádios do painel)
// $modoMenu = 'link'   -> leva ao painel já na seção escolhida (pg1.php?aba=...)
$modoMenu = $modoMenu ?? 'link';

$itensMenu = [
    'aulas'       => 'Aulas',
    'notas'       => 'Notas',
    'turmas'      => 'Turmas',
    'reunioes'    => 'Reuniões',
    'calendario'  => 'Calendário Escolar',
    'mensalidade' => 'Mensalidade',
];
?>
    <!-- Barra deslizante: só o ícone de 3 barras fica visível; abre ao passar o mouse -->
    <aside class="menu-lateral">
      <div class="menu-icone" tabindex="0" role="button" aria-label="Menu">
        <span></span>
        <span></span>
        <span></span>
      </div>

      <div class="menu-conteudo">
        <div class="logo-escola">
          <h2>Portal Escolar</h2>
        </div>

        <nav class="navegacao">
<?php foreach ($itensMenu as $chave => $titulo): ?>
<?php if ($modoMenu === 'painel'): ?>
          <label for="tab-<?= $chave ?>" class="aba-btn label-<?= $chave ?>"><?= $titulo ?></label>
<?php else: ?>
          <a href="pg1.php?aba=<?= $chave ?>" class="aba-btn"><?= $titulo ?></a>
<?php endif; ?>
<?php endforeach; ?>

          <!-- Perfil e suporte abrem em nova aba -->
          <div class="menu-divisor"></div>
          <a href="perfil.php" class="aba-btn" target="_blank" rel="noopener">Meu perfil</a>
          <a href="suporte.php" class="aba-btn" target="_blank" rel="noopener">Suporte</a>
        </nav>

        <div class="rodape-menu">
          <a href="../projeto.html" class="btn-sair">Sair do Sistema</a>
        </div>
      </div>
    </aside>
