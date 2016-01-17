<?php

namespace Stems\BlogBundle\Controller;

use Stems\BlogBundle\Entity\Post;
use Stems\CoreBundle\Controller\BaseFrontController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Stems\BlogBundle\Entity\Comment;

class FrontController extends BaseFrontController
{
	/**
	 * Overview of the blog articles, with config controlled listing style
	 *
	 * @param  Request 		$request 	The request object
	 */
	public function listAction(Request $request)
	{
		$chunk = $this->container->getParameter('stems.blog.index.chunk_size');

		// Get posts for the view
		$posts = $this->em->getRepository('StemsBlogBundle:Post')->findLatest($chunk);

		if ($this->container->getParameter('stems.blog.index.list_style') == 'sequential') {

			return $this->render('StemsBlogBundle:Front:sequential.html.twig', array(
				'posts' 		=> $posts,
				'page'			=> $this->page,
			));

		} else {

			// Paginate the result
			$data = $this->get('stems.core.pagination')->paginate($posts, $request, array('maxPerPage' => $chunk));

			return $this->render('StemsBlogBundle:Front:list.html.twig', array(
				'posts' 		=> $data,
				'page'			=> $this->page,
			));
		}
	}

	/**
	 * Display a blog post
	 *
	 * @param  $slug 	string 		The slug of the requested blog post
	 */
	public function postAction(Post $post)
	{
		// Redirect to the index if the collection isn't published
		if ($post->getStatus() !== 'Published' || $post->getCategory()->getSlug() !== 'articles') {
			$this->redirect($this->generateUrl('thread_editorspicks_front_list'));
		}

		// Set the dynamic page values
		$this->loadPage('magazine/{slug}', array(
			'title' 			=> $post,
			'windowTitle' 		=> $post->getMetaTitle(),
			'metaKeywords' 		=> $post->getMetaKeywords(),
			'metaDescription' 	=> $post->getMetaDescription(),
		));

		// Prerender the sections, as referencing twig within itself causes a circular reference
		$sections = $this->get('stems.core.sections.manager')->setBundle('blog')->renderSections($post);

		// Build the comment form
		$form = $this->createForm('blog_comment_type', new Comment());

		return $this->render('StemsBlogBundle:Front:post.html.twig', array(
			'page'		=> $this->page,
			'post' 		=> $post,
			'sections' 	=> $sections,
			'form' 		=> $form->createView(),
		));
	}

	/**
	 * Preview a blog post that isn't published yet
	 *
	 * @param  $slug 	string 		The slug of the requested blog post
	 */
	public function previewAction($slug)
	{
		// Redirect if the user isn't at least an admin
		if (!$this->get('security.context')->isGranted('ROLE_ADMIN')) {
			return $this->redirect('/');
		}

		// Get the requested post
		$post = $this->em->getRepository('StemsBlogBundle:Post')->findPublishedPost($slug);

		// Set the dynamic page values
		$this->page->setTitle($post->getTitle());
		$this->page->setWindowTitle($post->getTitle().' - '.$post->getExcerpt());
		$this->page->setmetaKeywords($post->getMetaKeywords());
		$this->page->setMetaDescription($post->getMetaDescription());
		$this->page->setDisableAnalytics(true);

		// Pre-render the sections, as referencing twig within itself causes a circular reference
		$sections = array();

		foreach ($post->getSections() as $link) {
			$sections[] = $this->get('stems.core.sections.manager')->setBundle('blog')->renderSection($link);
		}

		// Build the comment form
		$form = $this->createForm('blog_comment_type', new Comment());

		return $this->render('StemsBlogBundle:Front:post.html.twig', array(
			'page'		=> $this->page,
			'post' 		=> $post,
			'sections' 	=> $sections,
			'form' 		=> $form->createView(),
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
         	$xml .= '<description><![CDATA['.$post->getExcerpt().']]></description>';
         	$xml .= '<media:thumbnail url="'.$this->container->getParameter('stems.site.url').'/'.$post->getImage().'" />';

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
