<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index()
    {
        $baseUrl = url('/');
        $blogs = BlogController::getBlogs();

        $content = '<?xml version="1.0" encoding="UTF-8"?>';
        $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        // Static Pages
        $staticPages = [
            '/',
            '/how-it-works',
            '/faq',
            '/blog',
            '/coming-soon',
            '/feedback'
        ];

        foreach ($staticPages as $page) {
            $content .= '<url>';
            $content .= '<loc>' . $baseUrl . $page . '</loc>';
            $content .= '<changefreq>weekly</changefreq>';
            $content .= '<priority>' . ($page === '/' ? '1.0' : '0.8') . '</priority>';
            $content .= '</url>';
        }

        // Blog Posts
        foreach ($blogs as $blog) {
            $content .= '<url>';
            $content .= '<loc>' . route('blog.show', $blog['slug']) . '</loc>';
            $content .= '<lastmod>' . date('Y-m-d', strtotime($blog['date'])) . '</lastmod>';
            $content .= '<changefreq>monthly</changefreq>';
            $content .= '<priority>0.7</priority>';
            $content .= '</url>';
        }

        $content .= '</urlset>';

        return response($content, 200)
            ->header('Content-Type', 'text/xml');
    }
}
