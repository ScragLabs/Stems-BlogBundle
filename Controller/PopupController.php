<?php

namespace Stems\BlogBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
	Symfony\Component\HttpFoundation\RedirectResponse,
	Symfony\Component\HttpFoundation\JsonResponse,
	Symfony\Component\HttpFoundation\Request;

use Stems\BlogBundle\Entity\Section,
	Stems\BlogBundle\Entity\SectionProductGalleryProduct;


class PopupController extends Controller
{
	/**
	 * Return a form for adding a Product Gallery Product manually
	 */
	public function addProductGalleryProductAction(Request $request)
	{
		// get the Product Gallery Product and build the form
		$em      = $this->getDoctrine()->getManager();
		$product = new SectionProductGalleryProduct();
		$form    = $this->createForm(new SectionProductGalleryProductType(), $product);

		// render the html
		$html = $this->renderView('StemsBlogBundle:Popup:addProductGalleryProduct.html.twig', array(
			'product'	=> $product,
			'form'		=> $form,
		));

		// return the popup JSON
		return new JsonResponse(array(
			'html'		=> $html,
		));
	}

	/**
	 * Return a form for editing Product Gallery Products
	 * @param $id 		The ID of the Product Gallery Product
	 */
	public function editProductGalleryProductAction($id, Request $request)
	{
		// get the Product Gallery Product and build the form
		$em      = $this->getDoctrine()->getManager();
		$product = $em->getRepository('StemsBlogBundle:SectionProductGalleryProduct')->findOneById($id);
		$form    = $this->createForm(new SectionProductGalleryProductType(), $product);

		// render the html
		$html = $this->renderView('StemsBlogBundle:Popup:editProductGalleryProduct.html.twig', array(
			'product'	=> $product,
			'form'		=> $form,
		));

		// return the popup JSON
		return new JsonResponse(array(
			'html'		=> $html,
		));
	}
}
