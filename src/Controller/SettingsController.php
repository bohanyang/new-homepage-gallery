<?php

namespace App\Controller;

use App\Form\SettingsFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use function time;

class SettingsController extends AbstractController
{
    /**
     * @Route("/settings/", name="settings")
     */
    public function index(Request $request)
    {
        $form = $this->createForm(SettingsFormType::class, [
            'imageSize' => $request->cookies->has('image') ? $request->cookies->get('image') : '1366x768',
            'thumbnailSize' => $request->cookies->has('imagepreview') ? $request->cookies->get('imagepreview') : '800x480',
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

            $expire = time() + 3600 * 24 * 365 * 5;

            $response->headers->setCookie(
                new Cookie('image', $form['imageSize']->getData(), $expire)
            );
            $response->headers->setCookie(
                new Cookie('imagepreview', $form['thumbnailSize']->getData(), $expire)
            );

            return $response;
        }

        return $this->render('settings.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
