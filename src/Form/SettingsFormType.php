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
            'constraints' => [new NotBlank([
                'message' => '请从这些选项中选择一个'
            ])],
            'invalid_message' => '请选择列表中的一项'
        ];

        $builder
            ->add('imageSize', ChoiceType::class, [
                'label' => '图片尺寸',
                'choices' => [
                    '1920x1080 (高清; 横向)' => '1920x1080',
                    '1366x768 (标准; 横向)' => '1366x768',
                    '1080x1920 (高清; 纵向)' => '1080x1920',
                    '768x1280 (标准; 纵向)' => '768x1280',
                ],
                'expanded' => true
            ] + $defaults)
            ->add('thumbnailSize', ChoiceType::class, [
                'label' => '缩略图尺寸',
                'choices' => [
                    '800x480 (标准; 横向)' => '800x480',
                    '480x800 (标准; 纵向)' => '480x800',
                    '1366x768 (高清; 横向)' => '1366x768',
                ],
                'expanded' => true
            ] + $defaults)
            ->add('referrer', HiddenType::class)
        ;
    }
}
