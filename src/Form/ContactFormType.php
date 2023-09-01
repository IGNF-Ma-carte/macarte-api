<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Gregwar\CaptchaBundle\Type\CaptchaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ContactFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $data = $options['data'];
        
        $builder
            ->add('user', TextType::class, array(
                'label' => "Nom",
                'data' => isset($data['user']) ? $data['user'] : '', 
            ))
            ->add('email', EmailType::class, array(
                'label' => "Email",
                'data' => isset($data['userEmail']) ? $data['userEmail'] : '', 
            ))
            ->add('subject', ChoiceType::class, array(
                'label' => 'Votre demande concerne',
                'choices' => $data['choices'],
                'data' => isset($data['subject']) ? $data['subject'] : '',
            ))
            ->add('content', TextareaType::class, array(
                'label' => 'Contenu',
                'data' => isset($data['content']) ? $data['content'] : '', 
            ))
            ->add('captcha', CaptchaType::class, array(
                'reload' => true,
                'as_url' => true,
                'attr' => array(
                    'autocomplete' =>  'off',
                ),
                'row_attr' => [
                    'class' => 'captcha'
                ],
            ))
            ->add('service', HiddenType::class, array(
                'data' => isset($data['service']) ? $data['service'] : '', 
                'required' => false,
                ))
            ->add('userID', HiddenType::class, array(
                'data' => isset($data['userID']) ? $data['userID'] : '',
                'required' => false,
            ))
            ->add('mapID', HiddenType::class, array(
                'data' => isset($data['mapID']) ? $data['mapID'] : '',
                'required' => false,
            ))
            ->add('submit', SubmitType::class, array(
                'label' => 'Envoyer'
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }

    public function getBlockPrefix()
    {
        return '';
    }

    
}
