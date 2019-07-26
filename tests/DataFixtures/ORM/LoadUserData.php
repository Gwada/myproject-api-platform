<?php
namespace App\Tests\DataFixtures\ORM;

use App\Entity\User;
use App\Types\RoleType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadUserData extends Fixture implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        $manager->getClassMetaData(User::class)->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);

        $admin = new User();

        $encoder = $this->container->get('security.encoder_factory')->getEncoder($admin);
        $password = $encoder->encodePassword('Password-123', $admin->getSalt());

        $admin->setLogin('behat.admin');
        $admin->setPassword($password);
        $admin->setName('Admin');
        $admin->setUserlevel(RoleType::getLevelForRole(RoleType::ROLE_ADMIN));
        $admin->setActive(true);

        $manager->persist($admin);

        $manager->flush();
    }
}
