<?php

declare(strict_types=1);

namespace App\Form;

use App\Dto\UserAccountData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

final class UserAccountType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $new = $builder->getData()->id === null;
        $builder
            ->add('fullName', TextType::class, ['label' => 'Full name'])
            ->add('email', EmailType::class)
            ->add('role', ChoiceType::class, ['choices' => ['Staff' => 'staff', 'Admin' => 'admin', 'Customer' => 'customer']])
            ->add('active', ChoiceType::class, ['label' => 'Account status', 'choices' => ['Active' => true, 'Disabled' => false]])
            ->add('plainPassword', PasswordType::class, [
                'label' => $new ? 'Password' : 'Reset password',
                'required' => $new,
                'help' => $new ? 'Use 12–72 bytes. Share the password securely.' : 'Leave blank to keep the current password. Use 12–72 bytes for a new password.',
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => $new ? [new NotBlank()] : [],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => UserAccountData::class]);
    }
}
