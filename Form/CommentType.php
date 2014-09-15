<?php

namespace Stems\BlogBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CommentType extends AbstractType
{
	protected $requireLogin;

	function __construct($requireLogin) 
	{
		$this->requireLogin = $requireLogin;
	}

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    	if (!$this->requireLogin) {
    		$builder->add('author', null, array(
				'label'     		=> 'Your Name',
				'required'			=> true,
				'error_bubbling' 	=> true,
			));	
    	}

		$builder->add('content', null, array(
			'label'     		=> 'Your Comment',
			'required'			=> true,
			'error_bubbling' 	=> true,
		));	
	}

	public function getName()
	{
		return 'blog_comment_type';
	}
}
