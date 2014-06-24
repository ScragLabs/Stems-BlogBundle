<?php

namespace Stems\BlogBundle\Definition;

interface SectionInstanceInterface
{
	// renders the front end html of the section
	public function render($services, $link);

	// renders the admin form for the section
	public function editor($services, $link);

	// handles the posted edit data for the section
	public function save($services, $parameters, $request, $link);
}