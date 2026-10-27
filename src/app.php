<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addErrorMiddleware(true, true, true);

$arquivo = __DIR__ . '/../data/usuarios.json';

function lerUsuarios($arquivo) {
    return json_decode(file_get_contents($arquivo), true);
}
function salvarUsuarios($arquivo, $usuarios) {
    file_put_contents($arquivo, json_encode($usuarios, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
function jsonResponse(Response $response, $data, int $status = 200): Response {
    $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
    return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
}

// --- ROTAS ---
$app->get('/status', function (Request $req, Response $res) {
    return jsonResponse($res, ['status' => 'ok']);
});

$app->get('/usuarios', function (Request $req, Response $res) use ($arquivo) {
    return jsonResponse($res, lerUsuarios($arquivo));
});

$app->get('/usuarios/{id}', function (Request $req, Response $res, array $args) use ($arquivo) {
    $usuarios = lerUsuarios($arquivo);
    foreach ($usuarios as $u) {
        if ($u['id'] == $args['id']) return jsonResponse($res, $u);
    }
    return jsonResponse($res, ['erro' => 'Usuário não encontrado'], 404);
});

$app->post('/usuarios', function (Request $req, Response $res) use ($arquivo) {
    $dados = $req->getParsedBody();
    $usuarios = lerUsuarios($arquivo);
    $novoId = empty($usuarios) ? 1 : max(array_column($usuarios, 'id')) + 1;
    $novo = [
        'id' => $novoId, 'nome' => $dados['nome'] ?? '', 'email' => $dados['email'] ?? '',
        'login' => $dados['login'] ?? '', 'senha' => $dados['senha'] ?? ''
    ];
    $usuarios[] = $novo;
    salvarUsuarios($arquivo, $usuarios);
    return jsonResponse($res, $novo, 201);
});

$app->put('/usuarios/{id}', function (Request $req, Response $res, array $args) use ($arquivo) {
    $dados = $req->getParsedBody();
    $usuarios = lerUsuarios($arquivo);
    foreach ($usuarios as $i => $u) {
        if ($u['id'] == $args['id']) {
            $usuarios[$i]['nome'] = $dados['nome'] ?? $u['nome'];
            $usuarios[$i]['email'] = $dados['email'] ?? $u['email'];
            $usuarios[$i]['login'] = $dados['login'] ?? $u['login'];
            salvarUsuarios($arquivo, $usuarios);
            return jsonResponse($res, $usuarios[$i]);
        }
    }
    return jsonResponse($res, ['erro' => 'Usuário não encontrado'], 404);
});

$app->delete('/usuarios/{id}', function (Request $req, Response $res, array $args) use ($arquivo) {
    $usuarios = lerUsuarios($arquivo);
    foreach ($usuarios as $i => $u) {
        if ($u['id'] == $args['id']) {
            array_splice($usuarios, $i, 1);
            salvarUsuarios($arquivo, $usuarios);
            return jsonResponse($res, ['mensagem' => 'Usuário removido']);
        }
    }
    return jsonResponse($res, ['erro' => 'Usuário não encontrado'], 404);
});

$app->post('/login', function (Request $req, Response $res) use ($arquivo) {
    $dados = $req->getParsedBody();
    $usuarios = lerUsuarios($arquivo);
    foreach ($usuarios as $u) {
        if ($u['login'] === ($dados['login'] ?? '') && $u['senha'] === ($dados['senha'] ?? '')) {
            return jsonResponse($res, ['mensagem' => 'Login realizado com sucesso', 'usuario' => $u]);
        }
    }
    return jsonResponse($res, ['erro' => 'Credenciais inválidas'], 401);
});

$app->put('/usuarios/{id}/senha', function (Request $req, Response $res, array $args) use ($arquivo) {
    $dados = $req->getParsedBody();
    $usuarios = lerUsuarios($arquivo);
    foreach ($usuarios as $i => $u) {
        if ($u['id'] == $args['id']) {
            if ($u['senha'] !== ($dados['senha_atual'] ?? '')) {
                return jsonResponse($res, ['erro' => 'Senha atual incorreta'], 401);
            }
            $usuarios[$i]['senha'] = $dados['nova_senha'] ?? '';
            salvarUsuarios($arquivo, $usuarios);
            return jsonResponse($res, ['mensagem' => 'Senha alterada com sucesso']);
        }
    }
    return jsonResponse($res, ['erro' => 'Usuário não encontrado'], 404);
});

// RETORNA O APP PARA O PHPUNIT
return $app;