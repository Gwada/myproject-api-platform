<?php
namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;


class UserRepository extends ServiceEntityRepository implements UserProviderInterface
{
    private $logger = null;

    public function __construct(RegistryInterface $registry, LoggerInterface $logger)
    {
        parent::__construct($registry, User::class);

        $this->logger = $logger;
    }

    public function getUserByUsername($username)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('u')
            ->from(User::class, 'u')
            ->where('u.login = :username')
            ->andWhere('u.active = :active')
            ->setParameter('username', $username)
            ->setParameter('active', true);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @see \Symfony\Component\Security\Core\User\UserProviderInterface::loadUserByUsername()
     */
    public function loadUserByUsername($username)
    {
        $user = $this->getUserByUsername($username);

        if ($user !== null) {

            if (null !== $this->logger) {
                $this->logger->info(sprintf("User %s - Role: %s", $username, implode(',', $user->getRoles())));
            }

            return $user;
        }

        throw new UsernameNotFoundException();
    }

    /**
     * @see \Symfony\Component\Security\Core\User\UserProviderInterface::loadUser()
     */
    public function refreshUser(UserInterface $account)
    {
        if (!$account instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($account)));
        }

        return $this->loadUserByUsername($account->getUsername());
    }

    /**
     * @see \Symfony\Component\Security\Core\User\UserProviderInterface::supportsClass()
     * @codeCoverageIgnore
     */
    public function supportsClass($class)
    {
        return $class === User::class;
    }
}
