<?php

namespace App\Controller;

use App\Form\SettingsFormType;
use App\Settings;
use League\Uri\UriString;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class SettingsController extends AbstractController
{
    /** @var Settings */
    private $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    public function index(Request $request)
    {
        $form = $this->createForm(
            SettingsFormType::class,
            [
                'imageSize' => $this->settings->getImageSize(),
                'thumbnailSize' => $this->settings->getThumbnailSize(),
                'referrer' => $request->headers->get('referer')
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $redirectTo = '';
            $referrer = $form['referrer']->getData();

            if ($referrer !== null) {
                $referrer = UriString::parse($referrer);
                unset($referrer['scheme']);
                unset($referrer['user']);
                unset($referrer['pass']);
                unset($referrer['host']);
                unset($referrer['port']);
                unset($referrer['fragment']);
                $redirectTo = UriString::build($referrer);
            }

            $this->settings->setImageSize($form['imageSize']->getData());
            $this->settings->setThumbnailSize($form['thumbnailSize']->getData());

            if ($redirectTo === '') {
                return $this->redirectToRoute('settings');
            }

            return $this->redirect($redirectTo);
        }

        return $this->render(
            'settings.html.twig',
            [
                'form' => $form->createView()
            ]
        );
    }
}
