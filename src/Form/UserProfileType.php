<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/** @phpstan-ignore-next-line */
class UserProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'disabled' => true,
                'label' => 'user_profile.email',
            ])
            ->add('username', TextType::class, [
                'label' => 'user_profile.username',
                'constraints' => [
                    new NotBlank(['message' => 'user_profile.username_not_blank']),
                    new Length(['min' => 3, 'max' => 50]),
                ],
            ])
            ->add('oldPassword', PasswordType::class, [
                'mapped' => false,
                'label' => 'user_profile.current_password',
                'constraints' => [
                    new NotBlank(['message' => 'user_profile.current_password_required']),
                    new UserPassword(['message' => 'user_profile.incorrect_password']),
                ],
            ])
            ->add('newPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options'  => ['label' => 'user_profile.new_password'],
                'second_options' => ['label' => 'user_profile.repeat_new_password'],
                'invalid_message' => 'user_profile.passwords_must_match',
                'constraints' => [
                    new NotBlank(['message' => 'user_profile.new_password_not_blank']),
                    new Length(['min' => 6, 'max' => 50]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}