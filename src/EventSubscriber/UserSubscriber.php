<?php
namespace App\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\User;
use App\Types\RoleType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class UserSubscriber implements EventSubscriberInterface
{
    private $tokenInterface;

    private $authorizationChecker;

    private $encoderFactory;

    public function __construct(TokenStorageInterface $tokenInterface, AuthorizationCheckerInterface $checker, EncoderFactoryInterface $encoderFactory)
    {
        $this->tokenInterface = $tokenInterface;
        $this->authorizationChecker = $checker;
        $this->encoderFactory = $encoderFactory;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => [['prepareUser', EventPriorities::PRE_WRITE]],
        ];
    }

    public function prepareUser(ViewEvent $event)
    {
        $entity = $event->getControllerResult();

        if (!$entity instanceof User) {
            return;
        }

        if (!$this->authorizationChecker->isGranted(RoleType::ROLE_ADMIN)) {
            if (RoleType::getRoleForLevel($entity->getUserLevel()) === RoleType::ROLE_ADMIN) {
                throw new AccessDeniedException('Insufficient privileges to edit this user');
            }
        }

        if ($entity->getPlainPassword() !== null && $entity->getPlainPassword() !== '') {

            $encoder = $this->encoderFactory->getEncoder($entity);
            $password = $encoder->encodePassword($entity->getPlainPassword(), $entity->getSalt());

            $entity->setPassword($password);
            $entity->setLastPasswordChangedAt(new \DateTime());
        }
    }
}