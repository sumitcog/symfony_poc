<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class RegisterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $object = $options['data'] ?? null;
        $isEdit = $object && $object->getId();
    
        $builder
            ->add('fullName')
            ->add('username')
            ->add('email')
            ->add('password', PasswordType::class, [
                'required' => !$isEdit,
                'empty_data' => '',
            ]);

        if(!$isEdit)
        {
            $builder->add('is_author', CheckboxType::class, ['mapped' => false, 'required' => false]);
        } 
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
