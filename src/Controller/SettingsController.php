<?php

namespace App\Controller;

use App\Form\SettingsFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SettingsController extends AbstractController
{
    /**
     * @Route("/settings/", name="settings")
     */
    public function index(Request $request)
    {
        $form = $this->createForm(SettingsFormType::class, [
            'imageSize' => $request->cookies->has('image_size') ? $request->cookies->get('image_size') : '1366x768',
            'thumbnailSize' => $request->cookies->has('thumbnail_size') ? $request->cookies->get('thumbnail_size') : '800x480',
            'referrer' => $request->headers->has('referer') ? $request->headers->get('referer') : null
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $redirectTo = '';
            $referrer = $form['referrer']->getData();

            if ($referrer !== null) {
                $referrer = parse_url($referrer);
                if ($referrer !== false) {
                    if (isset($referrer['path'])) {
                        $redirectTo .= $referrer['path'];
                    }
                    if (isset($referrer['query'])) {
                        $redirectTo .= "?{$referrer['query']}";
                    }
                }
            }

            if ($redirectTo === '') {
                $response = $this->redirectToRoute('settings');
            } else {
                $response = $this->redirect($redirectTo);
            }

            $response->headers->setCookie(
                new Cookie('image_size', $form['imageSize']->getData())
            );
            $response->headers->setCookie(
                new Cookie('thumbnail_size', $form['thumbnailSize']->getData())
            );

            return $response;
        }

        return $this->render('settings.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
