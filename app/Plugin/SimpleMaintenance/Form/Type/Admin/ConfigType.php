<?php
/**
 * Created by SYSTEM_KD
 * Date: 2018/08/17
 */

namespace Plugin\SimpleMaintenance\Form\Type\Admin;


use Eccube\Form\Type\ToggleSwitchType;
use Eccube\Form\Validator\TwigLint;
use Plugin\SimpleMaintenance\Entity\SimpleMConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('mente_mode', ToggleSwitchType::class)
            ->add('admin_close_flg', ToggleSwitchType::class)
            ->add('page_html', TextareaType::class, [
                'label' => false,
                'required' => false,
                'constraints' => [
                    new TwigLint(),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            ['data_class' => SimpleMConfig::class]
        );
    }
}
