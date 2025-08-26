<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ResetPasswordRequestForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email address',
                'label_attr' => ['class' => 'block text-sm font-medium text-gray-700 dark:text-white'],
                'attr' => [
                    'autocomplete' => 'email',
                    'class' => 'appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 dark:text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm'
                ],
                'constraints' => [
                    new NotBlank(message: 'Please enter your email'),
                ],
                'help' => 'Enter your email address, and we will send you a link to reset your password.',
                'help_attr' => [
                    'class' => 'text-sm font-normal text-gray-700/80 dark:text-white',
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
