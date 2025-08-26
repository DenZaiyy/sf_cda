<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;
use Symfony\Component\Validator\Constraints\PasswordStrength;

class ChangePasswordForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'options' => [
                    'attr' => [
                        'autocomplete' => 'new-password'
                    ],
                    'toggle' => true,
                ],
                'first_options' => [
                    'constraints' => [
                        new NotBlank(message: 'Please enter a password'),
                        new Length(
                            min: 12,
                            max: 4096,
                            minMessage: 'Your password should be at least {{ limit }} characters',
                        ),
                        new PasswordStrength(),
                        new NotCompromisedPassword(),
                    ],
                    'label' => 'New password',
                    'label_attr' => ['class' => 'block text-sm font-medium text-gray-700 dark:text-white'],
                    'attr' => [
                        'placeholder' => 'Enter your new password',
                        'class' => 'appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 dark:text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm'
                    ]
                ],
                'second_options' => [
                    'label' => 'Repeat Password',
                    'label_attr' => ['class' => 'block text-sm font-medium text-gray-700 dark:text-white'],
                    'attr' => [
                        'placeholder' => 'Repeat your new password',
                        'class' => 'appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 dark:text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm'
                    ]
                ],
                'invalid_message' => 'The password fields must match.',
                'mapped' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
