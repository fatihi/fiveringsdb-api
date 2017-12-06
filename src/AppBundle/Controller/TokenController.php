<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Token;
use AppBundle\Service\Metagame;
use AppBundle\Service\TokenManager;
use AppBundle\Service\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Description of TokenController
 *
 * @author Alsciende <alsciende@icloud.com>
 */
class TokenController extends AbstractApiController
{
    /**
     * Uses the token to get the user data with Metagame, then saves it
     *
     * @Route("/tokens", name="tokens_create")
     * @Method("POST")
     */
    public function postAction (Request $request, Metagame $metagame, UserManager $userManager, TokenManager $tokenManager, EntityManagerInterface $entityManager)
    {
        $form = $this->createFormBuilder([])->add('id', TextType::class)->getForm();
        $form->submit(json_decode($request->getContent(), true), true);

        if ($form->isSubmitted() && $form->isValid()) {
            $tokenId = $form->getData()['id'];

            $token = $entityManager->find(Token::class, $tokenId);
            if ($token instanceof Token) {
                return $this->success($token);
            }

            $res = $metagame->get('api/users/me', [], $tokenId);
            if ($res->getStatusCode() !== 200) {
                return $this->failure('token_error', (string) $res->getBody());
            }
            $userData = json_decode((string) $res->getBody(), true);

            $user = $userManager->findUserById($userData['id']);
            if ($user === null) {
                $user = $userManager->createUser($userData['id'], $userData['username']);
                $userManager->updateUser($user);
            }

            $token = $tokenManager->createToken($tokenId, $user);
            $tokenManager->updateToken($token);

            return $this->success(
                $token,
                [
                    'Default',
                    'User',
                    'user' => [
                        'Default',
                        'Self',
                    ],
                ]
            );
        }

        return $this->failure('validation_error', $this->formatValidationErrors($form->getErrors(true)));
    }
}