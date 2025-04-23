<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResetPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('plainPassword', RepeatedType::class, [
            'type' => PasswordType::class,
            'first_options'  => ['label' => 'forgotten_password.form_new_pass.new_pass'],
            'second_options' => ['label' => 'forgotten_password.form_new_pass.confirm_pass'],
            'invalid_message' => 'forgotten_password.form_new_pass.invalid_msg',
            'required' => true,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        // Тук можем да оставим опции по подразбиране, защото формата не се свързва директно с entity.
        $resolver->setDefaults([]);
    }
}