<?php

namespace App\Form;

use App\Entity\Article;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

class ArticleFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('category', ChoiceType::class, array(
                'label' => "Catégorie",
                'choices' => Article::getCategories(),
            ))
            ->add('title', TextType::class, array(
                'label' => 'Titre'
            ))
            ->add('content', TextareaType::class, array(
                'label' => 'Contenu',
                'required' => false,
            ))
            ->add('status', ChoiceType::class, [
                'label' => "Statut",
                'choices' => Article::getStatuses(),
            ])
            // ->add('visible', CheckboxType::class, array(
            //     'label' => "Afficher sur l'accueil",
            //     'required' => false,
            // ))
            ->add('tags', CollectionType::class, array(
                'label' => false,
                'entry_type' => TextType::class,
                'allow_add' => true,
                'allow_delete' => true,
            ))
            ->add('imgUrl', TextType::class, array(
                'label' => " ",
                'required' => false,
            ))
            ->add('linkText', TextType::class, array(
                'label' => 'Texte du lien',
                'required' => false,
            ))
            ->add('linkUrl', UrlType::class, array(
                'label' => 'Url de destination',
                'required' => false,
            ))
            ->add('submit', SubmitType::class, array(
                'label' => 'Enregistrer'
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
        ]);
    }

    public function getBlockPrefix()
    {
        return '';
    }
}
