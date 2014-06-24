<?php

namespace Stems\BlogBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SectionScrapbookType extends AbstractType
{
	protected $id;

	public function __construct($link)
	{
		$this->id = $link->getId();
	}

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$builder->add('title', 'text', array(
			'label'     		=> 'Title',
			'required'			=> false,
			'error_bubbling' 	=> true,
		));	

		$builder->add('content', 'textarea', array(
			'label'     		=> 'Caption',
			'required'			=> false,
			'error_bubbling' 	=> true,
			'attr'				=> array('class' => 'markitup'),
		));	

		$builder->add('contentX', 'text', array(
			'label'     		=> false,
			'required'			=> false,
			'error_bubbling' 	=> true,
		));	

		$builder->add('contentY', 'text', array(
			'label'     		=> false,
			'required'			=> false,
			'error_bubbling' 	=> true,
		));	

		$builder->add('height', 'text', array(
			'label'     		=> 'Height',
			'required'			=> false,
			'error_bubbling' 	=> true,
		));

		$builder->add('background', 'text', array(
			'label'     		=> 'Background',
			'required'			=> false,
			'error_bubbling' 	=> true,
		));
	}

	public function getName()
	{
		return $this->id.'_section_scrapbook_type';
	}
}
