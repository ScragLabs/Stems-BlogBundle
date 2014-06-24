<?php

namespace Stems\BlogBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
	Symfony\Component\HttpFoundation\RedirectResponse,
	Symfony\Component\HttpFoundation\JsonResponse,
	Symfony\Component\HttpFoundation\Request;

use Stems\BlogBundle\Entity\Section,
	Stems\BlogBundle\Entity\SectionProductGalleryProduct;


class RestController extends Controller
{
	/**
	 * Returns form html for the requested section type
	 * @param $offset 	The amount of posts already loaded, and therefore the query offset
	 */
	public function getMorePostsAction($offset, $chunk=3)
	{
		// get more of the blog posts for the view
		$em = $this->getDoctrine()->getManager();
		$posts = $em->getRepository('StemsBlogBundle:Post')->findBy(array('deleted' => false, 'status' => 'Published'), array('published' => 'DESC'), $chunk, $offset);

		// render the html for the posts
		$html = '';

		foreach ($posts as &$post) {
			// prerender the sections, as referencing twig within itself causes a circular reference
			$sections = array();

			foreach ($post->getSections() as $link) {
				$sections[] = $this->get('stems.blog.sections')->renderSection($link);
			}

			$html .= $this->renderView('StemsBlogBundle:Rest:post.html.twig', array(
				'post' 		=> $post,
				'sections' 	=> $sections,
			));
		}
		
		// let the ajax response know when there's no more additional posts to load
		if (count($posts) < $chunk) {
			$stopLoading = true;
		} else {
			$stopLoading = false;
		}

		return new JsonResponse(array(
			'html'    		=> $html,
			'stopLoading'	=> $stopLoading,
		));
	}

	/**
	 * Returns form html for the requested section type
	 * @param $id 	Section type id
	 */
	public function addSectionTypeAction($id)
	{
		// get the section type
		$em = $this->getDoctrine()->getManager();
		$type = $em->getRepository('StemsBlogBundle:SectionType')->find($id);

		// create a new section of the specified type
		$class = 'Stems\\BlogBundle\\Entity\\'.$type->getClass();
		$section = new $class();

		$em->persist($section);
		$em->flush();
		
		// create the section linkage
		$link = new Section();
		$link->setType($type);
		$link->setEntity($section->getId());
		$em->persist($link);
		$em->flush();

		// get the form html
		$sectionHandler = $this->get('stems.blog.sections');
		$html = $section->editor($sectionHandler, $link);

		return new JsonResponse(array(
			'html'    => $html,
			'section' => $link->getId(),
		));
	}

	/**
	 * Removes the specified section and its linkage
	 * @param $id 	Section id
	 */
	public function removeSectionAction($id)
	{
		try
		{
			// get the section linkage and the specific section
			$em = $this->getDoctrine()->getManager();
			$link = $em->getRepository('StemsBlogBundle:Section')->find($id);
			$section = $em->getRepository('StemsBlogBundle:'.$link->getType()->getClass())->find($link->getEntity());

			$em->remove($section);
			$em->remove($link);
			$em->flush();

			return new JsonResponse(array(
				'success'	=> true,
				'message'	=> 'Section deleted.',
			));
		}
		catch (\Exception $e) 
		{
			return new JsonResponse(array(
				'success'	=> false,
				'message'	=> $e->message,
			));
		}
	}

	/**
	 * Adds a product to a product gallery section
	 * @param $id 		The ID of the Product Gallery Section to add the image to
	 */
	public function addProductGalleryProductAction($id, Request $request)
	{
		// get the url from the query paramter and attempt to parse the product
		$em = $this->getDoctrine()->getManager();

		$product = $em->getRepository('StemsSaleSirenBundle:Product')->getProductFromUrl($request->get('url'));

		// get the section for the field id
		$section = $em->getRepository('StemsBlogBundle:SectionProductGallery')->findOneById($id);

		// if we manage to parse a product from the url then create the product listing for the gallery
		if (is_object($product)) {

			// save the product as it may not already exist in the database
			$em->persist($product);

			// create a pick from the product
			$image = new SectionProductGalleryProduct();
			$image->setHeading($product->getName());
			$image->setCaption($product->getShop()->getName());
			$image->setUrl($product->getFrontendUrl());
			$image->setThumbnail($product->getThumbnail());
			$image->setImage($product->getImage());
			$image->setRatio($product->getShop()->getImageRatio());
			$image->setPid($product->getId());

			$em->persist($image);
			$em->flush();

			// get the associated section linkage to tag the fields with the right id
			$link = $em->getRepository('StemsBlogBundle:Section')->findOneByEntity($section->getId());

			// get the html for the new product gallery item and to add to the page
			$html = $this->renderView('StemsBlogBundle:Rest:productGalleryProduct.html.twig', array(
				'product'	=> $image,
				'section'	=> $section,
				'link'		=> $link,
			));

			// success response
			return new JsonResponse(array(
				'success'   => true,
				'html'		=> $html,
				'message' 	=> 'The product was successfully loaded from the link.'
			));
		} else {
			// error response
			return new JsonResponse(array(
				'success'   => false,
				'html'		=> '',
				'message' 	=> 'We could not load a product using that link.'
			));
		}
	}
}
