<?php

namespace Stems\BlogBundle\Controller;

use Stems\CoreBundle\Controller\BaseFrontController,
	Symfony\Component\HttpFoundation\RedirectResponse,
	Symfony\Component\HttpFoundation\Response,
	Symfony\Component\HttpFoundation\Request;

class FrontController extends BaseFrontController
{
	/**
	 * Overview of the blog articles
	 *
	 * @param  Request 		$request 	The request object
	 */
	public function listAction(Request $request)
	{
		// get all of the blog posts for the view
		$posts = $this->em->getRepository('StemsBlogBundle:Post')->findBy(array('deleted' => false, 'status' => 'Published'), array('published' => 'DESC'));

		// paginate the result
		$data = $this->get('stems.core.pagination')->paginate($posts, $request, array('maxPerPage' => 6));

		$this->render('StemsBlogBundle:Front:list.html.twig', array(
			'posts' 		=> $data,
			'page'			=> $this->page,
		));
	}

	/**
	 * Loads the blog articles sequentially
	 *
	 * @param  Request 		$request 	The request object
	 */
	public function sequentialAction(Request $request)
	{
		// get all of the blog posts for the view
		$posts = $this->em->getRepository('StemsBlogBundle:Post')->findBy(array('deleted' => false, 'status' => 'Published'), array('published' => 'DESC'), 3);

		// gather render sections for each of the posts
		$postSections = array();

		foreach ($posts as &$post) {
				
			// prerender the sections, as referencing twig within itself causes a circular reference (grrrr)
			$sections = array();

			foreach ($post->getSections() as $link) {
				$sections[] = $this->get('stems.core.sections.manager')->setBundle('blog')->renderSection($link);
			}

			$postSections[] = $sections; 
		}

		return $this->render('StemsBlogBundle:Front:sequential.html.twig', array(
			'posts' 		=> $posts,
			'postSections' 	=> $postSections,
			'page'			=> $this->page,
		));
	}

	/**
	 * Display a blog post
	 *
	 * @param  $slug 	string 		The slug of the requested blog post
	 */
	public function postAction($slug)
	{
		// get the requested post
		$post = $this->em->getRepository('StemsBlogBundle:Post')->findOneBySlug($slug);

		// set the dynamic page values
		$this->page->setTitle($post->getTitle());
		$this->page->setWindowTitle($post->getTitle().' - '.$post->getExcerpt());
		$this->page->setMetaKeywords($post->getMetaKeywords());
		$this->page->setMetaDescription($post->getMetaDescription());

		// prerender the sections, as referencing twig within itself causes a circular reference
		$sections = array();

		foreach ($post->getSections() as $link) {
			$sections[] = $this->get('stems.core.sections.manager')->setBundle('blog')->renderSection($link);
		}

		return $this->render('StemsBlogBundle:Front:post.html.twig', array(
			'page'		=> $this->page,
			'post' 		=> $post,
			'sections' 	=> $sections,
		));
	}

	/**
	 * Preview a blog post that isn't published yet
	 *
	 * @param  $slug 	string 		The slug of the requested blog post
	 */
	public function previewAction($slug)
	{
		// redirect if the user isn't at least an admin
		if (!$this->get('security.context')->isGranted('ROLE_ADMIN')) {
			return $this->redirect('/blog');
		}

		// get the requested post
		$post = $this->em->getRepository('StemsBlogBundle:Post')->findOneBySlug($slug);

		// set the dynamic page values
		$this->page->setTitle($post->getTitle());
		$this->page->setWindowTitle($post->getTitle().' - '.$post->getExcerpt());
		$this->page->setmetaKeywords($post->getMetaKeywords());
		$this->page->setMetaDescription($post->getMetaDescription());
		$this->page->setDisableAnalytics(true);

		// prerender the sections, as referencing twig within itself causes a circular reference
		$sections = array();

		foreach ($post->getSections() as $link) {
			$sections[] = $this->get('stems.core.sections.manager')->setBundle('blog')->renderSection($link);
		}

		return $this->render('StemsBlogBundle:Front:post.html.twig', array(
			'page'		=> $this->page,
			'post' 		=> $post,
			'sections' 	=> $sections,
		));
	}

	/**
	 * Serves the blog as an rss feed
	 */
	public function rssAction()
	{
		// get all of the blog posts for the feed
		$posts = $this->em->getRepository('StemsBlogBundle:Post')->findBy(array('deleted' => false, 'status' => 'Published'), array('published' => 'DESC'));

		// doctype
		$xml = '<?xml version="1.0" encoding="UTF-8"?>';

		// rss header
		$xml .= '
			<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/">
			   <channel>
			      <title><![CDATA['.$this->container->getParameter('stems.site.name').']]></title>
			      <link>'.$this->container->getParameter('stems.site.url').'</link>
			      <description>'.$this->container->getParameter('stems.site.description').'</description>
			      <lastBuildDate>'.$posts[0]->getPublished()->format('r').'</lastBuildDate>
			      <language>en-us</language>
			      <webMaster>'.$this->container->getParameter('stems.site.email').' Webmaster</webMaster>
			      <copyright>Copyright '.date('Y').'</copyright>
			      <ttl>3600</ttl>
		';

		// add the posts
		foreach ($posts as &$post) {

			if ($post->getExcerpt()) {
				$title = $post->getTitle().' - '.$post->getExcerpt();
			} else {
				$title = $post->getTitle();
			}

			$xml .= '<item>';
			$xml .= '<title><![CDATA['.$title.']]></title>';
         	$xml .= '<author><![CDATA['.$this->container->getParameter('stems.site.name').']]></author>';
         	$xml .= '<link>'.$this->container->getParameter('stems.site.url').'/blog/'.$post->getSlug().'</link>';
         	$xml .= '<guid>'.$this->container->getParameter('stems.site.url').'/blog/'.$post->getSlug().'</guid>';
         	$xml .= '<category>fashion</category>';
         	$xml .= '<pubDate>'.$post->getPublished()->format('r').'</pubDate>';
         	$xml .= '<description><![CDATA['.$post->getMetaDescription().']]></description>';
         	$xml .= '<media:thumbnail url="http://www.threadandmirror.com/'.$post->getImage().'" />';

			$xml .= '</item>';
		}

		// rss closure
		$xml .= '</channel></rss>';

		// create the response and set the type as xml
		$response = new Response($xml);
		$response->headers->set('Content-Type', 'text/xml');

		return $response;
	}
}
