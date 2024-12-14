<?php

namespace App\Controller;

use App\Entity\Address;
use App\Entity\User;
use App\Form\AddressType;
use App\Form\ProfileType;
use App\Repository\AddressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        AddressRepository $addressRepository
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Formulaire du profil
        $profileForm = $this->createForm(ProfileType::class, $user);
        $profileForm->handleRequest($request);

        if ($profileForm->isSubmitted() && $profileForm->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Vos informations ont été mises à jour avec succès.');

            return $this->redirectToRoute('app_profile');
        }

        // Formulaire d'adresse
        $address = new Address();
        $address->setUser($user);

        $addressForm = $this->createForm(AddressType::class, $address);
        $addressForm->handleRequest($request);

        if ($addressForm->isSubmitted() && $addressForm->isValid()) {
            $entityManager->persist($address);
            $entityManager->flush();
            $this->addFlash('success', 'Votre nouvelle adresse a été ajoutée avec succès.');

            return $this->redirectToRoute('app_profile');
        }

        // Récupération des adresses de l'utilisateur
        $addresses = $addressRepository->findBy(['user' => $user]);

        return $this->render('profile/index.html.twig', [
            'profileForm' => $profileForm->createView(),
            'addressForm' => $addressForm->createView(),
            'addresses' => $addresses,
        ]);
    }

    #[Route('/profile/address/{id}/edit', name: 'app_profile_address_edit')]
    public function editAddress(
        Request $request,
        Address $address,
        EntityManagerInterface $entityManager
    ): Response {
        // Vérification que l'adresse appartient bien à l'utilisateur courant
        if ($address->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(AddressType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'L\'adresse a été modifiée avec succès.');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit_address.html.twig', [
            'addressForm' => $form->createView(),
            'address' => $address,
        ]);
    }

    #[Route('/profile/address/{id}/delete', name: 'app_profile_address_delete', methods: ['POST'])]
    public function deleteAddress(
        Request $request,
        Address $address,
        EntityManagerInterface $entityManager
    ): Response {
        // Vérification que l'adresse appartient bien à l'utilisateur courant
        if ($address->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        // Vérification du token CSRF
        if ($this->isCsrfTokenValid('delete'.$address->getId(), $request->request->get('_token'))) {
            $entityManager->remove($address);
            $entityManager->flush();
            $this->addFlash('success', 'L\'adresse a été supprimée avec succès.');
        }

        return $this->redirectToRoute('app_profile');
    }

    #[Route('/profile/security', name: 'app_profile_security')]
    public function security(): Response
    {
        return $this->render('profile/security.html.twig');
    }

    #[Route('/profile/auctionsHistory', name: 'app_profile_auctions')]
    public function auctions(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Récupération des enchères de l'utilisateur
        $auctions = $user->getAuctions();
        $bids = $user->getBids();

        return $this->render('profile/auctionsHistory.html.twig', [
            'auctions' => $auctions,
            'bids' => $bids,
        ]);
    }
}