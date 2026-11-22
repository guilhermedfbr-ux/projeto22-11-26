<?php
// Dados do aluno.
// - nome, e-mail, foto e senha podem ser alterados em "Configurações" (ficam em dados/perfil.json).
// - os demais campos são EXEMPLOS. Quando existir login + banco de dados, troque por uma consulta ao banco.

$dados = [
    'nome'  => 'Aluno',
    'email' => 'aluno@email.com',
    'foto'  => '',
    'senha_hash' => '',
];

$pastaDados = __DIR__ . '/../dados';
$arquivoPerfil = $pastaDados . '/perfil.json';

if (is_file($arquivoPerfil)) {
    $salvo = json_decode((string) file_get_contents($arquivoPerfil), true);
    if (is_array($salvo)) {
        foreach (['nome', 'email', 'foto', 'senha_hash'] as $campo) {
            if (isset($salvo[$campo]) && is_string($salvo[$campo])) {
                $dados[$campo] = $salvo[$campo];
            }
        }
    }
}

// Iniciais para o avatar quando não há foto (ex.: "Maria Souza" -> "MS", "Aluno" -> "AL")
$partes = preg_split('/\s+/u', trim($dados['nome']), -1, PREG_SPLIT_NO_EMPTY);
if (!$partes) {
    $partes = ['A'];
}
$iniciais = count($partes) > 1
    ? mb_substr($partes[0], 0, 1) . mb_substr(end($partes), 0, 1)
    : mb_substr($partes[0], 0, 2);

// A foto só vale se o nome do arquivo tem o formato esperado e o arquivo existe
$fotoArquivo = '';
$fotoUrl = '';
if (preg_match('/^[a-f0-9]{16}\.(jpg|png|webp)$/', $dados['foto']) === 1
    && is_file($pastaDados . '/' . $dados['foto'])) {
    $fotoArquivo = $dados['foto'];
    $fotoUrl = 'foto.php?v=' . filemtime($pastaDados . '/' . $dados['foto']);
}

return [
    'nome'          => $dados['nome'],
    'avatar'        => mb_strtoupper($iniciais),
    'foto_arquivo'  => $fotoArquivo,
    'foto_url'      => $fotoUrl,
    'senha_hash'    => $dados['senha_hash'],
    // Senha de quem ainda não trocou a senha (troque em Configurações > Alterar senha)
    'senha_inicial' => 'aluno123',
    'tipo'          => 'Estudante',
    'email'         => $dados['email'],
    'matricula'     => '2026-0001',
    'turma'         => '3º Ano B - Ensino Médio',
    'turno'         => 'Matutino',
    'coordenador'   => 'Prof. Carlos Silva',
    'ano_letivo'    => '2026',
];
