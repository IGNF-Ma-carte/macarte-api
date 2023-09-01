<?php

namespace App\Form;

use App\Entity\Notification;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class NotificationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => Notification::getTypes(),
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
            ])
            ->add('scope', ChoiceType::class, [
                'label' => 'Portée',
                'choices' => Notification::getScopes(),
                
            ])
            ->add('showFrom', DateType::class, [
                'label' => 'Montrer depuis',
                'widget' => 'single_text',
                'attr' => [
                    'tabindex' => -1
                ]
            ])
            ->add('showUntil', DateType::class, [
                'label' => 'Montrer jusqu\'à',
                'widget' => 'single_text',
                'attr' => [
                    'tabindex' => -1
                ]
            ])
            ->add('repeatability', IntegerType::class, [
                'label' => 'Répétitivité',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Enregistrer',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Notification::class,
        ]);
    }

    public function getBlockPrefix()
    {
        return '';
    }
}
