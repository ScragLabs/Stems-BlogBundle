<?php

namespace Stems\BlogBundle\Controller;

use Stems\CoreBundle\Controller\BaseAdminController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Stems\BlogBundle\Form\AdminPostType;
use Stems\BlogBundle\Entity\Post;
use Stems\BlogBundle\Entity\Section;
use Doctrine\ORM\NoResultException;

class AdminController extends BaseAdminController
{
	protected $home = 'stems_admin_blog_overview';

	/**
	 * Render the dialogue for the module's dashboard entry in the admin panel
	 */
	public function dashboardAction()
	{
		// Get the number of unmoderated comments
		$comments = 1;

		return $this->render('StemsBlogBundle:Admin:dashboard.html.twig', array(
			'comments' => $comments,
		));
	}

	/**
	 * Build the sitemap entries for the bundle
	 */
	public function sitemapAction()
	{
		// The slug used for the blog (eg. news, blog or magazine)
		// @todo: properly integrate this site-wide via config
		$slug = 'blog';

		// Get the posts
		$em = $this->getDoctrine()->getEntityManager();
		$posts = $em->getRepository('StemsBlogBundle:Post')->findBy(array('deleted' => false, 'status' => 'Published'), array('created' => 'DESC'));

		return $this->render('StemsBlogBundle:Admin:sitemap.html.twig', array(
			'slug' 		=> $slug,
			'posts'		=> $posts,
		));
	}

	/**
	 * Blog overview
	 */
	public function indexAction()
	{		
		// get all undeleted articles
		$em = $this->getDoctrine()->getEntityManager();
		$posts = $em->getRepository('StemsBlogBundle:Post')->findBy(array('deleted' => false), array('created' => 'DESC'));

		return $this->render('StemsBlogBundle:Admin:index.html.twig', array(
			'posts' 	=> $posts,
		));
	}

	/**
	 * Create a post, using a template if defined
	 */
	public function createAction(Request $request)
	{
		$em = $this->getDoctrine()->getEntityManager();

		// Create a new post for persisting, so we already have an id for adding sections etc.
		$post = new Post();
		$post->setAuthor($this->getUser()->getId());
		$em->persist($post);
		
		// If a title was posted then use it
		$request->get('title') and $post->setTitle($request->get('title'));
		$em->flush();

		// add the blog template sections as defined in the config
		$position = 1;

		foreach ($this->container->getParameter('stems.blog.template_sections') as $sectionClass) {

			// only add the section if it exists
			$type = $em->getRepository('StemsBlogBundle:SectionType')->findOneByClass($sectionClass);
	
			if ($type) {

				// create a new section of the specified type
				$class = 'Stems\\BlogBundle\\Entity\\'.$type->getClass();
				$section = new $class();
				$em->persist($section);
				$em->flush();

				// create the section linkage
				$link = new Section();
				$link->setType($type);
				$link->setPost($post);
				$link->setPosition($position);
				$link->setEntity($section->getId());

				$em->persist($link);
				$em->flush();
				$position++;
			}
		}

		// Save all the things
		$em->flush();

		// Redirect to the edit page for the new post
		return $this->redirect($this->generateUrl('stems_admin_blog_edit', array('id' => $post->getId())));
	}

