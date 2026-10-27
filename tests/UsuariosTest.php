<?php
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

class UsuariosTest extends TestCase
{
    private $app;

    protected function setUp(): void
    {
        // Carrega o app para os testes
        $this->app = require __DIR__ . '/../src/app.php';
    }

    public function testRotaStatusDeveRetornar200()
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/status');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $body = json_decode((string) $response->getBody(), true);
        $this->assertEquals('ok', $body['status']);
    }

    public function testDeveListarOsUsuarios()
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/usuarios');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        
        $this->assertIsArray($body);
        $this->assertGreaterThanOrEqual(5, count($body));
    }

    public function testLoginComCredenciaisValidas()
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/login')
            ->withHeader('Content-Type', 'application/json');
            
        $request = $request->withParsedBody([
            'login' => 'ana',
            'senha' => '123456'
        ]);

        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Login realizado com sucesso', $body['mensagem']);
    }

    public function testLoginComSenhaIncorretaDeveRetornar401()
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/login')
            ->withHeader('Content-Type', 'application/json');
            
        $request = $request->withParsedBody([
            'login' => 'ana',
            'senha' => 'senha_errada'
        ]);

        $response = $this->app->handle($request);
        $this->assertEquals(401, $response->getStatusCode());
    }
}