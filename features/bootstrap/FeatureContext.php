<?php

use App\Entity\User;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\Request;
use Behat\Gherkin\Node\PyStringNode;
use Behatch\Json\Json;
use Behat\Mink\Exception\ExpectationException;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends \Behat\MinkExtension\Context\MinkContext implements Context, SnippetAcceptingContext, \Behat\Symfony2Extension\Context\KernelAwareContext
{
    use \Behat\Symfony2Extension\Context\KernelDictionary;

    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @var EncoderFactoryInterface
     */
    private $encoderFactory;

    /**
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    private $manager;

    /**
     * @var SchemaTool
     */
    private $schemaTool;

    /**
     * @var array
     */
    private $classes;

    /**
     * @var string
     */
    private $token;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct(ContainerInterface $container, ManagerRegistry $doctrine, EncoderFactoryInterface $encoderFactory)
    {
        $this->container = $container;
        $this->doctrine = $doctrine;
        $this->encoderFactory = $encoderFactory;
        $this->manager = $doctrine->getManager();
        $this->schemaTool = new SchemaTool($this->manager);
        $this->classes = $this->manager->getMetadataFactory()->getAllMetadata();
    }

    /**
     * @BeforeScenario @createSchema
     */
    public function createDatabase()
    {
        $this->schemaTool->dropSchema($this->classes);
        $this->doctrine->getManager()->clear();
        $this->schemaTool->createSchema($this->classes);
    }

    /**
     * @Given I load fixtures from folder :folder
     */
    public function loadFixtures($folder)
    {
        $kernel = $this->getKernel();
        $path = $kernel->getRootDir().'/../tests/'.$folder;

        $loader = new ContainerAwareLoader($this->container);
        $loader->loadFromDirectory($path);

        $purger = new ORMPurger();
        $executor = new ORMExecutor($this->manager, $purger);
        $executor->execute($loader->getFixtures(), true);
    }

    /**
     * @param string $username
     * @param string $password
     *
     * @Given I am authenticated as ":username" with ":password" password
     */
    public function iAmAuthenticatedAs($username, $password)
    {
        $request = Request::create('/api/login_check', Request::METHOD_POST, [
            '_username' => $username,
            '_password' => $password
        ]);

        $response = $this->getKernel()->handle($request);

        $content = json_decode($response->getContent(), true);

        if (! is_array($content)) {
            throw new \InvalidArgumentException('invalid token response');
        }

        $this->token = $content['token'];
    }

    /**
     * @Given I send a :method request to protected :url
     */
    public function iSendARequestToProtected($method, $url, PyStringNode $body = null)
    {
        /* @var \Symfony\Component\BrowserKit\Client $client */
        $client = $this->getMink()->getSession()->getDriver()->getClient();

        $client->request($method, $url, [], [], [
            'HTTP_Authorization' => 'Bearer ' . $this->token,
            'CONTENT_TYPE' => 'application/json',
        ], $body);
    }

    /**
     * @Given I send a :method request to protected :url with body:
     */
    public function iSendARequestToProtectedWithBody($method, $url, PyStringNode $body)
    {
        $this->iSendARequestToProtected($method, $url, $body);
    }

    /**
     * @Then the JSON with pattern should be equal to:
     */
    public function theJsonShouldBeEqualTo(PyStringNode $content)
    {
        $actual = new Json($this->getMink()->getSession()->getPage()->getContent());

        try {
            $expected = new Json(str_replace("+", "\\\+", $content));
        }
        catch (\Exception $e) {
            throw new \Exception('The expected JSON is not a valid');
        }

        $this->assertSamePattern(
            str_replace(["\\\\", "[{", "}]"], ["\\", "\[{", "}\]"], json_encode($expected->getContent(), JSON_UNESCAPED_UNICODE)),
            str_replace(["\/"], ["/"], json_encode($actual->getContent(), JSON_UNESCAPED_UNICODE)),
            "The json is equal to:\n". $actual->encode()
        );
    }

    /**
     * @Then the password ":clearPassword" should be valid for the user ":id"
     */
    public function thePasswordShouldBeValidForTheUser(string $password, int $id)
    {
        $user = $this->doctrine->getRepository(User::class)->find($id);

        if (null === $user) {
            throw new \Exception(sprintf('The user %s is doesn\'t exists', $id));
        }

        $encoder = $this->encoderFactory->getEncoder($user);

        $this->assert(
            $encoder->isPasswordValid($user->getPassword(), $password, $user->getSalt()),
            "Invalid password\n"
        );
    }

    /**
     * @Then the entity ":entityName" with id ":id" should be deleted
     */
    public function theEntityMustBeDeleted(int $id, string $entityName)
    {
        $entity = $this->doctrine->getRepository($entityName)->find($id);

        $this->assert(null, $entity);
    }

    /**
     * @Then the entity ":entityName" with id ":id" should return ":value" when calling ":methodName"
     */
    public function theEntityMustReturnValue(int $id, string $entityName, string $methodName, string $value)
    {
        $entity = $this->doctrine->getRepository($entityName)->find($id);

        if (null === $entity) {
            throw new \Exception(sprintf('The entity %s - %s is doesn\'t exists', $entityName, $id));
        }

        if (!method_exists($entity, $methodName)) {
            throw new \Exception(sprintf('The method %s of entity %s doesn\'t exists', $methodName, $entityName));
        }

        $this->assert(
            $entity->{$methodName}(),
            $value
        );
    }

    private function assertSamePattern($expected, $actual, $message = null)
    {
        $this->assert(
            (bool) preg_match('/'.$expected.'/', $actual),
            $message ?: "The element '$actual' is not equal to '$expected'"
        );
    }

    private function assert($test, $message)
    {
        if ($test === false) {
            throw new ExpectationException($message, $this->getSession());
        }
    }
}
