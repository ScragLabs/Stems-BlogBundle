<?php

namespace Stems\BlogBundle\Controller;

// Dependencies
use Stems\CoreBundle\Controller\BaseAdminController,
	Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter,
	Symfony\Component\HttpFoundation\RedirectResponse,
	Symfony\Component\HttpFoundation\Response,
	Symfony\Component\HttpFoundation\Request;

// Forms
use Stems\BlogBundle\Form\AdminPostType;

// Entities
use Stems\BlogBundle\Entity\Post,
	Stems\BlogBundle\Entity\Section;

// Exceptions
use Doctrine\ORM\NoResultException;

class AdminController extends BaseAdminController
{
	protected $home = 'stems_admin_blog_overview';

	/**
	 * Render the dialogue for the module's dashboard entry in the admin panel
	 */
	public function dashboardAction()
	{
		return $this->render('StemsBlogBundle:Admin:dashboard.html.twig', array());
	}

	/**
	 * Build the sitemap entries for the bundle
	 */
	public function sitemapAction()
	{
		// the slug used for the blog (eg. news, blog or magazine)
		/// @todo: properly integrate this site-wide via config
		$slug = 'blog';

		// get the posts
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

		// create a new post for persisting, so we already have an id for adding sections etc.
		$post = new Post();
		$post->setAuthor($this->getUser()->getId());
		$em->persist($post);
		
		// if a title was posted then use it
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

		// save all the things
		$em->flush();

		// redirect to the edit page for the new post
		return $this->redirect($this->generateUrl('stems_admin_blog_edit', array('id' => $post->getId())));
	}

	/**
	 * Edit a post
	 */
	public function editAction(Request $request, $id)
	{
		// get the entity manager from the section service so they all reference the same instance
		$sectionHandler = $this->get('stems.blog.sections');
		$em = $sectionHandler->getManager();

		// get the blog post request
		$post = $em->getRepository('StemsBlogBundle:Post')->findOneBy(array('id' => $id, 'deleted' => false));

		// throw an exception if the post could not be found
		if (!$post) {
			$request->getSession()->setFlash('error', 'The requested post could not be found.');
			return $this->redirect($this->generateUrl($this->home));
		}

		// create the edit form and forms for the sections
		$form = $this->createForm(new AdminPostType(), $post);
		$sectionForms = $sectionHandler->getEditors($post->getSections());

		// get the available section types
		$types = $em->getRepository('StemsBlogBundle:SectionType')->findByEnabled(true);

		// handle the form submission
		if ($request->getMethod() == 'POST') {

			// validate the submitted values
			$form->bindRequest($request);

			//if ($form->isValid()) {

				// update the post in the database
				$post->setNew(false);
				$post->setTitle(stripslashes($post->getTitle()));
				$post->setExcerpt(stripslashes($post->getExcerpt()));
				$post->setContent(stripslashes($post->getContent()));
				$post->setAuthor($this->getUser()->getId());

				// order the sections, attached to the page and save their values
				$position = 1;

				foreach ($request->get('sections') as $section) {
					
					// attach and update order
					$sectionEntity = $em->getRepository('StemsBlogBundle:Section')->find($section);
					$sectionEntity->setPost($post);
					$sectionEntity->setPosition($position);

					// get all form fields relevant to the section...
					foreach ($request->request->all() as $parameter => $value) {
						// strip the section id from the parameter group and save if it matches
						$explode = explode('_', $parameter);
						$parameterId = reset($explode);
						$parameterId == $sectionEntity->getId() and $sectionParameters = $value;
					}

					// ...then process and update the entity
					$sectionHandler->saveSection($sectionEntity, $sectionParameters, $request);
					$em->persist($sectionEntity);

					$position++;
				}

				// if there were no errors then save the entity, otherwise display the save errors
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
	 */
	public function deleteAction(Request $request, $id)
	{
		// get the entity
		$em = $this->getDoctrine()->getEntityManager();
		$post = $em->getRepository('StemsBlogBundle:Post')->findOneBy(array('id' => $id, 'deleted' => false));

		if ($post) {
			// delete the post if was found
			$name = $post->getTitle();
			$post->setDeleted(true);
			$em->persist($post);
			$em->flush();

			// return the success message
			$request->getSession()->setFlash('success', 'The post "'.$name.'" was successfully deleted!');
		} else {
			$request->getSession()->setFlash('error', 'The requested post could not be deleted as it does not exist in the database.');
		}

		return $this->redirect($this->generateUrl($this->home));
	}

	/**
	 * Publish/unpublish a post
	 */
	public function publishAction(Request $request, $id)
	{
		// get the entity
		$em = $this->getDoctrine()->getEntityManager();
		$post = $em->getRepository('StemsBlogBundle:Post')->findOneBy(array('id' => $id, 'deleted' => false));

		if ($post) {

			// set the post the published/unpublished 
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
