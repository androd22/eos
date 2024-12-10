<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Service\EmailService;
use App\Service\JWTService;
use App\Service\SendEmailService;
use App\Service\SendMailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class RegistrationController extends AbstractController
{


    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager,  JWTService $jwt, MailerInterface $mailer): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user, [
                'is_edit' => true
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )

            );
            $user->setIsKycVerified(true);
            $entityManager->persist($user);
            $entityManager->flush();


            $header = [
                'typ' => 'JWT',
                'alg' => 'HS256'
            ];
            // on crée le Payload
            $payload = [
                'user_id' => $user->getId()
            ];
            // on génère le token
                $token = $jwt->generate($header, $payload, $this->getParameter('jwt_secret'));

            // Envoyer l'email avec votre service
            $email = (new TemplatedEmail())
                ->from('androd@eos.com')
                ->to($user->getEmail())
                ->subject('Confirmation d\'inscription')
                ->htmlTemplate('emails/confirmation_email.html.twig')
                ->context([
                    'user' => $user,
                    'token' => $token,
                ]);


            $mailer->send($email);

            $this->addFlash('custom-success', [
                'message' => 'Un email de confirmation vous a été envoyé.',
                'styleClass' => 'custom-gradient'
            ]);
            return $this->redirectToRoute('app_home');

        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,

        ]);
    }

    #[\Symfony\Component\Routing\Annotation\Route('/registration/confirm/{token}', name: 'app_registration_confirm')]
    public function verifyUser($token, JWTService $jwt, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        // dd($jwt->isValid($token));
        // dd($jwt->getPayload($token));
        // dd($jwt->isExpired($token));
        // dd($jwt->check($token, $this->getParameter('app.jwtsecret')));

        // on vérifie si le token est valide, non expiré et valide
        if (
            $jwt->isValid($token) && !$jwt->isExpired($token) && $jwt->check($token, $this->getParameter('jwt_secret'))
        ) {
            // on récupère le payload
            $payload = $jwt->getPayload($token);

            // on récupère le user du token
            $user = $userRepository->find($payload['user_id']);

            // on vérifie que l'user existe et n'a pas encore activé son compte
            if ($user && !$user->isVerified()) {
                $user->setVerified(true);
                $em->flush();
                $this->addFlash('success', 'Votre compte a bien été activé');
                return $this->redirectToRoute('app_home');
            }
        }
        // ici un problème se pose dans le token
        $this->addFlash('danger', 'Token invalide ou expiré');
        return $this->redirectToRoute('app_login');
    }

    #[Route('/resendVerification', name: 'app_resend_verification')]
    public function resendVerification(JWTService $jwt, SendEmailService  $mail, UserRepository $userRepository): Response
    {

        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('danger', 'Veuillez vous connecter');
            return $this->redirectToRoute('app_login');
        }

        if ($user->isVerified()) {
            $this->addFlash('warning', 'Votre compte est déjà activé');
            return $this->redirectToRoute('default_home');
        }

        // on génère le JWT de l'utilisateur
        // on crée le Header
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];
        // on crée le Payload
        $payload = [
            'user_id' => $user->getId()
        ];
        // on génère le token
        $token = $jwt->generate($header, $payload, $this->getParameter('jwt_secret'));
        // dd($token);

        // Envoi du mail
        $mail->send(
            'androd@eos.com',
            $user->getEmail(),
            'Activation de votre compte',
            'registration',
            // ['user' => $user]
            compact('user', 'token')
        );
        $this->addFlash('success', 'Email de vérification envoyé');
        return $this->redirectToRoute('default_home');
    }
}
