<?php

namespace Stems\BlogBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SectionScrapbookTextType extends AbstractType
{
	protected $id;

	public function __construct($text)
	{
		$this->id = $text->getId();
	}

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$builder->add('content', 'textarea', array(
			'label'     		=> 'Content',
			'required'			=> false,
			'error_bubbling' 	=> true,
		));	

		$builder->add('x', 'text', array(
			'label'     		=> false,
			'required'			=> false,
			'error_bubbling' 	=> true,
		));	

		$builder->add('y', 'text', array(
			'label'     		=> false,
			'required'			=> false,
			'error_bubbling' 	=> true,
		));	

		$builder->add('width', 'text', array(
			'label'     		=> 'Width',
			'required'			=> false,
			'error_bubbling' 	=> true,
		));
	}

	public function getName()
	{
		return $this->id.'_section_scrapbooktext_type';
	}
}
