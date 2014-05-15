<?php

namespace CSBill\PaymentBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PaymentMethodSettingsForm extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $settings = $options['settings'];

        foreach ($settings as $setting) {
            $options = $this->getOptions($setting['type']);
            $builder->add($setting['name'], $setting['type'], $options);
        }
    }

    /**
     * @param string $field
     *
     * @return array
     */
    private function getOptions($field)
    {
        $options = array();

        switch($field) {
            case 'password' :
                $options['always_empty'] = false;
                break;
        }

        return $options;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(
            array(
                'settings'
            )
        );
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return 'method_settings';
    }
}
