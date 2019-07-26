<?php
namespace App\EventListener;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class AuthenticationSuccessListener
{
    private $requestStack;

    private $entityManager;

    public function __construct(RequestStack $requestStack, EntityManagerInterface $entityManager)
    {
        $this->requestStack = $requestStack;
        $this->entityManager = $entityManager;
    }

    /**
     * @param AuthenticationSuccessEvent $event
     */
    public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event)
    {
        $data = $event->getData();
        $user = $event->getUser();

        if ($user instanceof User) {

            $user->setLastLoginDate(new \DateTime());

            $this->entityManager->flush();

            $data['user'] = array(
                'id' => $user->getId(),
                'name' => $user->getName(),
                'role' => $user->getUserLevel(),
            );
        }

        $event->setData($data);
    }
}
