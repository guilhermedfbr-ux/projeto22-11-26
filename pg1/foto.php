<?php
declare(strict_types=1);

// Entrega a foto de perfil. A imagem fica na pasta "dados" (bloqueada no navegador)
// e só sai por aqui, com o tipo de arquivo correto.
$aluno = require __DIR__ . '/includes/aluno.php';

if ($aluno['foto_arquivo'] === '') {
    http_response_code(404);
    exit;
}

$tipos = [
    'jpg'  => 'image/jpeg',
    'png'  => 'image/png',
    'webp' => 'image/webp',
];
$caminho = __DIR__ . '/dados/' . $aluno['foto_arquivo'];
$extensao = pathinfo($caminho, PATHINFO_EXTENSION);

header('Content-Type: ' . $tipos[$extensao]);
header('Content-Length: ' . (string) filesize($caminho));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=86400');
readfile($caminho);
