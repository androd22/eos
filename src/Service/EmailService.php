<?php

namespace App\Service;

use App\DTO\ContactDTO;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private string $contactEmail
    ) {}

    /**
     * @throws SyntaxError
     * @throws TransportExceptionInterface
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function sendContactEmail(ContactDTO $contactDTO): void
    {
        $email = (new Email())
            ->from($contactDTO->getEmail())
            ->to($this->contactEmail)
            ->subject('Nouveau message de contact - ' . $contactDTO->getSubject())
            ->html($this->twig->render('emails/contact.html.twig', [
                'contact' => $contactDTO
            ]));

        $this->mailer->send($email);
    }
}