	/**
	 * Edit a blog post
	 *
	 * @param  integer 	$id  	The ID of the blog post
	 * @param  Request 
	 */
	public function editAction(Request $request, $id)
	{
		// Get the blog post requested
		$em   = $this->getDoctrine()->getEntityManager();
		$post = $em->getRepository('StemsBlogBundle:Post')->findOneBy(array('id' => $id, 'deleted' => false));

		// Load the section management service
		$sectionHandler = $this->get('stems.core.sections.manager')->setBundle('blog');

		// Throw an error if the post could not be found
		if (!$post) {
			$request->getSession()->setFlash('error', 'The requested post could not be found.');
			return $this->redirect($this->generateUrl($this->home));
		}

		// Create the edit form and forms for the sections
		$form = $this->createForm(new AdminPostType(), $post);
		$sectionForms = $sectionHandler->getEditors($post->getSections());

		// Get the available section types
		$types = $em->getRepository('StemsBlogBundle:SectionType')->findByEnabled(true);

		// Handle the form submission
		if ($request->getMethod() == 'POST') {

			// Validate the submitted values
			$form->bindRequest($request);

			//if ($form->isValid()) {

				// Update the post in the database
				$post->setNew(false);
				$post->setTitle(stripslashes($post->getTitle()));
				$post->setExcerpt(stripslashes($post->getExcerpt()));
				$post->setContent(stripslashes($post->getContent()));
				$post->setAuthor($this->getUser()->getId());

				// Order the sections, attached to the page and save their values
				$position = 1;

				foreach ($request->get('sections') as $section) {
					
					// Attach and update order
					$sectionEntity = $em->getRepository('StemsBlogBundle:Section')->find($section);
					$sectionEntity->setPost($post);
					$sectionEntity->setPosition($position);

					// Get all form fields relevant to the section...
					foreach ($request->request->all() as $parameter => $value) {
						// Strip the section id from the parameter group and save if it matches
						$explode = explode('_', $parameter);
						$parameterId = reset($explode);
						$parameterId == $sectionEntity->getId() and $sectionParameters = $value;
					}

					// ...then process and update the entity
					$sectionHandler->saveSection($sectionEntity, $sectionParameters, $request);
					$em->persist($sectionEntity);

					$position++;
				}

				// If there were no errors then save the entity, otherwise display the save errors
				// if ($sectionHandler->getSaveErrors()) {
					
					$em->persist($post);
					$em->flush();
					$request->getSession()->setFlash('success', 'The post "'.$post->getTitle().'" has been updated.');
					return $this->redirect($this->generateUrl($this->home));

				// } else {
				// 	$request->getSession()->setFlash('error', 'Your request was not processed as errors were found.');
				// 	$request->getSession()->setFlash('debug', '');
				// }
			//}
		}

		return $this->render('StemsBlogBundle:Admin:edit.html.twig', array(
			'form'			=> $form->createView(),
			'sectionForms'	=> $sectionForms,
			'post' 			=> $post,
			'types'			=> $types,
		));
	}

	/**
	 * Delete a post
	 *
	 * @param  integer 	$id  	The ID of the blog post
	 * @param  Request 
	 */
	public function deleteAction(Request $request, $id)
	{
		// Get the post
		$em   = $this->getDoctrine()->getEntityManager();
		$post = $em->getRepository('StemsBlogBundle:Post')->findOneBy(array('id' => $id, 'deleted' => false));

		if ($post) {
			// Delete the post if was found
			$name = $post->getTitle();
			$post->setDeleted(true);
			$em->persist($post);
			$em->flush();

			// Return the success message
			$request->getSession()->setFlash('success', 'The post "'.$name.'" was successfully deleted!');

		} else {
			$request->getSession()->setFlash('error', 'The requested post could not be deleted as it does not exist in the database.');
		}

		return $this->redirect($this->generateUrl($this->home));
	}

	/**
	 * Publish/unpublish a post
	 *
	 * @param  integer 	$id  	The ID of the blog post
	 * @param  Request 
	 */
	public function publishAction(Request $request, $id)
	{
		// Get the post
		$em   = $this->getDoctrine()->getEntityManager();
		$post = $em->getRepository('StemsBlogBundle:Post')->findOneBy(array('id' => $id, 'deleted' => false));

		if ($post) {

			// Set the post the published/unpublished 
			if ($post->getStatus() == 'Draft') {	
				$post->setStatus('Published');
				$post->setPublished(new \DateTime());
				$request->getSession()->setFlash('success', 'The post "'.$post->getTitle().'" was successfully published!');
			} else {
				$post->setStatus('Draft');
				$request->getSession()->setFlash('success', 'The post "'.$post->getTitle().'" was successfully unpublished!');
			}

			$em->persist($post);
			$em->flush();

		} else {
			$request->getSession()->setFlash('error', 'The requested post could not be published as it does not exist in the database.');
		}

		return $this->redirect($this->generateUrl($this->home));
	}
}
