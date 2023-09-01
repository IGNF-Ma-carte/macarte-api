<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Gregwar\CaptchaBundle\Type\CaptchaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, array(
                'label' => "Nom d'utilisateur",
            ))
            ->add('email', EmailType::class, array(
                'label' => 'Email',
            ))
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => array(
                    'label' => "Mot de passe *",
                    'help' => 'Le mot de passe doit contenir 10 caractères au minimum, dont au moins 1 lettre minuscule, 1 lettre majuscule, 1 chiffre et un caractère spécial (espaces autorisés)',
                ),
                'second_options' => array('label' => "Confirmation du mot de passe *"),
                'invalid_message' => 'Les deux mots de passe ne sont pas identiques',
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                    new Regex([
                        'pattern' => "/(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\s\-!$%^&@*()_+|~=`{}\[\]:;'<>?,.\/]).{10,}/",
                        'message' => 'Le mot de passe doit contenir 10 caractères au minimum, dont au moins 1 lettre minuscule, 1 lettre majuscule, 1 chiffre et un caractère spécial (espaces autorisés)'
                    ])
                ],
            ])
            // ->add('agreeTerms', CheckboxType::class, [
            //     'mapped' => false,
            //     'label' => 'lire les CGU (lien à  ajouter) et les valider',
            //     'constraints' => [
            //         new IsTrue([
            //             'message' => 'You should agree to our terms.',
            //         ]),
            //     ],
            // ])
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
            ->add('submit', SubmitType::class, array(
                'label' => 'Envoyer',
                'attr' =>  array(
                    'class' => '',
                )
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
