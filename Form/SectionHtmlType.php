<?php

namespace Stems\BlogBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SectionHtmlType extends AbstractType
{
	protected $id;

	public function __construct($link)
	{
		$this->id = $link->getId();
	}

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$builder->add('content', 'textarea', array(
			'label'     		=> false,
			'required'			=> false,
			'error_bubbling' 	=> true,
		));	
	}

	public function getName()
	{
		return $this->id.'_section_html_type';
	}
}
