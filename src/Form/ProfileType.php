<?php

namespace App\Form;

use App\Dto\ProfileData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('full_name', TextType::class, ['property_path' => 'fullName'])
            ->add('email', EmailType::class)
            ->add('phone', TelType::class, ['required' => false])
            ->add('address', TextareaType::class, ['required' => false])
            ->add('gender', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    'Male' => 'Male',
                    'Female' => 'Female',
                    'Other' => 'Other',
                ],
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('dob', DateType::class, [
                'required' => false,
                'widget' => 'choice',
                'years' => range(date('Y'), 1900),
                'format' => 'dd-MM-yyyy',
                'placeholder' => [
                    'year' => 'Year', 'month' => 'Month', 'day' => 'Day',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProfileData::class,
        ]);
    }
}
