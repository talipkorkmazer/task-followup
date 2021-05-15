<?php

namespace App\Security;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\ExpiredTokenException;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authentication\Token\PreAuthenticationJWTUserTokenInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Guard\JWTTokenAuthenticator;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Class TokenAuthenticator
 *
 * @package App\Security
 */
class TokenAuthenticator extends JWTTokenAuthenticator
{
    /**
     * @param PreAuthenticationJWTUserTokenInterface $preAuthToken
     * @param UserProviderInterface $userProvider
     * @return User
     */
    public function getUser($preAuthToken, UserProviderInterface $userProvider): User
    {
        /** @var User $user */
        $user = parent::getUser($preAuthToken, $userProvider);

        if (!is_null($user->getPasswordChangedDate()) && $preAuthToken->getPayload()['iat'] < $user->getPasswordChangedDate()) {
            throw new ExpiredTokenException();
        }

        return $user;
    }
}