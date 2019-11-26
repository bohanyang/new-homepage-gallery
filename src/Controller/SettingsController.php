<?php

namespace App\Controller;

use App\Form\SettingsFormType;
use App\Settings;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SettingsController extends AbstractController
{
    /** @var Settings */
    private $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @Route("/settings/", name="settings")
     */
    public function index(Request $request)
    {
        $form = $this->createForm(SettingsFormType::class, [
            'imageSize' => $this->settings->getImageSize(),
            'thumbnailSize' => $this->settings->getThumbnailSize(),
            'referrer' => $request->headers->get('referer')
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

            $this->settings->setImageSize($form['imageSize']->getData());
            $this->settings->setThumbnailSize($form['thumbnailSize']->getData());

            if ($redirectTo === '') {
                return $this->redirectToRoute('settings');
            }

            return $this->redirect($redirectTo);
        }

        return $this->render('settings.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
