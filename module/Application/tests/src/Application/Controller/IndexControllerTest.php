<?php

use Core\Test\ControllerTestCase;
use Application\Controller\IndexController;
use Application\Model\Post;
use Application\Model\Comment;
use Zend\Http\Request;
use Zend\Stdlib\Parameters;
use Zend\View\Renderer\PhpRenderer;


/**
 * @group Controller
 */
class IndexControllerTest extends ControllerTestCase
{
    /**
     * Namespace completa do Controller
     * @var string
     */
    protected $controllerFQDN = 'Application\Controller\IndexController';

    /**
     * Nome da rota. Geralmente o nome do módulo
     * @var string
     */
    protected $controllerRoute = 'application';

    /**
     * Testa o acesso a uma action que não existe
     */
    public function test404()
    {
        $this->routeMatch->setParam('action', 'action_nao_existente');
        $result = $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * Testa a página inicial, que deve mostrar os posts
     */
    public function testIndexAction()
    {
        // Cria posts para testar
        $postA = $this->addPost();
        $postB = $this->addPost();

        // Invoca a rota index
        $this->routeMatch->setParam('action', 'index');
        $result = $this->controller->dispatch($this->request, $this->response);

        // Verifica o response
        $response = $this->controller->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        // Testa se um ViewModel foi retornado
        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);

        // Testa os dados da view
        $variables = $result->getVariables();

        $this->assertArrayHasKey('posts', $variables);

        // Faz a comparação dos dados
        $controllerData = $variables["posts"]->getCurrentItems()->toArray();
        $this->assertEquals($postA->title, $controllerData[0]['title']);
        $this->assertEquals($postB->title, $controllerData[1]['title']);
    }  

    /**
     * Testa a página inicial, que deve mostrar os posts com paginador
     */
    public function testIndexActionPaginator()
    {
        // Cria posts para testar
        $post = array();
        for($i=0; $i< 25; $i++) {
            $post[] = $this->addPost();
        }

        // Invoca a rota index
        $this->routeMatch->setParam('action', 'index');
        $result = $this->controller->dispatch($this->request, $this->response);

        // Verifica o response
        $response = $this->controller->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        // Testa se um ViewModel foi retornado
        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);

        // Testa os dados da view
        $variables = $result->getVariables();

        $this->assertArrayHasKey('posts', $variables);

        //testa o paginator
        $paginator = $variables["posts"];
        $this->assertEquals('Zend\Paginator\Paginator', get_class($paginator));
        $posts = $paginator->getCurrentItems()->toArray();
        $this->assertEquals(10, count($posts));
        $this->assertEquals($post[0]->id, $posts[0]['id']);
        $this->assertEquals($post[1]->id, $posts[1]['id']);
        
        //testa a terceira página da paginação
        $this->routeMatch->setParam('action', 'index');
        $this->routeMatch->setParam('page', 3);
        $result = $this->controller->dispatch($this->request, $this->response);
        $variables = $result->getVariables();
        $controllerData = $variables["posts"]->getCurrentItems()->toArray();
        $this->assertEquals(5, count($controllerData));
    }   

    /**
     * Testa a página de detalhes do post
     * @expectedException Exception
     * @expectedExceptionMessage Código obrigatório
     */
    public function testWithOutIdPostAction()
    {
        // Invoca a rota
        $this->routeMatch->setParam('action', 'post');
        $result = $this->controller->dispatch($this->request, $this->response);
    }   

    /**
     * Testa a página de detalhes do post
     * @expectedException Exception
     * @expectedExceptionMessage Could not find row 1
     */
    public function testInvalidIdPostAction()
    {
        // Invoca a rota
        $this->routeMatch->setParam('action', 'post');
        $this->routeMatch->setParam('id', 1);
        $result = $this->controller->dispatch($this->request, $this->response);
    }   

    /**
     * Testa a página de detalhes do post
     */
    public function testPostAction()
    {
        // Cria posts para testar
        $postA = $this->addPost();

        //cria comentários
        $commentA = $this->addComment($postA->id);
        $commentB = $this->addComment($postA->id);

        // Invoca a rota index
        $this->routeMatch->setParam('action', 'post');
        $this->routeMatch->setParam('id', $postA->id);
        $result = $this->controller->dispatch($this->request, $this->response);

        // Verifica o response
        $response = $this->controller->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        // Testa se um ViewModel foi retornado
        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);

        // Testa os dados da view
        $variables = $result->getVariables();
        $this->assertArrayHasKey('post', $variables);

        // Faz a comparação dos dados
        $controllerData = $variables["post"];
        $post = $controllerData;
        $this->assertEquals($postA->title, $post['title']);

        $this->assertEquals(2, count($post['comments']));

        $this->assertEquals($commentA->id,$post['comments'][0]['id']);
        
    }     

    /**
     * Adiciona um post para os testes
     */
    private function addPost()
    {
        $post = new Post();
        $post->title = 'Apple compra a Coderockr';
        $post->description = 'A Apple compra a <b>Coderockr</b><br> ';
        $post->post_date = date('Y-m-d H:i:s');

        $saved = $this->getTable('Application\Model\Post')->save($post);

        return $saved;
    }

    /**
     * Adiciona um comemnt para os testes
     */
    private function addComment($post_id)
    {
        $comment = new Comment();
        $comment->post_id = $post_id;
        $comment->description = 'Boa compra!';
        $comment->name = 'Tony Stark';
        $comment->email = 'tony@starkenterprises.com';
        $comment->webpage = 'http://www.starkenterprises.com';
        $comment->comment_date = date('Y-m-d H:i:s');

        $saved = $this->getTable('Application\Model\Comment')->save($comment);

        return $saved;
    }    
}