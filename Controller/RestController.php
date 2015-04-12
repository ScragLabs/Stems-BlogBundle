<?php

namespace Stems\BlogBundle\Controller;

use Stems\CoreBundle\Controller\BaseRestController;
use Symfony\Component\HttpFoundation\Request;
use Stems\BlogBundle\Entity\Section;
use Stems\BlogBundle\Entity\SectionProductGalleryProduct;
use Stems\BlogBundle\Form\SectionProductGalleryProductType;
use Stems\MediaBundle\Entity\Image;
use Stems\MediaBundle\Form\ImageType;
use Stems\BlogBundle\Entity\Comment;

class RestController extends BaseRestController
{
	/**
	 * Returns form html for the requested section type
	 *
	 * @param  integer 	$offset 	The amount of posts already loaded, and therefore the query offset
	 * @param  integer 	$chunk 		The maximum amount of posts to get
	 * @return JsonResponse
	 */
	public function getMorePostsAction($offset, $chunk=3)
	{
		// Get more of the blog posts for the view
		$em    = $this->getDoctrine()->getManager();
		$posts = $em->getRepository('StemsBlogBundle:Post')->findLatest($chunk, $offset);

		// Render the html for the posts
		$html = '';

		foreach ($posts as $post) {

			// Prerender the sections, as referencing twig within itself causes a circular reference
			$sections = $this->get('stems.core.sections.manager')->setBundle('blog')->renderSections($post);

			$html .= $this->renderView('StemsBlogBundle:Rest:post.html.twig', array(
				'post' 		=> $post,
				'sections' 	=> $sections,
			));
		}
		
		// Let the ajax response know when there's no more additional posts to load
		count($posts) < $chunk and $this->setCallback('stopLoading');

		return $this->addHtml($html)->success()->sendResponse();
	}

	/**
	 * Processes the submission of an add comment form
	 *
	 * @param  integer 		$post 		The ID of the post to add the comment to
	 * @param  Request 		$request
	 * @return JsonResponse
	 */
	public function addCommentAction($post, Request $request)
	{
		// Get the post
		$em   = $this->getDoctrine()->getManager();
		$post = $em->getRepository('StemsBlogBundle:Post')->find($post);

		// Build the comment form
		$comment 	  = new Comment();
		$form         = $this->createForm('blog_comment', $comment);

		// Process the submission
		if ($request->getMethod() == 'POST') {

			// Validate the submitted values
			$form->bind($request);

			if ($form->isValid()) {

				// Set the user ID if we require login for commenting
				if ($this->container->getParameter('stems.blog.comments.require_login')) {
					if ($this->get('security.context')->isGranted('ROLE_USER')) {
						$comment->setAuthor($this->getUser()->getId());
					} else {
						return $this->error('You need to be logged in to post a comment.', true)->sendResponse();
					}
				}

				// Attach to the post and save
				$comment->setPost($post);
				$em->persist($comment);
				$em->flush();

				// Return the rendered comment
				$html = $this->renderView('StemsBlogBundle:Rest:comment.html.twig', array(
					'image'		 => $image,
					'created'	 => true,
					'moderation' => $this->container->getParameter('stems.blog.comments.moderated'),
				));

				return $this->addHtml($html)->setCallback('commentAdded')->success()->sendResponse();
			} else {
				// Add the validation errors to the response
				$this->addValidationErrors($form);
				
				return $this->setCallback('commentNotAdded')->error()->sendResponse();
			}
		}

		return $this->error('Unauthorised Method.')->sendResponse();
	}

	/**
	 * Returns form html for the requested section type
	 *
	 * @param  integer 	$type 	Section type id
	 * @return JsonResponse
	 */
	public function addSectionTypeAction($type)
	{
		// Get the section type
		$em    	   = $this->getDoctrine()->getManager();
		$available = $this->container->getParameter('stems.sections.available');

		// Create a new section of the specified type
		$class = $available['blog'][$type]['class'];
		$section = new $class();

		$em->persist($section);
		$em->flush();
		
		// Create the section linkage
		$link = new Section();
		$link->setType($type);
		$link->setEntity($section->getId());
		$em->persist($link);
		$em->flush();

		// Get the form html
		$sectionHandler = $this->get('stems.core.sections.manager')->setBundle('blog');

		$html = $section->editor($sectionHandler, $link);
		// Store the section id for use in the response handler
		$meta = array('section' => $link->getId());

		return $this->addHtml($html)->addMeta($meta)->success()->sendResponse();
	}

	/**
	 * Removes the specified section and its linkage
	 *
	 * @param  integer 		$id 	Section id
	 * @return JsonResponse
	 */
	public function removeSectionAction($id)
	{
		try
		{
			// Get the section linkage and the specific section
			$em      = $this->getDoctrine()->getManager();
			$link    = $em->getRepository('StemsBlogBundle:Section')->find($id);
			$types   = $this->container->getParameter('stems.sections.available');
			$section = $em->getRepository($types['blog'][$link->getType()]['entity'])->find($link->getEntity());

			$em->remove($section);
			$em->remove($link);
			$em->flush();

			return $this->success('Section deleted.')->sendResponse();
		}
		catch (\Exception $e) 
		{
			return $this->error($e->getMessage())->sendResponse();
		}
	}

	/**
	 * Updates the feature image for a blog post
	 *
	 * @param  integer 		$id 		The ID of the Product Gallery Section to add the image to
	 * @param  Request 		$request
	 * @return JsonResponse
	 */
	public function setFeatureImageAction($id, Request $request)
	{
		// Get the blog post and existing image
		$em    = $this->getDoctrine()->getManager();
		$post  = $em->getRepository('StemsBlogBundle:Post')->find($id);

		if ($post->getImage()) {
			$image = $em->getRepository('StemsMediaBundle:Image')->find($post->getImage());
		} else {
			$image = new Image();
			$image->setCategory('blog');
		}

		// Build the form and handle the request
		$form = $this->createForm('media_image_type', $image);

		if ($form->bind($request)->isValid()) {

			// Upload the file and save the entity
			$image->doUpload();
			$em->persist($image);
			$em->flush();

			$meta = array('id' => $image->getId());

			// Get the html for updating the feature image
			$html = $this->renderView('StemsBlogBundle:Rest:setFeatureImage.html.twig', array(
				'post'	=> $post,
				'image'	=> $image,
			));

			return $this->addHtml($html)->setCallback('updateFeatureImage')->addMeta($meta)->success('Image updated.')->sendResponse();
		} else {
			return $this->error('Please choose an image to upload.', true)->sendResponse();
		}
	}
}
