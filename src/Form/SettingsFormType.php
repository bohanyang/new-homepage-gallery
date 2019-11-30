<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class SettingsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $defaults = [
            'constraints' => [
                new NotBlank(
                    [
                        'message' => 'blank_choice'
                    ]
                )
            ],
            'invalid_message' => 'invalid_choice',
            'translation_domain' => 'settings_form',
        ];

        $builder
            ->add(
                'imageSize',
                ChoiceType::class,
                [
                    'label' => 'image_size',
                    'choices' => [
                        'image_sizes.1920x1080' => '1920x1080',
                        'image_sizes.1366x768' => '1366x768',
                        'image_sizes.1080x1920' => '1080x1920',
                        'image_sizes.768x1280' => '768x1280',
                    ],
                    'expanded' => true,
                ] + $defaults
            )
            ->add(
                'thumbnailSize',
                ChoiceType::class,
                [
                    'label' => 'thumbnail_size',
                    'choices' => [
                        'thumbnail_sizes.800x480' => '800x480',
                        'thumbnail_sizes.480x800' => '480x800',
                        'thumbnail_sizes.1366x768' => '1366x768',
                    ],
                    'expanded' => true,
                ] + $defaults
            )
            ->add('referrer', HiddenType::class);
    }
}
